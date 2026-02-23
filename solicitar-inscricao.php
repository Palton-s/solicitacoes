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

// Obter papel do GET (teacher ou student)
$papel = required_param('papel', PARAM_ALPHA);
if (!in_array($papel, ['teacher', 'student'])) {
    redirect(new moodle_url('/local/solicitacoes/selecionar-acao.php'));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/solicitar-inscricao.php', ['papel' => $papel]));
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
    $observacoes = optional_param('observacoes', '', PARAM_TEXT);
    
    // Validações
    if (empty($curso_id) || $curso_id <= 0) {
        $errors[] = get_string('error_curso_required', 'local_solicitacoes');
    }
    
    if (empty(trim($usuarios_ids))) {
        $errors[] = get_string('error_usuarios_required', 'local_solicitacoes');
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
            redirect(new moodle_url('/local/solicitacoes/solicitar-inscricao.php', ['papel' => $papel]),
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
$papel_label = get_string('papel_' . $papel, 'local_solicitacoes');
$template_data = [
    'action_url' => (new moodle_url('/local/solicitacoes/solicitar-inscricao.php', ['papel' => $papel]))->out(false),
    'sesskey' => sesskey(),
    'cancel_url' => (new moodle_url('/local/solicitacoes/selecionar-acao.php'))->out(false),
    'papel' => $papel,
    'papel_label' => $papel_label,
    'is_teacher' => ($papel === 'teacher')
];

echo $OUTPUT->render_from_template('local_solicitacoes/form_inscricao', $template_data);

echo $OUTPUT->footer();
