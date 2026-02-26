<?php
require('../../config.php');

require_login();

$context = context_system::instance();
$id      = required_param('id', PARAM_INT);

$canmanage  = has_capability('local/solicitacoes:manage', $context);
$canviewall = has_capability('local/solicitacoes:viewall', $context);

if (!$canmanage && !$canviewall) {
    if (!has_capability('local/solicitacoes:view', $context)) {
        redirect(
            new moodle_url('/'),
            get_string('error_nopermission_viewrequest', 'local_solicitacoes'),
            null,
            \core\output\notification::NOTIFY_INFO
        );
        exit;
    }
    $request = $DB->get_record('local_solicitacoes', ['id' => $id], '*', MUST_EXIST);
    if ($request->userid != $USER->id) {
        redirect(
            new moodle_url('/local/solicitacoes/minhas-solicitacoes.php'),
            get_string('error_nopermission_viewrequest', 'local_solicitacoes'),
            null,
            \core\output\notification::NOTIFY_INFO
        );
        exit;
    }
}

// ─── Dados ────────────────────────────────────────────────────────────────────

$systemcontext = context_system::instance();
$all_roles     = role_get_names($systemcontext, ROLENAME_ALIAS, false);
$roles_lookup  = [];
foreach ($all_roles as $roleid => $rolename) {
    $role = $DB->get_record('role', ['id' => $roleid], 'shortname');
    if ($role) {
        $roles_lookup[$role->shortname] = role_get_name($role);
    }
}

$sql = "SELECT r.*, u.firstname, u.lastname, u.email
        FROM {local_solicitacoes} r
        JOIN {user} u ON r.userid = u.id
        WHERE r.id = :id";
$request = $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);

$sql_cursos = "SELECT c.id, c.fullname, c.shortname
               FROM {local_curso_solicitacoes} cs
               JOIN {course} c ON cs.curso_id = c.id
               WHERE cs.solicitacao_id = :id";
$cursos = $DB->get_records_sql($sql_cursos, ['id' => $id]);

$sql_usuarios = "SELECT u.id, u.firstname, u.lastname, u.email, u.username
                 FROM {local_usuarios_solicitacoes} us
                 JOIN {user} u ON us.usuario_id = u.id
                 WHERE us.solicitacao_id = :id";
$usuarios = $DB->get_records_sql($sql_usuarios, ['id' => $id]);

// ─── Configuração da página ───────────────────────────────────────────────────

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/detalhes.php'), ['id' => $id]);
$PAGE->set_title(get_string('details', 'local_solicitacoes'));
$PAGE->set_heading(get_string('details', 'local_solicitacoes'));
$PAGE->set_pagelayout('standard');

$backurl = $canmanage
    ? new moodle_url('/local/solicitacoes/gerenciar.php')
    : new moodle_url('/local/solicitacoes/minhas-solicitacoes.php');

$PAGE->navbar->add(
    get_string('pluginname', 'local_solicitacoes'),
    new moodle_url('/local/solicitacoes/selecionar-acao.php')
);
$PAGE->navbar->add(get_string('back_to_list', 'local_solicitacoes'), $backurl);
$PAGE->navbar->add(get_string('details', 'local_solicitacoes'));

// ─── Helpers ──────────────────────────────────────────────────────────────────

$acao_strings = [
    'inscricao'   => get_string('acao_inscricao',   'local_solicitacoes'),
    'remocao'     => get_string('acao_remocao',     'local_solicitacoes'),
    'suspensao'   => get_string('acao_suspensao',   'local_solicitacoes'),
    'cadastro'    => get_string('acao_cadastro',    'local_solicitacoes'),
    'criar_curso' => get_string('acao_criar_curso', 'local_solicitacoes'),
];
$acao_label = $acao_strings[$request->tipo_acao] ?? $request->tipo_acao;

// Status → classe Bootstrap de badge (disponível no Moodle/Boost)
$status_badge_class = [
    'pendente'     => 'badge-warning',
    'aprovado'     => 'badge-success',
    'negado'       => 'badge-danger',
    'em_andamento' => 'badge-info',
    'concluido'    => 'badge-primary',
];
$badge_class = $status_badge_class[$request->status] ?? 'badge-secondary';
$badge_label = get_string('status_' . $request->status, 'local_solicitacoes');

