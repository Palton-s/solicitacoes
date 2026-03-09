/**
 * Custom user selector AMD module for local_solicitacoes.
 * Replaces core_user/form_user_selector to avoid the moodle/user:viewdetails
 * capability requirement that regular users do not have.
 */
define([], function() {
    return {
        /**
         * Fetch users from the local AJAX endpoint.
         *
         * @param {string} selector  The CSS selector for the autocomplete element.
         * @param {string} query     The search string typed by the user.
         * @param {Function} success Called with the raw response array on success.
         * @param {Function} failure Called with an Error on failure.
         */
        transport: function(selector, query, success, failure) {
            var url = M.cfg.wwwroot +
                '/local/solicitacoes/ajax/buscar-usuarios.php' +
                '?q=' + encodeURIComponent(query) +
                '&limit=30' +
                '&sesskey=' + encodeURIComponent(M.cfg.sesskey);

            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        success(data);
                    } catch (e) {
                        failure(e);
                    }
                } else {
                    failure(new Error('HTTP ' + xhr.status));
                }
            };
            xhr.onerror = function() {
                failure(new Error('Network error'));
            };
            xhr.send();
        },

        /**
         * Transform the raw user objects into {value, label} pairs for the autocomplete.
         *
         * @param {string} selector  The CSS selector for the autocomplete element.
         * @param {Array}  results   Raw response from transport().
         * @returns {Array} Array of {value, label} objects.
         */
        processResults: function(selector, results) {
            if (!Array.isArray(results)) {
                return [];
            }
            return results.map(function(user) {
                return {
                    value: user.id,
                    label: user.label || (user.fullname + ' (' + user.username + ')')
                };
            });
        }
    };
});