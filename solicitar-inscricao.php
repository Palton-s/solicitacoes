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
 * Formulário nativo do Moodle para inscrição de usuários
 */
class inscricao_form extends moodleform {

    protected function definition() {
        global $CFG, $DB;
        
        $mform = $this->_form;

        // Header 
        $mform->addElement('header', 'inscricao_details', get_string('form_inscricao_titulo', 'local_solicitacoes'));

        // Aviso/descrição
        $aviso_html = '<div class="alert alert-info" role="alert">' . 
                      '<i class="fas fa-info-circle"></i> ' . 
                      get_string('form_inscricao_descricao', 'local_solicitacoes') . 
                      '</div>';
        $mform->addElement('html', $aviso_html);

        // Campo de curso (por enquanto campo de texto, pode ser convertido para AJAX depois)
        $mform->addElement('text', 'curso_nome', get_string('curso_nome', 'local_solicitacoes'), ['size' => 50]);
        $mform->setType('curso_nome', PARAM_TEXT);
        $mform->addRule('curso_nome', null, 'required', null, 'client');
        $mform->addHelpButton('curso_nome', 'curso_nome_help', 'local_solicitacoes');

        // Papel no curso
        $systemcontext = context_system::instance();
        $all_roles = role_get_names($systemcontext, ROLENAME_ALIAS, false);
        $roles_options = array('' => get_string('select_role', 'local_solicitacoes'));
        
        foreach ($all_roles as $roleid => $rolename) {
            $role = $DB->get_record('role', ['id' => $roleid], 'shortname, name');
            if ($role) {
                $roles_options[$role->shortname] = role_get_name($role);
            }
        }
        
        $mform->addElement('select', 'papel', get_string('papel_label', 'local_solicitacoes'), $roles_options);
        $mform->addRule('papel', null, 'required', null, 'client');
        $mform->addHelpButton('papel', 'papel_help_dinamico', 'local_solicitacoes');

        // Campo de usuários (por enquanto textarea, pode ser convertido para AJAX depois)
        $mform->addElement('textarea', 'usuarios_busca', get_string('usuarios_busca', 'local_solicitacoes'), 
            ['wrap' => 'virtual', 'rows' => 3, 'cols' => 60]);
        $mform->setType('usuarios_busca', PARAM_TEXT);
        $mform->addRule('usuarios_busca', null, 'required', null, 'client');
        $mform->addHelpButton('usuarios_busca', 'usuarios_nomes_help', 'local_solicitacoes');

        // Observações
        $mform->addElement('textarea', 'observacoes', get_string('observacoes', 'local_solicitacoes'), 
            ['wrap' => 'virtual', 'rows' => 4, 'cols' => 60]);
        $mform->setType('observacoes', PARAM_TEXT);
        $mform->addHelpButton('observacoes', 'observacoes_help', 'local_solicitacoes');

        // Botões de ação
        $this->add_action_buttons(true, get_string('request_submit', 'local_solicitacoes'));
    }

    /**
     * Validação personalizada do formulário
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validar se os usuários foram informados
        if (empty($data['usuarios_busca']) || trim($data['usuarios_busca']) == '') {
            $errors['usuarios_busca'] = get_string('error_usuarios_required', 'local_solicitacoes');
        }

        return $errors;
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/solicitar-inscricao.php'));
$PAGE->set_title(get_string('form_inscricao_titulo', 'local_solicitacoes'));
$PAGE->set_heading(get_string('form_inscricao_titulo', 'local_solicitacoes'));

// Criar instância do formulário
$mform = new inscricao_form();

// Processar formulário se submetido  
if ($data = $mform->get_data()) {
    // Processar dados do formulário
    global $DB, $USER;
    
    try {
        $record = new stdClass();
        $record->userid = $USER->id;
        $record->tipo_acao = 'inscricao';
        $record->status = 'pendente';
        $record->timecreated = time();
        $record->timemodified = time();
        $record->papel = $data->papel;
        $record->observacoes = $data->observacoes;
        
        // Para simplificação inicial, vamos armazenar no campo observacoes
        $observacoes_completas = "CURSO: " . $data->curso_nome . "\n";
        $observacoes_completas .= "USUÁRIOS: " . $data->usuarios_busca . "\n";
        if (!empty($data->observacoes)) {
            $observacoes_completas .= "OBSERVAÇÕES: " . $data->observacoes;
        }
        $record->observacoes = $observacoes_completas;
        
        // Inserir a solicitação
        $solicitacao_id = $DB->insert_record('local_solicitacoes', $record);
        
        redirect(
            new moodle_url('/local/solicitacoes/confirmacao.php'),
            get_string('request_submitted', 'local_solicitacoes'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (Exception $e) {
        redirect(
            new moodle_url('/local/solicitacoes/solicitar-inscricao.php'),
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
