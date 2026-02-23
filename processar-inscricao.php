<?php
require('../../config.php');
require_once(__DIR__ . '/lib.php');

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

// Logs iniciais
error_log("========== PROCESSAR-INSCRICAO.PHP INICIADO ==========");
error_log("Processar Inscrição - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("Processar Inscrição - POST dados: " . print_r($_POST, true));

// Validar que é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Processar Inscrição - ERRO: Não é POST");
    redirect(new moodle_url('/local/solicitacoes/solicitar-inscricao.php'),
             'Método inválido', null, \core\output\notification::NOTIFY_ERROR);
    exit;
}

// Validar sesskey
try {
    require_sesskey();
    error_log("Processar Inscrição - Sesskey válido");
} catch (Exception $e) {
    error_log("Processar Inscrição - ERRO: Sesskey inválido - " . $e->getMessage());
    redirect(new moodle_url('/local/solicitacoes/solicitar-inscricao.php'),
             'Sesskey inválido', null, \core\output\notification::NOTIFY_ERROR);
    exit;
}

// Processar cancelamento
if (optional_param('cancel', 0, PARAM_BOOL)) {
    error_log("Processar Inscrição - Usuário cancelou");
    redirect(new moodle_url('/local/solicitacoes/selecionar-acao.php'));
    exit;
}

global $USER, $DB;

$errors = [];

// Coletar dados do formulário
$curso_id = required_param('curso_id_selected', PARAM_INT);
$usuarios_ids = required_param('usuarios_ids_selected', PARAM_TEXT);
$papel = required_param('papel', PARAM_ALPHANUMEXT);
$observacoes = optional_param('observacoes', '', PARAM_TEXT);

// Debug log
error_log("Processar Inscrição - Dados recebidos:");
error_log("  - curso_id: $curso_id");
error_log("  - usuarios_ids: $usuarios_ids");
error_log("  - papel: $papel");
error_log("  - observacoes: " . (empty($observacoes) ? '[vazio]' : substr($observacoes, 0, 50)));

// Validações
if (empty($curso_id) || $curso_id <= 0) {
    $errors[] = get_string('error_curso_required', 'local_solicitacoes');
    error_log("Processar Inscrição - ERRO: Curso vazio ou inválido");
}

if (empty(trim($usuarios_ids))) {
    $errors[] = get_string('error_usuarios_required', 'local_solicitacoes');
    error_log("Processar Inscrição - ERRO: Usuários vazio");
}

// Validar papel
if (empty($papel)) {
    $errors[] = get_string('error_papel_required', 'local_solicitacoes');
    error_log("Processar Inscrição - ERRO: Papel vazio");
} else {
    $role_check = $DB->get_record('role', ['shortname' => $papel]);
    if (!$role_check) {
        $errors[] = get_string('error_papel_invalid', 'local_solicitacoes');
        error_log("Processar Inscrição - ERRO: Papel inválido: " . $papel);
    } else {
        error_log("Processar Inscrição - Papel válido: " . $papel);
    }
}

error_log("Processar Inscrição - Total de erros de validação: " . count($errors));

// Se houver erros, redirecionar de volta
if (!empty($errors)) {
    $error_message = implode('<br>', $errors);
    error_log("Processar Inscrição - Redirecionando com erros: " . $error_message);
    redirect(new moodle_url('/local/solicitacoes/solicitar-inscricao.php'),
             $error_message, null, \core\output\notification::NOTIFY_ERROR);
    exit;
}

// Processar solicitação
error_log("Processar Inscrição - Sem erros, processando solicitação...");
$data = new \stdClass();
$data->tipo_acao = 'inscricao';
$data->curso_id_selected = $curso_id;
$data->usuarios_ids_selected = $usuarios_ids;
$data->papel = $papel;
$data->observacoes = $observacoes;

error_log("Processar Inscrição - Chamando controller...");
$success = \local_solicitacoes\solicitacoes_controller::process_request_submission($data);
error_log("Processar Inscrição - Resultado do controller: " . ($success ? 'SUCESSO' : 'FALHA'));

if ($success) {
    error_log("Processar Inscrição - Redirecionando para confirmacao.php");
    redirect(new moodle_url('/local/solicitacoes/confirmacao.php'),
             get_string('success_submit', 'local_solicitacoes'), 
             null, 
             \core\output\notification::NOTIFY_SUCCESS);
} else {
    error_log("Processar Inscrição - Redirecionando de volta com erro");
    redirect(new moodle_url('/local/solicitacoes/solicitar-inscricao.php'),
             get_string('error_submit', 'local_solicitacoes'), 
             null, 
             \core\output\notification::NOTIFY_ERROR);
}
exit;
