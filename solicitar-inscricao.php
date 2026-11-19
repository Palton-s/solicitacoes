<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

require_login();

// Forçar carregamento de strings problemáticas
$forced_strings = [
    'form_inscricao_descricao' => 'Você está solicitando inscrição de usuários em uma disciplina. Selecione o papel apropriado para os usuários.',
    'no_users_found' => 'Nenhum usuário encontrado',
    'observacoes_placeholder' => 'descreva detalhadamente o motivo da sua solicitação'
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
 * Formulário nativo do Moodle para inscrição de usuários
 */
class inscricao_form extends moodleform {

    protected function definition() {
        global $CFG, $DB;
        
        $mform = $this->_form;

        // Header 
        #$mform->addElement('header', 'inscricao_details', get_string('form_inscricao_titulo', 'local_solicitacoes'));

        // Aviso/descrição
        $aviso_html = '<div class="alert alert-info m-4" role="alert">' . 
                      '<i class="fas fa-info-circle"></i> ' . 
                      get_string_with_fallback('form_inscricao_descricao', 'local_solicitacoes') . 
                      '</div>';
        $mform->addElement('html', $aviso_html);

        // Campo de curso: busca assíncrona (AJAX), nenhum curso é carregado na página.
        local_solicitacoes_add_course_selector($mform, \local_solicitacoes\local\course_search::SCOPE_ALLOWED);

        // Papel no curso
        $roles_options = local_solicitacoes_get_allowed_roles_with_empty(get_string('select_role', 'local_solicitacoes'));
        
        $mform->addElement('select', 'papel', get_string('papel_label', 'local_solicitacoes'), $roles_options);
        $mform->setType('papel', PARAM_TEXT);
        $mform->addRule('papel', null, 'required', null, 'client');
        $mform->addHelpButton('papel', 'papel_help_dinamico', 'local_solicitacoes');

        // Campo de usuários: busca assíncrona (web service local_solicitacoes_search_users).
        local_solicitacoes_add_user_selector($mform, 'usuarios_busca', get_string('usuarios_busca', 'local_solicitacoes'));
        $mform->addHelpButton('usuarios_busca', 'usuarios_busca_help', 'local_solicitacoes');

        // Observações
        // Observações
        $mform->addElement('textarea', 'observacoes', get_string('observacoes', 'local_solicitacoes'), 
            array('rows' => 5, 'cols' => 50, 'placeholder' => get_string_with_fallback('observacoes_placeholder', 'local_solicitacoes')));
        $mform->setType('observacoes', PARAM_TEXT);
        $mform->addRule('observacoes', null, 'required', null, 'client');
        $mform->addHelpButton('observacoes', 'observacoes_help', 'local_solicitacoes');

        // Botões de ação
        $this->add_action_buttons(true, get_string('request_submit', 'local_solicitacoes'));
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

        // Validar os cursos selecionados: consulta apenas os IDs enviados.
        $erro_cursos = \local_solicitacoes\local\course_search::validate_selection(
            $data['curso_nome'] ?? null,
            \local_solicitacoes\local\course_search::SCOPE_ALLOWED
        );
        if ($erro_cursos !== null) {
            $errors['curso_nome'] = $erro_cursos;
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
            new moodle_url('/local/solicitacoes/minhas-solicitacoes.php'),
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
