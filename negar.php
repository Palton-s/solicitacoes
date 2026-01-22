<?php
require('../../config.php');

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
}

// Buscar solicitação
$request = $DB->get_record('local_solicitacoes', ['id' => $id], '*', MUST_EXIST);

// Verificar se já está negada
if ($request->status === 'negado') {
    redirect(
        new moodle_url('/local/solicitacoes/manage.php'),
        'Esta solicitação já foi negada.',
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/negar.php', array('id' => $id)));
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
        
        redirect(
            new moodle_url('/local/solicitacoes/manage.php'),
            'Solicitação negada com sucesso.',
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

echo $OUTPUT->header();

// Exibir informações da solicitação
echo html_writer::start_div('alert alert-warning');
echo html_writer::tag('h5', 'Você está prestes a negar esta solicitação:', array('class' => 'mb-3'));

// Traduzir tipo de ação
$acao_strings = array(
    'inscricao' => get_string('acao_inscricao', 'local_solicitacoes'),
    'remocao' => get_string('acao_remocao', 'local_solicitacoes'),
    'suspensao' => get_string('acao_suspensao', 'local_solicitacoes')
);
$acao_label = isset($acao_strings[$request->tipo_acao]) ? $acao_strings[$request->tipo_acao] : $request->tipo_acao;

echo html_writer::tag('p', html_writer::tag('strong', 'Tipo: ') . $acao_label);
echo html_writer::tag('p', html_writer::tag('strong', 'Curso: ') . format_string($request->curso_nome));

$solicitante = core_user::get_user($request->userid);
echo html_writer::tag('p', html_writer::tag('strong', 'Solicitante: ') . fullname($solicitante));

echo html_writer::end_div();

// Formulário para informar motivo
echo html_writer::start_div('card mt-4');
echo html_writer::start_div('card-header bg-danger text-white');
echo html_writer::tag('h5', get_string('motivo_negacao_label', 'local_solicitacoes'), array('class' => 'mb-0'));
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
echo html_writer::tag('button', 'Confirmar Negação', array('type' => 'submit', 'class' => 'btn btn-danger btn-lg mr-2'));

$cancel_url = new moodle_url('/local/solicitacoes/view.php', array('id' => $id));
echo html_writer::link($cancel_url, 'Cancelar', array('class' => 'btn btn-secondary btn-lg'));
echo html_writer::end_div();

echo html_writer::end_tag('form');

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
