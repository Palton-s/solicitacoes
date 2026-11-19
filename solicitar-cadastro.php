<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

require_login();

// Forçar carregamento de strings problemáticas
$forced_strings = [
    'form_cadastro_descricao' => 'Use este formulário para solicitar o cadastro de um novo usuário no sistema com um papel específico.',
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
 * Formulário nativo do Moodle para cadastro de novos usuários
 */
class cadastro_form extends moodleform {

    protected function definition() {
        global $CFG, $DB;
        
        $mform = $this->_form;

        // Header 
        #$mform->addElement('header', 'cadastro_details', get_string('form_cadastro_titulo', 'local_solicitacoes'));

        // Aviso/descrição
        $aviso_html = '<div class="alert alert-info m-4" role="alert">' . 
                      '<i class="fas fa-user-plus"></i> ' . 
                      get_string_with_fallback('form_cadastro_descricao', 'local_solicitacoes') . 
                      '</div>';
        $mform->addElement('html', $aviso_html);

        // Campo de curso: busca assíncrona (AJAX), nenhum curso é carregado na página.
        local_solicitacoes_add_course_selector($mform, \local_solicitacoes\local\course_search::SCOPE_ALLOWED);

        // Primeiro nome do usuário
        $mform->addElement('text', 'firstname', get_string('firstname', 'local_solicitacoes'), 
            array('size' => 30));
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', null, 'required', null, 'client');
        $mform->addRule('firstname', null, 'maxlength', 100, 'client');
        $mform->addHelpButton('firstname', 'firstname_help', 'local_solicitacoes');

        // Sobrenome do usuário
        $mform->addElement('text', 'lastname', get_string('lastname', 'local_solicitacoes'), 
            array('size' => 30));
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', null, 'required', null, 'client');
        $mform->addRule('lastname', null, 'maxlength', 100, 'client');
        $mform->addHelpButton('lastname', 'lastname_help', 'local_solicitacoes');

        // CPF (será usado como username)
        $mform->addElement('text', 'cpf', get_string('cpf', 'local_solicitacoes'), 
            array('size' => 15, 'placeholder' => '000.000.000-00'));
        $mform->setType('cpf', PARAM_TEXT);
        $mform->addRule('cpf', null, 'required', null, 'client');
        $mform->addRule('cpf', null, 'maxlength', 14, 'client');
        $mform->addHelpButton('cpf', 'cpf_help', 'local_solicitacoes');

        // Email do usuário
        $mform->addElement('text', 'email_novo_usuario', get_string('email_novo_usuario', 'local_solicitacoes'), 
            array('size' => 50));
        $mform->setType('email_novo_usuario', PARAM_EMAIL);
        $mform->addRule('email_novo_usuario', null, 'required', null, 'client');
        $mform->addRule('email_novo_usuario', null, 'email', null, 'client');
        $mform->addRule('email_novo_usuario', null, 'maxlength', 100, 'client');
        $mform->addHelpButton('email_novo_usuario', 'email_novo_usuario_help', 'local_solicitacoes');

        // Papel (role) do usuário
        $roles_options = local_solicitacoes_get_allowed_roles_with_empty(get_string('choose_role', 'local_solicitacoes'));
        
        $mform->addElement('select', 'papel', get_string('papel_usuario', 'local_solicitacoes'), $roles_options);
        $mform->setType('papel', PARAM_TEXT);
        $mform->addRule('papel', null, 'required', null, 'client');
        $mform->addHelpButton('papel', 'papel_usuario_help', 'local_solicitacoes');

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

        // Validar os cursos selecionados: consulta apenas os IDs enviados.
        $erro_cursos = \local_solicitacoes\local\course_search::validate_selection(
            $data['curso_nome'] ?? null,
            \local_solicitacoes\local\course_search::SCOPE_ALLOWED
        );
        if ($erro_cursos !== null) {
            $errors['curso_nome'] = $erro_cursos;
        }

        // Validar se o CPF já existe (usado como username)
        if (!empty($data['cpf'])) {
            // Limpar formatoção do CPF
            $cpf_limpo = preg_replace('/[^0-9]/', '', $data['cpf']);
            if (strlen($cpf_limpo) != 11) {
                $errors['cpf'] = get_string('error_cpf_invalid', 'local_solicitacoes');
            } else {
                // Verificar se CPF já existe como username
                $existing_user = $DB->get_record('user', ['username' => $cpf_limpo], 'id, username');
                if ($existing_user) {
                    $errors['cpf'] = get_string('error_cpf_exists', 'local_solicitacoes');
                }
            }
        } else {
            $errors['cpf'] = get_string('error_cpf_required', 'local_solicitacoes');
        }

        // Validar se o email já existe
        if (!empty($data['email_novo_usuario'])) {
            $existing_user = $DB->get_record('user', ['email' => $data['email_novo_usuario']], 'id, email');
            if ($existing_user) {
                $errors['email_novo_usuario'] = get_string('error_email_exists', 'local_solicitacoes');
            }
        }

        // Validar primeiro nome
        if (empty($data['firstname'])) {
            $errors['firstname'] = get_string('error_firstname_required', 'local_solicitacoes');
        }

        // Validar sobrenome
        if (empty($data['lastname'])) {
            $errors['lastname'] = get_string('error_lastname_required', 'local_solicitacoes');
        }

        // Validar papel selecionado
        if (!empty($data['papel'])) {
            $allowed_roles = local_solicitacoes_get_allowed_roles();
            if (!array_key_exists($data['papel'], $allowed_roles)) {
                $errors['papel'] = get_string('error_invalid_role', 'local_solicitacoes');
            }
        } else {
            $errors['papel'] = get_string('error_role_required', 'local_solicitacoes');
        }

        return $errors;
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/solicitar-cadastro.php'));
$PAGE->set_title(get_string('form_cadastro_titulo', 'local_solicitacoes'));
$PAGE->set_heading(get_string('form_cadastro_titulo', 'local_solicitacoes'));

// Criar instância do formulário
$mform = new cadastro_form();

// Processar formulário se submetido  
if ($data = $mform->get_data()) {
    // Adicionar o tipo de ação aos dados
    $data->tipo_acao = 'cadastro';
    
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
            new moodle_url('/local/solicitacoes/solicitar-cadastro.php'),
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

?>