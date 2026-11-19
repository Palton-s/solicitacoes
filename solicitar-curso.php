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
        global $CFG, $DB;
        
        $mform = $this->_form;

        // Header 
        #$mform->addElement('header', 'course_details', get_string('form_criar_curso_titulo', 'local_solicitacoes'));

        // Aviso SIGAA
        $aviso_html = '<div class="alert alert-info m-4" role="alert">' . 
                      get_string('aviso_criar_curso', 'local_solicitacoes') . 
                      '</div>';
        $mform->addElement('html', $aviso_html);

        // Código da disciplina (ex: CCA0081)
        $mform->addElement('text', 'codigo_disciplina', get_string('codigo_disciplina', 'local_solicitacoes'), [
            'size'        => 15,
            'maxlength'   => 14,
            'placeholder' => 'Ex: CCA0081',
            'style'       => 'text-transform:uppercase; letter-spacing:.05em; font-family:monospace;',
            'oninput'     => "this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'');",
        ]);
        $mform->setType('codigo_disciplina', PARAM_TEXT);
        $mform->addRule('codigo_disciplina', null, 'required', null, 'client');
        $mform->addHelpButton('codigo_disciplina', 'codigo_disciplina_help', 'local_solicitacoes');

        // Nome da disciplina
        $mform->addElement('text', 'nome_disciplina', get_string('nome_disciplina', 'local_solicitacoes'), [
            'size'        => 50,
            'placeholder' => 'Ex: Estrutura e Organização do Estado',
        ]);
        $mform->setType('nome_disciplina', PARAM_TEXT);
        $mform->addRule('nome_disciplina', null, 'required', null, 'client');
        $mform->addHelpButton('nome_disciplina', 'nome_disciplina_help', 'local_solicitacoes');

        // Turma (pré-preenchida com "Turma ")
        $mform->addElement('text', 'turma', get_string('turma', 'local_solicitacoes'), [
            'size'      => 20,
            'maxlength' => 50,
        ]);
        $mform->setType('turma', PARAM_TEXT);
        $mform->setDefault('turma', 'Turma ');
        $mform->addRule('turma', null, 'required', null, 'client');
        $mform->addHelpButton('turma', 'turma_help', 'local_solicitacoes');

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

        // Professor da disciplina
        $aviso_professor = '<div class="alert alert-info m-4" role="alert">' .
                          get_string('aviso_professor_curso', 'local_solicitacoes') .
                          '</div>';
        $mform->addElement('html', $aviso_professor);

        // Papel no curso
        $roles_options = local_solicitacoes_get_allowed_roles_with_empty(get_string('select_role', 'local_solicitacoes'));
        
        $mform->addElement('select', 'papel', get_string('papel_professor', 'local_solicitacoes'), $roles_options);
        $mform->setType('papel', PARAM_TEXT);
        $mform->setDefault('papel', 'editingteacher'); // Professor já marcado por padrão
        $mform->addRule('papel', null, 'required', null, 'client');
        $mform->addHelpButton('papel', 'papel_professor_help', 'local_solicitacoes');

        // Campo de usuário(s) professor(es) (autocomplete múltiplo)
        // Busca assíncrona (web service local_solicitacoes_search_users).
        local_solicitacoes_add_user_selector($mform, 'professor_usuario',
            get_string('professor_usuario', 'local_solicitacoes'), PARAM_TEXT);
        $mform->addHelpButton('professor_usuario', 'professor_usuario_help', 'local_solicitacoes');

        // Botões de ação
        $this->add_action_buttons(true, get_string('request_submit', 'local_solicitacoes'));
    }

    /**
     * Validação personalizada do formulário
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Validar formato do código da disciplina: 2-10 letras maiúsculas + exatamente 4 dígitos
        if (empty($data['codigo_disciplina'])) {
            $errors['codigo_disciplina'] = get_string('error_codigo_disciplina_required', 'local_solicitacoes');
        } else {
            $codigo = strtoupper(trim($data['codigo_disciplina']));
            if (!preg_match('/^[A-Z]{2,10}[0-9]{4}$/', $codigo)) {
                $errors['codigo_disciplina'] = get_string('error_codigo_disciplina_invalid', 'local_solicitacoes');
            }
        }

        // Nome da disciplina obrigatório
        if (empty(trim($data['nome_disciplina'] ?? ''))) {
            $errors['nome_disciplina'] = get_string('error_nome_disciplina_required', 'local_solicitacoes');
        }

        // Turma obrigatória
        if (empty(trim($data['turma'] ?? ''))) {
            $errors['turma'] = get_string('error_turma_required', 'local_solicitacoes');
        }

        // Validar se a categoria existe e o usuário tem permissão
        if (!empty($data['category'])) {
            $category = core_course_category::get($data['category'], IGNORE_MISSING);
            if (!$category) {
                $errors['category'] = get_string('error_invalid_category', 'local_solicitacoes');
            }
        }

        // Validar se todos os professores selecionados existem e estão ativos
        if (!empty($data['professor_usuario'])) {
            $prof_ids = is_array($data['professor_usuario']) ? $data['professor_usuario'] : [$data['professor_usuario']];
            foreach ($prof_ids as $prof_id) {
                $prof_id = (int)$prof_id;
                if ($prof_id <= 0) continue;
                $user = $DB->get_record('user', ['id' => $prof_id], 'id, deleted, confirmed');
                if (!$user || $user->deleted || !$user->confirmed) {
                    $errors['professor_usuario'] = get_string('error_invalid_user', 'local_solicitacoes');
                    break;
                }
            }
        }

        // Validar se o papel é válido para contexto de curso
        if (!empty($data['papel'])) {
            $allowed_roles = local_solicitacoes_get_allowed_roles();
            if (!array_key_exists($data['papel'], $allowed_roles)) {
                $errors['papel'] = get_string('error_papel_invalid', 'local_solicitacoes');
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
        $record->papel = $data->papel;                   // Papel selecionado para o professor
        $record->timecreated = time();                  // Campo correto: timecreated (não data_criacao) 
        $record->timemodified = time();                 // Campo obrigatório que estava faltando
        $record->codigo_disciplina = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($data->codigo_disciplina))));
        $record->nome_disciplina   = trim($data->nome_disciplina);
        $record->turma             = trim($data->turma);
        // Mantém campos legados preenchidos para compatibilidade com código antigo
        $record->codigo_sigaa      = $record->codigo_disciplina;
        $record->course_shortname  = $record->nome_disciplina;
        $record->course_summary = $data->course_summary;
        $record->unidade_academica_id = $data->category;  // Campo correto: unidade_academica_id (não category_id)
        $record->ano_semestre = get_config('local_solicitacoes', 'ano_semestre') ?: '';
        $record->razoes_criacao = $data->razoes_criacao;
        
        $solicitacao_id = $DB->insert_record('local_solicitacoes', $record);

        // Salvar professores da disciplina (um ou mais usuários selecionados)
        if (!empty($data->professor_usuario)) {
            $prof_ids = is_array($data->professor_usuario) ? $data->professor_usuario : [$data->professor_usuario];
            $now = time();
            foreach ($prof_ids as $prof_id) {
                $prof_id = (int)$prof_id;
                if ($prof_id <= 0) continue;
                $rel = new stdClass();
                $rel->solicitacao_id = $solicitacao_id;
                $rel->usuario_id     = $prof_id;
                $rel->timecreated    = $now;
                $DB->insert_record('local_usuarios_solicitacoes', $rel);
            }
        }

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
