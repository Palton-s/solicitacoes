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
$PAGE->set_url(new moodle_url('/local/solicitacoes/selecionar-acao.php'));
$PAGE->set_title(get_string('selecionar_acao_titulo', 'local_solicitacoes'));
$PAGE->set_heading(get_string('selecionar_acao_titulo', 'local_solicitacoes'));

// Incluir CSS customizado
$PAGE->requires->css(new moodle_url('/local/solicitacoes/styles/action_selection.css'));

echo $OUTPUT->header();

// Preparar dados para o template
$actions = [
    [
        'icon' => 'fas fa-users',
        'title' => get_string('btn_grupo_usuarios_titulo', 'local_solicitacoes'),
        'description' => get_string('btn_grupo_usuarios_desc', 'local_solicitacoes'),
        'url' => (new moodle_url('/local/solicitacoes/selecionar-usuarios.php'))->out(false),
        'class' => 'action-users'
    ],
    [
        'icon' => 'fas fa-book-open',
        'title' => get_string('btn_grupo_cursos_titulo', 'local_solicitacoes'),
        'description' => get_string('btn_grupo_cursos_desc', 'local_solicitacoes'),
        'url' => (new moodle_url('/local/solicitacoes/selecionar-cursos.php'))->out(false),
        'class' => 'action-course'
    ]
];

$template_data = [
    'subtitle' => get_string('selecionar_acao_subtitulo', 'local_solicitacoes'),
    'actions' => $actions,
    'minhas_solicitacoes_url' => (new moodle_url('/local/solicitacoes/minhas-solicitacoes.php'))->out(false)
];

echo $OUTPUT->render_from_template('local_solicitacoes/selecionar_acao', $template_data);

echo $OUTPUT->footer();
