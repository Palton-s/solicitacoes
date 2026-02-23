<?php
/**
 * Script para limpar cache de strings do Moodle
 * Execute este arquivo acessando: /local/solicitacoes/clear_cache.php
 */

require('../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

// Limpar cache de strings
get_string_manager()->reset_caches();

// Limpar todos os caches
purge_all_caches();

echo "✅ Cache limpo com sucesso!<br>";
echo "As strings de idioma foram recarregadas.<br>";
echo "<br>";
echo "<a href='detalhes.php?id=1'>Voltar para visualização</a><br>";
echo "<a href='gerenciar.php'>Voltar para gerenciamento</a>";
