<?php
/**
 * Página principal do plugin - redireciona para seleção de ação
 *
 * @package    local_solicitacoes
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();

// Redirecionar para a página de seleção de ação
redirect(new moodle_url('/local/solicitacoes/selecionar-acao.php'));