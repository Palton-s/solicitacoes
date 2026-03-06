<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

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

/**
 * Formulário nativo do Moodle para criação de course requests
 */
class criar_curso_form extends moodleform {

    protected function definition() {
        global $CFG;
        
        $mform = $this->_form;

        // Header 
        #$mform->addElement('header', 'course_details', get_string('form_criar_curso_titulo', 'local_solicitacoes'));

        // Aviso SIGAA
        $aviso_html = '<div class="alert alert-info m-4" role="alert">' . 
                      get_string('aviso_criar_curso', 'local_solicitacoes') . 
                      '</div>';
        $mform->addElement('html', $aviso_html);

        // Código SIGAA
        $mform->addElement('text', 'codigo_sigaa', get_string('codigo_sigaa', 'local_solicitacoes'), ['size' => 30]);
        $mform->setType('codigo_sigaa', PARAM_TEXT);
        $mform->addRule('codigo_sigaa', null, 'required', null, 'client');
        $mform->addHelpButton('codigo_sigaa', 'codigo_sigaa_help', 'local_solicitacoes');

        // Course shortname
        $mform->addElement('text', 'course_shortname', get_string('course_shortname', 'local_solicitacoes'), ['size' => 30]);
        $mform->setType('course_shortname', PARAM_TEXT);
        $mform->addRule('course_shortname', null, 'required', null, 'client');
        $mform->addHelpButton('course_shortname', 'course_shortname_help', 'local_solicitacoes');

        // Nome completo do curso
        $mform->addElement('text', 'course_fullname', get_string('course_fullname', 'local_solicitacoes'), ['size' => 50]);
        $mform->setType('course_fullname', PARAM_TEXT);
        $mform->addRule('course_fullname', null, 'required', null, 'client');
        $mform->addHelpButton('course_fullname', 'course_fullname_help', 'local_solicitacoes');

        // Categoria do curso (implementação nativa do Moodle)
        $displaylist = core_course_category::make_categories_list();
        $mform->addElement('autocomplete', 'category', get_string('unidade_academica', 'local_solicitacoes'), $displaylist, [
            'multiple' => false,
            'placeholder' => get_string('select_category', 'local_solicitacoes'),
            'noselectionstring' => get_string('select_category', 'local_solicitacoes'),
        ]);
        $mform->setType('category', PARAM_INT);
        $mform->addRule('category', null, 'required', null, 'client');
        $mform->addHelpButton('category', 'unidade_academica_help', 'local_solicitacoes');

        // Ano/Semestre
        $semestre_options = [
            '' => get_string('select_option', 'local_solicitacoes'),
            '2024.1' => '2024.1',  
            '2024.2' => '2024.2',
            '2025.1' => '2025.1',
            '2025.2' => '2025.2', 
            '2026.1' => '2026.1',
            '2026.2' => '2026.2',
        ];
        $mform->addElement('select', 'ano_semestre', get_string('ano_semestre', 'local_solicitacoes'), $semestre_options);
        $mform->addRule('ano_semestre', null, 'required', null, 'client');
        $mform->addHelpButton('ano_semestre', 'ano_semestre_help', 'local_solicitacoes');

        // Sumário do curso
        $mform->addElement('textarea', 'course_summary', get_string('course_summary', 'local_solicitacoes'), 
            ['wrap' => 'virtual', 'rows' => 4, 'cols' => 60]);
        $mform->setType('course_summary', PARAM_TEXT);
        $mform->addHelpButton('course_summary', 'course_summary_help', 'local_solicitacoes');

        // Razões para criação do curso
        $mform->addElement('textarea', 'razoes_criacao', get_string('razoes_criacao', 'local_solicitacoes'), 
            ['wrap' => 'virtual', 'rows' => 6, 'cols' => 60]);
        $mform->setType('razoes_criacao', PARAM_TEXT);
        $mform->addRule('razoes_criacao', null, 'required', null, 'client');
        $mform->addHelpButton('razoes_criacao', 'razoes_criacao_help', 'local_solicitacoes');

        // Botões de ação
        $this->add_action_buttons(true, get_string('request_submit', 'local_solicitacoes'));
    }

    /**
     * Validação personalizada do formulário
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validar se a categoria existe e o usuário tem permissão
        if (!empty($data['category'])) {
            $category = core_course_category::get($data['category'], IGNORE_MISSING);
            if (!$category) {
                $errors['category'] = get_string('error_invalid_category', 'local_solicitacoes');
            }
        }

        return $errors;
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/solicitar-curso.php'));
$PAGE->set_title(get_string('form_criar_curso_titulo', 'local_solicitacoes'));
$PAGE->set_heading(get_string('form_criar_curso_titulo', 'local_solicitacoes'));

// Criar instância do formulário
$mform = new criar_curso_form();

// Processar formulário se submetido  
if ($data = $mform->get_data()) {
    // Processar dados do formulário
    global $DB, $USER;
    
    try {
        $record = new stdClass();
        $record->userid = $USER->id;                    // Campo correto: userid (não user_id)
        $record->tipo_acao = 'criar_curso';             // Campo correto: tipo_acao (não tipo)
        $record->status = 'pendente';
        $record->timecreated = time();                  // Campo correto: timecreated (não data_criacao) 
        $record->timemodified = time();                 // Campo obrigatório que estava faltando
        $record->codigo_sigaa = $data->codigo_sigaa;
        $record->course_shortname = $data->course_shortname;
        $record->course_summary = $data->course_summary;
        $record->unidade_academica_id = $data->category;  // Campo correto: unidade_academica_id (não category_id)
        $record->ano_semestre = $data->ano_semestre;
        $record->razoes_criacao = $data->razoes_criacao;
        
        $DB->insert_record('local_solicitacoes', $record);
        
        redirect(
            new moodle_url('/local/solicitacoes/minhas-solicitacoes.php'),
            get_string('request_submitted', 'local_solicitacoes'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (Exception $e) {
        redirect(
            new moodle_url('/local/solicitacoes/solicitar-curso.php'),
            get_string('error_submitting', 'local_solicitacoes') . ': ' . $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// Se foi cancelado
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/solicitacoes/selecionar-acao.php'));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
