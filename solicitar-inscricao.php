<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');

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

// Buscar papéis disponíveis no Moodle
$systemcontext = context_system::instance();
$all_roles = role_get_names($systemcontext, ROLENAME_ALIAS, false);
$roles_array = [];
foreach ($all_roles as $roleid => $rolename) {
    $role = $DB->get_record('role', ['id' => $roleid], 'shortname, name');
    if ($role) {
        $roles_array[] = [
            'shortname' => $role->shortname,
            'localname' => role_get_name($role)
        ];
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/solicitar-inscricao.php'));
$PAGE->set_title(get_string('form_inscricao_titulo', 'local_solicitacoes'));
$PAGE->set_heading(get_string('form_inscricao_titulo', 'local_solicitacoes'));

// Incluir CSS e JS necessários
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css'));
$PAGE->requires->css(new moodle_url('/local/solicitacoes/styles/tomselect_custom.css'));
$PAGE->requires->css(new moodle_url('/local/solicitacoes/styles/request_form.css'));
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js'), true);

// Inicializar M.cfg.wwwroot para AJAX
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
    redirect(new moodle_url('/local/solicitacoes/selecionar-acao.php'));
    exit;
}

// Processar submissão do formulário
if (data_submitted() && confirm_sesskey() && optional_param('submitbutton', 0, PARAM_TEXT)) {
    global $USER, $DB;
    
    $errors = [];
    
    // Coletar dados do formulário
    $curso_id = required_param('curso_id_selected', PARAM_INT);
    $usuarios_ids = required_param('usuarios_ids_selected', PARAM_TEXT);
    $papel = required_param('papel', PARAM_ALPHANUMEXT);
    $observacoes = optional_param('observacoes', '', PARAM_TEXT);
    
    // Validações
    if (empty($curso_id) || $curso_id <= 0) {
        $errors[] = get_string('error_curso_required', 'local_solicitacoes');
    }
    
    if (empty(trim($usuarios_ids))) {
        $errors[] = get_string('error_usuarios_required', 'local_solicitacoes');
    }
    
    // Validar papel
    if (empty($papel)) {
        $errors[] = get_string('error_papel_required', 'local_solicitacoes');
    } else {
        $role_check = $DB->get_record('role', ['shortname' => $papel]);
        if (!$role_check) {
            $errors[] = get_string('error_papel_invalid', 'local_solicitacoes');
        }
    }
    
    // Se não houver erros, processar
    if (empty($errors)) {
        $data = new \stdClass();
        $data->tipo_acao = 'inscricao';
        $data->curso_id_selected = $curso_id;
        $data->usuarios_ids_selected = $usuarios_ids;
        $data->papel = $papel;
        $data->observacoes = $observacoes;
        
        $success = \local_solicitacoes\solicitacoes_controller::process_request_submission($data);
        if ($success) {
            redirect(new moodle_url('/local/solicitacoes/confirmacao.php'),
                     get_string('success_submit', 'local_solicitacoes'), null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect(new moodle_url('/local/solicitacoes/solicitar-inscricao.php'),
                     get_string('error_submit', 'local_solicitacoes'), null, \core\output\notification::NOTIFY_ERROR);
        }
        exit;
    } else {
        // Mostrar erros
        foreach ($errors as $error) {
            echo $OUTPUT->notification($error, \core\output\notification::NOTIFY_ERROR);
        }
    }
}

// Preparar dados para o template
$template_data = [
    'action_url' => (new moodle_url('/local/solicitacoes/solicitar-inscricao.php'))->out(false),
    'sesskey' => sesskey(),
    'cancel_url' => (new moodle_url('/local/solicitacoes/selecionar-acao.php'))->out(false),
    'roles' => $roles_array
];

echo $OUTPUT->render_from_template('local_solicitacoes/form_inscricao', $template_data);

echo $OUTPUT->footer();
