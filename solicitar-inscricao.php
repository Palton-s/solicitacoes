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
        if (empty($data['usuarios_busca']) || !is_array($data['usuarios_busca']) || count($data['usuarios_busca']) == 0) {
            $errors['usuarios_busca'] = get_string('error_usuarios_required', 'local_solicitacoes');
        }

        // Validar se o curso selecionado é válido
        if (!empty($data['curso_nome'])) {
            $curso = $DB->get_record('course', ['id' => $data['curso_nome']], 'id, fullname');
            if (!$curso) {
                $errors['curso_nome'] = get_string('error_invalid_course', 'local_solicitacoes');
            }
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
    // Processar dados do formulário
    global $DB, $USER;
    
    try {
        $record = new stdClass();
        $record->userid = $USER->id;
        $record->tipo_acao = 'inscricao';
        $record->status = 'pendente';
        $record->timecreated = time();
        $record->timemodified = time();
        $record->papel = $data->papel;
        
        // Construir informações para observações
        // Buscar informações do curso selecionado
        $curso_info = "Curso não encontrado";
        if (!empty($data->curso_nome)) {
            $curso = $DB->get_record('course', ['id' => $data->curso_nome], 'id, fullname, shortname');
            if ($curso) {
                $curso_info = $curso->fullname . ' (' . $curso->shortname . ') - ID: ' . $curso->id;
            }
        }
        $observacoes_completas = "CURSO SELECIONADO: " . $curso_info . "\n";
        
        // Processar usuários selecionados
        if (!empty($data->usuarios_busca) && is_array($data->usuarios_busca)) {
            $usernames = array();
            foreach ($data->usuarios_busca as $userid) {
                $user = $DB->get_record('user', ['id' => $userid], 'firstname, lastname, email');
                if ($user) {
                    $usernames[] = fullname($user) . ' (' . $user->email . ')';
                }
            }
            $observacoes_completas .= "USUÁRIOS SELECIONADOS: " . implode(', ', $usernames) . "\n";
            $observacoes_completas .= "IDs DOS USUÁRIOS: " . implode(',', $data->usuarios_busca) . "\n";
        }
        
        if (!empty($data->observacoes)) {
            $observacoes_completas .= "OBSERVAÇÕES ADICIONAIS: " . $data->observacoes;
        }
        $record->observacoes = $observacoes_completas;
        
        // Inserir a solicitação
        $solicitacao_id = $DB->insert_record('local_solicitacoes', $record);
        
        redirect(
            new moodle_url('/local/solicitacoes/confirmacao.php'),
            get_string('request_submitted', 'local_solicitacoes'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (Exception $e) {
        redirect(
            new moodle_url('/local/solicitacoes/solicitar-inscricao.php'),
            get_string('error_submitting', 'local_solicitacoes') . ': ' . $e->getMessage(),
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