/**
 * Renderiza uma linha de detalhe com ícone nativo do Moodle.
 */
function detail_row(string $icon, string $label, string $value): string {
    global $OUTPUT;
    $pix = $OUTPUT->pix_icon($icon, '', 'moodle', ['class' => 'mr-1']);
    return html_writer::tag('p',
        $pix . html_writer::tag('strong', $label . ': ') . $value,
        ['class' => 'mb-2']
    );
}

/**
 * Renderiza um card Bootstrap com header e conteúdo.
 */
function render_card(string $header_html, string $body_html, string $header_extra_class = ''): string {
    $hclass = trim('card-header ' . $header_extra_class);
    $out  = html_writer::start_div('card mb-3');
    $out .= html_writer::div($header_html, $hclass);
    $out .= html_writer::div($body_html, 'card-body');
    $out .= html_writer::end_div();
    return $out;
}

echo $OUTPUT->header();

// ─── Título + badge de status ─────────────────────────────────────────────────

$header_name = ($request->tipo_acao === 'criar_curso')
    ? $acao_label . ' — ' . format_string($request->course_shortname)
    : $acao_label . ' — ' . format_string($request->curso_nome);

$badge_html = html_writer::tag('span', $badge_label, [
    'class' => 'badge badge-pill ' . $badge_class,
    'style' => 'font-size:.85rem; vertical-align:middle;',
]);
$date_html = html_writer::tag('small',
    userdate($request->timecreated, get_string('strftimedatetime', 'langconfig')),
    ['class' => 'text-muted ml-2']
);

echo $OUTPUT->heading($header_name . ' ' . $badge_html . $date_html, 3);

// ─── Layout em duas colunas ───────────────────────────────────────────────────
echo html_writer::start_div('row');

// ════ COLUNA ESQUERDA ════════════════════════════════════════════════════════
echo html_writer::start_div('col-lg-8');

