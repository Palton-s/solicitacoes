<?php
require('../../config.php');

require_login();

$context = context_system::instance();
$id = required_param('id', PARAM_INT);

// Verificar se pode ver todas ou gerenciar
$canmanage = has_capability('local/solicitacoes:manage', $context);
$canviewall = has_capability('local/solicitacoes:viewall', $context);

if (!$canmanage && !$canviewall) {
    // Se não pode ver todas, verifica se é a própria solicitação
    if (!has_capability('local/solicitacoes:view', $context)) {
        redirect(
            new moodle_url('/'),
            get_string('error_nopermission_viewrequest', 'local_solicitacoes'),
            null,
            \core\output\notification::NOTIFY_INFO
        );
    }
    $request = $DB->get_record('local_solicitacoes', ['id' => $id], '*', MUST_EXIST);
    if ($request->userid != $USER->id) {
        redirect(
            new moodle_url('/local/solicitacoes/myrequests.php'),
            get_string('error_nopermission_viewrequest', 'local_solicitacoes'),
            null,
            \core\output\notification::NOTIFY_INFO
        );
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/view.php'), array('id' => $id));
$PAGE->set_title(get_string('details', 'local_solicitacoes'));
$PAGE->set_heading(get_string('details', 'local_solicitacoes'));

// Buscar solicitação com dados do usuário
$sql = "SELECT r.*, u.firstname, u.lastname, u.email
        FROM {local_solicitacoes} r 
        JOIN {user} u ON r.userid = u.id 
        WHERE r.id = :id";

$request = $DB->get_record_sql($sql, array('id' => $id), MUST_EXIST);

echo $OUTPUT->header();

// Exibir detalhes da solicitação
echo html_writer::start_tag('div', array('class' => 'solicitacao-details'));

// Traduzir tipo de ação
$acao_strings = array(
    'inscricao' => get_string('acao_inscricao', 'local_solicitacoes'),
    'remocao' => get_string('acao_remocao', 'local_solicitacoes'),
    'suspensao' => get_string('acao_suspensao', 'local_solicitacoes')
);
$acao_label = isset($acao_strings[$request->tipo_acao]) ? $acao_strings[$request->tipo_acao] : $request->tipo_acao;

echo html_writer::tag('h3', $acao_label . ' - ' . format_string($request->curso_nome));

echo html_writer::start_tag('div', array('class' => 'solicitacao-meta'));

$statuses = array(
    'pendente' => get_string('status_pendente', 'local_solicitacoes'),
    'em_andamento' => get_string('status_em_andamento', 'local_solicitacoes'),
    'concluido' => get_string('status_concluido', 'local_solicitacoes')
);

echo html_writer::tag('p', html_writer::tag('strong', get_string('user', 'local_solicitacoes') . ': ') . fullname($request));
echo html_writer::tag('p', html_writer::tag('strong', 'Email: ') . $request->email);
echo html_writer::tag('p', html_writer::tag('strong', get_string('action_type', 'local_solicitacoes') . ': ') . $acao_label);
echo html_writer::tag('p', html_writer::tag('strong', get_string('course', 'local_solicitacoes') . ': ') . format_string($request->curso_nome));

// Mostrar papel apenas para inscrições
if ($request->tipo_acao == 'inscricao' && !empty($request->papel)) {
    $papel_strings = array(
        'student' => get_string('papel_student', 'local_solicitacoes'),
        'teacher' => get_string('papel_teacher', 'local_solicitacoes'),
        'editingteacher' => get_string('papel_editingteacher', 'local_solicitacoes'),
        'manager' => get_string('papel_manager', 'local_solicitacoes')
    );
    $papel_label = isset($papel_strings[$request->papel]) ? $papel_strings[$request->papel] : $request->papel;
    echo html_writer::tag('p', html_writer::tag('strong', get_string('role', 'local_solicitacoes') . ': ') . $papel_label);
}

echo html_writer::tag('p', html_writer::tag('strong', get_string('status', 'local_solicitacoes') . ': ') . $statuses[$request->status]);
echo html_writer::tag('p', html_writer::tag('strong', get_string('created_at', 'local_solicitacoes') . ': ') . userdate($request->timecreated));

if ($request->timemodified != $request->timecreated) {
    echo html_writer::tag('p', html_writer::tag('strong', get_string('last_modified', 'local_solicitacoes') . ': ') . userdate($request->timemodified));
}

if ($request->adminid) {
    $admin = core_user::get_user($request->adminid);
    echo html_writer::tag('p', html_writer::tag('strong', get_string('handled_by', 'local_solicitacoes') . ': ') . fullname($admin));
}

echo html_writer::end_tag('div');

echo html_writer::tag('h4', get_string('target_users', 'local_solicitacoes'));
echo html_writer::tag('div', nl2br(format_text($request->usuarios_nomes)), array('class' => 'solicitacao-mensagem'));

if (!empty($request->observacoes)) {
    echo html_writer::tag('h4', get_string('observacoes', 'local_solicitacoes'));
    echo html_writer::tag('div', nl2br(format_text($request->observacoes)), array('class' => 'solicitacao-mensagem'));
}

echo html_writer::end_tag('div');

// Ações de mudança de status
echo html_writer::start_tag('div', array('class' => 'solicitacao-actions'));
echo html_writer::tag('h4', get_string('update_status', 'local_solicitacoes'));

$baseurl = new moodle_url('/local/solicitacoes/manage.php');
$buttons = array();

$statuses_actions = array(
    'pendente' => get_string('status_pendente', 'local_solicitacoes'),
    'em_andamento' => get_string('status_em_andamento', 'local_solicitacoes'),
    'concluido' => get_string('status_concluido', 'local_solicitacoes')
);

foreach ($statuses_actions as $status_key => $status_label) {
    if ($status_key === $request->status) {
        continue; // não mostra o status atual
    }
    
    $url = new moodle_url($baseurl, array(
        'action' => 'updatestatus',
        'id' => $request->id,
        'status' => $status_key,
        'sesskey' => sesskey()
    ));
    
    $class = 'btn ';
    switch($status_key) {
        case 'pendente':
            $class .= 'btn-secondary';
            break;
        case 'em_andamento':
            $class .= 'btn-warning';
            break;
        case 'concluido':
            $class .= 'btn-success';
            break;
    }
    
    $buttons[] = html_writer::link($url, $status_label, array('class' => $class));
}

echo implode(' ', $buttons);
echo html_writer::end_tag('div');

// Link para voltar
echo html_writer::start_tag('div', array('class' => 'mt-3'));
$backurl = new moodle_url('/local/solicitacoes/manage.php');
echo html_writer::link($backurl, '← ' . get_string('back_to_list', 'local_solicitacoes'), array('class' => 'btn btn-primary'));
echo html_writer::end_tag('div');

echo $OUTPUT->footer();