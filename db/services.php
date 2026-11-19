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
 * Funções externas (AJAX) do plugin.
 *
 * Funcionam via lib/ajax/service.php com a sessão do usuário; não é preciso
 * habilitar os web services do site.
 *
 * @package    local_solicitacoes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_solicitacoes_search_courses' => [
        'classname'     => 'local_solicitacoes\external\search_courses',
        'methodname'    => 'execute',
        'description'   => 'Busca cursos para os formulários de solicitação.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/solicitacoes:submit',
    ],
    'local_solicitacoes_search_users' => [
        'classname'     => 'local_solicitacoes\external\search_users',
        'methodname'    => 'execute',
        'description'   => 'Busca usuários para os formulários de solicitação.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/solicitacoes:submit',
    ],
    'local_solicitacoes_get_similar_courses' => [
        'classname'     => 'local_solicitacoes\external\get_similar_courses',
        'methodname'    => 'execute',
        'description'   => 'Lista disciplinas existentes parecidas com solicitações de criação de curso.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/solicitacoes:manage',
    ],
];
