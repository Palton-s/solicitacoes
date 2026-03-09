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
$PAGE->set_url(new moodle_url('/local/solicitacoes/selecionar-usuarios.php'));
$PAGE->set_title(get_string('selecionar_usuarios_titulo', 'local_solicitacoes'));
$PAGE->set_heading(get_string('selecionar_usuarios_titulo', 'local_solicitacoes'));
$PAGE->requires->css(new moodle_url('/local/solicitacoes/styles/action_selection.css'));

$actions = [
    [
        'icon' => 'fas fa-user-plus',
        'title' => get_string('btn_inscrever_usuario_titulo', 'local_solicitacoes'),
        'description' => get_string('btn_inscrever_usuario_desc', 'local_solicitacoes'),
        'url' => (new moodle_url('/local/solicitacoes/solicitar-inscricao.php'))->out(false),
        'class' => 'action-enrol'
    ],
    [
        'icon' => 'fas fa-user-times',
        'title' => get_string('btn_remover_usuario_titulo', 'local_solicitacoes'),
        'description' => get_string('btn_remover_usuario_desc', 'local_solicitacoes'),
        'url' => (new moodle_url('/local/solicitacoes/solicitar-remocao.php'))->out(false),
        'class' => 'action-remove'
    ],
    [
        'icon' => 'fas fa-pause-circle',
        'title' => get_string('btn_suspender_usuario_titulo', 'local_solicitacoes'),
        'description' => get_string('btn_suspender_usuario_desc', 'local_solicitacoes'),
        'url' => (new moodle_url('/local/solicitacoes/solicitar-suspensao.php'))->out(false),
        'class' => 'action-suspend'
    ],
    [
        'icon' => 'fas fa-user-check',
        'title' => get_string('btn_cadastrar_usuario_titulo', 'local_solicitacoes'),
        'description' => get_string('btn_cadastrar_usuario_desc', 'local_solicitacoes'),
        'url' => (new moodle_url('/local/solicitacoes/solicitar-cadastro.php'))->out(false),
        'class' => 'action-register'
    ]
];

$template_data = [
    'subtitle' => get_string('selecionar_usuarios_subtitulo', 'local_solicitacoes'),
    'actions' => $actions,
    'minhas_solicitacoes_url' => (new moodle_url('/local/solicitacoes/minhas-solicitacoes.php'))->out(false)
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_solicitacoes/selecionar_acao', $template_data);
echo $OUTPUT->footer();
