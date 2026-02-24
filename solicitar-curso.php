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



// Pega a lista formatada de categorias (igual à que você viu no curso)
$categorias_raw = core_course_category::make_categories_list();
$categorias_formatadas = [];

foreach ($categorias_raw as $id => $nome) {
    $categorias_formatadas[] = [
        'id' => $id,
        'nome' => $nome
    ];
}

// Preparar dados para o template
$template_data = [
    'action_url' => (new moodle_url('/local/solicitacoes/processar-curso.php'))->out(false),
    'sesskey' => sesskey(),
    'cancel_url' => (new moodle_url('/local/solicitacoes/selecionar-acao.php'))->out(false),
    'aviso_sigaa' => get_string('aviso_criar_curso', 'local_solicitacoes'),
    'categorias' => $categorias_formatadas // <--- Nova variável
];

echo $OUTPUT->render_from_template('local_solicitacoes/form_criar_curso', $template_data);

echo $OUTPUT->footer();