if ($request->tipo_acao === 'criar_curso') {
    // Card: detalhes do curso a criar
    $body = '';
    $body .= detail_row('b/memo',     get_string('codigo_sigaa',      'local_solicitacoes'), format_string($request->codigo_sigaa));
    $body .= detail_row('b/tag',      get_string('course_shortname',  'local_solicitacoes'), format_string($request->course_shortname));

    if (!empty($request->unidade_academica_id)) {
        $categoria = $DB->get_record('course_categories', ['id' => $request->unidade_academica_id]);
        if ($categoria) {
            $body .= detail_row('b/bookmark', get_string('unidade_academica', 'local_solicitacoes'), format_string($categoria->name));
        }
    }

    $body .= detail_row('b/time', get_string('ano_semestre', 'local_solicitacoes'), format_string($request->ano_semestre));

    if (!empty($request->course_summary)) {
        $body .= html_writer::tag('strong', get_string('course_summary', 'local_solicitacoes') . ':');
        $body .= $OUTPUT->box(format_text($request->course_summary, FORMAT_HTML), 'generalbox mt-1 mb-3');
    }

    if (!empty($request->razoes_criacao)) {
        $body .= html_writer::tag('strong', get_string('razoes_criacao', 'local_solicitacoes') . ':');
        $body .= $OUTPUT->box(
            html_writer::tag('div', nl2br(s($request->razoes_criacao)), ['style' => 'white-space:pre-wrap']),
            'generalbox mt-1'
        );
    }

    echo render_card(
        $OUTPUT->pix_icon('b/globe', '', 'moodle', ['class' => 'mr-1']) .
        html_writer::tag('strong', get_string('acao_criar_curso', 'local_solicitacoes')),
        $body
    );

} else {
    // Card: usuários afetados / novo usuário
    $card_title = ($request->tipo_acao === 'cadastro')
        ? get_string('novo_usuario',  'local_solicitacoes')
        : get_string('target_users', 'local_solicitacoes');

    $list_items = '';

    if ($request->tipo_acao === 'cadastro') {
        $list_items .= html_writer::start_tag('li', ['class' => 'list-group-item']);
        $list_items .= html_writer::tag('strong', format_string($request->firstname . ' ' . $request->lastname)) .
                       html_writer::tag('div',
                           get_string('cpf', 'local_solicitacoes') . ': ' . s($request->cpf) . html_writer::empty_tag('br') .
                           get_string('email_novo_usuario', 'local_solicitacoes') . ': ' . s($request->email),
                           ['class' => 'small text-muted mt-1']
                       );
        $list_items .= html_writer::end_tag('li');

    } else if (!empty($usuarios)) {
        foreach ($usuarios as $usuario) {
            $user_obj    = core_user::get_user($usuario->id);
            $user_pic    = $OUTPUT->user_picture($user_obj, ['size' => 35, 'link' => false, 'class' => 'mr-2']);
            $list_items .= html_writer::start_tag('li', ['class' => 'list-group-item d-flex align-items-center']);
            $list_items .= $user_pic;
            $list_items .= html_writer::div(
                html_writer::tag('strong', fullname($usuario)) .
                html_writer::tag('div', s($usuario->email) . ' (' . s($usuario->username) . ')', ['class' => 'small text-muted']),
                ''
            );
            $list_items .= html_writer::end_tag('li');
        }
    } else {
        foreach (explode("\n", trim($request->usuarios_nomes)) as $nome) {
            $nome = trim($nome);
            if ($nome === '') {
                continue;
            }
            $list_items .= html_writer::tag('li', format_string($nome), ['class' => 'list-group-item']);
        }
    }

    echo render_card(
        $OUTPUT->pix_icon('b/group-member', '', 'moodle', ['class' => 'mr-1']) .
        html_writer::tag('strong', $card_title),
        html_writer::tag('ul', $list_items, ['class' => 'list-group list-group-flush']),
        'p-2'
    );
}

// Card: Observações
if (!empty($request->observacoes)) {
    echo render_card(
        $OUTPUT->pix_icon('b/memo', '', 'moodle', ['class' => 'mr-1']) .
        html_writer::tag('strong', get_string('observacoes', 'local_solicitacoes')),
        html_writer::tag('div', nl2br(s($request->observacoes)), ['class' => 'text-muted', 'style' => 'white-space:pre-wrap'])
    );
}

// Card: Motivo da negação
if ($request->status === 'negado' && !empty($request->motivo_negacao)) {
    echo render_card(
        $OUTPUT->pix_icon('b/memo', '', 'moodle', ['class' => 'mr-1']) .
        html_writer::tag('strong', get_string('motivo_negacao', 'local_solicitacoes')),
        html_writer::tag('div', nl2br(s($request->motivo_negacao)), ['style' => 'white-space:pre-wrap'])
    );
}

echo html_writer::end_div(); // fim col-lg-8

// ════ COLUNA DIREITA ══════════════════════════════════════════════════════════
echo html_writer::start_div('col-lg-4');

// Card: Curso(s) envolvido(s)
if ($request->tipo_acao !== 'criar_curso') {
    $course_body = '';

    if (!empty($cursos)) {
        $table             = new html_table();
        $table->head       = [get_string('fullnamecourse'), get_string('shortnamecourse')];
        $table->data       = [];
        $table->attributes = ['class' => 'table table-sm mb-0'];
        foreach ($cursos as $curso) {
            $table->data[] = [format_string($curso->fullname), format_string($curso->shortname)];
        }
        $course_body .= html_writer::table($table);
    } else {
        $course_body .= html_writer::tag('p', format_string($request->curso_nome), ['class' => 'mb-1']);
    }

    if (in_array($request->tipo_acao, ['inscricao', 'cadastro']) && !empty($request->papel)) {
        $papel_label  = $roles_lookup[$request->papel] ?? $request->papel;
        $course_body .= html_writer::tag('p',
            html_writer::tag('strong', get_string('role', 'local_solicitacoes') . ': ') . $papel_label,
            ['class' => 'mt-2 mb-0']
        );
    }

    echo render_card(
        $OUTPUT->pix_icon('b/course', '', 'moodle', ['class' => 'mr-1']) .
        html_writer::tag('strong', get_string('course', 'local_solicitacoes')),
        $course_body
    );
}

