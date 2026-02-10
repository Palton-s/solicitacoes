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
 * Version information for the Solicitações plugin.
 *
 * @package    local_solicitacoes
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_solicitacoes';
$plugin->version   = 2026020905;        // YYYYMMDDXX (data + versão do dia).
$plugin->requires  = 2022041900;        // Requer Moodle 4.0 ou superior.
$plugin->maturity  = MATURITY_STABLE;   // MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC ou MATURITY_STABLE.
$plugin->release   = 'v1.0.0';          // Versão legível para humanos.