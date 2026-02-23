<?php
// ESTE ARQUIVO NÃO ESTÁ MAIS SENDO UTILIZADO
// O formulário agora é renderizado via template Mustache: templates/form_solicitacao.mustache
// Processamento feito diretamente em: nova-solicitacao.php
// Este arquivo foi mantido apenas como referência/backup

namespace local_solicitacoes\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class request_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        // Tipo de ação
        $acoes = array(
            'inscricao' => get_string('acao_inscricao', 'local_solicitacoes'),
            'remocao' => get_string('acao_remocao', 'local_solicitacoes'),
            'suspensao' => get_string('acao_suspensao', 'local_solicitacoes')
        );
        $mform->addElement('select', 'tipo_acao', get_string('tipo_acao', 'local_solicitacoes'), $acoes);
        $mform->addRule('tipo_acao', get_string('required'), 'required', null, 'client');

        // Campo text para curso com Tom Select
        $mform->addElement('text', 'curso_search', get_string('curso_nome', 'local_solicitacoes'), 
            array('placeholder' => 'Digite para buscar cursos...'));
        $mform->setType('curso_search', PARAM_TEXT);
        $mform->addHelpButton('curso_search', 'curso_nome', 'local_solicitacoes');
        
        // Campo oculto para ID do curso selecionado
        $mform->addElement('hidden', 'curso_id_selected');
        $mform->setType('curso_id_selected', PARAM_INT);
        
        // Campo text para usuários com Tom Select
        $mform->addElement('text', 'usuarios_search', get_string('usuarios_busca', 'local_solicitacoes'), 
            array('placeholder' => 'Digite para buscar usuários...'));
        $mform->setType('usuarios_search', PARAM_TEXT);
        $mform->addHelpButton('usuarios_search', 'usuarios_busca', 'local_solicitacoes');
        
        // Campo oculto para IDs dos usuários selecionados
        $mform->addElement('hidden', 'usuarios_ids_selected');
        $mform->setType('usuarios_ids_selected', PARAM_TEXT);

        // Campos ocultos para compatibilidade e debug
        $mform->addElement('hidden', 'usuarios_nomes');
        $mform->setType('usuarios_nomes', PARAM_TEXT);
        
        // Campo de debug para verificar submissão
        $mform->addElement('hidden', 'form_submitted');
        $mform->setType('form_submitted', PARAM_TEXT);
        $mform->setDefault('form_submitted', '1');

        // Papel (apenas para inscrição) - buscar do banco de dados
        global $DB;
        $papeis = array('' => 'Selecione...');
        
        // Buscar roles atribuíveis em contexto de curso
        $roles = $DB->get_records_sql(
            "SELECT r.id, r.shortname, r.name 
             FROM {role} r
             JOIN {role_context_levels} rcl ON rcl.roleid = r.id
             WHERE rcl.contextlevel = :contextlevel
             AND r.archetype IN ('student', 'teacher', 'editingteacher', 'manager')
             ORDER BY r.sortorder",
            array('contextlevel' => CONTEXT_COURSE)
        );
        
        foreach ($roles as $role) {
            // Usar role_get_name() para obter o nome traduzido corretamente
            $papeis[$role->shortname] = role_get_name($role);
        }
        
        $mform->addElement('select', 'papel', get_string('papel', 'local_solicitacoes'), $papeis);
        $mform->addHelpButton('papel', 'papel', 'local_solicitacoes');
        $mform->hideIf('papel', 'tipo_acao', 'neq', 'inscricao');

        // Observações
        $mform->addElement('textarea', 'observacoes', get_string('observacoes', 'local_solicitacoes'), 
            'wrap="virtual" rows="4" cols="50"');
        $mform->setType('observacoes', PARAM_TEXT);
        $mform->addHelpButton('observacoes', 'observacoes', 'local_solicitacoes');

        $this->add_action_buttons(true, get_string('request_submit', 'local_solicitacoes'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Debug - ver dados recebidos
        error_log("Form validation - data received: " . print_r($data, true));

        // Validar papel obrigatório para inscrição
        if ($data['tipo_acao'] == 'inscricao' && empty($data['papel'])) {
            $errors['papel'] = 'Campo obrigatório';
        }

        return $errors;
    }
}