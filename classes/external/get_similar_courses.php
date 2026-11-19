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
use local_solicitacoes\local\similar_courses;

/**
 * Web service: disciplinas similares às solicitações de criação de curso (tela de gerenciamento).
 *
 * @package    local_solicitacoes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_similar_courses extends external_api {

    /**
     * Parâmetros.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'requestids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'ID da solicitação')
            ),
        ]);
    }

    /**
     * Executa a verificação.
     *
     * @param int[] $requestids
     * @return array
     */
    public static function execute(array $requestids): array {
        $params = self::validate_parameters(self::execute_parameters(), ['requestids' => $requestids]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/solicitacoes:manage', $context);

        \core\session\manager::write_close();

        $found = similar_courses::find($params['requestids']);

        $formatopts = ['context' => $context];
        $result = [];
        foreach ($found as $requestid => $courses) {
            $items = [];
            foreach ($courses as $course) {
                $items[] = [
                    'id'    => $course['id'],
                    'label' => format_string($course['shortname'], true, $formatopts) . ' — ' .
                               format_string($course['fullname'], true, $formatopts),
                    'url'   => (new \moodle_url('/course/view.php', ['id' => $course['id']]))->out(false),
                ];
            }
            $result[] = ['requestid' => (int)$requestid, 'courses' => $items];
        }

        return $result;
    }

    /**
     * Retorno.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'requestid' => new external_value(PARAM_INT, 'ID da solicitação'),
            'courses'   => new external_multiple_structure(new external_single_structure([
                'id'    => new external_value(PARAM_INT, 'ID do curso'),
                'label' => new external_value(PARAM_RAW, 'Rótulo (HTML escapado)'),
                'url'   => new external_value(PARAM_URL, 'Link do curso'),
            ])),
        ]));
    }
}
