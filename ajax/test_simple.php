<?php
// Teste ultra simples sem Moodle
header('Content-Type: application/json');

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query)) {
    echo json_encode(array('error' => 'Query vazia'));
    exit;
}

// Dados de teste
$results = array(
    array('id' => 1, 'name' => 'Curso de Teste 1', 'type' => 'course'),
    array('id' => 2, 'name' => 'Usuario Teste 1', 'type' => 'user'),
    array('id' => 3, 'name' => 'Curso de Matemática', 'type' => 'course'),
);

// Filtrar por query
$filtered = array();
foreach ($results as $item) {
    if (stripos($item['name'], $query) !== false) {
        $filtered[] = $item;
    }
}

echo json_encode($filtered);