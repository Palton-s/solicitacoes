<?php
require('../../config.php');

require_login();

echo "<h2>Forçar Recarregamento de Strings</h2>";

try {
    // 1. Atualizar versão do plugin para forçar reinstalação das strings
    $current_time = time();
    
    // 2. Deletar cache específico de strings do plugin
    if (class_exists('cache')) {
        $cache = cache::make('core', 'string');
        $cache->delete('en_local_solicitacoes');
        $cache->delete('pt_br_local_solicitacoes');
        $cache->purge();
        echo "<p style='color: green;'>✓ Cache de strings removido</p>";
    }
    
    // 3. Limpar diretamente do banco se necessário
    global $DB;
    
    // Verificar se há cache no banco
    $cache_records = $DB->get_records('cache_text', ['component' => 'local_solicitacoes']);
    if ($cache_records) {
        $DB->delete_records('cache_text', ['component' => 'local_solicitacoes']);
        echo "<p style='color: green;'>✓ Cache do banco removido (" . count($cache_records) . " registros)</p>";
    }
    
    // 4. Forçar reload das strings do arquivo físico
    $string_manager = get_string_manager();
    
    // Reset total do string manager
    if (method_exists($string_manager, 'reset_caches')) {
        $string_manager->reset_caches(true);
        echo "<p style='color: green;'>✓ String manager resetado</p>";
    }
    
    // 5. Testar carregamento das strings problemáticas
    echo "<h3>Teste Final:</h3>";
    
    $strings_teste = [
        'form_remocao_descricao' => 'Use este formulário para solicitar a remoção de usuários de um ou mais cursos.',
        'no_users_found' => 'Nenhum usuário encontrado',
        'observacoes_placeholder' => 'Digite observações adicionais sobre esta solicitação (opcional)...'
    ];
    
    foreach ($strings_teste as $key => $expected) {
        try {
            // Força reload do arquivo de strings
            $lang_file = $CFG->dirroot . '/local/solicitacoes/lang/pt_br/local_solicitacoes.php';
            if (file_exists($lang_file)) {
                $string = array();
                include($lang_file);
                
                if (isset($string[$key])) {
                    echo "<p style='color: green;'>✓ Arquivo: '$key' = " . htmlspecialchars($string[$key]) . "</p>";
                } else {
                    echo "<p style='color: red;'>✗ Arquivo: '$key' não encontrada</p>";
                }
            }
            
            // Testar via get_string 
            $valor = get_string($key, 'local_solicitacoes');
            if ($valor === "[[{$key}]]") {
                echo "<p style='color: orange;'>⚠ get_string('$key'): Ainda no cache antigo</p>";
            } else {
                echo "<p style='color: green;'>✓ get_string('$key'): " . htmlspecialchars($valor) . "</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ '$key': " . $e->getMessage() . "</p>";
        }
    }
    
    // 6. Instruções finais
    echo "<hr>";
    echo "<h3>Próximos Passos:</h3>";
    echo "<ol>";
    echo "<li><a href='solicitar-remocao.php' target='_blank'>Testar página de remoção</a></li>";
    echo "<li><a href='solicitar-inscricao.php' target='_blank'>Testar página de inscrição</a></li>";
    echo "<li>Se ainda não funcionar, reinicie o servidor web (Apache/Nginx)</li>";
    echo "</ol>";
    
    echo "<p><strong>Note:</strong> As correções implementadas nos arquivos PHP incluem fallbacks que devem resolver o problema mesmo que o cache não seja limpo completamente.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERRO: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>