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
$PAGE->set_url(new moodle_url('/local/solicitacoes/solicitar-curso.php'));
$PAGE->set_title(get_string('form_criar_curso_titulo', 'local_solicitacoes'));
$PAGE->set_heading(get_string('form_criar_curso_titulo', 'local_solicitacoes'));

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
    $codigo_sigaa = optional_param('codigo_sigaa', '', PARAM_TEXT);
    $course_shortname = optional_param('course_shortname', '', PARAM_TEXT);
    $unidade_academica_id = optional_param('unidade_academica_id', 0, PARAM_INT);
    $ano_semestre = optional_param('ano_semestre', '', PARAM_TEXT);
    $course_summary = optional_param('course_summary', '', PARAM_RAW);
    $razoes_criacao = optional_param('razoes_criacao', '', PARAM_TEXT);
    
    // Debug log
    error_log("Criar Curso - Dados recebidos - codigo: $codigo_sigaa, shortname: $course_shortname, categoria: $unidade_academica_id, ano: $ano_semestre");
    
    // Validações
    if (empty(trim($codigo_sigaa))) {
        $errors[] = get_string('error_codigo_sigaa_required', 'local_solicitacoes');
    }
    
    if (empty(trim($course_shortname))) {
        $errors[] = get_string('error_course_shortname_required', 'local_solicitacoes');
    }
    
    if (empty($unidade_academica_id) || $unidade_academica_id <= 0) {
        $errors[] = get_string('error_unidade_required', 'local_solicitacoes');
    }
    
    if (empty(trim($ano_semestre))) {
        $errors[] = get_string('error_ano_semestre_required', 'local_solicitacoes');
    }
    
    if (empty(trim($razoes_criacao))) {
        $errors[] = get_string('error_razoes_criacao_required', 'local_solicitacoes');
    }
    
    // Se não houver erros, processar
    if (empty($errors)) {
        $data = new \stdClass();
        $data->tipo_acao = 'criar_curso';
        $data->codigo_sigaa = trim($codigo_sigaa);
        $data->course_shortname = trim($course_shortname);
        $data->unidade_academica_id = $unidade_academica_id;
        $data->ano_semestre = trim($ano_semestre);
        $data->course_summary = $course_summary;
        $data->razoes_criacao = trim($razoes_criacao);
        
        $success = \local_solicitacoes\solicitacoes_controller::process_request_submission($data);
        if ($success) {
            redirect(new moodle_url('/local/solicitacoes/confirmacao.php'),
                     get_string('success_submit', 'local_solicitacoes'), null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect(new moodle_url('/local/solicitacoes/solicitar-curso.php'),
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
    'action_url' => (new moodle_url('/local/solicitacoes/solicitar-curso.php'))->out(false),
    'sesskey' => sesskey(),
    'cancel_url' => (new moodle_url('/local/solicitacoes/selecionar-acao.php'))->out(false),
    'aviso_sigaa' => get_string('aviso_criar_curso', 'local_solicitacoes')
];

echo $OUTPUT->render_from_template('local_solicitacoes/form_criar_curso', $template_data);

echo $OUTPUT->footer();
