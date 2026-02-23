<?php

// Endpoint AJAX para busca de cursos
define('AJAX_SCRIPT', true);

try {
    // Carregar configuração do Moodle
    require_once('../../../config.php');
    
    // Headers para AJAX
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Access-Control-Allow-Origin: *');
    
    // Obter parâmetros da URL
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Debug - log dos parâmetros
    error_log("Buscar Cursos - Query: '$query', Limit: $limit");
    
    // Validar parâmetros
    if (empty($query) || strlen($query) < 2) {
        error_log("Buscar Cursos - Query muito curta: '$query'");
        echo json_encode(array('debug' => 'Query muito curta'));
        exit;
    }
    
    if ($limit > 50) {
        $limit = 50;
    }
    
    global $DB;
    
    // Escape da query para SQL LIKE
    $searchterm = '%' . str_replace(array('%', '_'), array('\\%', '\\_'), $query) . '%';
    
    // SQL para buscar cursos reais do Moodle com filtro
    $sql = "SELECT c.id, c.fullname, c.shortname, c.visible
            FROM {course} c 
            WHERE (c.fullname LIKE ? OR c.shortname LIKE ?)
            AND c.id > ?
            ORDER BY c.visible DESC, c.fullname ASC";

    $params = array($searchterm, $searchterm, 1);
    
    // Verificar se $DB está disponível
    if (!isset($DB)) {
        error_log("Buscar Cursos - \$DB não está disponível");
        echo json_encode(array('error' => 'Database não disponível'));
        exit;
    }
    
    try {
        error_log("Buscar Cursos - SQL: $sql");
        error_log("Buscar Cursos - Params: " . print_r($params, true));
        
        // Executar consulta no banco com limite
        $courses = $DB->get_records_sql($sql, $params, 0, $limit);
        
        error_log("Buscar Cursos - Cursos encontrados: " . count($courses));
        
        $results = array();
        foreach ($courses as $course) {
            // Limpar strings para output seguro
            $fullname = htmlspecialchars($course->fullname, ENT_QUOTES, 'UTF-8');
            $shortname = htmlspecialchars($course->shortname, ENT_QUOTES, 'UTF-8');
            
            $results[] = array(
                'id' => (int)$course->id,
                'fullname' => $fullname,
                'shortname' => $shortname,
                'label' => $fullname . ' (' . $shortname . ')',
                'visible' => (int)$course->visible === 1
            );
        }
        
        error_log("Buscar Cursos - Resultados processados: " . count($results));
        
    } catch (Exception $dbError) {
        error_log("Erro na consulta de cursos: " . $dbError->getMessage());
        echo json_encode(array('error' => 'Erro na consulta: ' . $dbError->getMessage()));
        exit;
    }
    
    // Retornar resultados em JSON
    echo json_encode($results);
    
} catch (Exception $e) {
    // Log do erro e retorno de erro genérico
    error_log("Erro no buscar-cursos.php: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'Erro interno'));
}
