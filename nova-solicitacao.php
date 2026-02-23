<?php
require('../../config.php');

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

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/nova-solicitacao.php'));
$PAGE->set_title(get_string('request_form_title', 'local_solicitacoes'));
$PAGE->set_heading(get_string('request_form_title', 'local_solicitacoes'));

// Incluir CSS e JS externos via API do Moodle
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css'));
$PAGE->requires->css(new moodle_url('/local/solicitacoes/styles/request_form.css'));
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js'), true);
$PAGE->requires->js(new moodle_url('/local/solicitacoes/styles/request_form.js'), true);

// Garantir que M.cfg.wwwroot está disponível
$PAGE->requires->js_init_code("
    if (typeof M === 'undefined') {
        window.M = { cfg: { wwwroot: '{$CFG->wwwroot}' } };
    } else if (!M.cfg) {
        M.cfg = { wwwroot: '{$CFG->wwwroot}' };
    } else if (!M.cfg.wwwroot) {
        M.cfg.wwwroot = '{$CFG->wwwroot}';
    }
");

echo $OUTPUT->header();

// Processar cancelamento
if (optional_param('cancel', 0, PARAM_BOOL)) {
    if (has_capability('local/solicitacoes:manage', $context)) {
        redirect(new moodle_url('/local/solicitacoes/gerenciar.php'));
        exit;
    } else {
        redirect(new moodle_url('/local/solicitacoes/minhas-solicitacoes.php'));
        exit;
    }
}

// Processar submissão do formulário
if (data_submitted() && confirm_sesskey() && optional_param('submitbutton', 0, PARAM_TEXT)) {
    // Validar dados
    $errors = array();
    
    $tipo_acao = required_param('tipo_acao', PARAM_TEXT);
    $curso_id = optional_param('curso_id_selected', 0, PARAM_INT);
    $usuarios_ids = optional_param('usuarios_ids_selected', '', PARAM_TEXT);
    $papel = optional_param('papel', '', PARAM_TEXT);
    $observacoes = optional_param('observacoes', '', PARAM_TEXT);
    
    // Campos de cadastro de usuário
    $firstname = optional_param('firstname', '', PARAM_TEXT);
    $lastname = optional_param('lastname', '', PARAM_TEXT);
    $cpf = optional_param('cpf', '', PARAM_TEXT);
    $email_novo_usuario = optional_param('email_novo_usuario', '', PARAM_EMAIL);
    
    // Campos de criação de curso
    $codigo_sigaa = optional_param('codigo_sigaa', '', PARAM_TEXT);
    $course_shortname = optional_param('course_shortname', '', PARAM_TEXT);
    $course_summary = optional_param('course_summary', '', PARAM_RAW);
    $unidade_academica_id = optional_param('unidade_academica_id', 0, PARAM_INT);
    $ano_semestre = optional_param('ano_semestre', '', PARAM_TEXT);
    $razoes_criacao = optional_param('razoes_criacao', '', PARAM_TEXT);
    
    // Validações
    if ($tipo_acao != 'criar_curso' && empty($curso_id)) {
        $errors[] = get_string('error_curso_required', 'local_solicitacoes');
    }
    
    if ($tipo_acao == 'criar_curso') {
        // Validações específicas para criação de curso
        if (empty($codigo_sigaa)) {
            $errors[] = get_string('error_codigo_sigaa_required', 'local_solicitacoes');
        }
        
        if (empty($course_shortname)) {
            $errors[] = get_string('error_course_shortname_required', 'local_solicitacoes');
        } else {
            // Verificar se já existe um curso com este shortname
            if ($DB->record_exists('course', array('shortname' => $course_shortname))) {
                $errors[] = get_string('error_course_shortname_duplicate', 'local_solicitacoes');
            }
        }
        
        if (empty($unidade_academica_id)) {
            $errors[] = get_string('error_unidade_required', 'local_solicitacoes');
        }
        
        if (empty($ano_semestre)) {
            $errors[] = get_string('error_ano_semestre_required', 'local_solicitacoes');
        }
        
        if (empty($razoes_criacao)) {
            $errors[] = get_string('error_razoes_criacao_required', 'local_solicitacoes');
        }
    } else if ($tipo_acao == 'cadastro') {
        // Validações específicas para cadastro
        if (empty($firstname)) {
            $errors[] = get_string('error_firstname_required', 'local_solicitacoes');
        }
        
        if (empty($lastname)) {
            $errors[] = get_string('error_lastname_required', 'local_solicitacoes');
        }
        
        if (empty($cpf)) {
            $errors[] = get_string('error_cpf_required', 'local_solicitacoes');
        } else {
            // Remover caracteres não numéricos
            $cpf = preg_replace('/[^0-9]/', '', $cpf);
            
            // Validar formato (11 dígitos)
            if (strlen($cpf) != 11) {
                $errors[] = get_string('error_cpf_invalid', 'local_solicitacoes');
            }
        }
        
        if (empty($email_novo_usuario)) {
            $errors[] = get_string('error_email_novo_required', 'local_solicitacoes');
        } else if (!validate_email($email_novo_usuario)) {
            $errors[] = get_string('error_email_invalid', 'local_solicitacoes');
        }
        
        if (empty($papel)) {
            $errors[] = get_string('error_papel_required', 'local_solicitacoes');
        }
    } else {
        // Validações para inscrição, remoção, suspensão
        if (empty($usuarios_ids)) {
            $errors[] = get_string('error_usuarios_required', 'local_solicitacoes');
        }
        
        if ($tipo_acao == 'inscricao' && empty($papel)) {
            $errors[] = get_string('error_papel_required', 'local_solicitacoes');
        }
    }
    
    if (empty($errors)) {
        // Montar objeto de dados para processamento
        $data = new stdClass();
        $data->tipo_acao = $tipo_acao;
        $data->curso_id_selected = $curso_id;
        $data->usuarios_ids_selected = $usuarios_ids;
        $data->papel = $papel;
        $data->observacoes = $observacoes;
        
        // Adicionar campos de cadastro se aplicável
        if ($tipo_acao == 'cadastro') {
            $data->firstname = $firstname;
            $data->lastname = $lastname;
            $data->cpf = $cpf;
            $data->email_novo_usuario = $email_novo_usuario;
        }
        
        // Adicionar campos de criação de curso se aplicável
        if ($tipo_acao == 'criar_curso') {
            $data->codigo_sigaa = $codigo_sigaa;
            $data->course_shortname = $course_shortname;
            $data->course_summary = $course_summary;
            $data->unidade_academica_id = $unidade_academica_id;
            $data->ano_semestre = $ano_semestre;
            $data->razoes_criacao = $razoes_criacao;
        }
        
        \local_solicitacoes\solicitacoes_controller::process_request_submission($data);
        redirect(new moodle_url('/local/solicitacoes/confirmacao.php'));
        exit;
    } else {
        // Mostrar erros
        foreach ($errors as $error) {
            echo $OUTPUT->notification($error, \core\output\notification::NOTIFY_ERROR);
        }
    }
}

// Preparar dados para o template
global $DB;

// Tipos de ação
$acoes = array(
    array('value' => 'inscricao', 'label' => get_string('acao_inscricao', 'local_solicitacoes')),
    array('value' => 'remocao', 'label' => get_string('acao_remocao', 'local_solicitacoes')),
    array('value' => 'suspensao', 'label' => get_string('acao_suspensao', 'local_solicitacoes')),
    array('value' => 'cadastro', 'label' => get_string('acao_cadastro', 'local_solicitacoes')),
    array('value' => 'criar_curso', 'label' => get_string('acao_criar_curso', 'local_solicitacoes'))
);

// Buscar papéis (roles) disponíveis
$papeis = array();
$roles = $DB->get_records_sql(
    "SELECT r.id, r.shortname, r.name 
     FROM {role} r
     JOIN {role_context_levels} rcl ON rcl.roleid = r.id
     WHERE rcl.contextlevel = :contextlevel
     AND r.archetype IN ('student', 'teacher', 'editingteacher', 'manager')
     ORDER BY r.sortorder",
    array('contextlevel' => CONTEXT_COURSE)
);

foreach ($roles as $role) {
    $papeis[] = array(
        'value' => $role->shortname,
        'label' => role_get_name($role)
    );
}

// URL de cancelamento
$cancel_url = has_capability('local/solicitacoes:manage', $context) 
    ? new moodle_url('/local/solicitacoes/gerenciar.php')
    : new moodle_url('/local/solicitacoes/minhas-solicitacoes.php');

// Dados para o template
$template_data = array(
    'action_url' => (new moodle_url('/local/solicitacoes/nova-solicitacao.php'))->out(false),
    'sesskey' => sesskey(),
    'acoes' => $acoes,
    'papeis' => $papeis,
    'cancel_url' => $cancel_url->out(false),
    'aviso_criar_curso' => get_string('aviso_criar_curso', 'local_solicitacoes')
);

// Renderizar template
echo $OUTPUT->render_from_template('local_solicitacoes/form_solicitacao', $template_data);

echo $OUTPUT->footer();