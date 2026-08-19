<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$context = context_system::instance();

// Verificar permissão para ver próprias solicitações
if (!has_capability('local/solicitacoes:view', $context)) {
    redirect(
        new moodle_url('/'),
        get_string('error_nopermission_view', 'local_solicitacoes'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
    exit;
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/minhas-solicitacoes.php'));
$PAGE->set_title(get_string('my_requests', 'local_solicitacoes'));
$PAGE->set_heading(get_string('my_requests', 'local_solicitacoes'));

echo $OUTPUT->header();

global $DB, $USER;

// Pegar filtro de status
$filter = optional_param('filter', 'all', PARAM_ALPHA);

// Buscar papéis disponíveis para exibição
$systemcontext = context_system::instance();
$all_roles = role_get_names($systemcontext, ROLENAME_ALIAS, false);
$roles_lookup = [];
foreach ($all_roles as $roleid => $rolename) {
    $role = $DB->get_record('role', ['id' => $roleid], 'shortname');
    if ($role) {
        $roles_lookup[$role->shortname] = role_get_name($role);
    }
}

// Preparar dados para abas de filtro
$filter_tabs = [];
$filter_options = ['all' => 'Todas', 'pendente' => 'Pendentes', 'aprovado' => 'Aprovadas', 'negado' => 'Negadas'];
foreach ($filter_options as $key => $label) {
    $filter_tabs[] = [
        'label' => $label,
        'url' => (new moodle_url('/local/solicitacoes/minhas-solicitacoes.php', ['filter' => $key]))->out(false),
        'active' => ($filter === $key)
    ];
}

// Buscar solicitações do usuário atual com filtro
$params = ['userid' => $USER->id];
$where_filter = '';

if ($filter !== 'all') {
    $where_filter = ' AND s.status = :status';
    $params['status'] = $filter;
}
$sql = "SELECT s.*, 
               c.id AS cid, c.fullname AS cname,
               u.id AS uid, u.firstname AS ufirst, u.lastname AS ulast
          FROM {local_solicitacoes} s
          LEFT JOIN {local_curso_solicitacoes} cs ON cs.solicitacao_id = s.id
          LEFT JOIN {course} c ON cs.curso_id = c.id
          LEFT JOIN {local_usuarios_solicitacoes} us ON us.solicitacao_id = s.id
          LEFT JOIN {user} u ON us.usuario_id = u.id
         WHERE s.userid = :userid $where_filter
         ORDER BY s.timecreated DESC";

$recordset = $DB->get_recordset_sql($sql, $params);

$requests = [];
foreach ($recordset as $record) {
    $id = $record->id;
    if (!isset($requests[$id])) {
        $requests[$id] = $record;
        $requests[$id]->cursos = [];
        $requests[$id]->usuarios_alvo = [];
    }
    
    if (!empty($record->cid) && !isset($requests[$id]->cursos[$record->cid])) {
        $requests[$id]->cursos[$record->cid] = $record->cname;
    }
    
    if (!empty($record->uid) && !isset($requests[$id]->usuarios_alvo[$record->uid])) {
        $requests[$id]->usuarios_alvo[$record->uid] = $record->ufirst . ' ' . $record->ulast;
    }
}
$recordset->close();

// Preparar dados para o template
$template_data = [
    'filters'          => $filter_tabs,
    'has_requests'     => !empty($requests),
    'requests'         => [],
    'can_manage'       => has_capability('local/solicitacoes:manage', $context),
    'new_request_url'  => (new moodle_url('/local/solicitacoes/selecionar-acao.php'))->out(false),
    // Strings da tabela (passadas do PHP para evitar [[key]] no template)
    'str_my_requests'          => local_solicitacoes_str('my_requests'),
    'str_thankyou_new_request' => local_solicitacoes_str('thankyou_new_request'),
    'str_access_all_requests'  => local_solicitacoes_str('access_all_requests'),
    'str_action_type'          => local_solicitacoes_str('action_type'),
    'str_course'               => local_solicitacoes_str('course'),
    'str_target_users'         => local_solicitacoes_str('target_users'),
    'str_role'                 => local_solicitacoes_str('role'),
    'str_justificativa'        => local_solicitacoes_str('observacoes'),
    'str_created_at'           => local_solicitacoes_str('created_at'),
    'str_status'               => local_solicitacoes_str('status'),
    'str_actions'              => local_solicitacoes_str('actions'),
    'str_view'                 => local_solicitacoes_str('view'),
    'str_no_requests'          => local_solicitacoes_str('no_requests'),
];

if (!$requests) {
    echo $OUTPUT->render_from_template('local_solicitacoes/my_requests', $template_data);
    echo $OUTPUT->footer();
    exit;
}

foreach ($requests as $r) {
    // Processar Cursos
    if ($r->tipo_acao === 'criar_curso') {
        if (!empty($r->codigo_disciplina)) {
            $partes = array_filter([
                trim($r->codigo_disciplina),
                !empty($r->nome_disciplina) ? trim($r->nome_disciplina) : null,
                !empty($r->turma)           ? trim($r->turma)           : null,
                !empty($r->ano_semestre)    ? trim($r->ano_semestre)    : null,
            ]);
        } else {
            $partes = array_filter([
                !empty($r->codigo_sigaa)     ? trim($r->codigo_sigaa)     : null,
                !empty($r->course_shortname) ? trim($r->course_shortname) : null,
                !empty($r->ano_semestre)     ? trim($r->ano_semestre)     : null,
            ]);
        }
        $cursos_display = implode(' - ', $partes);
    } else {
        $links_cursos = [];
        $showcourselinks = !($r->tipo_acao == 'remove_course' && $r->status == 'aprovado');
        foreach ($r->cursos as $cid => $cname) {
            if ($showcourselinks) {
                $url = new moodle_url('/course/view.php', ['id' => $cid]);
                $links_cursos[] = html_writer::link($url, format_string($cname));
            } else {
                $links_cursos[] = format_string($cname);
            }
        }
        $cursos_display = implode(', ', $links_cursos);
    }

    // Processar Usuários Alvo
    // Para cadastro, mostrar dados do novo usuário ao invés de usuários existentes
    if ($r->tipo_acao == 'cadastro') {
        $usuarios_display = get_string('novo_usuario', 'local_solicitacoes') . ': ' .
                           format_string($r->firstname . ' ' . $r->lastname) . ' (' . $r->cpf . ')';
    } else {
        $links_usuarios = [];
        foreach ($r->usuarios_alvo as $uid => $uname) {
            $url = new moodle_url('/user/profile.php', ['id' => $uid]);
            $links_usuarios[] = html_writer::link($url, fullname((object)['firstname'=>$uname, 'lastname'=>'']));
        }
        
        if (count($links_usuarios) > 3) {
            $usuarios_display = implode(", ", array_slice($links_usuarios, 0, 3)) . "... (+" . (count($links_usuarios) - 3) . ")";
        } else {
            $usuarios_display = implode(", ", $links_usuarios);
        }
    }

    // Status formatado com classes Bootstrap
    $statuskey = 'status_' . $r->status;
    $statuslabel = get_string($statuskey, 'local_solicitacoes');
    
    // Definir classe de badge baseada no status
    $status_badge_classes = [
        'pendente' => 'badge-warning',
        'aprovado' => 'badge-success',
        'negado' => 'badge-danger',
        'em_andamento' => 'badge-info',
        'concluido' => 'badge-success'
    ];
    $status_class = 'badge ' . (isset($status_badge_classes[$r->status]) ? $status_badge_classes[$r->status] : 'badge-secondary');
    
    // Traduzir tipo de ação
    $acao_strings = [
        'inscricao' => get_string('acao_inscricao', 'local_solicitacoes'),
        'remocao' => get_string('acao_remocao', 'local_solicitacoes'),
        'suspensao' => get_string('acao_suspensao', 'local_solicitacoes'),
        'cadastro' => get_string('acao_cadastro', 'local_solicitacoes'),
        'criar_curso' => get_string('acao_criar_curso', 'local_solicitacoes'),
        'remove_course' => get_string('acao_remover_curso', 'local_solicitacoes')
    ];
    $acao_label = isset($acao_strings[$r->tipo_acao]) ? $acao_strings[$r->tipo_acao] : $r->tipo_acao;
    
    // Papel (apenas para inscrições e cadastro)
    $papel_display = '';
    if (($r->tipo_acao == 'inscricao' || $r->tipo_acao == 'cadastro') && !empty($r->papel)) {
        $papel_display = isset($roles_lookup[$r->papel]) ? $roles_lookup[$r->papel] : $r->papel;
    }

    // Preparar dados para o template
    // Justificativa truncada
    if ($r->tipo_acao === 'criar_curso') {
        $just_raw = trim($r->razoes_criacao ?? '');
    } else {
        $obs = trim($r->observacoes ?? '');
        if (str_contains($obs, 'OBSERVAÇÕES ADICIONAIS:')) {
            $obs = trim(explode('OBSERVAÇÕES ADICIONAIS:', $obs)[1]);
        } elseif (str_contains($obs, 'CURSOS SELECIONADOS:')) {
            $obs = '';
        }
        $just_raw = $obs;
    }
    $just_max = 120;
    if (mb_strlen($just_raw) > $just_max) {
        $justificativa_display = html_writer::tag('span', s(mb_substr($just_raw, 0, $just_max) . '…'), [
            'title' => s($just_raw),
            'style' => 'cursor:help; white-space:normal;',
        ]);
    } else {
        $justificativa_display = s($just_raw);
    }

    $request_data = [
        'request_id' => $r->id,
        'acao_label' => $acao_label,
        'cursos_display' => $cursos_display,
        'usuarios_display' => $usuarios_display,
        'papel_display'       => $papel_display,
        'justificativa_display' => $justificativa_display,
        'created_date' => userdate($r->timecreated),
        'status_label' => $statuslabel,
        'status_badge_class' => $status_class, // Badge com estilo colorido
        'view_url' => (new moodle_url('/local/solicitacoes/detalhes.php', ['id' => $r->id]))->out(false),
    ];
    
    $template_data['requests'][] = $request_data;
}

// Renderizar o template
echo $OUTPUT->render_from_template('local_solicitacoes/my_requests', $template_data);

echo $OUTPUT->footer();
