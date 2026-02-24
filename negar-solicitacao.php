<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

require_login();

// Função helper para get_string com fallback
if (!function_exists('get_string_with_fallback')) {
    function get_string_with_fallback($key, $component = 'moodle', $a = null) {
        $result = get_string($key, $component, $a);
        if ($result === "[[{$key}]]") {
            // Fallback para strings que não carregaram
            $fallbacks = [
                'motivo_negacao_placeholder' => 'Digite aqui o motivo pelo qual esta solicitação está sendo negada...',
                'motivo_negacao_help' => 'Este motivo será visível para o solicitante.'
            ];
            return isset($fallbacks[$key]) ? $fallbacks[$key] : $result;
        }
        return $result;
    }
}

$context = context_system::instance();
$id = required_param('id', PARAM_INT);

// Verificar permissão para gerenciar solicitações
if (!has_capability('local/solicitacoes:manage', $context)) {
    redirect(
        new moodle_url('/'),
        get_string('error_nopermission_manage', 'local_solicitacoes'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
    exit;
}

// Buscar solicitação
$request = $DB->get_record('local_solicitacoes', ['id' => $id], '*', MUST_EXIST);

/**
 * Formulário nativo do Moodle para negar solicitações
 */
class negar_solicitacao_form extends moodleform {
    
    private $request_data;
    private $cursos;
    private $usuarios;
    
    public function __construct($action = null, $request_data = null, $cursos = null, $usuarios = null) {
        $this->request_data = $request_data;
        $this->cursos = $cursos;
        $this->usuarios = $usuarios;
        parent::__construct($action);
    }

    protected function definition() {
        global $CFG, $DB;
        
        $mform = $this->_form;
        $request = $this->request_data;
        $cursos = $this->cursos;
        $usuarios = $this->usuarios;

        // Header 
        $mform->addElement('header', 'negacao_details', get_string('deny_request_title', 'local_solicitacoes'));

        // Informações da solicitação
        $info_html = '<div class="alert alert-warning" role="alert">';
        $info_html .= '<h6 class="mb-3"><i class="fas fa-exclamation-triangle"></i> ' . get_string('deny_request_warning', 'local_solicitacoes') . '</h6>';
        
        // Tipo de ação
        $acao_strings = [
            'inscricao' => get_string('acao_inscricao', 'local_solicitacoes'),
            'remocao' => get_string('acao_remocao', 'local_solicitacoes'),
            'suspensao' => get_string('acao_suspensao', 'local_solicitacoes'),
            'cadastro' => get_string('acao_cadastro', 'local_solicitacoes'),
            'criar_curso' => get_string('acao_criar_curso', 'local_solicitacoes')
        ];
        $acao_label = isset($acao_strings[$request->tipo_acao]) ? $acao_strings[$request->tipo_acao] : $request->tipo_acao;
        $info_html .= '<p><strong>' . get_string('action_type', 'local_solicitacoes') . ':</strong> ' . $acao_label . '</p>';
        
        // Cursos
        if (!empty($cursos)) {
            $cursos_list = [];
            foreach ($cursos as $curso) {
                $cursos_list[] = format_string($curso->fullname);
            }
            $info_html .= '<p><strong>' . get_string('course', 'local_solicitacoes') . '(s):</strong> ' . implode(', ', $cursos_list) . '</p>';
        }
        
        // Usuários
        if (!empty($usuarios)) {
            $info_html .= '<p><strong>' . get_string('target_users', 'local_solicitacoes') . ':</strong></p><ul class="mb-2">';
            foreach ($usuarios as $usuario) {
                $info_html .= '<li>' . fullname($usuario) . ' (' . $usuario->email . ')</li>';
            }
            $info_html .= '</ul>';
        }
        
        // Solicitante
        $solicitante = core_user::get_user($request->userid);
        $info_html .= '<p><strong>' . get_string('requester', 'local_solicitacoes') . ':</strong> ' . fullname($solicitante) . '</p>';
        
        $info_html .= '</div>';
        $mform->addElement('html', $info_html);

        // Campo para motivo da negação
        $mform->addElement('textarea', 'motivo_negacao', get_string('motivo_negacao_label', 'local_solicitacoes'), 
            array('rows' => 6, 'cols' => 60, 'placeholder' => get_string_with_fallback('motivo_negacao_placeholder', 'local_solicitacoes')));
        $mform->setType('motivo_negacao', PARAM_TEXT);
        $mform->addRule('motivo_negacao', null, 'required', null, 'client');
        $mform->addHelpButton('motivo_negacao', 'motivo_negacao_help', 'local_solicitacoes');

        // Botões de ação
        $this->add_action_buttons(true, get_string('confirm_deny', 'local_solicitacoes'));
    }

    /**
     * Validação personalizada do formulário
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validar se o motivo não está vazio
        if (empty(trim($data['motivo_negacao']))) {
            $errors['motivo_negacao'] = get_string('motivo_negacao_required', 'local_solicitacoes');
        }

        return $errors;
    }
}

// Buscar cursos relacionados
$sql_cursos = "SELECT c.id, c.fullname, c.shortname
               FROM {local_curso_solicitacoes} cs
               JOIN {course} c ON cs.curso_id = c.id
               WHERE cs.solicitacao_id = :id";
$cursos = $DB->get_records_sql($sql_cursos, array('id' => $id));

// Buscar usuários relacionados
$sql_usuarios = "SELECT u.id, u.firstname, u.lastname, u.email, u.username
                 FROM {local_usuarios_solicitacoes} us
                 JOIN {user} u ON us.usuario_id = u.id
                 WHERE us.solicitacao_id = :id";
$usuarios = $DB->get_records_sql($sql_usuarios, array('id' => $id));

// Verificar se já está negada
if ($request->status === 'negado') {
    redirect(
        new moodle_url('/local/solicitacoes/gerenciar.php'),
        'Esta solicitação já foi negada.',
        null,
        \core\output\notification::NOTIFY_INFO
    );
    exit;
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/negar-solicitacao.php', array('id' => $id)));
$PAGE->set_title(get_string('deny_request_title', 'local_solicitacoes'));
$PAGE->set_heading(get_string('deny_request_title', 'local_solicitacoes'));

// Criar instância do formulário
$mform = new negar_solicitacao_form(null, $request, $cursos, $usuarios);

// Processar formulário se submetido  
if ($data = $mform->get_data()) {
    // Atualizar solicitação
    $request->status = 'negado';
    $request->motivo_negacao = $data->motivo_negacao;
    $request->timemodified = time();
    $request->adminid = $USER->id;
    
    $DB->update_record('local_solicitacoes', $request);
    
    // Enviar notificação de negação
    local_solicitacoes_notify_negada($id);
    
    redirect(
        new moodle_url('/local/solicitacoes/gerenciar.php'),
        get_string('request_denied_success', 'local_solicitacoes'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Se foi cancelado
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/solicitacoes/detalhes.php', array('id' => $id)));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();?