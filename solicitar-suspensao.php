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

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/solicitar-suspensao.php'));
$PAGE->set_title(get_string('form_suspensao_titulo', 'local_solicitacoes'));
$PAGE->set_heading(get_string('form_suspensao_titulo', 'local_solicitacoes'));

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

// Capturar submitbutton uma única vez
$submitbutton = optional_param('submitbutton', '', PARAM_TEXT);

// Processar submissão do formulário
if (data_submitted() && confirm_sesskey() && $submitbutton) {
    global $USER, $DB;
    
    $errors = [];
    
    // Coletar dados do formulário
    $curso_id = optional_param('curso_id_selected', 0, PARAM_INT);
    $usuarios_ids = optional_param('usuarios_ids_selected', '', PARAM_TEXT);
    $observacoes = optional_param('observacoes', '', PARAM_TEXT);
    
    // Debug log
    error_log("Suspensão - Dados recebidos - curso: $curso_id, usuarios: $usuarios_ids, motivo: $observacoes");
    
    // Validações
    if (empty($curso_id) || $curso_id <= 0) {
        $errors[] = get_string('error_curso_required', 'local_solicitacoes');
    }
    
    if (empty(trim($usuarios_ids))) {
        $errors[] = get_string('error_usuarios_required', 'local_solicitacoes');
    }
    
    if (empty(trim($observacoes))) {
        $errors[] = get_string('error_motivo_required', 'local_solicitacoes');
    }
    
    // Se não houver erros, processar
    if (empty($errors)) {
        $data = new \stdClass();
        $data->tipo_acao = 'suspensao';
        $data->curso_id_selected = $curso_id;
        $data->usuarios_ids_selected = $usuarios_ids;
        $data->observacoes = $observacoes;
        
        $success = \local_solicitacoes\solicitacoes_controller::process_request_submission($data);
        if ($success) {
            redirect(new moodle_url('/local/solicitacoes/confirmacao.php'),
                     get_string('success_submit', 'local_solicitacoes'), null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect(new moodle_url('/local/solicitacoes/solicitar-suspensao.php'),
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
    'action_url' => (new moodle_url('/local/solicitacoes/solicitar-suspensao.php'))->out(false),
    'sesskey' => sesskey(),
    'cancel_url' => (new moodle_url('/local/solicitacoes/selecionar-acao.php'))->out(false)
];

echo $OUTPUT->render_from_template('local_solicitacoes/form_suspensao', $template_data);

echo $OUTPUT->footer();
