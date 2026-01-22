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

// Adicionar Font Awesome para ícones
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'));
$PAGE->requires->css(new moodle_url('/local/solicitacoes/styles/view_details.css'));

// Buscar solicitação com dados do usuário
$sql = "SELECT r.*, u.firstname, u.lastname, u.email
        FROM {local_solicitacoes} r 
        JOIN {user} u ON r.userid = u.id 
        WHERE r.id = :id";

$request = $DB->get_record_sql($sql, array('id' => $id), MUST_EXIST);

// Buscar cursos relacionados
$sql_cursos = "SELECT c.id, c.fullname, c.shortname
               FROM {local_curso_solicitacoes} cs
               JOIN {course} c ON cs.curso_id = c.id
               WHERE cs.solicitacao_id = :id";
$cursos = $DB->get_records_sql($sql_cursos, array('id' => $id));

// Buscar usuários relacionados
$sql_usuarios = "SELECT u.id, u.firstname, u.lastname, u.email, u.username
                 FROM {local_usuarios_solicitacoes} us
                 JOIN {user} u ON us.usuario_id = u.id
                 WHERE us.solicitacao_id = :id";
$usuarios = $DB->get_records_sql($sql_usuarios, array('id' => $id));

echo $OUTPUT->header();

// Traduzir tipo de ação
$acao_strings = array(
    'inscricao' => get_string('acao_inscricao', 'local_solicitacoes'),
    'remocao' => get_string('acao_remocao', 'local_solicitacoes'),
    'suspensao' => get_string('acao_suspensao', 'local_solicitacoes')
);
$acao_label = isset($acao_strings[$request->tipo_acao]) ? $acao_strings[$request->tipo_acao] : $request->tipo_acao;

// Preparar status badge
$status_badges = array(
    'pendente' => array('class' => 'badge-warning', 'label' => get_string('status_pendente', 'local_solicitacoes')),
    'aprovado' => array('class' => 'badge-success', 'label' => get_string('status_aprovado', 'local_solicitacoes')),
    'negado' => array('class' => 'badge-danger', 'label' => get_string('status_negado', 'local_solicitacoes')),
    'em_andamento' => array('class' => 'badge-info', 'label' => get_string('status_em_andamento', 'local_solicitacoes')),
    'concluido' => array('class' => 'badge-success', 'label' => get_string('status_concluido', 'local_solicitacoes'))
);
$badge_info = isset($status_badges[$request->status]) ? $status_badges[$request->status] : array('class' => 'badge-secondary', 'label' => $request->status);

// ===== CABEÇALHO =====
echo html_writer::start_div('mb-4');
echo html_writer::start_div('d-flex justify-content-between align-items-start flex-wrap');
echo html_writer::start_div('');
echo html_writer::tag('h3', $acao_label . ' - ' . format_string($request->curso_nome), array('class' => 'mb-2'));
echo html_writer::tag('span', $badge_info['label'], array('class' => 'badge ' . $badge_info['class'] . ' mr-2', 'style' => 'font-size: 1rem;'));
echo html_writer::tag('small', userdate($request->timecreated, get_string('strftimedatetime', 'langconfig')), array('class' => 'text-muted'));
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// ===== CONTAINER PRINCIPAL (DUAS COLUNAS) =====
echo html_writer::start_div('row');

// ===== COLUNA ESQUERDA =====
echo html_writer::start_div('col-lg-8');

// Card: Usuários Afetados
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-header bg-primary text-white');
echo html_writer::tag('h5', get_string('target_users', 'local_solicitacoes'), array('class' => 'mb-0'));
echo html_writer::end_div();
echo html_writer::start_div('card-body p-0');

