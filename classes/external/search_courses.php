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

namespace local_solicitacoes\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/solicitacoes/external_compat.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_solicitacoes\local\course_search;

/**
 * Web service: busca de cursos para os formulários de solicitação.
 *
 * @package    local_solicitacoes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_courses extends external_api {

    /**
     * Parâmetros.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'Termo de busca'),
            'scope' => new external_value(PARAM_ALPHA, 'Escopo: allowed, accessible ou all', VALUE_DEFAULT,
                course_search::SCOPE_ALLOWED),
            'limit' => new external_value(PARAM_INT, 'Máximo de resultados', VALUE_DEFAULT, 30),
        ]);
    }

    /**
     * Executa a busca.
     *
     * @param string $query
     * @param string $scope
     * @param int $limit
     * @return array
     */
    public static function execute(string $query, string $scope = course_search::SCOPE_ALLOWED, int $limit = 30): array {
        $params = self::validate_parameters(self::execute_parameters(),
            ['query' => $query, 'scope' => $scope, 'limit' => $limit]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/solicitacoes:submit', $context);

        if (!course_search::is_valid_scope($params['scope'])) {
            throw new \invalid_parameter_exception('scope');
        }

        // Preenche a cache de sessão com os acessos do usuário antes de liberar a sessão.
        if ($params['scope'] !== course_search::SCOPE_ALL) {
            course_search::get_user_access();
        }

        // Libera o lock da sessão: buscas em paralelo não ficam enfileiradas.
        \core\session\manager::write_close();

        $found = course_search::search($params['query'], $params['scope'], $params['limit']);

        $courses = [];
        foreach ($found['courses'] as $course) {
            $courses[] = [
                'id'      => (int)$course->id,
                'label'   => course_search::format_label($course),
                'visible' => !empty($course->visible),
            ];
        }

        return ['courses' => $courses, 'overflow' => $found['overflow']];
    }

    /**
     * Retorno.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courses' => new external_multiple_structure(new external_single_structure([
                'id'      => new external_value(PARAM_INT, 'ID do curso'),
                'label'   => new external_value(PARAM_RAW, 'Rótulo (HTML escapado)'),
                'visible' => new external_value(PARAM_BOOL, 'Curso visível'),
            ])),
            'overflow' => new external_value(PARAM_BOOL, 'Há mais resultados do que o limite'),
        ]);
    }
}
