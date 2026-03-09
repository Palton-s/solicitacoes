<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

require_login();

// Forçar carregamento de strings problemáticas
$forced_strings = [
    'form_suspensao_descricao' => 'Use este formulário para solicitar a suspensão temporária de usuários em um ou mais cursos.',
    'no_users_found' => 'Nenhum usuário encontrado',
    'observacoes_placeholder' => 'Digite observações adicionais sobre esta solicitação (opcional)...'
];

// Adicionar strings ao cache temporariamente
foreach ($forced_strings as $key => $value) {
    if (get_string($key, 'local_solicitacoes') === "[[{$key}]]") {
        // Se a string não carregou, usar valor direto
        $GLOBALS['forced_strings_'.$key] = $value;
    }
}

// Função helper para get_string com fallback
if (!function_exists('get_string_with_fallback')) {
    function get_string_with_fallback($key, $component = 'moodle', $a = null) {
        $result = get_string($key, $component, $a);
        if ($result === "[[{$key}]]" && isset($GLOBALS['forced_strings_'.$key])) {
            return $GLOBALS['forced_strings_'.$key];
        }
        return $result;
    }
}

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
 * Formulário nativo do Moodle para suspensão de usuários em cursos
 */
class suspensao_form extends moodleform {

    protected function definition() {
        global $CFG, $DB;
        
        $mform = $this->_form;

        // Header 
        #$mform->addElement('header', 'suspensao_details', get_string('form_suspensao_titulo', 'local_solicitacoes'));

        // Aviso/descrição
        $aviso_html = '<div class="alert alert-warning m-4" role="alert">' . 
                      '<i class="fas fa-pause-circle"></i> ' . 
                      get_string_with_fallback('form_suspensao_descricao', 'local_solicitacoes') . 
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

        // Campo de usuários (autocomplete múltiplo)
        $mform->addElement('autocomplete', 'usuarios_busca', get_string('usuarios_busca', 'local_solicitacoes'), array(), array(
            'multiple' => true,
            'placeholder' => get_string('usuarios_busca_help', 'local_solicitacoes'),
            'casesensitive' => false,
            'showsuggestions' => true,
            'noselectionstring' => get_string_with_fallback('no_users_found', 'local_solicitacoes'),
            'ajax' => 'local_solicitacoes/user_selector',
        ));
        $mform->setType('usuarios_busca', PARAM_SEQUENCE);
        $mform->addRule('usuarios_busca', null, 'required', null, 'client');
        $mform->addHelpButton('usuarios_busca', 'usuarios_busca_help', 'local_solicitacoes');

        // Observações
        $mform->addElement('textarea', 'observacoes', get_string('observacoes', 'local_solicitacoes'), 
            array('rows' => 5, 'cols' => 50, 'placeholder' => get_string_with_fallback('observacoes_placeholder', 'local_solicitacoes')));
        $mform->setType('observacoes', PARAM_TEXT);
        $mform->addHelpButton('observacoes', 'observacoes_help', 'local_solicitacoes');

        // Botões de ação
        $this->add_action_buttons(true, get_string('request_submit', 'local_solicitacoes'));
    }

    /**
     * Buscar cursos disponíveis para o usuário atual:
     * - admins e usuários com papel no contexto do sistema veem todos os cursos
     * - demais: cursos onde está matriculado + cursos de categorias com papel atribuído
     */
    protected function get_available_courses() {
        global $DB, $USER;
        
        $cursos_options = array();
        
        try {
            $system_context = context_system::instance();

            // Admins e usuários com papel no contexto do sistema (ex: gerentes) veem tudo
            if (is_siteadmin() || $DB->record_exists('role_assignments', [
                'userid'    => $USER->id,
                'contextid' => $system_context->id,
            ])) {
                $sql = "SELECT id, fullname, shortname 
                        FROM {course} 
                        WHERE id > 1
                        ORDER BY fullname ASC";
                $cursos = $DB->get_records_sql($sql);
            } else {
                // 1. Cursos onde o usuário está matriculado (inclui ocultos acessíveis)
                $cursos = enrol_get_users_courses($USER->id, true);

                // 2. Cursos pertencentes a categorias onde o usuário tem papel (ocultos inclusive)
                $sql_cat = "SELECT DISTINCT c.id, c.fullname, c.shortname 
                            FROM {course} c
                            JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :ctxcourse
                            WHERE c.id != 1
                            AND EXISTS (
                                SELECT 1 FROM {role_assignments} ra
                                JOIN {context} catctx ON catctx.id = ra.contextid
                                WHERE ra.userid = :userid
                                AND catctx.contextlevel = :ctxcat
                                AND ctx.path LIKE " . $DB->sql_concat('catctx.path', "'/%'") . "
                            )
                            ORDER BY c.fullname ASC";

                $cat_courses = $DB->get_records_sql($sql_cat, [
                    'ctxcourse' => CONTEXT_COURSE,
                    'userid'    => $USER->id,
                    'ctxcat'    => CONTEXT_COURSECAT,
                ]);

                // Combinar (chaves são IDs de curso — sem duplicatas)
                $cursos = $cursos + $cat_courses;
            }

            foreach ($cursos as $curso) {
                $cursos_options[$curso->id] = $curso->fullname . ' (' . $curso->shortname . ')';
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar cursos: " . $e->getMessage());
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
                // Validar cada curso selecionado e verificar se o usuário tem acesso
                $cursos_acessiveis = $this->get_available_courses();
                $cursos_invalidos = array();
                foreach ($cursos_selecionados as $curso_id) {
                    $curso_id = (int)$curso_id;
                    if ($curso_id <= 0 || !array_key_exists($curso_id, $cursos_acessiveis)) {
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
$PAGE->set_url(new moodle_url('/local/solicitacoes/solicitar-suspensao.php'));
$PAGE->set_title(get_string('form_suspensao_titulo', 'local_solicitacoes'));
$PAGE->set_heading(get_string('form_suspensao_titulo', 'local_solicitacoes'));

// Criar instância do formulário
$mform = new suspensao_form();

// Processar formulário se submetido  
if ($data = $mform->get_data()) {
    // Adicionar o tipo de ação aos dados
    $data->tipo_acao = 'suspensao';
    
    // Utilizar o controller para processar a solicitação
    require_once(__DIR__ . '/classes/solicitacoes_controller.php');
    
    $success = \local_solicitacoes\solicitacoes_controller::process_request_submission($data);
    
    if ($success) {
        redirect(
            new moodle_url('/local/solicitacoes/minhas-solicitacoes.php'),
            get_string('request_submitted', 'local_solicitacoes'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect(
            new moodle_url('/local/solicitacoes/solicitar-suspensao.php'),
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