// Processar lista de usuários da tabela relacionada ou campo texto como fallback
echo html_writer::start_tag('ul', array('class' => 'list-group list-group-flush'));
if (!empty($usuarios)) {
    foreach ($usuarios as $usuario) {
        echo html_writer::start_tag('li', array('class' => 'list-group-item'));
        echo html_writer::tag('i', '', array('class' => 'fa fa-user-circle mr-2 text-primary', 'style' => 'font-size: 1.2rem;'));
        echo html_writer::tag('strong', fullname($usuario));
        echo html_writer::tag('br', '');
        echo html_writer::tag('small', $usuario->email . ' (' . $usuario->username . ')', array('class' => 'text-muted'));
        echo html_writer::end_tag('li');
    }
} else {
    // Fallback para o campo texto se não houver registros na tabela relacionada
    $usuarios_nomes = explode("\n", trim($request->usuarios_nomes));
    foreach ($usuarios_nomes as $usuario_nome) {
        $usuario_nome = trim($usuario_nome);
        if (empty($usuario_nome)) continue;
        
        echo html_writer::start_tag('li', array('class' => 'list-group-item'));
        echo html_writer::tag('i', '', array('class' => 'fa fa-user-circle mr-2 text-primary', 'style' => 'font-size: 1.2rem;'));
        echo html_writer::tag('strong', format_string($usuario_nome));
        echo html_writer::end_tag('li');
    }
}
echo html_writer::end_tag('ul');
echo html_writer::end_div();
echo html_writer::end_div();

