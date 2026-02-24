<?php
// Verificar sintaxe do arquivo de idioma
$arquivo_idioma = __DIR__ . '/lang/pt_br/local_solicitacoes.php';

echo "<h2>Verificação do Arquivo de Idioma</h2>";

// Verificar se o arquivo existe
if (!file_exists($arquivo_idioma)) {
    echo "<p style='color: red;'>ERRO: Arquivo não encontrado: $arquivo_idioma</p>";
    exit;
}

// Verificar sintaxe PHP
$output = array();
$return_var = 0;
exec("php -l " . escapeshellarg($arquivo_idioma) . " 2>&1", $output, $return_var);

echo "<h3>Sintaxe PHP:</h3>";
if ($return_var === 0) {
    echo "<p style='color: green;'>✓ Sintaxe OK</p>";
} else {
    echo "<p style='color: red;'>✗ Erro de sintaxe:</p>";
    echo "<pre>" . implode("\n", $output) . "</pre>";
}

// Testar carregamento das strings específicas
echo "<h3>Teste de Strings:</h3>";

try {
    // Incluir o arquivo manualmente para testar
    $string = array();
    include $arquivo_idioma;
    
    $strings_teste = ['no_users_found', 'form_remocao_descricao', 'form_inscricao_descricao'];
    
    foreach ($strings_teste as $key) {
        if (isset($string[$key])) {
            echo "<p style='color: green;'>✓ '$key': " . htmlspecialchars($string[$key]) . "</p>";
        } else {
            echo "<p style='color: red;'>✗ '$key': NÃO ENCONTRADA</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERRO ao carregar strings: " . $e->getMessage() . "</p>";
}

// Testar através do get_string do Moodle
echo "<h3>Teste via get_string():</h3>";
require('../../config.php');

$strings_teste = ['no_users_found', 'form_remocao_descricao', 'form_inscricao_descricao'];

foreach ($strings_teste as $key) {
    try {
        $valor = get_string($key, 'local_solicitacoes');
        echo "<p style='color: green;'>✓ get_string('$key'): " . htmlspecialchars($valor) . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ get_string('$key'): " . $e->getMessage() . "</p>";
    }
}
?>