<?php
require('../../config.php');
require_login();

echo "<h2>Limpeza de Cache - Strings de Idioma</h2>";

try {
    // Limpar cache de strings de idioma usando a API padrão do Moodle
    $stringmanager = get_string_manager();
    
    // Reset all caches
    if (method_exists($stringmanager, 'reset_caches')) {
        $stringmanager->reset_caches(true);
        echo "<p style='color: green;'>✓ Cache do string manager limpo</p>";
    }
    
    // Limpar cache MUC se disponível
    if (class_exists('cache')) {
        // Cache de strings
        try {
            $cache = cache::make('core', 'string');
            $cache->purge();
            echo "<p style='color: green;'>✓ Cache MUC de strings limpo</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange'>⚠ Cache MUC de strings: " . $e->getMessage() . "</p>";
        }
        
        // Cache de idioma
        try {
            $cache = cache::make('core', 'langmenu');  
            $cache->purge();
            echo "<p style='color: green;'>✓ Cache de menu de idioma limpo</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange'>⚠ Cache de menu de idioma: " . $e->getMessage() . "</p>";
        }
    }
    
    // Forçar recarga das strings testando algumas
    echo "<h3>Teste de Strings Após Limpeza:</h3>";
    
    $strings_teste = ['no_users_found', 'form_remocao_descricao', 'form_inscricao_descricao'];
    
    foreach ($strings_teste as $key) {
        try {
            $valor = get_string($key, 'local_solicitacoes');
            echo "<p style='color: green;'>✓ '$key': " . htmlspecialchars($valor) . "</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ '$key': " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>Links para Testar:</h3>";
    echo "<p><a href='solicitar-remocao.php' style='padding: 10px; background: #007cba; color: white; text-decoration: none; border-radius: 4px;'>Testar Página de Remoção</a></p>";
    echo "<p><a href='solicitar-inscricao.php' style='padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;'>Testar Página de Inscrição</a></p>";
    
    echo "<hr>";
    echo "<h3>Manual - Se ainda não funcionar:</h3>";
    echo "<ol>";
    echo "<li>Acesse: <strong>Administração do Site > Desenvolvimento > Limpar caches</strong></li>";
    echo "<li>Clique em <strong>Limpar todos os caches</strong></li>";
    echo "<li>Ou execute no terminal do servidor: <code>php admin/cli/purge_caches.php</code></li>";
    echo "</ol>";

} catch (Exception $e) {
    echo "<p style='color: red;'>ERRO: " . $e->getMessage() . "</p>";
}
?>