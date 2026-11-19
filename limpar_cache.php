<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Limpeza do cache de strings de idioma (somente administradores do site).
 *
 * @package    local_solicitacoes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

// Somente administradores do site (inclui require_login).
require_admin();

$context = context_system::instance();
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/limpar_cache.php'));
$PAGE->set_title('Limpeza de cache - Strings de idioma');
$PAGE->set_heading('Limpeza de cache - Strings de idioma');

// Sem confirmação + sesskey válida, apenas pede confirmação (protege contra CSRF).
if (!$confirm || !confirm_sesskey()) {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        'Limpar o cache de strings de idioma? Em servidores lentos isso pode deixar as próximas páginas mais lentas por alguns instantes.',
        new moodle_url('/local/solicitacoes/limpar_cache.php', ['confirm' => 1, 'sesskey' => sesskey()]),
        new moodle_url('/local/solicitacoes/gerenciar.php')
    );
    echo $OUTPUT->footer();
    exit;
}

get_string_manager()->reset_caches(true);
cache::make('core', 'string')->purge();
cache::make('core', 'langmenu')->purge();

redirect(
    new moodle_url('/local/solicitacoes/gerenciar.php'),
    'Cache de strings de idioma limpo.',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
