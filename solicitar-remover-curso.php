<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

require_login();

$context = context_system::instance();

if (!has_capability('local/solicitacoes:submit', $context)) {
    redirect(
        new moodle_url('/'),
        get_string('error_nopermission_submit', 'local_solicitacoes'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

class remover_curso_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;

        $aviso_html = '<div class="alert alert-warning m-4" role="alert">' .
                      '<i class="fas fa-exclamation-triangle"></i> ' .
                      get_string('form_remover_curso_descricao', 'local_solicitacoes') .
                      '</div>';
        $mform->addElement('html', $aviso_html);

        // Campo de curso: busca assíncrona (AJAX), nenhum curso é carregado na página.
        local_solicitacoes_add_course_selector($mform, \local_solicitacoes\local\course_search::SCOPE_ALL);

        $mform->addElement('textarea', 'observacoes', get_string('observacoes', 'local_solicitacoes'),
            ['rows' => 5, 'cols' => 50, 'placeholder' => get_string('observacoes_placeholder', 'local_solicitacoes')]);
        $mform->setType('observacoes', PARAM_TEXT);
        $mform->addRule('observacoes', null, 'required', null, 'client');
        $mform->addHelpButton('observacoes', 'observacoes_help', 'local_solicitacoes');

        $this->add_action_buttons(true, get_string('request_submit', 'local_solicitacoes'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $erro_cursos = \local_solicitacoes\local\course_search::validate_selection(
            $data['curso_nome'] ?? null,
            \local_solicitacoes\local\course_search::SCOPE_ALL
        );
        if ($erro_cursos !== null) {
            $errors['curso_nome'] = $erro_cursos;
        }

        return $errors;
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/solicitar-remover-curso.php'));
$PAGE->set_title(get_string('form_remover_curso_titulo', 'local_solicitacoes'));
$PAGE->set_heading(get_string('form_remover_curso_titulo', 'local_solicitacoes'));

$mform = new remover_curso_form();

if ($data = $mform->get_data()) {
    $data->tipo_acao = 'remove_course';

    require_once(__DIR__ . '/classes/solicitacoes_controller.php');

    $success = \local_solicitacoes\solicitacoes_controller::process_request_submission($data);

    if ($success) {
        redirect(
            new moodle_url('/local/solicitacoes/minhas-solicitacoes.php'),
            get_string('request_submitted', 'local_solicitacoes'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    redirect(
        new moodle_url('/local/solicitacoes/solicitar-remover-curso.php'),
        get_string('error_submitting', 'local_solicitacoes'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/solicitacoes/selecionar-cursos.php'));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
