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

    require_once(__DIR__ . '/../lib.php');

    // Escape da query para SQL LIKE
    $searchterm = '%' . str_replace(array('%', '_'), array('\\%', '\\_'), $query) . '%';

    // Filtro de categorias configuradas no plugin
    [$cat_cond, $cat_params] = local_solicitacoes_get_allowed_categories_sql('ctx');

    $sql = "SELECT c.id, c.fullname, c.shortname, c.visible
            FROM {course} c
            JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :ctxcourse
            WHERE (c.fullname LIKE :term1 OR c.shortname LIKE :term2)
            AND c.id > 1
            AND $cat_cond
            ORDER BY c.visible DESC, c.fullname ASC";

    $params = array_merge(
        ['ctxcourse' => CONTEXT_COURSE, 'term1' => $searchterm, 'term2' => $searchterm],
        $cat_params
    );
    
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
        $courses = $DB->get_records_sql($sql, $params, 0, (int)$limit);
        
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
