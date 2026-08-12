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

$sql = "SELECT r.*, u.firstname AS requester_firstname, u.lastname AS requester_lastname, u.email AS requester_email
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
    'remove_course' => get_string('acao_remover_curso', 'local_solicitacoes'),
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
 * Renderiza uma linha de detalhe: label em bold + valor simples.
 */
function detail_row(string $label, string $value): string {
    return html_writer::tag('p',
        html_writer::tag('strong', $label . ': ') . $value,
        ['class' => 'mb-2']
    );
}

/**
 * Renderiza um card com header em texto simples e corpo.
 */
function render_card(string $header_html, string $body_html, string $header_extra_class = ''): string {
    $hclass = trim('card-header ' . $header_extra_class);
    $out  = html_writer::start_div('card mb-3');
    $out .= html_writer::div($header_html, $hclass);
    $out .= html_writer::div($body_html, 'card-body');
    $out .= html_writer::end_div();
    return $out;
}

// ─── Dados auxiliares ─────────────────────────────────────────────────────────
$solicitante = core_user::get_user($request->userid);

$categoria_nome = '';
if (!empty($request->unidade_academica_id)) {
    $cat = $DB->get_record('course_categories', ['id' => $request->unidade_academica_id]);
    if ($cat) {
        $categoria_nome = format_string($cat->name);
    }
}

$admin = (!empty($request->adminid)) ? core_user::get_user($request->adminid) : null;

// ─── Saída ────────────────────────────────────────────────────────────────────
echo $OUTPUT->header();

// Botão voltar
$btn_back = new single_button($backurl, get_string('back_to_list', 'local_solicitacoes'), 'get');
echo html_writer::div($OUTPUT->render($btn_back), 'mb-3');

// ── Card único ────────────────────────────────────────────────────────────────
echo html_writer::start_div('card shadow-sm');

// Cabeçalho do card: tipo + status
$status_colors = [
    'pendente'     => '#f0ad4e',
    'aprovado'     => '#5cb85c',
    'negado'       => '#d9534f',
    'em_andamento' => '#5bc0de',
    'concluido'    => '#337ab7',
];
$header_color = $status_colors[$request->status] ?? '#6c757d';

echo html_writer::start_div('card-header d-flex align-items-center justify-content-between py-3', [
    'style' => 'background:' . $header_color . '; color:#fff; border-bottom:none;'
]);
echo html_writer::tag('h4',
    s($acao_label) . '&nbsp;&nbsp;' . html_writer::tag('code',
        '#' . $request->id,
        ['style' => 'color:rgba(255,255,255,.8); font-size:.8rem; font-weight:normal; vertical-align:middle;']
    ),
    ['class' => 'mb-0', 'style' => 'color:#fff;']
);
echo html_writer::tag('span', s($badge_label), [
    'class' => 'badge badge-pill',
    'style' => 'background:rgba(0,0,0,.25); font-size:.9rem; color:#fff;'
]);
echo html_writer::end_div(); // card-header

echo html_writer::start_div('card-body');

// ── Seção: Dados da solicitação ───────────────────────────────────────────────
echo html_writer::tag('h6', 'Dados da Solicitação', [
    'class' => 'text-uppercase text-muted mb-3',
    'style' => 'letter-spacing:.05em; font-size:.75rem;'
]);

echo html_writer::start_tag('dl', ['class' => 'row mb-0']);

function dl_row(string $label, string $value): string {
    $dt = html_writer::tag('dt', $label, ['class' => 'col-sm-4 font-weight-semibold text-muted']);
    $dd = html_writer::tag('dd', $value,  ['class' => 'col-sm-8 mb-2']);
    return $dt . $dd;
}

// Protocolo (ID) sempre como primeira linha
echo dl_row(
    'Protocolo',
    html_writer::tag('code', '#' . $request->id,
        ['style' => 'font-size:1em; letter-spacing:.04em;'])
);

