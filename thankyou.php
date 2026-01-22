<?php
require('../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/thankyou.php'));
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
$newrequesturl = new moodle_url('/local/solicitacoes/index.php');
echo html_writer::link(
    $newrequesturl, 
    get_string('thankyou_new_request', 'local_solicitacoes'), 
    ['class' => 'btn btn-primary mr-2']
);

$homeurl = new moodle_url('/');
echo html_writer::link(
    $homeurl, 
    get_string('thankyou_back_home', 'local_solicitacoes'), 
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
