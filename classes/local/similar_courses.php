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

namespace local_solicitacoes\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/solicitacoes/lib.php');

/**
 * Detecção de disciplinas já existentes parecidas com uma solicitação de criação.
 *
 * Mesmo critério de antes (similar_text >= 90% contra nome breve ou nome
 * completo), mas executado via AJAX depois que a página carrega e com um
 * pré-filtro por tamanho que descarta matematicamente os cursos que nunca
 * poderiam atingir 90%, evitando milhões de chamadas ao similar_text.
 *
 * @package    local_solicitacoes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class similar_courses {

    /** @var float Similaridade mínima (%). */
    const THRESHOLD = 90.0;

    /** @var int Máximo de solicitações por chamada. */
    const MAX_REQUESTS = 200;

    /** @var int Máximo de cursos similares devolvidos por solicitação. */
    const MAX_MATCHES = 10;

    /**
     * Encontra cursos similares para as solicitações informadas.
     *
     * @param int[] $requestids
     * @return array requestid => [ ['id' => int, 'shortname' => string, 'fullname' => string], ... ]
     */
    public static function find(array $requestids): array {
        global $DB;

        $requestids = array_slice(array_values(array_unique(array_filter(array_map('intval', $requestids)))),
            0, self::MAX_REQUESTS);
        if (empty($requestids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($requestids, SQL_PARAMS_NAMED, 'req');
        $requests = $DB->get_records_sql(
            "SELECT id, nome_disciplina, course_shortname
               FROM {local_solicitacoes}
              WHERE id $insql AND tipo_acao = :tipo",
            array_merge($inparams, ['tipo' => 'criar_curso'])
        );

        $searches = [];
        foreach ($requests as $request) {
            $search = strtolower(trim($request->nome_disciplina ?? $request->course_shortname ?? ''));
            if ($search !== '') {
                $searches[$request->id] = $search;
            }
        }

        $result = array_fill_keys(array_keys($requests), []);
        if (empty($searches)) {
            return $result;
        }

        [$catcond, $catparams] = local_solicitacoes_get_allowed_categories_sql('ctx');
        if ($catcond === '1=0') {
            return $result;
        }

        // Recordset: não mantém todos os cursos em memória de uma vez.
        $rs = $DB->get_recordset_sql(
            "SELECT c.id, c.fullname, c.shortname
               FROM {course} c
               JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :ctxcourse
              WHERE c.id <> :siteid AND $catcond
           ORDER BY c.fullname ASC",
            array_merge(['ctxcourse' => CONTEXT_COURSE, 'siteid' => SITEID], $catparams)
        );

        $lengths = [];
        foreach ($searches as $rid => $search) {
            $lengths[$rid] = strlen($search);
        }

        foreach ($rs as $course) {
            $short = strtolower($course->shortname);
            $full = strtolower($course->fullname);
            $lshort = strlen($short);
            $lfull = strlen($full);

            foreach ($searches as $rid => $search) {
                if (count($result[$rid]) >= self::MAX_MATCHES) {
                    continue;
                }
                $ls = $lengths[$rid];
                if (self::can_match($ls, $lshort) && self::percent($search, $short) >= self::THRESHOLD ||
                        self::can_match($ls, $lfull) && self::percent($search, $full) >= self::THRESHOLD) {
                    $result[$rid][] = [
                        'id'        => (int)$course->id,
                        'shortname' => $course->shortname,
                        'fullname'  => $course->fullname,
                    ];
                }
            }
        }
        $rs->close();

        return $result;
    }

    /**
     * similar_text() devolve pct = 2 * comuns / (l1 + l2) * 100, e comuns <= min(l1, l2).
     * Logo, só é possível atingir o limite se 200 * min / (l1 + l2) >= THRESHOLD.
     *
     * @param int $l1
     * @param int $l2
     * @return bool
     */
    protected static function can_match(int $l1, int $l2): bool {
        if ($l1 === 0 || $l2 === 0) {
            return false;
        }
        return (200.0 * min($l1, $l2) / ($l1 + $l2)) >= self::THRESHOLD;
    }

    /**
     * Percentual de similaridade (similar_text).
     *
     * @param string $a
     * @param string $b
     * @return float
     */
    protected static function percent(string $a, string $b): float {
        $pct = 0.0;
        similar_text($a, $b, $pct);
        return $pct;
    }
}
