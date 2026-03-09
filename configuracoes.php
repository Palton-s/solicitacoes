<?php
require('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_solicitacoes_settings');

$context = context_system::instance();
require_capability('moodle/site:config', $context);

class local_solicitacoes_config_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $categoryoptions = [0 => get_string('none')];
        $categoryoptions += core_course_category::make_categories_list();

        $mform->addElement('autocomplete', 'hidden_course_category', get_string('hidden_course_category', 'local_solicitacoes'), $categoryoptions, [
            'multiple' => false,
            'placeholder' => get_string('select_category', 'local_solicitacoes'),
            'noselectionstring' => get_string('select_category', 'local_solicitacoes'),
        ]);
        $mform->setType('hidden_course_category', PARAM_INT);

        $this->add_action_buttons(false, get_string('savechanges'));
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if (!empty($data['hidden_course_category'])) {
            $categoryid = (int)$data['hidden_course_category'];
            if (!$DB->record_exists('course_categories', ['id' => $categoryid])) {
                $errors['hidden_course_category'] = get_string('error_invalid_category', 'local_solicitacoes');
            }
        }

        return $errors;
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/configuracoes.php'));
$PAGE->set_title(get_string('pluginname', 'local_solicitacoes'));
$PAGE->set_heading(get_string('pluginname', 'local_solicitacoes'));

$form = new local_solicitacoes_config_form();
$form->set_data([
    'hidden_course_category' => (int)get_config('local_solicitacoes', 'hidden_course_category')
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/solicitacoes/configuracoes.php'));
}

if ($data = $form->get_data()) {
    set_config('hidden_course_category', (int)$data->hidden_course_category, 'local_solicitacoes');

    redirect(
        new moodle_url('/local/solicitacoes/configuracoes.php'),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('hidden_course_category_desc', 'local_solicitacoes'), 4);
$form->display();
echo $OUTPUT->footer();