if ($request->tipo_acao === 'criar_curso') {
    if (!empty($request->codigo_disciplina)) {
        // Registro novo: exibe campos separados
        echo dl_row(
            get_string('codigo_disciplina', 'local_solicitacoes'),
            html_writer::tag('strong',
                html_writer::tag('code', s($request->codigo_disciplina),
                    ['style' => 'font-size:1.05em; letter-spacing:.05em;'])
            )
        );
        echo dl_row(
            get_string('nome_disciplina', 'local_solicitacoes'),
            s($request->nome_disciplina)
        );
        if (!empty($request->turma)) {
            echo dl_row(get_string('turma', 'local_solicitacoes'), s($request->turma));
        }
    } else {
        // Registro antigo (legado): exibe campos originais
        echo dl_row(
            get_string('codigo_sigaa', 'local_solicitacoes'),
            html_writer::tag('strong', s($request->codigo_sigaa))
        );
        if (!empty($request->course_shortname)) {
            echo dl_row(
                get_string('course_shortname', 'local_solicitacoes'),
                s($request->course_shortname)
            );
        }
    }
    if ($categoria_nome !== '') {
        echo dl_row(get_string('unidade_academica', 'local_solicitacoes'), s($categoria_nome));
    }
    echo dl_row(get_string('ano_semestre', 'local_solicitacoes'), s($request->ano_semestre));

    if (!empty($request->course_summary)) {
        echo dl_row(get_string('course_summary', 'local_solicitacoes'),
            html_writer::tag('div', nl2br(s($request->course_summary)), ['style' => 'white-space:pre-wrap'])
        );
    }
    if (!empty($request->razoes_criacao)) {
        echo dl_row(get_string('razoes_criacao', 'local_solicitacoes'),
            html_writer::tag('div', nl2br(s($request->razoes_criacao)), ['style' => 'white-space:pre-wrap'])
        );
    }

} else {
    echo dl_row(get_string('action_type', 'local_solicitacoes'), s($acao_label));

    // Cursos afetados
    if (!empty($cursos)) {
        $nomes = [];
        foreach ($cursos as $c) {
            $url    = new moodle_url('/course/view.php', ['id' => $c->id]);
            $nomes[] = html_writer::link($url, format_string($c->fullname)) .
                       html_writer::tag('small', ' (' . s($c->shortname) . ')', ['class' => 'text-muted']);
        }
        echo dl_row(get_string('course', 'local_solicitacoes'), implode('<br>', $nomes));
    }

    // Papel
    if (in_array($request->tipo_acao, ['inscricao', 'cadastro']) && !empty($request->papel)) {
        echo dl_row(get_string('role', 'local_solicitacoes'),
            $roles_lookup[$request->papel] ?? s($request->papel)
        );
    }

    // Usuários afetados
    if ($request->tipo_acao === 'cadastro') {
        $user_info = html_writer::tag('strong', s($request->firstname . ' ' . $request->lastname)) . '<br>' .
                     html_writer::tag('small',
                         get_string('cpf', 'local_solicitacoes') . ': ' . s($request->cpf) . '&nbsp;&nbsp;|&nbsp;&nbsp;' .
                         get_string('email_novo_usuario', 'local_solicitacoes') . ': ' . s($request->email),
                         ['class' => 'text-muted']
                     );
        echo dl_row(get_string('novo_usuario', 'local_solicitacoes'), $user_info);

    } elseif (!empty($usuarios)) {
        $user_lines = [];
        foreach ($usuarios as $u) {
            $url = new moodle_url('/user/profile.php', ['id' => $u->id]);
            $user_lines[] = html_writer::link($url, fullname($u)) .
                            html_writer::tag('small', ' — ' . s($u->email), ['class' => 'text-muted']);
        }
        echo dl_row(get_string('target_users', 'local_solicitacoes'), implode('<br>', $user_lines));
    }
}

// Observações gerais
if (!empty($request->observacoes)) {
    $obs_text = $request->observacoes;
    // Se tiver as linhas técnicas de debug, mostrar só as observações adicionais
    if (str_contains($obs_text, 'OBSERVAÇÕES ADICIONAIS:')) {
        $obs_text = trim(explode('OBSERVAÇÕES ADICIONAIS:', $obs_text)[1]);
    } elseif (str_contains($obs_text, 'CURSOS SELECIONADOS:')) {
        $obs_text = ''; // só informação técnica, não exibe
    }
    if ($obs_text !== '') {
        echo dl_row(get_string('observacoes', 'local_solicitacoes'),
            html_writer::tag('div', nl2br(s($obs_text)), ['class' => 'text-muted', 'style' => 'white-space:pre-wrap'])
        );
    }
}

echo html_writer::end_tag('dl');

// ── Divisor ──────────────────────────────────────────────────────────────────
echo html_writer::tag('hr', '', ['class' => 'my-3']);

