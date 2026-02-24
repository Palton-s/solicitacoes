<?php
require('../../config.php');

global $DB;

echo "<h2>Debug das Tabelas</h2>";

// Verificar se as tabelas existem
$tables_to_check = ['local_solicitacoes', 'local_curso_solicitacoes', 'local_usuarios_solicitacoes'];

foreach ($tables_to_check as $table) {
    echo "<h3>Tabela: $table</h3>";
    
    try {
        $exists = $DB->get_manager()->table_exists($table);
        echo "<p>Existe: " . ($exists ? "SIM" : "NÃO") . "</p>";
        
        if ($exists) {
            $count = $DB->count_records($table);
            echo "<p>Registros: $count</p>";
            
            if ($count > 0) {
                $records = $DB->get_records($table, [], '', '*', 0, 5);
                echo "<pre>";
                print_r($records);
                echo "</pre>";
            }
        }
    } catch (Exception $e) {
        echo "<p>ERRO: " . $e->getMessage() . "</p>";
    }
}

// Verificar uma solicitação específica (ID 12)
echo "<h3>Detalhes da Solicitação ID 12</h3>";
try {
    $solicitacao = $DB->get_record('local_solicitacoes', ['id' => 12]);
    if ($solicitacao) {
        echo "<pre>";
        print_r($solicitacao);
        echo "</pre>";
        
        // Verificar cursos relacionados
        echo "<h4>Cursos relacionados</h4>";
        $cursos = $DB->get_records('local_curso_solicitacoes', ['solicitacao_id' => 12]);
        echo "<pre>";
        print_r($cursos);
        echo "</pre>";
        
        // Verificar usuários relacionados
        echo "<h4>Usuários relacionados</h4>";
        $usuarios = $DB->get_records('local_usuarios_solicitacoes', ['solicitacao_id' => 12]);
        echo "<pre>";
        print_r($usuarios);
        echo "</pre>";
    } else {
        echo "<p>Solicitação ID 12 não encontrada</p>";
    }
} catch (Exception $e) {
    echo "<p>ERRO ao buscar solicitação: " . $e->getMessage() . "</p>";
}
?>