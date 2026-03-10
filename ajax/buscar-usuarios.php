<?php

// ⚠️ ARQUIVO OBSOLETO - NÃO É MAIS USADO
// Este arquivo foi substituído pelo sistema nativo do Moodle 'core_user/form_user_selector'
// Mantido apenas para compatibilidade ou caso seja necessário reverter
//
// Para usar este sistema novamente:
// 1. Altere 'ajax' => 'core_user/form_user_selector' para 'ajax' => 'local_solicitacoes/user_selector'
// 2. Remova o 'valuehtmlcallback' dos formulários
// 3. Remova a exigência da permissão moodle/user:viewdetails dos usuários

// Endpoint AJAX para busca de usuários
define('AJAX_SCRIPT', true);

try {
    // Carregar configuração do Moodle
    require_once('../../../config.php');

    require_login();
    require_sesskey();

    // Headers para AJAX
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    // Obter parâmetros da URL
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Debug - log dos parâmetros
    error_log("Buscar Usuários - Query: '$query', Limit: $limit");
    
    // Validar parâmetros
    if (empty($query) || strlen($query) < 2) {
        error_log("Buscar Usuários - Query muito curta: '$query'");
        echo json_encode(array('debug' => 'Query muito curta'));
        exit;
    }
    
    if ($limit > 50) {
        $limit = 50;
    }
    
    global $DB;
    
    // Verificar se $DB está disponível
    if (!isset($DB)) {
        error_log("Buscar Usuários - \$DB não está disponível");
        echo json_encode(array('error' => 'Database não disponível'));
        exit;
    }
    
    // Escape da query para SQL LIKE
    $searchterm = '%' . str_replace(array('%', '_'), array('\\%', '\\_'), $query) . '%';
    
    // SQL para buscar usuários reais do Moodle com filtro
    $sql = "SELECT u.id, u.firstname, u.lastname, u.username, u.email, u.suspended
            FROM {user} u 
            WHERE (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)
            AND u.deleted = ?
            AND u.confirmed = ?
            AND u.id > ?
            ORDER BY u.suspended ASC, u.lastname ASC, u.firstname ASC";

    $params = array($searchterm, $searchterm, $searchterm, $searchterm, 0, 1, 1);
    
    error_log("Buscar Usuários - SQL: $sql");
    error_log("Buscar Usuários - Params: " . print_r($params, true));
    
    try {
        // Executar consulta no banco com limite
        $users = $DB->get_records_sql($sql, $params, 0, $limit);
        
        error_log("Buscar Usuários - Usuários encontrados: " . count($users));
        
        $results = array();
        foreach ($users as $user) {
            // Limpar strings para output seguro
            $firstname = htmlspecialchars($user->firstname, ENT_QUOTES, 'UTF-8');
            $lastname = htmlspecialchars($user->lastname, ENT_QUOTES, 'UTF-8');
            $username = htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8');
            $email = htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8');
            
            $fullname = trim($firstname . ' ' . $lastname);
            
            $results[] = array(
                'id' => (int)$user->id,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'username' => $username,
                'email' => $email,
                'fullname' => $fullname,
                'label' => $fullname . ' (' . $username . ')',
                'display' => $fullname . ' - ' . $email,
                'suspended' => (int)$user->suspended === 1
            );
        }
        
        error_log("Buscar Usuários - Resultados processados: " . count($results));
        
    } catch (Exception $dbError) {
        error_log("Erro na consulta de usuários: " . $dbError->getMessage());
        echo json_encode(array('error' => 'Erro na consulta: ' . $dbError->getMessage()));
        exit;
    }
    
    // Retornar resultados em JSON
    echo json_encode($results);
    
} catch (Exception $e) {
    // Log do erro e retorno de erro genérico
    error_log("Erro no buscar-usuarios.php: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'Erro interno'));
}
