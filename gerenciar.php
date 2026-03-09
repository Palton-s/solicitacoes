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
$all_roles = role_get_names($systemcontext, ROLENAME_ALIAS, false);
$roles_lookup = [];
foreach ($all_roles as $roleid => $rolename) {
    $role = $DB->get_record('role', ['id' => $roleid], 'shortname');
    if ($role) {
        $roles_lookup[$role->shortname] = role_get_name($role);
    }
}

// Tratar ações via GET/POST
$action = optional_param('action', '', PARAM_ALPHA);
$id     = optional_param('id', 0, PARAM_INT);
$status = optional_param('status', '', PARAM_ALPHA);
$filter = optional_param('filter', 'pendente', PARAM_ALPHA);
$motivo_negacao = optional_param('motivo_negacao', '', PARAM_TEXT);

// Ação: Atualizar status
if ($action === 'updatestatus' && $id && in_array($status, ['pendente','aprovado','negado'])) {
    require_sesskey();
    if ($request = $DB->get_record('local_solicitacoes', ['id' => $id], '*', MUST_EXIST)) {
        // Se for aprovação, executar a ação solicitada
        if ($status === 'aprovado') {
            $resultado = \local_solicitacoes\solicitacoes_controller::execute_request_action($id);
            if (!$resultado['success']) {
                redirect(new moodle_url('/local/solicitacoes/gerenciar.php', ['filter' => $filter]), 
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
        
        redirect(new moodle_url('/local/solicitacoes/gerenciar.php', ['filter' => $filter]),
                 get_string('success_update', 'local_solicitacoes'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Ação: Excluir solicitação
if ($action === 'delete' && $id) {
    require_sesskey();
    if ($DB->record_exists('local_solicitacoes', ['id' => $id])) {
        $DB->delete_records('local_solicitacoes', ['id' => $id]);
        redirect(new moodle_url('/local/solicitacoes/gerenciar.php', ['filter' => $filter]),
                 get_string('success_delete', 'local_solicitacoes'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
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

// Buscar solicitações com filtro
$params = [];
$where_clause = '';

if ($filter !== 'all') {
    $where_clause = ' AND s.status = :status';  // Mudou de WHERE para AND
    $params['status'] = $filter;
}
#$requests = $DB->get_records('local_solicitacoes', $where, 'timecreated DESC');
$sql = "SELECT s.*, 
               c.id AS cid, c.fullname AS cname,
               u.id AS uid, u.firstname AS ufirst, u.lastname AS ulast
          FROM {local_solicitacoes} s
          LEFT JOIN {local_curso_solicitacoes} cs ON cs.solicitacao_id = s.id
          LEFT JOIN {course} c ON cs.curso_id = c.id
          LEFT JOIN {local_usuarios_solicitacoes} us ON us.solicitacao_id = s.id
          LEFT JOIN {user} u ON us.usuario_id = u.id
         WHERE 1=1 $where_clause";



$recordset = $DB->get_recordset_sql($sql, $params);

$requests = [];
$counter = 0; // Criar índice único manualmente
foreach ($recordset as $record) {
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
    
    $counter++;
}
$recordset->close(); // IMPORTANTE: fechar o recordset

// Ordena por data de criação (mais recentes primeiro)
usort($requests, function($a, $b) {
    return $b->timecreated - $a->timecreated;
});

// Preparar dados para o template
$template_data = [
    'filters' => $filter_tabs,
    'has_requests' => !empty($requests),
    'requests' => []
];


if (!$requests) {
    // Se não houver solicitações, renderizar template com has_requests = false
    echo $OUTPUT->render_from_template('local_solicitacoes/manage_requests', $template_data);
    echo $OUTPUT->footer();
    exit;
}

foreach ($requests as $r) {
    // 1. Processar Cursos (Transformar dicionário em links)
    $links_cursos = [];
    foreach ($r->cursos as $cid => $cname) {
        $url = new moodle_url('/course/view.php', ['id' => $cid]);
        $links_cursos[] = html_writer::link($url, format_string($cname));
    }
    $cursos_display = implode(', ', $links_cursos);

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
    $solicitante = core_user::get_user($r->userid);
    $solicitante_url = new moodle_url('/user/profile.php', ['id' => $r->userid]);
    $solicitante_nome = html_writer::link($solicitante_url, fullname($solicitante));

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

    // Preparar dados para o template
    $baseurl = new moodle_url('/local/solicitacoes/gerenciar.php', ['filter' => $filter]);
    
    $request_data = [
        'solicitante_link' => $solicitante_nome,
        'acao_label' => $acao_label,
        'cursos_display' => $cursos_display,
        'usuarios_display' => $usuarios_display,
        'papel_display' => $papel_display,
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

// Renderizar o template com todos os dados
echo $OUTPUT->render_from_template('local_solicitacoes/manage_requests', $template_data);

echo $OUTPUT->footer();