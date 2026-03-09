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

        $cursos_disponiveis = $this->get_available_courses();
        $mform->addElement('autocomplete', 'curso_nome', get_string('curso_nome', 'local_solicitacoes'), $cursos_disponiveis, [
            'multiple' => true,
            'placeholder' => get_string('searching_courses', 'local_solicitacoes'),
            'noselectionstring' => get_string('no_courses_found', 'local_solicitacoes'),
        ]);
        $mform->setType('curso_nome', PARAM_SEQUENCE);
        $mform->addRule('curso_nome', null, 'required', null, 'client');
        $mform->addHelpButton('curso_nome', 'curso_nome_help', 'local_solicitacoes');

        $mform->addElement('textarea', 'observacoes', get_string('observacoes', 'local_solicitacoes'),
            ['rows' => 5, 'cols' => 50, 'placeholder' => get_string('observacoes_placeholder', 'local_solicitacoes')]);
        $mform->setType('observacoes', PARAM_TEXT);
        $mform->addHelpButton('observacoes', 'observacoes_help', 'local_solicitacoes');

        $this->add_action_buttons(true, get_string('request_submit', 'local_solicitacoes'));
    }

    protected function get_available_courses() {
        global $DB;

        $cursos_options = [];

        $sql = "SELECT id, fullname, shortname
                  FROM {course}
                 WHERE id > 1
                 ORDER BY fullname ASC";

        $cursos = $DB->get_records_sql($sql);
        foreach ($cursos as $curso) {
            $cursos_options[$curso->id] = $curso->fullname . ' (' . $curso->shortname . ')';
        }

        return $cursos_options;
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if (empty($data['curso_nome'])) {
            $errors['curso_nome'] = get_string('error_course_required', 'local_solicitacoes');
            return $errors;
        }

        $cursos_selecionados = is_array($data['curso_nome']) ? $data['curso_nome'] : [$data['curso_nome']];
        $cursos_invalidos = [];

        foreach ($cursos_selecionados as $curso_id) {
            $curso_id = (int)$curso_id;
            if ($curso_id <= 0 || !$DB->record_exists('course', ['id' => $curso_id])) {
                $cursos_invalidos[] = $curso_id;
            }
        }

        if (!empty($cursos_invalidos)) {
            $errors['curso_nome'] = get_string('error_invalid_courses', 'local_solicitacoes', implode(', ', $cursos_invalidos));
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
