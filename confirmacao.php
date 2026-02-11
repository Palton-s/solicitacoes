<?php
require('../../config.php');

require_login();

$context = context_system::instance();

// Verificar permissões do usuário
$canview = has_capability('local/solicitacoes:view', $context);
$canmanage = has_capability('local/solicitacoes:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/confirmacao.php'));
$PAGE->set_title(get_string('thankyou_title', 'local_solicitacoes'));
$PAGE->set_heading(get_string('thankyou_title', 'local_solicitacoes'));

echo $OUTPUT->header();

// Mensagem de sucesso
echo $OUTPUT->notification(
    get_string('thankyou_message', 'local_solicitacoes'), 
    'success'
);

// Card com informações adicionais
echo html_writer::start_div('card mt-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('thankyou_next_steps', 'local_solicitacoes'), ['class' => 'card-title']);
echo html_writer::tag('p', get_string('thankyou_info', 'local_solicitacoes'), ['class' => 'card-text']);

// Botões de ação
echo html_writer::start_div('mt-3');

// Botão "Minhas Solicitações" - apenas para usuários que podem visualizar mas não gerenciar
if ($canview && !$canmanage) {
    $myrequestsurl = new moodle_url('/local/solicitacoes/minhas-solicitacoes.php');
    echo html_writer::link(
        $myrequestsurl, 
        get_string('my_requests', 'local_solicitacoes'), 
        ['class' => 'btn mr-2']
    );
}

$newrequesturl = new moodle_url('/local/solicitacoes/nova-solicitacao.php');
echo html_writer::link(
    $newrequesturl, 
    get_string('thankyou_new_request', 'local_solicitacoes'), 
    ['class' => 'btn btn-primary mr-2']
);

$homeurl = new moodle_url('/');
echo html_writer::link(
    $homeurl, 
    get_string('thankyou_back_home', 'local_solicitacoes'), 
    ['class' => 'btn']
);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
