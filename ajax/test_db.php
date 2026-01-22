<?php
// Script para verificar estrutura da tabela
require_once('../../../config.php');

header('Content-Type: application/json');

try {
    global $DB;
    
    // Verificar se a tabela existe
    $dbman = $DB->get_manager();
    $table_exists = $dbman->table_exists('local_solicitacoes');
    
    $result = array(
        'table_exists' => $table_exists,
        'columns' => array()
    );
    
    if ($table_exists) {
        // Buscar estrutura da tabela
        $columns = $DB->get_columns('local_solicitacoes');
        $result['columns'] = array_keys($columns);
        
        // Testar inserção simples
        $test_record = new stdClass();
        $test_record->userid = 1;
        $test_record->timecreated = time();
        $test_record->timemodified = time();
        $test_record->tipo_acao = 'inscricao';
        $test_record->curso_nome = 'Teste';
        $test_record->usuarios_nomes = 'Teste User';
        $test_record->status = 'pendente';
        
        // Tentar inserir (vai falhar se estrutura estiver errada)
        $DB->insert_record('local_solicitacoes', $test_record);
        $result['test_insert'] = 'success';
        
        // Remover registro de teste
        $DB->delete_records('local_solicitacoes', array('curso_nome' => 'Teste'));
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(array(
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ));
}