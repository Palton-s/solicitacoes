<?php
require('../../config.php');

require_login();

$context = context_system::instance();

// Verificar permissão para criar solicitações
if (!has_capability('local/solicitacoes:submit', $context)) {
    redirect(
        new moodle_url('/'),
        get_string('error_nopermission_submit', 'local_solicitacoes'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/index.php'));
$PAGE->set_title(get_string('request_form_title', 'local_solicitacoes'));
$PAGE->set_heading(get_string('request_form_title', 'local_solicitacoes'));

// Incluir CSS e JS externos via API do Moodle
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css'));
$PAGE->requires->css(new moodle_url('/local/solicitacoes/styles/request_form.css'));
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js'), true);
$PAGE->requires->js(new moodle_url('/local/solicitacoes/styles/request_form.js'));

echo $OUTPUT->header();

$classname = '\local_solicitacoes\form\request_form';
$mform = new $classname();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));
} else if ($data = $mform->get_data()) {
    \local_solicitacoes\solicitacoes_controller::process_request_submission($data);
    redirect(new moodle_url('/local/solicitacoes/thankyou.php'));
}

$mform->display();

echo $OUTPUT->footer();