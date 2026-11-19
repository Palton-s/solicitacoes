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

/**
 * Busca de usuários leve, com mínimo de caracteres e LIMIT no banco.
 *
 * - Termo com "@": busca por e-mail que começa com o termo.
 * - Só dígitos (CPF, com ou sem pontuação): busca por username que começa
 *   com os dígitos (usa o índice de username).
 * - Demais: cada palavra precisa aparecer no nome, sobrenome, username ou e-mail.
 *
 * @package    local_solicitacoes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_search {

    /** @var int Mínimo de caracteres para disparar a busca. */
    const MIN_CHARS = 3;
    /** @var int Máximo de resultados por busca. */
    const MAX_RESULTS = 50;

    /**
     * Busca usuários.
     *
     * @param string $query
     * @param int $limit
     * @return array ['users' => stdClass[], 'overflow' => bool]
     */
    public static function search(string $query, int $limit = 30): array {
        global $DB, $CFG;

        $query = trim($query);
        if (\core_text::strlen($query) < self::MIN_CHARS) {
            return ['users' => [], 'overflow' => false];
        }
        $limit = max(1, min($limit, self::MAX_RESULTS));

        $params = [
            'guestid'   => $CFG->siteguest,
            'mnethost'  => $CFG->mnet_localhost_id,
        ];
        $conditions = [];

        $digits = preg_replace('/[\s.\-\/]/', '', $query);
        if (strpos($query, '@') !== false) {
            $params['email'] = $DB->sql_like_escape($query) . '%';
            $conditions[] = $DB->sql_like('u.email', ':email', false);
        } else if (ctype_digit($digits) && strlen($digits) >= self::MIN_CHARS) {
            // Só dígitos: LIKE direto (sem LOWER/COLLATE) para permitir uso do índice de username.
            $params['username'] = $digits . '%';
            $conditions[] = 'u.username LIKE :username';
        } else {
            $words = array_slice(preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY), 0, 5);
            foreach ($words as $i => $word) {
                $contains = '%' . $DB->sql_like_escape($word) . '%';
                $prefix = $DB->sql_like_escape($word) . '%';
                $params["wf{$i}"] = $contains;
                $params["wl{$i}"] = $contains;
                $params["wu{$i}"] = $prefix;
                $params["we{$i}"] = $prefix;
                $conditions[] = '(' .
                    $DB->sql_like('u.firstname', ":wf{$i}", false) . ' OR ' .
                    $DB->sql_like('u.lastname', ":wl{$i}", false) . ' OR ' .
                    $DB->sql_like('u.username', ":wu{$i}", false) . ' OR ' .
                    $DB->sql_like('u.email', ":we{$i}", false) .
                ')';
            }
        }

        $namefields = self::name_fields_sql('u');
        $sql = "SELECT u.id, u.username, u.email, u.suspended, $namefields
                  FROM {user} u
                 WHERE u.deleted = 0
                   AND u.id <> :guestid
                   AND u.mnethostid = :mnethost
                   AND " . implode(' AND ', $conditions) . "
              ORDER BY u.firstname ASC, u.lastname ASC, u.id ASC";

        $users = $DB->get_records_sql($sql, $params, 0, $limit + 1);
        $overflow = count($users) > $limit;

        return ['users' => array_slice($users, 0, $limit, true), 'overflow' => $overflow];
    }

    /**
     * Rótulo exibido para um usuário (escapado para HTML).
     *
     * @param \stdClass $user
     * @return string
     */
    public static function format_label(\stdClass $user): string {
        $label = s(fullname($user) . ' (' . $user->username . ') - ' . $user->email);
        if (!empty($user->suspended)) {
            $label .= ' [' . s(get_string('user_suspended_tag', 'local_solicitacoes')) . ']';
        }
        return $label;
    }

    /**
     * Rótulo de um usuário pelo ID (usado no valuehtmlcallback do autocomplete).
     *
     * @param int $userid
     * @return string
     */
    public static function get_label(int $userid): string {
        global $DB;
        if ($userid <= 0) {
            return '';
        }
        $namefields = self::name_fields_sql('u');
        $user = $DB->get_record_sql(
            "SELECT u.id, u.username, u.email, u.suspended, $namefields FROM {user} u WHERE u.id = :id AND u.deleted = 0",
            ['id' => $userid]
        );
        return $user ? self::format_label($user) : '';
    }

    /**
     * Campos de nome necessários para fullname().
     *
     * @param string $alias
     * @return string
     */
    protected static function name_fields_sql(string $alias): string {
        $fields = \core_user\fields::get_name_fields();
        return implode(', ', array_map(function($f) use ($alias) {
            return "$alias.$f";
        }, $fields));
    }
}
