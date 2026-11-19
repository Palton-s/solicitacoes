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
 * Carrega, depois que a página abre, o aviso de disciplinas similares
 * das solicitações de criação de curso (tela Gerenciar Solicitações).
 *
 * @module     local_solicitacoes/similar_courses
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/str'], function($, Ajax, Str) {

    var SELECTOR = '.local-solicitacoes-similar[data-requestid]';
    var BATCH = 50;

    /**
     * Escapa texto para HTML.
     *
     * @param {String} text
     * @returns {String}
     */
    var escapeHtml = function(text) {
        return $('<div>').text(text).html();
    };

    /**
     * Preenche os placeholders de um lote com o resultado.
     *
     * @param {Array} results
     * @param {String} title
     */
    var render = function(results, title) {
        results.forEach(function(item) {
            var el = $(SELECTOR + '[data-requestid="' + item.requestid + '"]');
            if (!item.courses.length) {
                el.remove();
                return;
            }
            var html = '<small class="text-danger"><strong>' + escapeHtml(title) + '</strong>' +
                '<ul class="mb-0 pl-3">';
            item.courses.forEach(function(course) {
                // O label já vem escapado (format_string) do servidor.
                html += '<li><a href="' + escapeHtml(course.url) + '" target="_blank">' + course.label + '</a></li>';
            });
            html += '</ul></small>';
            el.html(html);
        });
    };

    return {
        /**
         * Inicializa: busca em lotes os similares de todas as solicitações listadas.
         */
        init: function() {
            var ids = [];
            $(SELECTOR).each(function() {
                ids.push(parseInt($(this).attr('data-requestid'), 10));
            });
            if (!ids.length) {
                return;
            }

            Str.get_string('disciplinas_similares', 'local_solicitacoes').then(function(title) {
                var chain = $.Deferred().resolve().promise();
                for (var i = 0; i < ids.length; i += BATCH) {
                    (function(batch) {
                        // Lotes sequenciais: não sobrecarrega um servidor que já está lento.
                        chain = chain.then(function() {
                            return Ajax.call([{
                                methodname: 'local_solicitacoes_get_similar_courses',
                                args: {requestids: batch}
                            }])[0].then(function(results) {
                                render(results, title);
                                return results;
                            }).catch(function() {
                                batch.forEach(function(id) {
                                    $(SELECTOR + '[data-requestid="' + id + '"]').remove();
                                });
                            });
                        });
                    })(ids.slice(i, i + BATCH));
                }
                return title;
            }).catch(function() {
                $(SELECTOR).remove();
            });
        }
    };
});
