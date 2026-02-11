<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$context = context_system::instance();
$id = required_param('id', PARAM_INT);

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

// Buscar solicitação
$request = $DB->get_record('local_solicitacoes', ['id' => $id], '*', MUST_EXIST);

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

// Verificar se já está negada
if ($request->status === 'negado') {
    redirect(
        new moodle_url('/local/solicitacoes/gerenciar.php'),
        'Esta solicitação já foi negada.',
        null,
        \core\output\notification::NOTIFY_INFO
    );
    exit;
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/negar-solicitacao.php', array('id' => $id)));
$PAGE->set_title('Negar Solicitação');
$PAGE->set_heading('Negar Solicitação');

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    
    $motivo_negacao = required_param('motivo_negacao', PARAM_TEXT);
    
    if (empty(trim($motivo_negacao))) {
        \core\notification::error(get_string('motivo_negacao_required', 'local_solicitacoes'));
    } else {
        // Atualizar solicitação
        $request->status = 'negado';
        $request->motivo_negacao = $motivo_negacao;
        $request->timemodified = time();
        $request->adminid = $USER->id;
        
        $DB->update_record('local_solicitacoes', $request);
        
        // Enviar notificação de negação
        local_solicitacoes_notify_negada($id);
        
        redirect(
            new moodle_url('/local/solicitacoes/gerenciar.php'),
            'Solicitação negada com sucesso.',
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
        exit;
    }
}

echo $OUTPUT->header();

// Exibir informações da solicitação
echo html_writer::start_div('alert alert-secondary');
echo html_writer::tag('h5', 'Você está prestes a negar esta solicitação:', array('class' => 'mb-3'));

// Traduzir tipo de ação
$acao_strings = array(
    'inscricao' => get_string('acao_inscricao', 'local_solicitacoes'),
    'remocao' => get_string('acao_remocao', 'local_solicitacoes'),
    'suspensao' => get_string('acao_suspensao', 'local_solicitacoes')
);
$acao_label = isset($acao_strings[$request->tipo_acao]) ? $acao_strings[$request->tipo_acao] : $request->tipo_acao;

echo html_writer::tag('p', html_writer::tag('strong', 'Tipo: ') . $acao_label);

// Exibir cursos
if (!empty($cursos)) {
    $cursos_list = array();
    foreach ($cursos as $curso) {
        $cursos_list[] = format_string($curso->fullname);
    }
    echo html_writer::tag('p', html_writer::tag('strong', 'Curso(s): ') . implode(', ', $cursos_list));
} else {
    echo html_writer::tag('p', html_writer::tag('strong', 'Curso: ') . format_string($request->curso_nome));
}

// Exibir usuários afetados
if (!empty($usuarios)) {
    echo html_writer::tag('p', html_writer::tag('strong', 'Usuários afetados:'));
    echo html_writer::start_tag('ul', array('class' => 'mb-2'));
    foreach ($usuarios as $usuario) {
        echo html_writer::tag('li', fullname($usuario) . ' (' . $usuario->email . ')');
    }
    echo html_writer::end_tag('ul');
} else {
    // Fallback para campo texto
    echo html_writer::tag('p', html_writer::tag('strong', 'Usuários: ') . nl2br(format_text($request->usuarios_nomes)));
}

$solicitante = core_user::get_user($request->userid);
echo html_writer::tag('p', html_writer::tag('strong', 'Solicitante: ') . fullname($solicitante));

echo html_writer::end_div();

// Formulário para informar motivo
echo html_writer::start_div('card mt-4');
echo html_writer::start_div('card-header bg-light');
echo html_writer::tag('h5', get_string('motivo_negacao_label', 'local_solicitacoes'), array('class' => 'mb-0 text-dark'));
echo html_writer::end_div();
echo html_writer::start_div('card-body');

echo html_writer::start_tag('form', array('method' => 'post', 'action' => ''));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));

echo html_writer::start_div('form-group');
echo html_writer::tag('label', 'Informe o motivo da negação:', array('for' => 'motivo_negacao', 'class' => 'font-weight-bold'));
echo html_writer::tag('textarea', '', array(
    'class' => 'form-control',
    'id' => 'motivo_negacao',
    'name' => 'motivo_negacao',
    'rows' => '6',
    'required' => 'required',
    'placeholder' => 'Digite aqui o motivo pelo qual esta solicitação está sendo negada...'
));
echo html_writer::tag('small', 'Este motivo será visível para o solicitante.', array('class' => 'form-text text-muted'));
echo html_writer::end_div();

echo html_writer::start_div('form-group mt-4');
echo html_writer::tag('button', 'Confirmar Negação', array('type' => 'submit', 'class' => 'btn btn-secondary btn-lg mr-2'));

$cancel_url = new moodle_url('/local/solicitacoes/detalhes.php', array('id' => $id));
echo html_writer::link($cancel_url, 'Cancelar', array('class' => 'btn btn-secondary btn-lg'));
echo html_writer::end_div();

echo html_writer::end_tag('form');

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
