<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$context = context_system::instance();

// Verificar permissão para gerenciar solicitações
if (!has_capability('local/solicitacoes:manage', $context)) {
    redirect(
        new moodle_url('/'),
        get_string('error_nopermission_manage', 'local_solicitacoes'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
    exit;
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/gerenciar.php'));
$PAGE->set_title(get_string('list_title', 'local_solicitacoes'));
$PAGE->set_heading(get_string('list_title', 'local_solicitacoes'));

echo $OUTPUT->header();

global $DB, $OUTPUT;

// Buscar papéis disponíveis para exibição
$systemcontext = context_system::instance();
$roles_lookup = [];
foreach (get_all_roles() as $role) {
    $roles_lookup[$role->shortname] = role_get_name($role);
}

// Tratar ações via GET/POST
$action = optional_param('action', '', PARAM_ALPHA);
$id     = optional_param('id', 0, PARAM_INT);
$status = optional_param('status', '', PARAM_ALPHA);
$filter = optional_param('filter', 'pendente', PARAM_ALPHA);
$page   = max(0, optional_param('page', 0, PARAM_INT));
$motivo_negacao = optional_param('motivo_negacao', '', PARAM_TEXT);

// Ação: Atualizar status
if ($action === 'updatestatus' && $id && in_array($status, ['pendente','aprovado','negado'])) {
    require_sesskey();
    if ($request = $DB->get_record('local_solicitacoes', ['id' => $id], '*', MUST_EXIST)) {
        // Se for aprovação, executar a ação solicitada
        if ($status === 'aprovado') {
            $resultado = \local_solicitacoes\solicitacoes_controller::execute_request_action($id);
            if (!$resultado['success']) {
                redirect(new moodle_url('/local/solicitacoes/gerenciar.php', ['filter' => $filter, 'page' => $page]), 
                         $resultado['message'], null, \core\output\notification::NOTIFY_ERROR);
            }
        }
        
        // Se for negação, redirecionar para página de negação se não houver motivo
        if ($status === 'negado') {
            if (empty($motivo_negacao)) {
                // Redirecionar para página de negação
                redirect(new moodle_url('/local/solicitacoes/negar-solicitacao.php', ['id' => $id]));
                exit; // Parar execução após redirect
            }
            $request->motivo_negacao = $motivo_negacao;
        }
        
        $request->status       = $status;
        $request->timemodified = time();
        $request->adminid      = $USER->id;
        $DB->update_record('local_solicitacoes', $request);
        
        // Enviar notificação apropriada
        if ($status === 'aprovado') {
            local_solicitacoes_notify_aprovada($id);
        }
        
        redirect(new moodle_url('/local/solicitacoes/gerenciar.php', ['filter' => $filter, 'page' => $page]),
                 get_string('success_update', 'local_solicitacoes'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Ação: Excluir solicitação
if ($action === 'delete' && $id) {
    require_sesskey();
    if ($DB->record_exists('local_solicitacoes', ['id' => $id])) {
        $DB->delete_records('local_solicitacoes', ['id' => $id]);
        redirect(new moodle_url('/local/solicitacoes/gerenciar.php', ['filter' => $filter, 'page' => $page]),
                 get_string('success_delete', 'local_solicitacoes'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Ação: Aprovação em massa
if ($action === 'bulk_approve') {
    require_sesskey();
    $selected_ids = optional_param_array('selected_ids', [], PARAM_INT);
    $selected_ids = array_values(array_filter(array_map('intval', $selected_ids)));

    if (empty($selected_ids)) {
        redirect(
            new moodle_url('/local/solicitacoes/gerenciar.php', ['filter' => $filter, 'page' => $page]),
            'Nenhuma solicitação selecionada.',
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    $success_count = 0;
    $error_count   = 0;
    $error_msgs    = [];

    foreach ($selected_ids as $req_id) {
        $req = $DB->get_record('local_solicitacoes', ['id' => $req_id], '*');
        if (!$req || $req->status !== 'pendente') {
            continue; // ignora registros não-pendentes ou inexistentes
        }

        $resultado = \local_solicitacoes\solicitacoes_controller::execute_request_action($req_id);
        if (!$resultado['success']) {
            $error_count++;
            $error_msgs[] = '#' . $req_id . ': ' . $resultado['message'];
            continue;
        }

        $req->status       = 'aprovado';
        $req->timemodified = time();
        $req->adminid      = $USER->id;
        $DB->update_record('local_solicitacoes', $req);
        local_solicitacoes_notify_aprovada($req_id);
        $success_count++;
    }

    if ($error_count === 0 && $success_count > 0) {
        $msg_type = \core\output\notification::NOTIFY_SUCCESS;
        $msg      = $success_count . ' solicitação(ões) aprovada(s) com sucesso.';
    } elseif ($success_count === 0) {
        $msg_type = \core\output\notification::NOTIFY_ERROR;
        $msg      = 'Erro ao aprovar: ' . implode('; ', $error_msgs);
    } else {
        $msg_type = \core\output\notification::NOTIFY_WARNING;
        $msg      = $success_count . ' aprovada(s), ' . $error_count . ' com erro(s): ' . implode('; ', $error_msgs);
    }

    redirect(
        new moodle_url('/local/solicitacoes/gerenciar.php', ['filter' => $filter, 'page' => $page]),
        $msg,
        null,
        $msg_type
    );
}

// Preparar dados para abas de filtro
$filter_tabs = [];
$filter_options = ['pendente' => 'Pendentes', 'aprovado' => 'Aprovadas', 'negado' => 'Negadas', 'all' => 'Todas'];
foreach ($filter_options as $key => $label) {
    $filter_tabs[] = [
        'label' => $label,
        'url' => (new moodle_url('/local/solicitacoes/gerenciar.php', ['filter' => $key]))->out(false),
        'active' => ($filter === $key)
    ];
}

// Buscar solicitações com filtro, paginadas.
$perpage = 50;

$params = [];
$where  = '1=1';
if ($filter !== 'all') {
    $where = 'status = :status';
    $params['status'] = $filter;
}

$total_requests = $DB->count_records_select('local_solicitacoes', $where, $params);

// Página além do fim (ex.: após excluir o último item da última página): volta para a última existente.
$last_page = max(0, (int)ceil($total_requests / $perpage) - 1);
if ($page > $last_page) {
    $page = $last_page;
}

// 1º passo: somente os IDs da página atual (consulta leve, com LIMIT).
// O LIMIT não pode ir na consulta com JOIN abaixo, que gera uma linha por curso × usuário.
$page_ids = array_keys($DB->get_records_select(
    'local_solicitacoes', $where, $params, 'timecreated DESC, id DESC', 'id', $page * $perpage, $perpage
));

// 2º passo: dados completos apenas das solicitações da página.
$requests = [];
$recordset = null;
if (!empty($page_ids)) {
    [$ids_sql, $ids_params] = $DB->get_in_or_equal($page_ids, SQL_PARAMS_NAMED, 'pgid');
    $sql = "SELECT s.*,
                   c.id AS cid, c.fullname AS cname,
                   u.id AS uid, u.firstname AS ufirst, u.lastname AS ulast
              FROM {local_solicitacoes} s
              LEFT JOIN {local_curso_solicitacoes} cs ON cs.solicitacao_id = s.id
              LEFT JOIN {course} c ON cs.curso_id = c.id
              LEFT JOIN {local_usuarios_solicitacoes} us ON us.solicitacao_id = s.id
              LEFT JOIN {user} u ON us.usuario_id = u.id
             WHERE s.id $ids_sql";
    $recordset = $DB->get_recordset_sql($sql, $ids_params);
}

foreach (($recordset ?? []) as $record) {
    $id = $record->id;
    if (!isset($requests[$id])) {
        // Inicializa o objeto da solicitação
        $requests[$id] = $record;
        $requests[$id]->cursos = [];
        $requests[$id]->usuarios_alvo = [];
    }
    
    // Adiciona o curso ao dicionário (evitando duplicados)
    if (!empty($record->cid) && !isset($requests[$id]->cursos[$record->cid])) {
        $requests[$id]->cursos[$record->cid] = $record->cname;
    }
    
    // Adiciona o usuário ao dicionário (evitando duplicados)
    if (!empty($record->uid) && !isset($requests[$id]->usuarios_alvo[$record->uid])) {
        $requests[$id]->usuarios_alvo[$record->uid] = $record->ufirst . ' ' . $record->ulast;
    }
}
if ($recordset) {
    $recordset->close(); // IMPORTANTE: fechar o recordset
}

// Ordena por data de criação (mais recentes primeiro), mesma ordem da paginação.
usort($requests, function($a, $b) {
    return ($b->timecreated <=> $a->timecreated) ?: ($b->id <=> $a->id);
});

// URL base da listagem (preserva filtro e página nas ações e redirecionamentos).
$list_url = new moodle_url('/local/solicitacoes/gerenciar.php', ['filter' => $filter, 'page' => $page]);
$paging_bar = $OUTPUT->paging_bar($total_requests, $page, $perpage,
    new moodle_url('/local/solicitacoes/gerenciar.php', ['filter' => $filter]));
$paging_summary = '';
if ($total_requests > 0) {
    $paging_summary = get_string('showing_requests', 'local_solicitacoes', (object)[
        'from'  => $page * $perpage + 1,
        'to'    => min($total_requests, ($page + 1) * $perpage),
        'total' => $total_requests,
    ]);
}

// A verificação de disciplinas similares (criar_curso) é carregada via AJAX
// depois que a página abre: local_solicitacoes/similar_courses.
$has_similarity_check = false;

// Solicitantes: uma única consulta em vez de uma por linha.
$requester_ids = array_unique(array_map(function($r) {
    return (int)$r->userid;
}, $requests));
$requesters = [];
if (!empty($requester_ids)) {
    $requester_fields = 'id, ' . implode(', ', \core_user\fields::get_name_fields());
    $requesters = $DB->get_records_list('user', 'id', $requester_ids, '', $requester_fields);
}

// Link "Voltar" para a coordenação de cursos
$back_url = (new moodle_url('/course/view.php', ['id' => 1776]))->out(false);

// Preparar dados para o template
$template_data = [
    'filters'          => $filter_tabs,
    'has_requests'     => !empty($requests),
    'observacao_header' => local_solicitacoes_str('observacoes'),
    'form_action_url'  => (new moodle_url('/local/solicitacoes/gerenciar.php'))->out(false),
    'sesskey'          => sesskey(),
    'current_filter'   => $filter,
    'current_page'     => $page,
    'paging_bar'       => $paging_bar,
    'paging_summary'   => $paging_summary,
    'back_url'         => $back_url,
    // Strings da tabela (passadas do PHP para evitar [[key]] no template)
    'str_back'             => local_solicitacoes_str('back'),
    'str_approve_selected' => local_solicitacoes_str('approve_selected'),
    'str_bulk_hint'        => local_solicitacoes_str('bulk_hint'),
    'str_select_all'       => local_solicitacoes_str('select_all'),
    'str_user'             => local_solicitacoes_str('user'),
    'str_action_type'      => local_solicitacoes_str('action_type'),
    'str_course'           => local_solicitacoes_str('course'),
    'str_target_users'     => local_solicitacoes_str('target_users'),
    'str_role'             => local_solicitacoes_str('role'),
    'str_created_at'       => local_solicitacoes_str('created_at'),
    'str_status'           => local_solicitacoes_str('status'),
    'str_actions'          => local_solicitacoes_str('actions'),
    'str_approve'          => local_solicitacoes_str('approve'),
    'str_deny'             => local_solicitacoes_str('deny'),
    'str_view'             => local_solicitacoes_str('view'),
    'str_delete'           => local_solicitacoes_str('delete'),
    'str_no_requests'      => local_solicitacoes_str('no_requests'),
    'str_confirm_delete'   => local_solicitacoes_str('confirm_delete'),
    'requests'             => []
];


if (!$requests) {
    // Se não houver solicitações, renderizar template com has_requests = false
    echo $OUTPUT->render_from_template('local_solicitacoes/manage_requests', $template_data);
    echo $OUTPUT->footer();
    exit;
}

foreach ($requests as $r) {
    // 1. Processar Cursos (Transformar dicionário em links)
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
        foreach ($r->cursos as $cid => $cname) {
            $url = new moodle_url('/course/view.php', ['id' => $cid]);
            $links_cursos[] = html_writer::link($url, format_string($cname));
        }
        $cursos_display = implode(', ', $links_cursos);
    }

    // 2. Processar Usuários Alvo (Transformar dicionário em links)
    // Para cadastro, mostrar dados do novo usuário ao invés de usuários existentes
    if ($r->tipo_acao == 'cadastro') {
        $usuarios_display = get_string('novo_usuario', 'local_solicitacoes') . ': ' .
                           format_string($r->firstname . ' ' . $r->lastname) . ' (' . $r->cpf . ')';
    } else {
        $links_usuarios = [];
        foreach ($r->usuarios_alvo as $uid => $uname) {
            $url = new moodle_url('/user/profile.php', ['id' => $uid]);
            $links_usuarios[] = html_writer::link($url, fullname((object)['firstname'=>$uname, 'lastname'=>''])); 
            // Nota: se preferir usar a função fullname do Moodle, passe o objeto do usuário completo.
        }
        
        // Lógica de "ver mais" para usuários se houver muitos
        if (count($links_usuarios) > 3) {
            $usuarios_display = implode(", ", array_slice($links_usuarios, 0, 3)) . "... (+" . (count($links_usuarios) - 3) . ")";
        } else {
            $usuarios_display = implode(", ", $links_usuarios);
        }
    }

    // 3. Dados do Solicitante
    $solicitante = $requesters[$r->userid] ?? null;
    $solicitante_url = new moodle_url('/user/profile.php', ['id' => $r->userid]);
    $solicitante_nome = $solicitante ? html_writer::link($solicitante_url, fullname($solicitante)) : '-';

    // Status formatado com cores
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
    $status_badge_class = isset($status_badge_classes[$r->status]) ? $status_badge_classes[$r->status] : 'badge-secondary';

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

    // Coluna Justificativa: mostra o texto inserido pelo solicitante (truncado)
    $observacao_display = '';

    // Extrair texto limpo conforme tipo de ação
    if ($r->tipo_acao === 'criar_curso') {
        $just_raw = trim($r->razoes_criacao ?? '');
    } else {
        $obs = trim($r->observacoes ?? '');
        // Inscrições acumulam informações técnicas — extrai só a parte do usuário
        if (str_contains($obs, 'OBSERVAÇÕES ADICIONAIS:')) {
            $obs = trim(explode('OBSERVAÇÕES ADICIONAIS:', $obs)[1]);
        } elseif (str_contains($obs, 'CURSOS SELECIONADOS:')) {
            $obs = '';
        }
        $just_raw = $obs;
    }

    // Renderizar: se longo, trunca em 120 chars e exibe texto completo no title (tooltip)
    if ($just_raw !== '') {
        $max = 120;
        if (mb_strlen($just_raw) > $max) {
            $short = mb_substr($just_raw, 0, $max) . '…';
            $observacao_display = html_writer::tag('span', s($short), [
                'title' => s($just_raw),
                'style' => 'cursor:help; white-space:normal;',
            ]);
        } else {
            $observacao_display = s($just_raw);
        }
    }

    // Para criar_curso: adiciona aviso de disciplinas similares abaixo da justificativa
    // (placeholder preenchido de forma assíncrona; some se não houver similares)
    if ($r->tipo_acao === 'criar_curso' && trim($r->nome_disciplina ?? $r->course_shortname ?? '') !== '') {
        $has_similarity_check = true;
        $sim_html = html_writer::div(
            html_writer::tag('small', s(local_solicitacoes_str('checking_similar_courses')), ['class' => 'text-muted']),
            'local-solicitacoes-similar',
            ['data-requestid' => $r->id]
        );
        $observacao_display = ($observacao_display ? $observacao_display . '<br>' : '') . $sim_html;
    }

    // Preparar dados para o template
    $baseurl = $list_url;

    $request_data = [
        'request_id'   => $r->id,
        'show_checkbox' => ($r->status === 'pendente'),
        'solicitante_link' => $solicitante_nome,
        'acao_label' => $acao_label,
        'cursos_display' => $cursos_display,
        'usuarios_display' => $usuarios_display,
        'papel_display' => $papel_display,
        'observacao_display' => $observacao_display,
        'created_date' => userdate($r->timecreated),
        'status_label' => $statuslabel,
        'status_badge_class' => $status_badge_class,
        'show_approve' => ($r->status === 'pendente'),
        'show_reject' => ($r->status === 'pendente'),
        'approve_url' => (new moodle_url($baseurl, [
            'action' => 'updatestatus',
            'id' => $r->id,
            'status' => 'aprovado',
            'sesskey' => sesskey(),
        ]))->out(false),
        'reject_url' => (new moodle_url('/local/solicitacoes/negar-solicitacao.php', [
            'id' => $r->id,
        ]))->out(false),
        'view_url' => (new moodle_url('/local/solicitacoes/detalhes.php', ['id' => $r->id]))->out(false),
        'delete_url' => (new moodle_url($baseurl, [
            'action' => 'delete',
            'id' => $r->id,
            'sesskey' => sesskey(),
        ]))->out(false),
        'confirm_delete_msg' => get_string('confirm_delete', 'local_solicitacoes'),
    ];
    
    $template_data['requests'][] = $request_data;
}

if ($has_similarity_check) {
    $PAGE->requires->js_call_amd('local_solicitacoes/similar_courses', 'init');
}

// Renderizar o template com todos os dados
echo $OUTPUT->render_from_template('local_solicitacoes/manage_requests', $template_data);

echo $OUTPUT->footer();