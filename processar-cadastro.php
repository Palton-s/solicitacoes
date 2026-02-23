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
error_log("========== PROCESSAR-CADASTRO.PHP INICIADO ==========");
error_log("Processar Cadastro - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("Processar Cadastro - POST dados: " . print_r($_POST, true));

// Validar que é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Processar Cadastro - ERRO: Não é POST");
    redirect(new moodle_url('/local/solicitacoes/solicitar-cadastro.php'),
             'Método inválido', null, \core\output\notification::NOTIFY_ERROR);
    exit;
}

// Validar sesskey
try {
    require_sesskey();
    error_log("Processar Cadastro - Sesskey válido");
} catch (Exception $e) {
    error_log("Processar Cadastro - ERRO: Sesskey inválido - " . $e->getMessage());
    redirect(new moodle_url('/local/solicitacoes/solicitar-cadastro.php'),
             'Sesskey inválido', null, \core\output\notification::NOTIFY_ERROR);
    exit;
}

// Processar cancelamento
if (optional_param('cancel', 0, PARAM_BOOL)) {
    error_log("Processar Cadastro - Usuário cancelou");
    redirect(new moodle_url('/local/solicitacoes/selecionar-acao.php'));
    exit;
}

global $USER, $DB;

$errors = [];

// Coletar dados do formulário
$curso_id = required_param('curso_id_selected', PARAM_INT);
$papel = required_param('papel', PARAM_ALPHANUMEXT);
$firstname = required_param('firstname', PARAM_TEXT);
$lastname = required_param('lastname', PARAM_TEXT);
$cpf = required_param('cpf', PARAM_TEXT);
$email_novo_usuario = required_param('email_novo_usuario', PARAM_EMAIL);
$observacoes = optional_param('observacoes', '', PARAM_TEXT);

// Debug log
error_log("Processar Cadastro - Dados recebidos:");
error_log("  - curso_id: $curso_id");
error_log("  - papel: $papel");
error_log("  - firstname: $firstname");
error_log("  - lastname: $lastname");
error_log("  - cpf: $cpf");
error_log("  - email_novo_usuario: $email_novo_usuario");

// Validações
if (empty($curso_id) || $curso_id <= 0) {
    $errors[] = get_string('error_curso_required', 'local_solicitacoes');
    error_log("Processar Cadastro - ERRO: Curso vazio ou inválido");
}

// Validar papel
if (empty($papel)) {
    $errors[] = get_string('error_papel_required', 'local_solicitacoes');
    error_log("Processar Cadastro - ERRO: Papel vazio");
} else {
    $role_check = $DB->get_record('role', ['shortname' => $papel]);
    if (!$role_check) {
        $errors[] = get_string('error_papel_invalid', 'local_solicitacoes');
        error_log("Processar Cadastro - ERRO: Papel inválido: " . $papel);
    } else {
        error_log("Processar Cadastro - Papel válido: " . $papel);
    }
}

if (empty(trim($firstname))) {
    $errors[] = get_string('error_firstname_required', 'local_solicitacoes');
    error_log("Processar Cadastro - ERRO: Firstname vazio");
}

if (empty(trim($lastname))) {
    $errors[] = get_string('error_lastname_required', 'local_solicitacoes');
    error_log("Processar Cadastro - ERRO: Lastname vazio");
}

if (empty(trim($cpf))) {
    $errors[] = get_string('error_cpf_required', 'local_solicitacoes');
    error_log("Processar Cadastro - ERRO: CPF vazio");
}

if (empty(trim($email_novo_usuario))) {
    $errors[] = get_string('error_email_required', 'local_solicitacoes');
    error_log("Processar Cadastro - ERRO: Email vazio");
}

error_log("Processar Cadastro - Total de erros de validação: " . count($errors));

// Se houver erros, redirecionar de volta
if (!empty($errors)) {
    $error_message = implode('<br>', $errors);
    error_log("Processar Cadastro - Redirecionando com erros: " . $error_message);
    redirect(new moodle_url('/local/solicitacoes/solicitar-cadastro.php'),
             $error_message, null, \core\output\notification::NOTIFY_ERROR);
    exit;
}

// Processar solicitação
error_log("Processar Cadastro - Sem erros, processando solicitação...");
$data = new \stdClass();
$data->tipo_acao = 'cadastro';
$data->curso_id_selected = $curso_id;
$data->papel = $papel;
$data->firstname = trim($firstname);
$data->lastname = trim($lastname);
$data->cpf = trim($cpf);
$data->email_novo_usuario = trim($email_novo_usuario);
$data->observacoes = $observacoes;

error_log("Processar Cadastro - Chamando controller...");
$success = \local_solicitacoes\solicitacoes_controller::process_request_submission($data);
error_log("Processar Cadastro - Resultado do controller: " . ($success ? 'SUCESSO' : 'FALHA'));

if ($success) {
    error_log("Processar Cadastro - Redirecionando para confirmacao.php");
    redirect(new moodle_url('/local/solicitacoes/confirmacao.php'),
             get_string('success_submit', 'local_solicitacoes'), 
             null, 
             \core\output\notification::NOTIFY_SUCCESS);
} else {
    error_log("Processar Cadastro - Redirecionando de volta com erro");
    redirect(new moodle_url('/local/solicitacoes/solicitar-cadastro.php'),
             get_string('error_submit', 'local_solicitacoes'), 
             null, 
             \core\output\notification::NOTIFY_ERROR);
}
exit;