// ── Seção: Histórico ─────────────────────────────────────────────────────────
echo html_writer::tag('h6', 'Histórico', [
    'class' => 'text-uppercase text-muted mb-3',
    'style' => 'letter-spacing:.05em; font-size:.75rem;'
]);

echo html_writer::start_tag('dl', ['class' => 'row mb-0']);

// Solicitante
$solicitante_pic = $OUTPUT->user_picture($solicitante, ['size' => 24, 'link' => false, 'class' => 'mr-1']);
$solicitante_url = new moodle_url('/user/profile.php', ['id' => $solicitante->id]);
echo dl_row(
    get_string('user', 'local_solicitacoes'),
    $solicitante_pic .
    html_writer::link($solicitante_url, fullname($solicitante)) .
    html_writer::tag('small', '&nbsp;—&nbsp;' . s($solicitante->email), ['class' => 'text-muted'])
);

// Criado em
echo dl_row(
    get_string('created_at', 'local_solicitacoes'),
    userdate($request->timecreated, get_string('strftimedatetime', 'langconfig'))
);

// Aprovado/negado por
if ($admin) {
    $action_label = ($request->status === 'aprovado')
        ? get_string('approve', 'local_solicitacoes')
        : ($request->status === 'negado' ? get_string('deny', 'local_solicitacoes') : get_string('handled_by', 'local_solicitacoes'));

    $admin_pic = $OUTPUT->user_picture($admin, ['size' => 24, 'link' => false, 'class' => 'mr-1']);
    $admin_url = new moodle_url('/user/profile.php', ['id' => $admin->id]);
    $admin_val = $admin_pic .
                 html_writer::link($admin_url, fullname($admin));
    if ($request->timemodified && $request->timemodified != $request->timecreated) {
        $admin_val .= html_writer::tag('small',
            '&nbsp;—&nbsp;' . userdate($request->timemodified, get_string('strftimedatetime', 'langconfig')),
            ['class' => 'text-muted']
        );
    }
    echo dl_row($action_label . ' por', $admin_val);
}

// Motivo da negação
if ($request->status === 'negado' && !empty($request->motivo_negacao)) {
    echo dl_row(
        get_string('motivo_negacao', 'local_solicitacoes'),
        html_writer::tag('div',
            nl2br(s($request->motivo_negacao)),
            ['class' => 'text-danger', 'style' => 'white-space:pre-wrap']
        )
    );
}

echo html_writer::end_tag('dl');

// ── Seção: Ações (gestores) ───────────────────────────────────────────────────
if ($canmanage) {
    echo html_writer::tag('hr', '', ['class' => 'my-3']);
    echo html_writer::tag('h6', 'Ações', [
        'class' => 'text-uppercase text-muted mb-3',
        'style' => 'letter-spacing:.05em; font-size:.75rem;'
    ]);

    $baseurl = new moodle_url('/local/solicitacoes/gerenciar.php');

    echo html_writer::start_div('d-flex flex-wrap', ['style' => 'gap:.5rem;']);

    if ($request->status !== 'aprovado') {
        $url_aprovar = new moodle_url($baseurl, [
            'action'  => 'updatestatus',
            'id'      => $request->id,
            'status'  => 'aprovado',
            'sesskey' => sesskey(),
        ]);
        $btn = new single_button($url_aprovar, get_string('approve', 'local_solicitacoes'), 'get', single_button::BUTTON_PRIMARY);
        echo $OUTPUT->render($btn);
    }

    if ($request->status !== 'negado') {
        $url_negar = new moodle_url('/local/solicitacoes/negar-solicitacao.php', ['id' => $request->id]);
        $btn       = new single_button($url_negar, get_string('deny', 'local_solicitacoes'), 'get', single_button::BUTTON_SECONDARY);
        echo $OUTPUT->render($btn);
    }

    $url_delete = new moodle_url($baseurl, [
        'action'  => 'delete',
        'id'      => $request->id,
        'sesskey' => sesskey(),
    ]);
    $btn_delete = new single_button($url_delete, get_string('delete', 'local_solicitacoes'), 'get', single_button::BUTTON_DANGER);
    $btn_delete->add_confirm_action(get_string('confirm_delete', 'local_solicitacoes'));
    echo $OUTPUT->render($btn_delete);

    echo html_writer::end_div();
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

echo $OUTPUT->footer();