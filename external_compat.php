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
 * Compatibilidade da API de funções externas entre Moodle 4.0/4.1 e 4.2+.
 *
 * A partir do Moodle 4.2 as classes ficam em \core_external\*. Nas versões
 * anteriores, elas são globais (lib/externallib.php). Este arquivo cria
 * aliases para que o plugin use sempre os nomes novos.
 *
 * @package    local_solicitacoes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!class_exists('\core_external\external_api')) {
    global $CFG;
    require_once($CFG->libdir . '/externallib.php');

    $localsolicitacoesexternalclasses = [
        'external_api',
        'external_function_parameters',
        'external_value',
        'external_single_structure',
        'external_multiple_structure',
    ];
    foreach ($localsolicitacoesexternalclasses as $localsolicitacoesclass) {
        if (!class_exists('core_external\\' . $localsolicitacoesclass, false)) {
            class_alias($localsolicitacoesclass, 'core_external\\' . $localsolicitacoesclass);
        }
    }
    unset($localsolicitacoesexternalclasses, $localsolicitacoesclass);
}
