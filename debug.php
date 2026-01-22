<?php
require('../../config.php');
require_login();

$context = context_system::instance();

// Verificar permissão para gerenciar solicitações
if (!has_capability('local/solicitacoes:manage', $context)) {
    redirect(
        new moodle_url('/'),
        get_string('error_nopermission_manage', 'local_solicitacoes'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/debug.php'));
$PAGE->set_title('Debug - Estrutura da Tabela');
$PAGE->set_heading('Debug - Estrutura da Tabela');

echo $OUTPUT->header();

echo '<h2>Diagnóstico da Tabela local_solicitacoes</h2>';

global $DB;

try {
    // Verificar se a tabela existe
    $dbman = $DB->get_manager();
    $table = new xmldb_table('local_solicitacoes');
    
    if ($dbman->table_exists($table)) {
        echo '<p style="color: green;">✓ Tabela local_solicitacoes existe</p>';
        
        // Listar colunas
        echo '<h3>Colunas da tabela:</h3>';
        $columns = $DB->get_columns('local_solicitacoes');
        echo '<ul>';
        foreach ($columns as $column) {
            echo '<li><strong>' . $column->name . '</strong> - ' . $column->type . 
                 ($column->max_length ? ' (' . $column->max_length . ')' : '') .
                 ($column->not_null ? ' NOT NULL' : ' NULL') .
                 ($column->default_value !== null ? ' DEFAULT: ' . $column->default_value : '') .
                 '</li>';
        }
        echo '</ul>';
        
        // Testar inserção simples
        echo '<h3>Teste de inserção:</h3>';
        try {
            $record = new stdClass();
            $record->userid = $USER->id;
            $record->timecreated = time();
            $record->timemodified = time();
            $record->tipo_acao = 'inscricao';
            $record->curso_nome = 'Teste Debug';
            $record->usuarios_nomes = 'Usuário Teste';
            $record->papel = 'student';
            $record->observacoes = 'Teste de debug';
            $record->status = 'pendente';
            $record->adminid = null;
            
            $id = $DB->insert_record('local_solicitacoes', $record, true);
            echo '<p style="color: green;">✓ Teste de inserção bem-sucedido. ID: ' . $id . '</p>';
            
            // Remover o registro de teste
            $DB->delete_records('local_solicitacoes', array('id' => $id));
            echo '<p style="color: blue;">ℹ Registro de teste removido</p>';
            
        } catch (Exception $e) {
            echo '<p style="color: red;">✗ Erro ao inserir: ' . $e->getMessage() . '</p>';
        }
        
    } else {
        echo '<p style="color: red;">✗ Tabela local_solicitacoes NÃO existe</p>';
    }
    
} catch (Exception $e) {
    echo '<p style="color: red;">Erro: ' . $e->getMessage() . '</p>';
}

echo '<hr>';
echo '<p><a href="' . new moodle_url('/local/solicitacoes/index.php') . '">← Voltar ao formulário</a></p>';

echo $OUTPUT->footer();