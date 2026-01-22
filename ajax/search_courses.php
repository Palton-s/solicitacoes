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
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Debug - log dos parâmetros
    error_log("Search Courses - Query: '$query', Limit: $limit");
    
    // Validar parâmetros
    if (empty($query) || strlen($query) < 2) {
        error_log("Search Courses - Query muito curta: '$query'");
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
        error_log("Search Courses - \$DB não está disponível");
        echo json_encode(array('error' => 'Database não disponível'));
        exit;
    }
    
    try {
        error_log("Search Courses - SQL: $sql");
        error_log("Search Courses - Params: " . print_r($params, true));
        
        // Executar consulta no banco com limite
        $courses = $DB->get_records_sql($sql, $params, 0, $limit);
        
        error_log("Search Courses - Cursos encontrados: " . count($courses));
        
        $results = array();
        foreach ($courses as $course) {
            // Limpar strings para output seguro
            $fullname = htmlspecialchars($course->fullname, ENT_QUOTES, 'UTF-8');
            $shortname = htmlspecialchars($course->shortname, ENT_QUOTES, 'UTF-8');
            
            $results[] = array(
                'id' => (int)$course->id,
                'name' => $fullname,
                'shortname' => $shortname,
                'label' => $fullname . ' (' . $shortname . ')',
                'visible' => (int)$course->visible === 1
            );
        }
        
        error_log("Search Courses - Resultados processados: " . count($results));
        
    } catch (Exception $dbError) {
        error_log("Erro na consulta de cursos: " . $dbError->getMessage());
        echo json_encode(array('error' => 'Erro na consulta: ' . $dbError->getMessage()));
        exit;
    }
    
    // Retornar resultados em JSON
    echo json_encode($results);
    
} catch (Exception $e) {
    // Log do erro e retorno de erro genérico
    error_log("Erro no search_courses.php: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'Erro interno'));
}