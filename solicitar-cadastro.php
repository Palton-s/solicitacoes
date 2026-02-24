<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

require_login();

// Forçar carregamento de strings problemáticas
$forced_strings = [
    'form_cadastro_descricao' => 'Use este formulário para solicitar o cadastro de um novo usuário no sistema com um papel específico.',
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
 * Formulário nativo do Moodle para cadastro de novos usuários
 */
class cadastro_form extends moodleform {

    protected function definition() {
        global $CFG, $DB;
        
        $mform = $this->_form;

        // Header 
        $mform->addElement('header', 'cadastro_details', get_string('form_cadastro_titulo', 'local_solicitacoes'));

        // Aviso/descrição
        $aviso_html = '<div class="alert alert-info" role="alert">' . 
                      '<i class="fas fa-user-plus"></i> ' . 
                      get_string_with_fallback('form_cadastro_descricao', 'local_solicitacoes') . 
                      '</div>';
        $mform->addElement('html', $aviso_html);

        // Nome completo do usuário
        $mform->addElement('text', 'nome_completo', get_string('nome_completo', 'local_solicitacoes'), 
            array('size' => 50));
        $mform->setType('nome_completo', PARAM_TEXT);
        $mform->addRule('nome_completo', null, 'required', null, 'client');
        $mform->addRule('nome_completo', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('nome_completo', 'nome_completo_help', 'local_solicitacoes');

        // Email do usuário
        $mform->addElement('text', 'email', get_string('email', 'local_solicitacoes'), 
            array('size' => 50));
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', null, 'required', null, 'client');
        $mform->addRule('email', null, 'email', null, 'client');
        $mform->addRule('email', null, 'maxlength', 100, 'client');
        $mform->addHelpButton('email', 'email_help', 'local_solicitacoes');

        // Nome de usuário (username)
        $mform->addElement('text', 'username', get_string('username', 'local_solicitacoes'), 
            array('size' => 30));
        $mform->setType('username', PARAM_USERNAME);
        $mform->addRule('username', null, 'required', null, 'client');
        $mform->addRule('username', null, 'maxlength', 100, 'client');
        $mform->addHelpButton('username', 'username_help', 'local_solicitacoes');

        // Papel (role) do usuário
        $roles_options = $this->get_available_roles();
        $mform->addElement('select', 'papel_usuario', get_string('papel_usuario', 'local_solicitacoes'), $roles_options);
        $mform->setType('papel_usuario', PARAM_INT);
        $mform->addRule('papel_usuario', null, 'required', null, 'client');
        $mform->addHelpButton('papel_usuario', 'papel_usuario_help', 'local_solicitacoes');

        // Observações
        $mform->addElement('textarea', 'observacoes', get_string('observacoes', 'local_solicitacoes'), 
            array('rows' => 5, 'cols' => 50, 'placeholder' => get_string_with_fallback('observacoes_placeholder', 'local_solicitacoes')));
        $mform->setType('observacoes', PARAM_TEXT);
        $mform->addHelpButton('observacoes', 'observacoes_help', 'local_solicitacoes');

        // Botões de ação
        $this->add_action_buttons(true, get_string('request_submit', 'local_solicitacoes'));
    }

    /**
     * Buscar papéis disponíveis no sistema 
     */
    protected function get_available_roles() {
        global $DB;
        
        $roles_options = array();
        $roles_options[0] = get_string('choose_role', 'local_solicitacoes');
        
        try {
            // Buscar papéis disponíveis no contexto do sistema
            $systemcontext = context_system::instance();
            $all_roles = role_get_names($systemcontext, ROLENAME_ALIAS, false);
            
            foreach ($all_roles as $roleid => $rolename) {
                $role = $DB->get_record('role', ['id' => $roleid], 'shortname, name');
                if ($role) {
                    $roles_options[$roleid] = role_get_name($role);
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar papéis: " . $e->getMessage());
        }
        
        return $roles_options;
    }

    /**
     * Validação personalizada do formulário
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Validar se o email já existe
        if (!empty($data['email'])) {
            $existing_user = $DB->get_record('user', ['email' => $data['email']], 'id, email');
            if ($existing_user) {
                $errors['email'] = get_string('error_email_exists', 'local_solicitacoes');
            }
        }

        // Validar se o username já existe
        if (!empty($data['username'])) {
            $existing_user = $DB->get_record('user', ['username' => $data['username']], 'id, username');
            if ($existing_user) {
                $errors['username'] = get_string('error_username_exists', 'local_solicitacoes');
            }
        }

        // Validar papel selecionado
        if (!empty($data['papel_usuario']) && $data['papel_usuario'] > 0) {
            $role = $DB->get_record('role', ['id' => $data['papel_usuario']], 'id, name');
            if (!$role) {
                $errors['papel_usuario'] = get_string('error_invalid_role', 'local_solicitacoes');
            }
        } else {
            $errors['papel_usuario'] = get_string('error_role_required', 'local_solicitacoes');
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
            new moodle_url('/local/solicitacoes/confirmacao.php'),
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