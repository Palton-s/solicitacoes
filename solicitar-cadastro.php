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
     * Buscar cursos disponíveis para seleção 
     */
    protected function get_available_courses() {
        global $DB, $USER;
        
        $cursos_options = array();
        
        try {
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