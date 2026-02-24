<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

require_login();

$context = context_system::instance();

// Verificar permissão para criar solicitações
if (!has_capability('local/solicitacoes:submit', $context)) {
    redirect(
        new moodle_url('/'),
        get_string('error_nopermission_submit', 'local_solicitacoes'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

/**
 * Formulário nativo do Moodle para inscrição de usuários
 */
class inscricao_form extends moodleform {

    protected function definition() {
        global $CFG, $DB;
        
        $mform = $this->_form;

        // Header 
        $mform->addElement('header', 'inscricao_details', get_string('form_inscricao_titulo', 'local_solicitacoes'));

        // Aviso/descrição
        $aviso_html = '<div class="alert alert-info" role="alert">' . 
                      '<i class="fas fa-info-circle"></i> ' . 
                      get_string('form_inscricao_descricao', 'local_solicitacoes') . 
                      '</div>';
        $mform->addElement('html', $aviso_html);

        // Campo de curso (autocomplete com cursos carregados no PHP)
        $cursos_disponiveis = $this->get_available_courses();
        $mform->addElement('autocomplete', 'curso_nome', get_string('curso_nome', 'local_solicitacoes'), $cursos_disponiveis, array(
            'multiple' => true,
            'placeholder' => get_string('searching_courses', 'local_solicitacoes'),
            'noselectionstring' => get_string('no_courses_found', 'local_solicitacoes'),
        ));
        $mform->setType('curso_nome', PARAM_SEQUENCE);
        $mform->addRule('curso_nome', null, 'required', null, 'client');
        $mform->addHelpButton('curso_nome', 'curso_nome_help', 'local_solicitacoes');

        // Papel no curso
        $systemcontext = context_system::instance();
        $all_roles = role_get_names($systemcontext, ROLENAME_ALIAS, false);
        $roles_options = array('' => get_string('select_role', 'local_solicitacoes'));
        
        foreach ($all_roles as $roleid => $rolename) {
            $role = $DB->get_record('role', ['id' => $roleid], 'shortname, name');
            if ($role) {
                $roles_options[$role->shortname] = role_get_name($role);
            }
        }
        
        $mform->addElement('select', 'papel', get_string('papel_label', 'local_solicitacoes'), $roles_options);
        $mform->setType('papel', PARAM_TEXT);
        $mform->addRule('papel', null, 'required', null, 'client');
        $mform->addHelpButton('papel', 'papel_help_dinamico', 'local_solicitacoes');

        // Campo de usuários (autocomplete múltiplo)
        $mform->addElement('autocomplete', 'usuarios_busca', get_string('usuarios_busca', 'local_solicitacoes'), array(), array(
            'multiple' => true,
            'placeholder' => get_string('usuarios_busca_help', 'local_solicitacoes'),
            'casesensitive' => false,
            'showsuggestions' => true,
            'noselectionstring' => get_string('no_users_found', 'local_solicitacoes'),
            'ajax' => 'core_user/form_user_selector',
        ));
        $mform->setType('usuarios_busca', PARAM_SEQUENCE);
        $mform->addRule('usuarios_busca', null, 'required', null, 'client');
        $mform->addHelpButton('usuarios_busca', 'usuarios_busca_help', 'local_solicitacoes');

        // Observações
        $mform->addElement('textarea', 'observacoes', get_string('observacoes', 'local_solicitacoes'), 
            ['wrap' => 'virtual', 'rows' => 4, 'cols' => 60]);
        $mform->setType('observacoes', PARAM_TEXT);
        $mform->addHelpButton('observacoes', 'observacoes_help', 'local_solicitacoes');

        // Botões de ação
        $this->add_action_buttons(true, get_string('request_submit', 'local_solicitacoes'));
    }

    /**
     * Buscar cursos disponíveis para o usuário atual
     */
    private function get_available_courses() {
        global $DB, $USER;
        
        $cursos_options = array('' => get_string('searching_courses', 'local_solicitacoes'));
        
        // Buscar todos os cursos onde o usuário tem algum papel (é participante)
        $sql = "SELECT DISTINCT c.id, c.fullname, c.shortname 
                FROM {course} c
                JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
                LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = :userid
                WHERE c.id != 1 
                AND (ra.id IS NOT NULL OR c.visible = 1)
                ORDER BY c.fullname";
        
        $cursos = $DB->get_records_sql($sql, ['userid' => $USER->id]);
        
        foreach ($cursos as $curso) {
            $cursos_options[$curso->id] = $curso->fullname . ' (' . $curso->shortname . ')';
        }
        
        return $cursos_options;
    }

    /**
     * Validação personalizada do formulário
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Validar se pelo menos um usuário foi selecionado
        $usuarios_validos = false;
        if (!empty($data['usuarios_busca'])) {
            if (is_array($data['usuarios_busca'])) {
                $usuarios_validos = count($data['usuarios_busca']) > 0;
            } else {
                // Se vier como string (fallback), verificar se não está vazio
                $usuarios_validos = trim($data['usuarios_busca']) !== '';
            }
        }
        
        if (!$usuarios_validos) {
            $errors['usuarios_busca'] = get_string('error_usuarios_required', 'local_solicitacoes');
        }

        // Validar se pelo menos um curso foi selecionado e se são válidos
        if (!empty($data['curso_nome'])) {
            $cursos_selecionados = is_array($data['curso_nome']) ? $data['curso_nome'] : [$data['curso_nome']];
            
            if (empty($cursos_selecionados)) {
                $errors['curso_nome'] = get_string('error_course_required', 'local_solicitacoes');
            } else {
                // Validar cada curso selecionado
                $cursos_invalidos = array();
                foreach ($cursos_selecionados as $curso_id) {
                    $curso_id = (int)$curso_id;
                    if ($curso_id > 0) {
                        $curso = $DB->get_record('course', ['id' => $curso_id], 'id, fullname');
                        if (!$curso) {
                            $cursos_invalidos[] = $curso_id;
                        }
                    } else {
                        $cursos_invalidos[] = $curso_id;
                    }
                }
                
                if (!empty($cursos_invalidos)) {
                    $errors['curso_nome'] = get_string('error_invalid_courses', 'local_solicitacoes', implode(', ', $cursos_invalidos));
                }
            }
        } else {
            $errors['curso_nome'] = get_string('error_course_required', 'local_solicitacoes');
        }

        return $errors;
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/solicitar-inscricao.php'));
$PAGE->set_title(get_string('form_inscricao_titulo', 'local_solicitacoes'));
$PAGE->set_heading(get_string('form_inscricao_titulo', 'local_solicitacoes'));

// Criar instância do formulário
$mform = new inscricao_form();

// Processar formulário se submetido  
if ($data = $mform->get_data()) {
    // Adicionar o tipo de ação aos dados
    $data->tipo_acao = 'inscricao';
    
    // Utilizar o controller para processar a solicitação
    require_once(__DIR__ . '/classes/solicitacoes_controller.php');
    
    $success = \local_solicitacoes\solicitacoes_controller::process_request_submission($data);
    
    if ($success) {
        redirect(
            new moodle_url('/local/solicitacoes/confirmacao.php'),
            get_string('request_submitted', 'local_solicitacoes'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect(
            new moodle_url('/local/solicitacoes/solicitar-inscricao.php'),
            get_string('error_submitting', 'local_solicitacoes'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// Se foi cancelado
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/solicitacoes/selecionar-acao.php'));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
