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
 * Busca assíncrona de usuários para o elemento autocomplete do Moodle.
 *
 * Usa o web service local_solicitacoes_search_users, que exige apenas a
 * capacidade local/solicitacoes:submit (não precisa de moodle/user:viewalldetails).
 *
 * @module     local_solicitacoes/user_selector
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/str'], function($, Ajax, Str) {

    var MIN_CHARS = 3;
    var cache = {};
    var stringsPromise = null;

    /**
     * Strings usadas como mensagem no dropdown.
     *
     * @returns {Promise}
     */
    var getStrings = function() {
        if (!stringsPromise) {
            stringsPromise = Str.get_strings([
                {key: 'search_min_chars', component: 'local_solicitacoes', param: MIN_CHARS},
                {key: 'search_error', component: 'local_solicitacoes'}
            ]);
        }
        return stringsPromise;
    };

    return {
        /**
         * Consulta o web service local_solicitacoes_search_users.
         *
         * @param {String} selector Seletor do <select> original.
         * @param {String} query Texto digitado.
         * @param {Function} success Callback de sucesso.
         * @param {Function} failure Callback de erro.
         */
        transport: function(selector, query, success, failure) {
            var term = $.trim(query || '');

            if (term.length < MIN_CHARS) {
                getStrings().then(function(strings) {
                    success(strings[0]);
                    return strings;
                }).catch(failure);
                return;
            }

            var key = term.toLowerCase();
            if (cache.hasOwnProperty(key)) {
                success(cache[key]);
                return;
            }

            Ajax.call([{
                methodname: 'local_solicitacoes_search_users',
                args: {query: term, limit: 30}
            }])[0].then(function(response) {
                cache[key] = response;
                success(response);
                return response;
            }).catch(function() {
                getStrings().then(function(strings) {
                    success(strings[1]);
                    return strings;
                }).catch(failure);
            });
        },

        /**
         * Converte a resposta em opções {value, label}.
         *
         * @param {String} selector Seletor do <select> original.
         * @param {Object|String} results Resposta do transport (ou mensagem).
         * @returns {Array|String}
         */
        processResults: function(selector, results) {
            if (typeof results === 'string') {
                return results;
            }
            return (results.users || []).map(function(user) {
                return {value: user.id, label: user.label};
            });
        }
    };
});