// Card: Solicitante
$solicitante     = core_user::get_user($request->userid);
$solicitante_pic = $OUTPUT->user_picture($solicitante, ['size' => 50, 'link' => false]);
echo render_card(
    $OUTPUT->pix_icon('b/user', '', 'moodle', ['class' => 'mr-1']) .
    html_writer::tag('strong', get_string('user', 'local_solicitacoes')),
    html_writer::div(
        $solicitante_pic .
        html_writer::div(
            html_writer::tag('strong', fullname($request)) .
            html_writer::tag('div', s($request->email), ['class' => 'small text-muted mt-1']),
            'ml-2'
        ),
        'd-flex align-items-center'
    )
);

// Card: Administrador responsável
if ($request->adminid) {
    $admin      = core_user::get_user($request->adminid);
    $admin_pic  = $OUTPUT->user_picture($admin, ['size' => 40, 'link' => false]);
    $admin_info = html_writer::tag('strong', fullname($admin));
    if ($request->timemodified != $request->timecreated) {
        $admin_info .= html_writer::tag('div',
            get_string('last_modified', 'local_solicitacoes') . ': ' .
            userdate($request->timemodified, get_string('strftimedatetime', 'langconfig')),
            ['class' => 'small text-muted mt-1']
        );
    }
    echo render_card(
        $OUTPUT->pix_icon('b/user', '', 'moodle', ['class' => 'mr-1']) .
        html_writer::tag('strong', get_string('handled_by', 'local_solicitacoes')),
        html_writer::div(
            $admin_pic . html_writer::div($admin_info, 'ml-2'),
            'd-flex align-items-center'
        )
    );
}

echo html_writer::end_div(); // fim col-lg-4
echo html_writer::end_div(); // fim row

// ─── Ações (apenas gestores) ──────────────────────────────────────────────────
if ($canmanage) {
    $baseurl        = new moodle_url('/local/solicitacoes/gerenciar.php');
    $action_buttons = '';

    if ($request->status !== 'aprovado') {
        $url_aprovar = new moodle_url($baseurl, [
            'action'  => 'updatestatus',
            'id'      => $request->id,
            'status'  => 'aprovado',
            'sesskey' => sesskey(),
        ]);
        $btn = new single_button($url_aprovar, get_string('approve', 'local_solicitacoes'), 'get', single_button::BUTTON_PRIMARY);
        $action_buttons .= $OUTPUT->render($btn);
    }

    if ($request->status !== 'negado') {
        $url_negar  = new moodle_url('/local/solicitacoes/negar-solicitacao.php', ['id' => $request->id]);
        $btn        = new single_button($url_negar, get_string('deny', 'local_solicitacoes'), 'get', single_button::BUTTON_SECONDARY);
        $action_buttons .= $OUTPUT->render($btn);
    }

    $url_delete = new moodle_url($baseurl, [
        'action'  => 'delete',
        'id'      => $request->id,
        'sesskey' => sesskey(),
    ]);
    $btn_delete = new single_button($url_delete, get_string('delete', 'local_solicitacoes'), 'get', single_button::BUTTON_DANGER);
    $btn_delete->add_confirm_action(get_string('confirm_delete', 'local_solicitacoes'));
    $action_buttons .= $OUTPUT->render($btn_delete);

    echo render_card(
        html_writer::tag('strong', get_string('actions', 'local_solicitacoes')),
        html_writer::div($action_buttons, 'd-flex flex-wrap gap-2'),
        'bg-light'
    );
}

// ─── Botão voltar ──────────────────────────────────────────────────────────────
$btn_back = new single_button($backurl, get_string('back_to_list', 'local_solicitacoes'), 'get');
echo $OUTPUT->render($btn_back);

echo $OUTPUT->footer();