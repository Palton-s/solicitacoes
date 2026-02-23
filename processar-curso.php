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
error_log("========== PROCESSAR-CURSO.PHP INICIADO ==========");
error_log("Processar Curso - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("Processar Curso - POST dados: " . print_r($_POST, true));

// Validar que é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Processar Curso - ERRO: Não é POST");
    redirect(new moodle_url('/local/solicitacoes/solicitar-curso.php'),
             'Método inválido', null, \core\output\notification::NOTIFY_ERROR);
    exit;
}

// Validar sesskey
try {
    require_sesskey();
    error_log("Processar Curso - Sesskey válido");
} catch (Exception $e) {
    error_log("Processar Curso - ERRO: Sesskey inválido - " . $e->getMessage());
    redirect(new moodle_url('/local/solicitacoes/solicitar-curso.php'),
             'Sesskey inválido', null, \core\output\notification::NOTIFY_ERROR);
    exit;
}

// Processar cancelamento
if (optional_param('cancel', 0, PARAM_BOOL)) {
    error_log("Processar Curso - Usuário cancelou");
    redirect(new moodle_url('/local/solicitacoes/selecionar-acao.php'));
    exit;
}

global $USER, $DB;

$errors = [];

// Coletar dados do formulário
$codigo_sigaa = required_param('codigo_sigaa', PARAM_TEXT);
$course_shortname = required_param('course_shortname', PARAM_TEXT);
$unidade_academica_id = required_param('unidade_academica_id', PARAM_INT);
$ano_semestre = required_param('ano_semestre', PARAM_TEXT);
$course_summary = optional_param('course_summary', '', PARAM_RAW);
$razoes_criacao = required_param('razoes_criacao', PARAM_TEXT);

// Debug log
error_log("Processar Curso - Dados recebidos:");
error_log("  - codigo_sigaa: $codigo_sigaa");
error_log("  - course_shortname: $course_shortname");
error_log("  - unidade_academica_id: $unidade_academica_id");
error_log("  - ano_semestre: $ano_semestre");
error_log("  - razoes_criacao: " . substr($razoes_criacao, 0, 100));

// Validações
if (empty(trim($codigo_sigaa))) {
    $errors[] = get_string('error_codigo_sigaa_required', 'local_solicitacoes');
    error_log("Processar Curso - ERRO: Código SIGAA vazio");
}

if (empty(trim($course_shortname))) {
    $errors[] = get_string('error_course_shortname_required', 'local_solicitacoes');
    error_log("Processar Curso - ERRO: Course shortname vazio");
}

if (empty($unidade_academica_id) || $unidade_academica_id <= 0) {
    $errors[] = get_string('error_unidade_required', 'local_solicitacoes');
    error_log("Processar Curso - ERRO: Unidade acadêmica vazia ou inválida");
}

if (empty(trim($ano_semestre))) {
    $errors[] = get_string('error_ano_semestre_required', 'local_solicitacoes');
    error_log("Processar Curso - ERRO: Ano/Semestre vazio");
}

if (empty(trim($razoes_criacao))) {
    $errors[] = get_string('error_razoes_criacao_required', 'local_solicitacoes');
    error_log("Processar Curso - ERRO: Razões da criação vazias");
}

error_log("Processar Curso - Total de erros de validação: " . count($errors));

// Se houver erros, redirecionar de volta
if (!empty($errors)) {
    $error_message = implode('<br>', $errors);
    error_log("Processar Curso - Redirecionando com erros: " . $error_message);
    redirect(new moodle_url('/local/solicitacoes/solicitar-curso.php'),
             $error_message, null, \core\output\notification::NOTIFY_ERROR);
    exit;
}

// Processar solicitação
error_log("Processar Curso - Sem erros, processando solicitação...");
$data = new \stdClass();
$data->tipo_acao = 'criar_curso';
$data->codigo_sigaa = trim($codigo_sigaa);
$data->course_shortname = trim($course_shortname);
$data->unidade_academica_id = $unidade_academica_id;
$data->ano_semestre = trim($ano_semestre);
$data->course_summary = $course_summary;
$data->razoes_criacao = trim($razoes_criacao);

error_log("Processar Curso - Chamando controller...");
$success = \local_solicitacoes\solicitacoes_controller::process_request_submission($data);
error_log("Processar Curso - Resultado do controller: " . ($success ? 'SUCESSO' : 'FALHA'));

if ($success) {
    error_log("Processar Curso - Redirecionando para confirmacao.php");
    redirect(new moodle_url('/local/solicitacoes/confirmacao.php'),
             get_string('success_submit', 'local_solicitacoes'), 
             null, 
             \core\output\notification::NOTIFY_SUCCESS);
} else {
    error_log("Processar Curso - Redirecionando de volta com erro");
    redirect(new moodle_url('/local/solicitacoes/solicitar-curso.php'),
             get_string('error_submit', 'local_solicitacoes'), 
             null, 
             \core\output\notification::NOTIFY_ERROR);
}
exit;
