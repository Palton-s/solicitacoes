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
        'icon' => 'fas fa-chalkboard-teacher',
        'title' => get_string('btn_vincular_professor_titulo', 'local_solicitacoes'),
        'description' => get_string('btn_vincular_professor_desc', 'local_solicitacoes'),
        'url' => (new moodle_url('/local/solicitacoes/solicitar-inscricao.php', ['papel' => 'teacher']))->out(false),
        'class' => 'action-teacher'
    ],
    [
        'icon' => 'fas fa-user-graduate',
        'title' => get_string('btn_vincular_aluno_titulo', 'local_solicitacoes'),
        'description' => get_string('btn_vincular_aluno_desc', 'local_solicitacoes'),
        'url' => (new moodle_url('/local/solicitacoes/solicitar-inscricao.php', ['papel' => 'student']))->out(false),
        'class' => 'action-student'
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
        'icon' => 'fas fa-user-plus',
        'title' => get_string('btn_cadastrar_usuario_titulo', 'local_solicitacoes'),
        'description' => get_string('btn_cadastrar_usuario_desc', 'local_solicitacoes'),
        'url' => (new moodle_url('/local/solicitacoes/solicitar-cadastro.php'))->out(false),
        'class' => 'action-register'
    ],
    [
        'icon' => 'fas fa-book-open',
        'title' => get_string('btn_criar_curso_titulo', 'local_solicitacoes'),
        'description' => get_string('btn_criar_curso_desc', 'local_solicitacoes'),
        'url' => (new moodle_url('/local/solicitacoes/solicitar-curso.php'))->out(false),
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
