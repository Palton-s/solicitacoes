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
 * Definições de cache (MUC) do plugin.
 *
 * @package    local_solicitacoes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Cursos/categorias a que o usuário logado tem acesso. Evita repetir as
    // consultas de matrícula e de papéis a cada tecla digitada na busca.
    'useraccess' => [
        'mode'       => cache_store::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => false,
        'ttl'        => 300,
    ],
    // Caminhos de contexto das categorias permitidas nas configurações do plugin.
    // A chave inclui o valor da configuração, então mudanças invalidam sozinhas.
    'allowedcatpaths' => [
        'mode'       => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl'        => 600,
    ],
];
