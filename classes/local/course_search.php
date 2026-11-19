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
 * Busca de cursos otimizada para ambientes lentos.
 *
 * Em vez de carregar todos os cursos acessíveis na página, a busca é feita
 * sob demanda (AJAX) com LIMIT no banco, e a validação do formulário confere
 * apenas os IDs selecionados.
 *
 * Escopos:
 *  - SCOPE_ALLOWED:    cursos acessíveis ao usuário E dentro das categorias
 *                      permitidas nas configurações (inscrição, cadastro).
 *  - SCOPE_ACCESSIBLE: cursos acessíveis ao usuário, sem filtro de categoria
 *                      (remoção, suspensão).
 *  - SCOPE_ALL:        qualquer curso (remover curso).
 *
 * "Acessível" = admin ou papel no contexto do sistema veem tudo; demais veem
 * cursos com matrícula ativa + cursos de categorias onde têm papel atribuído.
 *
 * @package    local_solicitacoes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_search {

    /** @var string Acessíveis + filtro de categorias do plugin. */
    const SCOPE_ALLOWED = 'allowed';
    /** @var string Acessíveis, sem filtro de categorias do plugin. */
    const SCOPE_ACCESSIBLE = 'accessible';
    /** @var string Todos os cursos. */
    const SCOPE_ALL = 'all';

    /** @var int Mínimo de caracteres para disparar a busca. */
    const MIN_CHARS = 3;
    /** @var int Máximo de resultados por busca. */
    const MAX_RESULTS = 50;

    /**
     * Verifica se o escopo é válido.
     *
     * @param string $scope
     * @return bool
     */
    public static function is_valid_scope(string $scope): bool {
        return in_array($scope, [self::SCOPE_ALLOWED, self::SCOPE_ACCESSIBLE, self::SCOPE_ALL], true);
    }

    /**
     * Busca cursos pelo termo digitado, respeitando o escopo e o acesso do usuário.
     *
     * @param string $query Termo digitado (cada palavra precisa aparecer no nome completo ou no nome breve).
     * @param string $scope Um dos SCOPE_*.
     * @param int $limit Máximo de resultados.
     * @return array ['courses' => stdClass[], 'overflow' => bool]
     */
    public static function search(string $query, string $scope, int $limit = 30): array {
        global $DB;

        $words = self::split_words($query);
        if (empty($words) || \core_text::strlen(trim($query)) < self::MIN_CHARS) {
            return ['courses' => [], 'overflow' => false];
        }
        $limit = max(1, min($limit, self::MAX_RESULTS));

        $params = [];
        $conditions = [];
        foreach ($words as $i => $word) {
            $term = '%' . $DB->sql_like_escape($word) . '%';
            $params["qf{$i}"] = $term;
            $params["qs{$i}"] = $term;
            $conditions[] = '(' . $DB->sql_like('c.fullname', ":qf{$i}", false) .
                            ' OR ' . $DB->sql_like('c.shortname', ":qs{$i}", false) . ')';
        }

        $access = self::build_access_sql($scope);
        if ($access === null) {
            return ['courses' => [], 'overflow' => false];
        }

        $sql = "SELECT c.id, c.fullname, c.shortname, c.visible, {$access['viacat']} AS viacat
                  FROM {course} c
                  {$access['join']}
                 WHERE c.id <> :siteid
                   AND " . implode(' AND ', $conditions) . "
                   AND {$access['where']}
              ORDER BY c.visible DESC, c.fullname ASC";
        $params = array_merge($params, $access['params'], ['siteid' => SITEID]);

        // Busca alguns registros a mais: cursos ocultos podem ser descartados abaixo
        // e o registro extra indica que há mais resultados (overflow).
        $records = $DB->get_records_sql($sql, $params, 0, $limit + 11);
        $courses = self::filter_hidden($records, $access['checkhidden']);

        $overflow = count($courses) > $limit;
        return ['courses' => array_slice($courses, 0, $limit, true), 'overflow' => $overflow];
    }

    /**
     * Retorna os IDs (do conjunto informado) que o usuário pode usar no escopo.
     * Usado na validação do formulário: consulta apenas os cursos selecionados.
     *
     * @param int[] $courseids
     * @param string $scope
     * @return int[] IDs válidos.
     */
    public static function filter_accessible(array $courseids, string $scope): array {
        global $DB;

        $courseids = array_values(array_unique(array_filter(array_map('intval', $courseids), function($id) {
            return $id > 0 && $id != SITEID;
        })));
        if (empty($courseids)) {
            return [];
        }

        $access = self::build_access_sql($scope);
        if ($access === null) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'selcrs');
        $sql = "SELECT c.id, c.visible, {$access['viacat']} AS viacat
                  FROM {course} c
                  {$access['join']}
                 WHERE c.id $insql
                   AND {$access['where']}";
        $records = $DB->get_records_sql($sql, array_merge($inparams, $access['params']));

        return array_map('intval', array_keys(self::filter_hidden($records, $access['checkhidden'])));
    }

    /**
     * Valida o valor submetido de um campo de cursos.
     *
     * @param mixed $value Valor do formulário (array ou string).
     * @param string $scope
     * @return string|null Mensagem de erro ou null se válido.
     */
    public static function validate_selection($value, string $scope): ?string {
        if (empty($value)) {
            return get_string('error_course_required', 'local_solicitacoes');
        }
        $selected = is_array($value) ? $value : explode(',', (string)$value);
        $selected = array_values(array_filter(array_map('intval', $selected)));
        if (empty($selected)) {
            return get_string('error_course_required', 'local_solicitacoes');
        }

        $valid = self::filter_accessible($selected, $scope);
        $invalid = array_diff($selected, $valid);
        if (!empty($invalid)) {
            return get_string('error_invalid_courses', 'local_solicitacoes', implode(', ', $invalid));
        }
        return null;
    }

    /**
     * Rótulo exibido para um curso (já escapado para HTML).
     *
     * @param \stdClass $course Precisa de fullname e shortname.
     * @return string
     */
    public static function format_label(\stdClass $course): string {
        $context = \context_system::instance();
        return format_string($course->fullname, true, ['context' => $context]) .
               ' (' . format_string($course->shortname, true, ['context' => $context]) . ')';
    }

    /**
     * Rótulo de um curso pelo ID (usado no valuehtmlcallback do autocomplete).
     *
     * @param int $courseid
     * @return string
     */
    public static function get_label(int $courseid): string {
        global $DB;
        if ($courseid <= 0) {
            return '';
        }
        $course = $DB->get_record('course', ['id' => $courseid], 'id, fullname, shortname');
        return $course ? self::format_label($course) : '';
    }

    /**
     * Separa o termo em palavras (máx. 5) para busca.
     *
     * @param string $query
     * @return string[]
     */
    protected static function split_words(string $query): array {
        $words = preg_split('/\s+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        return array_slice($words ?: [], 0, 5);
    }

    /**
     * Remove cursos ocultos que o usuário só enxergaria por matrícula sem ter
     * permissão de ver cursos ocultos (mesmo comportamento de enrol_get_users_courses).
     *
     * @param \stdClass[] $records
     * @param bool $checkhidden
     * @return \stdClass[]
     */
    protected static function filter_hidden(array $records, bool $checkhidden): array {
        if (!$checkhidden) {
            return $records;
        }
        foreach ($records as $id => $record) {
            if (empty($record->visible) && empty($record->viacat)) {
                $context = \context_course::instance($id, IGNORE_MISSING);
                if (!$context || !has_capability('moodle/course:viewhiddencourses', $context)) {
                    unset($records[$id]);
                }
            }
        }
        return $records;
    }

    /**
     * Monta a parte do SQL que restringe os cursos ao que o usuário pode ver.
     *
     * @param string $scope
     * @return array|null null se o usuário não tem acesso a nenhum curso. Caso contrário:
     *         ['join' => string, 'where' => string, 'params' => array, 'viacat' => string, 'checkhidden' => bool]
     */
    protected static function build_access_sql(string $scope): ?array {
        global $DB;

        if (!self::is_valid_scope($scope)) {
            throw new \coding_exception('Escopo de busca de cursos inválido: ' . $scope);
        }

        $where = [];
        $params = [];
        $needctx = false;
        $viacat = '0';
        $checkhidden = false;

        if ($scope !== self::SCOPE_ALL) {
            $access = self::get_user_access();

            if (!$access->unrestricted) {
                $or = [];
                if (!empty($access->enrolled)) {
                    [$insql, $inparams] = $DB->get_in_or_equal($access->enrolled, SQL_PARAMS_NAMED, 'enrcrs');
                    $or[] = "c.id $insql";
                    $params = array_merge($params, $inparams);
                }
                if (!empty($access->catpaths)) {
                    $needctx = true;
                    // Os parâmetros nomeados não podem se repetir no SQL, então a
                    // condição é gerada duas vezes com prefixos diferentes.
                    [$catwhere, $catparams] = self::paths_condition($access->catpaths, 'ucat');
                    [$catcase, $caseparams] = self::paths_condition($access->catpaths, 'ucase');
                    $or[] = $catwhere;
                    $viacat = "CASE WHEN $catcase THEN 1 ELSE 0 END";
                    $params = array_merge($params, $catparams, $caseparams);
                }
                if (empty($or)) {
                    return null;
                }
                $where[] = '(' . implode(' OR ', $or) . ')';
                $checkhidden = true;
            }

            if ($scope === self::SCOPE_ALLOWED) {
                [$allowedcond, $allowedparams] = local_solicitacoes_get_allowed_categories_sql('ctx');
                if ($allowedcond === '1=0') {
                    return null;
                }
                if ($allowedcond !== '1=1') {
                    $needctx = true;
                    $where[] = $allowedcond;
                    $params = array_merge($params, $allowedparams);
                }
            }
        }

        $join = '';
        if ($needctx) {
            $join = 'JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :ctxcourselevel';
            $params['ctxcourselevel'] = CONTEXT_COURSE;
        }

        return [
            'join'        => $join,
            'where'       => empty($where) ? '1=1' : implode(' AND ', $where),
            'params'      => $params,
            'viacat'      => $viacat,
            'checkhidden' => $checkhidden,
        ];
    }

    /**
     * Gera "(ctx.path LIKE :p0 OR ctx.path LIKE :p1 ...)" para os caminhos de categoria.
     *
     * @param string[] $paths
     * @param string $prefix
     * @return array [sql, params]
     */
    protected static function paths_condition(array $paths, string $prefix): array {
        $conds = [];
        $params = [];
        foreach (array_values($paths) as $i => $path) {
            $params["{$prefix}{$i}"] = $path . '/%';
            $conds[] = "ctx.path LIKE :{$prefix}{$i}";
        }
        return ['(' . implode(' OR ', $conds) . ')', $params];
    }

    /**
     * Dados de acesso do usuário logado, guardados em cache de sessão (5 min).
     *
     * @return \stdClass {unrestricted: bool, enrolled: int[], catpaths: string[]}
     */
    public static function get_user_access(): \stdClass {
        global $USER;

        $cache = \cache::make('local_solicitacoes', 'useraccess');
        $key = 'u' . (int)$USER->id;
        $access = $cache->get($key);
        if ($access === false) {
            $access = self::load_user_access((int)$USER->id);
            $cache->set($key, $access);
        }
        return $access;
    }

    /**
     * Carrega do banco os dados de acesso do usuário (consultas leves, por índice de userid).
     *
     * @param int $userid
     * @return \stdClass
     */
    protected static function load_user_access(int $userid): \stdClass {
        global $DB;

        $access = (object)['unrestricted' => false, 'enrolled' => [], 'catpaths' => []];

        $systemcontext = \context_system::instance();
        if (is_siteadmin($userid) ||
                $DB->record_exists('role_assignments', ['userid' => $userid, 'contextid' => $systemcontext->id])) {
            $access->unrestricted = true;
            return $access;
        }

        // Matrículas ativas (mesmo critério de enrol_get_users_courses($userid, true)),
        // mas buscando só os IDs, sem carregar cursos e contextos inteiros.
        $now = round(time(), -2);
        $enrolled = $DB->get_fieldset_sql(
            "SELECT DISTINCT e.courseid
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
              WHERE ue.userid = :userid
                AND ue.status = :uestatus
                AND e.status = :estatus
                AND ue.timestart < :now1
                AND (ue.timeend = 0 OR ue.timeend > :now2)",
            [
                'userid'   => $userid,
                'uestatus' => ENROL_USER_ACTIVE,
                'estatus'  => ENROL_INSTANCE_ENABLED,
                'now1'     => $now,
                'now2'     => $now,
            ]
        );
        $access->enrolled = array_values(array_filter(array_map('intval', $enrolled), function($id) {
            return $id != SITEID;
        }));

        // Categorias onde o usuário tem algum papel.
        $access->catpaths = array_values(array_unique($DB->get_fieldset_sql(
            "SELECT ctx.path
               FROM {role_assignments} ra
               JOIN {context} ctx ON ctx.id = ra.contextid
              WHERE ra.userid = :userid AND ctx.contextlevel = :ctxcat",
            ['userid' => $userid, 'ctxcat' => CONTEXT_COURSECAT]
        )));

        return $access;
    }
}
