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

// Log geral de acesso (sempre executa)
error_log("========== INSCRICAO.PHP CARREGADO ==========");
error_log("Inscrição - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("Inscrição - POST dados: " . print_r($_POST, true));
error_log("Inscrição - GET dados: " . print_r($_GET, true));

echo $OUTPUT->header();

// Preparar dados para o template
$template_data = [
    'action_url' => (new moodle_url('/local/solicitacoes/processar-inscricao.php'))->out(false),
    'sesskey' => sesskey(),
    'cancel_url' => (new moodle_url('/local/solicitacoes/selecionar-acao.php'))->out(false),
    'roles' => $roles_array
];

echo $OUTPUT->render_from_template('local_solicitacoes/form_inscricao', $template_data);

echo $OUTPUT->footer();
