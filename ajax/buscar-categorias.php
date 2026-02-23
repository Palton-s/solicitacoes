<?php

// Endpoint AJAX para busca de categorias de curso (unidades acadêmicas)
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
    error_log("Buscar Categorias - Query: '$query', Limit: $limit");
    
    // Validar parâmetros
    if (empty($query) || strlen($query) < 2) {
        error_log("Buscar Categorias - Query muito curta: '$query'");
        echo json_encode(array('debug' => 'Query muito curta'));
        exit;
    }
    
    if ($limit > 50) {
        $limit = 50;
    }
    
    global $DB;
    
    // Escape da query para SQL LIKE
    $searchterm = '%' . str_replace(array('%', '_'), array('\\%', '\\_'), $query) . '%';
    
    // SQL para buscar categorias reais do Moodle
    $sql = "SELECT c.id, c.name, c.path, c.visible, c.depth
            FROM {course_categories} c 
            WHERE (c.name LIKE ?)
            ORDER BY c.visible DESC, c.sortorder ASC";

    $params = array($searchterm);
    
    // Verificar se $DB está disponível
    if (!isset($DB)) {
        error_log("Buscar Categorias - \$DB não está disponível");
        echo json_encode(array('error' => 'Database não disponível'));
        exit;
    }
    
    try {
        error_log("Buscar Categorias - SQL: $sql");
        error_log("Buscar Categorias - Params: " . print_r($params, true));
        
        // Executar consulta no banco com limite
        $categories = $DB->get_records_sql($sql, $params, 0, $limit);
        
        error_log("Buscar Categorias - Categorias encontradas: " . count($categories));
        
        $results = array();
        foreach ($categories as $category) {
            // Construir o caminho hierárquico da categoria
            $categoryname = htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8');
            
            // Tentar obter o caminho completo
            $label = $categoryname;
            
            // Buscar categorias pai para construir o caminho
            if (!empty($category->path)) {
                $path_ids = explode('/', trim($category->path, '/'));
                if (count($path_ids) > 1) {
                    // Remover o próprio ID da categoria do caminho
                    array_pop($path_ids);
                    
                    if (!empty($path_ids)) {
                        // Buscar os nomes das categorias pai
                        list($insql, $inparams) = $DB->get_in_or_equal($path_ids);
                        $parent_sql = "SELECT id, name FROM {course_categories} WHERE id $insql ORDER BY depth ASC";
                        $parents = $DB->get_records_sql($parent_sql, $inparams);
                        
                        $path_parts = array();
                        foreach ($parents as $parent) {
                            $path_parts[] = htmlspecialchars($parent->name, ENT_QUOTES, 'UTF-8');
                        }
                        $path_parts[] = $categoryname;
                        
                        $label = implode(' > ', $path_parts);
                    }
                }
            }
            
            $results[] = array(
                'id' => (int)$category->id,
                'name' => $categoryname,
                'label' => $label,
                'visible' => (int)$category->visible === 1
            );
        }
        
        error_log("Buscar Categorias - Resultados processados: " . count($results));
        
    } catch (Exception $dbError) {
        error_log("Erro na consulta de categorias: " . $dbError->getMessage());
        echo json_encode(array('error' => 'Erro na consulta: ' . $dbError->getMessage()));
        exit;
    }
    
    // Retornar resultados em JSON
    echo json_encode($results);
    
} catch (Exception $e) {
    // Log do erro e retorno de erro genérico
    error_log("Erro no buscar-categorias.php: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'Erro interno'));
}
