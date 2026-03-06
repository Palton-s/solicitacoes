<?php
require('../../config.php');

require_login();

$context = context_system::instance();

if (!has_capability('local/solicitacoes:submit', $context)) {
    redirect(
        new moodle_url('/'),
        get_string('error_nopermission_submit', 'local_solicitacoes'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/selecionar-cursos.php'));
$PAGE->set_title(get_string('selecionar_cursos_titulo', 'local_solicitacoes'));
$PAGE->set_heading(get_string('selecionar_cursos_titulo', 'local_solicitacoes'));
$PAGE->requires->css(new moodle_url('/local/solicitacoes/styles/action_selection.css'));

$actions = [
    [
        'icon' => 'fas fa-plus-circle',
        'title' => get_string('btn_criar_curso_titulo', 'local_solicitacoes'),
        'description' => get_string('btn_criar_curso_desc', 'local_solicitacoes'),
        'url' => (new moodle_url('/local/solicitacoes/solicitar-curso.php'))->out(false),
        'class' => 'action-course'
    ],
    [
        'icon' => 'fas fa-folder-minus',
        'title' => get_string('btn_remover_curso_titulo', 'local_solicitacoes'),
        'description' => get_string('btn_remover_curso_desc', 'local_solicitacoes'),
        'url' => (new moodle_url('/local/solicitacoes/solicitar-remover-curso.php'))->out(false),
        'class' => 'action-remove-course'
    ]
];

$template_data = [
    'subtitle' => get_string('selecionar_cursos_subtitulo', 'local_solicitacoes'),
    'actions' => $actions,
    'minhas_solicitacoes_url' => (new moodle_url('/local/solicitacoes/minhas-solicitacoes.php'))->out(false)
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_solicitacoes/selecionar_acao', $template_data);
echo $OUTPUT->footer();
