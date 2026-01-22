<?php
require('../../config.php');

require_login();

$context = context_system::instance();

// Verificar permissão para ver próprias solicitações
if (!has_capability('local/solicitacoes:view', $context)) {
    print_error('error_nopermission_view', 'local_solicitacoes');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/myrequests.php'));
$PAGE->set_title(get_string('my_requests', 'local_solicitacoes'));
$PAGE->set_heading(get_string('my_requests', 'local_solicitacoes'));

echo $OUTPUT->header();

global $DB, $USER;

// Buscar solicitações do usuário atual
$sql = "SELECT s.*, 
               c.id AS cid, c.fullname AS cname,
               u.id AS uid, u.firstname AS ufirst, u.lastname AS ulast
          FROM {local_solicitacoes} s
          LEFT JOIN {local_curso_solicitacoes} cs ON cs.solicitacao_id = s.id
          LEFT JOIN {course} c ON cs.curso_id = c.id
          LEFT JOIN {local_usuarios_solicitacoes} us ON us.solicitacao_id = s.id
          LEFT JOIN {user} u ON us.usuario_id = u.id
         WHERE s.userid = :userid
         ORDER BY s.timecreated DESC";

$params = ['userid' => $USER->id];
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
    'has_requests' => !empty($requests),
    'requests' => [],
    'can_manage' => has_capability('local/solicitacoes:manage', $context),
];

if (!$requests) {
    echo $OUTPUT->render_from_template('local_solicitacoes/my_requests', $template_data);
    echo $OUTPUT->footer();
    exit;
}

foreach ($requests as $r) {
    // Processar Cursos
    $links_cursos = [];
    foreach ($r->cursos as $cid => $cname) {
        $url = new moodle_url('/course/view.php', ['id' => $cid]);
        $links_cursos[] = html_writer::link($url, format_string($cname));
    }
    $cursos_display = implode(', ', $links_cursos);

    // Processar Usuários Alvo
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

    // Status formatado com classes Bootstrap
    $statuskey = 'status_' . $r->status;
    $statuslabel = get_string($statuskey, 'local_solicitacoes');
    
    // Definir cores/classes de badge para cada status
    $status_badge_classes = [
        'pendente' => 'badge badge-warning text-dark',
        'aprovado' => 'badge badge-success',
        'negado' => 'badge badge-danger',
        'em_andamento' => 'badge badge-info',
        'concluido' => 'badge badge-primary'
    ];
    $status_class = isset($status_badge_classes[$r->status]) ? $status_badge_classes[$r->status] : 'badge badge-secondary';
    
    // Traduzir tipo de ação
    $acao_strings = [
        'inscricao' => get_string('acao_inscricao', 'local_solicitacoes'),
        'remocao' => get_string('acao_remocao', 'local_solicitacoes'),
        'suspensao' => get_string('acao_suspensao', 'local_solicitacoes')
    ];
    $acao_label = isset($acao_strings[$r->tipo_acao]) ? $acao_strings[$r->tipo_acao] : $r->tipo_acao;
    
    // Papel (apenas para inscrições)
    $papel_display = '';
    if ($r->tipo_acao == 'inscricao' && !empty($r->papel)) {
        $papel_strings = [
            'student' => get_string('papel_student', 'local_solicitacoes'),
            'teacher' => get_string('papel_teacher', 'local_solicitacoes'),
            'editingteacher' => get_string('papel_editingteacher', 'local_solicitacoes'),
            'manager' => get_string('papel_manager', 'local_solicitacoes')
        ];
        $papel_display = isset($papel_strings[$r->papel]) ? $papel_strings[$r->papel] : $r->papel;
    }

    // Preparar dados para o template
    $request_data = [
        'acao_label' => $acao_label,
        'cursos_display' => $cursos_display,
        'usuarios_display' => $usuarios_display,
        'papel_display' => $papel_display,
        'created_date' => userdate($r->timecreated),
        'status_label' => $statuslabel,
        'status_class' => $r->status, // Para colorir status
        'view_url' => (new moodle_url('/local/solicitacoes/view.php', ['id' => $r->id]))->out(false),
    ];
    
    $template_data['requests'][] = $request_data;
}

// Renderizar o template
echo $OUTPUT->render_from_template('local_solicitacoes/my_requests', $template_data);

echo $OUTPUT->footer();
