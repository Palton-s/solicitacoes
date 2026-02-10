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
$PAGE->set_url(new moodle_url('/local/solicitacoes/index.php'));
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
        redirect(new moodle_url('/local/solicitacoes/manage.php'));
    } else {
        redirect(new moodle_url('/local/solicitacoes/myrequests.php'));
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
    
    // Validações
    if (empty($curso_id)) {
        $errors[] = get_string('error_curso_required', 'local_solicitacoes');
    }
    
    if (empty($usuarios_ids)) {
        $errors[] = get_string('error_usuarios_required', 'local_solicitacoes');
    }
    
    if ($tipo_acao == 'inscricao' && empty($papel)) {
        $errors[] = get_string('error_papel_required', 'local_solicitacoes');
    }
    
    if (empty($errors)) {
        // Montar objeto de dados para processamento
        $data = new stdClass();
        $data->tipo_acao = $tipo_acao;
        $data->curso_id_selected = $curso_id;
        $data->usuarios_ids_selected = $usuarios_ids;
        $data->papel = $papel;
        $data->observacoes = $observacoes;
        
        \local_solicitacoes\solicitacoes_controller::process_request_submission($data);
        redirect(new moodle_url('/local/solicitacoes/thankyou.php'));
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
    array('value' => 'suspensao', 'label' => get_string('acao_suspensao', 'local_solicitacoes'))
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
    ? new moodle_url('/local/solicitacoes/manage.php')
    : new moodle_url('/local/solicitacoes/myrequests.php');

// Dados para o template
$template_data = array(
    'action_url' => (new moodle_url('/local/solicitacoes/index.php'))->out(false),
    'sesskey' => sesskey(),
    'acoes' => $acoes,
    'papeis' => $papeis,
    'cancel_url' => $cancel_url->out(false)
);

// Renderizar template
echo $OUTPUT->render_from_template('local_solicitacoes/form_solicitacao', $template_data);

echo $OUTPUT->footer();