// Card: Observações
if (!empty($request->observacoes)) {
    echo html_writer::start_div('card mb-3');
    echo html_writer::start_div('card-header bg-light');
    echo html_writer::tag('h5', get_string('observacoes', 'local_solicitacoes'), array('class' => 'mb-0'));
    echo html_writer::end_div();
    echo html_writer::start_div('card-body bg-light');
    echo html_writer::tag('div', nl2br(format_text($request->observacoes)), array('class' => 'text-muted', 'style' => 'white-space: pre-wrap;'));
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Card: Motivo da Negação (apenas se status = negado)
if ($request->status === 'negado' && !empty($request->motivo_negacao)) {
    echo html_writer::start_div('card mb-3 border-danger');
    echo html_writer::start_div('card-header bg-danger text-white');
    echo html_writer::tag('h5', get_string('motivo_negacao', 'local_solicitacoes'), array('class' => 'mb-0'));
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');
    echo html_writer::tag('div', nl2br(format_text($request->motivo_negacao)), array('class' => 'text-dark', 'style' => 'white-space: pre-wrap;'));
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div(); // fim coluna esquerda

// ===== COLUNA DIREITA =====
echo html_writer::start_div('col-lg-4');

// Card: Dados do Curso
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-header bg-info text-white');
echo html_writer::tag('h6', get_string('course', 'local_solicitacoes'), array('class' => 'mb-0'));
echo html_writer::end_div();
echo html_writer::start_div('card-body');

// Exibir cursos da tabela relacionada ou campo texto como fallback
if (!empty($cursos)) {
    foreach ($cursos as $curso) {
        echo html_writer::tag('p', html_writer::tag('strong', 'Nome: ') . format_string($curso->fullname), array('class' => 'mb-1'));
        if (!empty($curso->shortname)) {
            echo html_writer::tag('p', html_writer::tag('small', 'Sigla: ' . $curso->shortname, array('class' => 'text-muted')), array('class' => 'mb-2'));
        }
    }
} else {
    echo html_writer::tag('p', html_writer::tag('strong', 'Nome: ') . format_string($request->curso_nome), array('class' => 'mb-2'));
}

// Mostrar papel apenas para inscrições
if ($request->tipo_acao == 'inscricao' && !empty($request->papel)) {
    $papel_strings = array(
        'student' => get_string('papel_student', 'local_solicitacoes'),
        'teacher' => get_string('papel_teacher', 'local_solicitacoes'),
        'editingteacher' => get_string('papel_editingteacher', 'local_solicitacoes'),
        'manager' => get_string('papel_manager', 'local_solicitacoes')
    );
    $papel_label = isset($papel_strings[$request->papel]) ? $papel_strings[$request->papel] : $request->papel;
    echo html_writer::tag('p', html_writer::tag('strong', get_string('role', 'local_solicitacoes') . ': ') . $papel_label, array('class' => 'mb-0'));
}
echo html_writer::end_div();
echo html_writer::end_div();

// Card: Solicitante
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-header bg-secondary text-white');
echo html_writer::tag('h6', get_string('user', 'local_solicitacoes'), array('class' => 'mb-0'));
echo html_writer::end_div();
echo html_writer::start_div('card-body');
echo html_writer::tag('p', html_writer::tag('i', '', array('class' => 'fa fa-user mr-2')) . html_writer::tag('strong', fullname($request)), array('class' => 'mb-2'));
echo html_writer::tag('p', html_writer::tag('i', '', array('class' => 'fa fa-envelope mr-2')) . html_writer::tag('small', $request->email, array('class' => 'text-muted')), array('class' => 'mb-0'));
echo html_writer::end_div();
echo html_writer::end_div();

// Card: Administrador Responsável (se aplicável)
if ($request->adminid) {
    $admin = core_user::get_user($request->adminid);
    echo html_writer::start_div('card mb-3');
    echo html_writer::start_div('card-header bg-dark text-white');
    echo html_writer::tag('h6', get_string('handled_by', 'local_solicitacoes'), array('class' => 'mb-0'));
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', html_writer::tag('i', '', array('class' => 'fa fa-user-shield mr-2')) . html_writer::tag('strong', fullname($admin)), array('class' => 'mb-2'));
    if ($request->timemodified != $request->timecreated) {
        echo html_writer::tag('p', html_writer::tag('small', get_string('last_modified', 'local_solicitacoes') . ': ' . userdate($request->timemodified), array('class' => 'text-muted')), array('class' => 'mb-0'));
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div(); // fim coluna direita
echo html_writer::end_div(); // fim row

// ===== RODAPÉ - AÇÕES (apenas para gestores) =====
if ($canmanage) {
    echo html_writer::start_div('card mt-4');
    echo html_writer::start_div('card-header bg-light');
    echo html_writer::tag('h5', get_string('actions', 'local_solicitacoes'), array('class' => 'mb-0'));
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');
    
    $baseurl = new moodle_url('/local/solicitacoes/manage.php');
    $buttons = array();

    // Botão Aprovar - só mostrar se não estiver aprovado
    if ($request->status !== 'aprovado') {
        $url_aprovar = new moodle_url($baseurl, array(
            'action' => 'updatestatus',
            'id' => $request->id,
            'status' => 'aprovado',
            'sesskey' => sesskey()
        ));
        $buttons[] = html_writer::link($url_aprovar, get_string('approve', 'local_solicitacoes'), 
            array('class' => 'btn btn-success mr-2'));
    }

    // Botão Negar - só mostrar se não estiver negado (redireciona para página de negação)
    if ($request->status !== 'negado') {
        $url_negar = new moodle_url('/local/solicitacoes/negar.php', array('id' => $request->id));
        $buttons[] = html_writer::link($url_negar, get_string('deny', 'local_solicitacoes'), 
            array('class' => 'btn btn-danger mr-2'));
    }

    // Botão Excluir - sempre mostrar
    $url_delete = new moodle_url($baseurl, array(
        'action' => 'delete',
        'id' => $request->id,
        'sesskey' => sesskey()
    ));
    $buttons[] = html_writer::link($url_delete, get_string('delete', 'local_solicitacoes'), 
        array('class' => 'btn btn-warning mr-2', 'onclick' => 'return confirm("' . get_string('confirm_delete', 'local_solicitacoes') . '");'));

    echo implode(' ', $buttons);
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Link para voltar
echo html_writer::start_tag('div', array('class' => 'mt-3'));
$backurl = $canmanage ? new moodle_url('/local/solicitacoes/manage.php') : new moodle_url('/local/solicitacoes/myrequests.php');
echo html_writer::link($backurl, '← ' . get_string('back_to_list', 'local_solicitacoes'), array('class' => 'btn btn-outline-primary'));
echo html_writer::end_tag('div');

echo $OUTPUT->footer();