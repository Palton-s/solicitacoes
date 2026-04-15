<?php
require('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_solicitacoes_settings');

$context = context_system::instance();
require_capability('moodle/site:config', $context);

class local_solicitacoes_config_form extends moodleform {
    protected function definition() {
        global $DB;
        $mform = $this->_form;

        // Seção de configurações gerais
        $mform->addElement('header', 'general_settings', get_string('general_settings', 'local_solicitacoes'));

        // Categoria oculta para remoção de cursos
        $categoryoptions = [0 => get_string('none')];
        $categoryoptions += core_course_category::make_categories_list();

        $mform->addElement('autocomplete', 'hidden_course_category', get_string('hidden_course_category', 'local_solicitacoes'), $categoryoptions, [
            'multiple' => false,
            'placeholder' => get_string('select_category', 'local_solicitacoes'),
            'noselectionstring' => get_string('select_category', 'local_solicitacoes'),
        ]);
        $mform->setType('hidden_course_category', PARAM_INT);
        $mform->addHelpButton('hidden_course_category', 'hidden_course_category', 'local_solicitacoes');

        // Seção de papéis permitidos
        $mform->addElement('header', 'roles_settings', get_string('roles_settings', 'local_solicitacoes'));

        // Buscar todos os papéis de contexto de curso
        $course_role_ids = get_roles_for_contextlevels(CONTEXT_COURSE);
        $role_options = [];

        foreach ($course_role_ids as $roleid) {
            $role = $DB->get_record('role', ['id' => $roleid], 'shortname, name');
            if ($role) {
                $role_options[$role->shortname] = role_get_name($role);
            }
        }

        $mform->addElement('autocomplete', 'allowed_roles', get_string('allowed_roles', 'local_solicitacoes'), $role_options, [
            'multiple' => true,
            'placeholder' => get_string('select_roles_placeholder', 'local_solicitacoes'),
        ]);
        $mform->setType('allowed_roles', PARAM_RAW);
        $mform->addHelpButton('allowed_roles', 'allowed_roles', 'local_solicitacoes');

        $this->add_action_buttons(false, get_string('savechanges'));
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        // Validar categoria oculta
        if (!empty($data['hidden_course_category'])) {
            $categoryid = (int)$data['hidden_course_category'];
            if (!$DB->record_exists('course_categories', ['id' => $categoryid])) {
                $errors['hidden_course_category'] = get_string('error_invalid_category', 'local_solicitacoes');
            }
        }

        // Validar papéis permitidos
        if (!empty($data['allowed_roles'])) {
            $submitted_roles = is_array($data['allowed_roles']) ? $data['allowed_roles'] : [$data['allowed_roles']];
            $course_role_ids = get_roles_for_contextlevels(CONTEXT_COURSE);
            $valid_roles = [];
            
            foreach ($course_role_ids as $roleid) {
                $role = $DB->get_record('role', ['id' => $roleid], 'shortname');
                if ($role) {
                    $valid_roles[] = $role->shortname;
                }
            }
            
            $invalid_roles = array_diff($submitted_roles, $valid_roles);
            if (!empty($invalid_roles)) {
                $errors['allowed_roles'] = get_string('error_invalid_roles', 'local_solicitacoes', implode(', ', $invalid_roles));
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

// Carregar dados atuais das configurações
$current_allowed_roles = get_config('local_solicitacoes', 'allowed_roles');
error_log("DEBUG Configurações - current_allowed_roles do banco: " . var_export($current_allowed_roles, true));
$allowed_roles_array = [];
if (!empty($current_allowed_roles)) {
    $allowed_roles_array = explode(',', $current_allowed_roles);
    $allowed_roles_array = array_map('trim', $allowed_roles_array);
} else {
    // Papéis padrão se nenhum configurado
    $allowed_roles_array = ['student', 'teacher', 'editingteacher'];
}
error_log("DEBUG Configurações - allowed_roles_array para form: " . print_r($allowed_roles_array, true));

$form_data = [
    'hidden_course_category' => (int)get_config('local_solicitacoes', 'hidden_course_category'),
    'allowed_roles' => $allowed_roles_array
];
error_log("DEBUG Configurações - form_data completo: " . print_r($form_data, true));
$form->set_data($form_data);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/solicitacoes/configuracoes.php'));
}

if ($data = $form->get_data()) {
    // Debug: Log dos dados recebidos
    error_log("DEBUG Configurações - Dados recebidos: " . print_r($data, true));
    
    // Salvar categoria oculta
    set_config('hidden_course_category', (int)$data->hidden_course_category, 'local_solicitacoes');

    // Salvar papéis permitidos
    $allowed_roles = '';
    if (!empty($data->allowed_roles)) {
        error_log("DEBUG Configurações - allowed_roles não vazio: " . print_r($data->allowed_roles, true));
        if (is_array($data->allowed_roles)) {
            // Filtrar valores vazios e duplicados
            $roles_clean = array_filter(array_unique($data->allowed_roles));
            $allowed_roles = implode(',', $roles_clean);
            error_log("DEBUG Configurações - roles_clean: " . print_r($roles_clean, true));
        } else {
            $allowed_roles = trim($data->allowed_roles);
        }
    }
    
    // Se nenhum papel foi selecionado, usar padrões
    if (empty($allowed_roles)) {
        $allowed_roles = 'student,teacher,editingteacher';
        error_log("DEBUG Configurações - Usando papéis padrão");
    }
    
    error_log("DEBUG Configurações - Salvando allowed_roles: " . $allowed_roles);
    set_config('allowed_roles', $allowed_roles, 'local_solicitacoes');

    redirect(
        new moodle_url('/local/solicitacoes/configuracoes.php'),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_solicitacoes'), 2);
echo $OUTPUT->heading(get_string('general_settings', 'local_solicitacoes'), 3);
$form->display();
echo $OUTPUT->footer();
