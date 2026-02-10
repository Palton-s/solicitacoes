// JavaScript para Tom Select - aguardar carregamento completo
window.addEventListener("load", function() {
    console.log("=== Window load event ===");
    console.log("TomSelect disponível?", typeof TomSelect);
    console.log("M.cfg disponível?", typeof M !== 'undefined' && M.cfg ? M.cfg : 'não disponível');

    // Aguardar 1 segundo para garantir que tudo carregou
    setTimeout(function() {
        console.log("=== Inicializando Tom Select após timeout ===");

        if (typeof TomSelect === "undefined") {
            console.error("Tom Select NÃO CARREGOU!");
            alert("Erro: Tom Select não carregou. Verifique sua conexão com a internet.");
            return;
        }

        console.log("Tom Select OK, buscando campos...");
        
        // Debug: listar todos os inputs no formulário
        var allInputs = document.querySelectorAll('input, select, textarea');
        console.log("Total de campos encontrados:", allInputs.length);
        allInputs.forEach(function(input) {
            console.log("  Campo:", input.tagName, "id=", input.id, "name=", input.name);
        });

        var cursoSelect = null;
        var usuariosSelect = null;

        // Inicializar Tom Select para CURSOS
        var cursoField = document.getElementById("curso_search");
        console.log("Campo curso:", cursoField);

        if (cursoField) {
            console.log("Campo curso encontrado, inicializando...");

            // Obter o caminho base do Moodle corretamente
            var moodleRoot = (typeof M !== 'undefined' && M.cfg && M.cfg.wwwroot) 
                ? M.cfg.wwwroot 
                : window.location.origin;
            console.log("Moodle root detectado:", moodleRoot);

            cursoSelect = new TomSelect(cursoField, {
                valueField: "id",
                labelField: "name",
                searchField: "name",
                placeholder: "Digite para buscar cursos...",
                wrapperClass: "form-control h-auto",
                plugins: ["remove_button"],
                create: false,
                render: {
                    item: function(data, escape) {
                        return '<div class="alert alert-success m-1 p-2">' + escape(data.name) + '</div>';
                    }
                },
                load: function(query, callback) {
                    if (query.length < 2) {
                        callback();
                        return;
                    }

                    var url = moodleRoot + "/local/solicitacoes/ajax/search_courses.php?query=" + encodeURIComponent(query) + "&limit=20";
                    console.log("Buscando cursos:", url);

                    fetch(url)
                        .then(function(response) {
                            console.log("Resposta recebida - Status:", response.status);
                            if (!response.ok) {
                                throw new Error("HTTP " + response.status);
                            }
                            return response.json();
                        })
                        .then(function(data) {
                            console.log("Cursos encontrados:", data);
                            if (Array.isArray(data)) {
                                callback(data);
                            } else {
                                console.error("Resposta não é um array:", data);
                                callback();
                            }
                        })
                        .catch(function(error) {
                            console.error("Erro ao buscar cursos:", error);
                            callback();
                        });
                },
                onChange: function(value) {
                    var hiddenField = document.querySelector("input[name=curso_id_selected]");
                    if (hiddenField) {
                        hiddenField.value = value;
                        console.log("Curso selecionado:", value);
                    }
                }
            });
            console.log("Tom Select curso inicializado!");
        } else {
            console.error("Campo curso_search não encontrado!");
        }

        // Inicializar Tom Select para USUÁRIOS (múltipla seleção)
        var usuariosField = document.getElementById("usuarios_search");
        if (usuariosField) {
            console.log("Campo usuarios encontrado, inicializando...");

            // Obter o caminho base do Moodle corretamente
            var moodleRoot = (typeof M !== 'undefined' && M.cfg && M.cfg.wwwroot) 
                ? M.cfg.wwwroot 
                : window.location.origin;

            usuariosSelect = new TomSelect(usuariosField, {
                valueField: "id",
                labelField: "fullname",
                searchField: ["fullname", "username", "email"],
                placeholder: "Digite para buscar usuários...",
                wrapperClass: "form-control h-auto",
                plugins: ["remove_button"],
                maxItems: null,
                create: false,
                load: function(query, callback) {
                    if (query.length < 2) {
                        callback();
                        return;
                    }

                    var url = moodleRoot + "/local/solicitacoes/ajax/search_users.php?query=" + encodeURIComponent(query) + "&limit=20";
                    console.log("Buscando usuários:", url);

                    fetch(url)
                        .then(function(response) {
                            console.log("Resposta recebida - Status:", response.status);
                            if (!response.ok) {
                                throw new Error("HTTP " + response.status);
                            }
                            return response.json();
                        })
                        .then(function(data) {
                            console.log("Usuários encontrados:", data);
                            if (Array.isArray(data)) {
                                callback(data);
                            } else {
                                console.error("Resposta não é um array:", data);
                                callback();
                            }
                        })
                        .catch(function(error) {
                            console.error("Erro ao buscar usuários:", error);
                            callback();
                        });
                },
                render: {
                    item: function(data, escape) {
                        return '<div class="alert alert-success m-1 p-2">' + escape(data.fullname) + '</div>';
                    },
                    option: function(data, escape) {
                        return "<div><strong>" + escape(data.fullname) + "</strong><br>" +
                            "<small>" + escape(data.username) + " - " + escape(data.email) + "</small></div>";
                    }
                },
                onChange: function(values) {
                    console.log("onChange chamado, tipo:", typeof values, "valor:", values);
                    var hiddenField = document.querySelector("input[name=usuarios_ids_selected]");
                    if (hiddenField) {
                        // values pode ser array ou string dependendo da configuração
                        var valueStr = Array.isArray(values) ? values.join(",") : String(values);
                        hiddenField.value = valueStr;
                        console.log("Campo oculto usuarios_ids_selected atualizado:", hiddenField.value);
                    } else {
                        console.error("Campo oculto usuarios_ids_selected NÃO ENCONTRADO!");
                    }
                }
            });
            console.log("Tom Select usuarios inicializado!");

            // Adicionar listener no submit para garantir sincronização
            var form = document.querySelector("form.mform");
            if (form) {
                form.addEventListener("submit", function(e) {
                    console.log("=== SUBMIT DO FORMULÁRIO ===");

                    // Sincronizar valores do Tom Select antes de enviar
                    if (usuariosSelect) {
                        var selectedValues = usuariosSelect.getValue();
                        console.log("Valores no Tom Select no momento do submit:", selectedValues);
                        console.log("Tipo:", typeof selectedValues, "Array?", Array.isArray(selectedValues));

                        var hiddenField = document.querySelector("input[name=usuarios_ids_selected]");
                        if (hiddenField) {
                            var valueStr = Array.isArray(selectedValues) ? selectedValues.join(",") : String(selectedValues);
                            hiddenField.value = valueStr;
                            console.log("Campo usuarios_ids_selected forçado no submit:", hiddenField.value);
                        } else {
                            console.error("Campo usuarios_ids_selected não encontrado no submit!");
                        }
                    }

                    if (cursoSelect) {
                        var cursoValue = cursoSelect.getValue();
                        console.log("Curso no submit:", cursoValue);
                        var cursoHidden = document.querySelector("input[name=curso_id_selected]");
                        if (cursoHidden) {
                            cursoHidden.value = cursoValue;
                            console.log("Campo curso_id_selected no submit:", cursoHidden.value);
                        }
                    }

                    // Log de todos os campos ocultos
                    console.log("Todos os campos ocultos:");
                    var allHidden = form.querySelectorAll("input[type=hidden]");
                    allHidden.forEach(function(field) {
                        console.log("  ", field.name, "=", field.value);
                    });
                });
            }
        } else {
            console.error("Campo usuarios_search não encontrado!");
        }

        // Controlar visibilidade do campo Papel
        var tipoAcaoField = document.getElementById("tipo_acao");
        var papelContainer = document.getElementById("papel_container");
        var papelField = document.getElementById("papel");
        
        if (tipoAcaoField && papelContainer && papelField) {
            tipoAcaoField.addEventListener("change", function() {
                if (this.value === "inscricao") {
                    papelContainer.style.display = "block";
                    papelField.setAttribute("required", "required");
                } else {
                    papelContainer.style.display = "none";
                    papelField.removeAttribute("required");
                }
            });
            // Disparar o evento para configurar o estado inicial
            tipoAcaoField.dispatchEvent(new Event("change"));
        }

    }, 1000); // Aguardar 1 segundo
});