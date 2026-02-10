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

        // Controlar visibilidade dos campos baseado no tipo de ação
        var tipoAcaoField = document.getElementById("tipo_acao");
        var papelContainer = document.getElementById("papel_container");
        var papelField = document.getElementById("papel");
        var cursoContainer = document.getElementById("curso_container");
        var cursoField = document.getElementById("curso_search");
        var usuariosContainer = document.getElementById("usuarios_container");
        var usuariosField = document.getElementById("usuarios_search");
        var cadastroContainer = document.getElementById("cadastro_container");
        var firstnameField = document.getElementById("firstname");
        var lastnameField = document.getElementById("lastname");
        var cpfField = document.getElementById("cpf");
        var emailField = document.getElementById("email_novo_usuario");
        
        if (tipoAcaoField) {
            tipoAcaoField.addEventListener("change", function() {
                var tipoAcao = this.value;
                
                if (tipoAcao === "cadastro") {
                    // Mostrar campos de cadastro, curso e papel
                    // Ocultar campo de usuários existentes
                    if (cadastroContainer) {
                        cadastroContainer.style.display = "flex";
                        cadastroContainer.style.flexWrap = "wrap";
                    }
                    if (cursoContainer) cursoContainer.style.display = "block";
                    if (papelContainer) papelContainer.style.display = "block";
                    if (usuariosContainer) usuariosContainer.style.display = "none";
                    
                    // Ajustar required
                    if (firstnameField) firstnameField.setAttribute("required", "required");
                    if (lastnameField) lastnameField.setAttribute("required", "required");
                    if (cpfField) cpfField.setAttribute("required", "required");
                    if (emailField) emailField.setAttribute("required", "required");
                    if (papelField) papelField.setAttribute("required", "required");
                    if (cursoField) cursoField.setAttribute("required", "required");
                    if (usuariosField) usuariosField.removeAttribute("required");
                    
                } else if (tipoAcao === "inscricao") {
                    // Mostrar curso, usuários e papel
                    // Ocultar campos de cadastro
                    if (cursoContainer) cursoContainer.style.display = "block";
                    if (usuariosContainer) usuariosContainer.style.display = "block";
                    if (papelContainer) papelContainer.style.display = "block";
                    if (cadastroContainer) cadastroContainer.style.display = "none";
                    
                    // Ajustar required
                    if (papelField) papelField.setAttribute("required", "required");
                    if (cursoField) cursoField.setAttribute("required", "required");
                    if (usuariosField) usuariosField.setAttribute("required", "required");
                    if (firstnameField) firstnameField.removeAttribute("required");
                    if (lastnameField) lastnameField.removeAttribute("required");
                    if (cpfField) cpfField.removeAttribute("required");
                    if (emailField) emailField.removeAttribute("required");
                    
                } else {
                    // remocao ou suspensao: mostrar curso e usuários, ocultar papel e cadastro
                    if (cursoContainer) cursoContainer.style.display = "block";
                    if (usuariosContainer) usuariosContainer.style.display = "block";
                    if (papelContainer) papelContainer.style.display = "none";
                    if (cadastroContainer) cadastroContainer.style.display = "none";
                    
                    // Ajustar required
                    if (papelField) papelField.removeAttribute("required");
                    if (cursoField) cursoField.setAttribute("required", "required");
                    if (usuariosField) usuariosField.setAttribute("required", "required");
                    if (firstnameField) firstnameField.removeAttribute("required");
                    if (lastnameField) lastnameField.removeAttribute("required");
                    if (cpfField) cpfField.removeAttribute("required");
                    if (emailField) emailField.removeAttribute("required");
                }
            });
            // Disparar o evento para configurar o estado inicial
            tipoAcaoField.dispatchEvent(new Event("change"));
        }

        // Validação e Formatação de CPF em tempo real
        if (cpfField) {
            // Criar elemento para mensagem de feedback
            var feedbackDiv = document.createElement("div");
            feedbackDiv.id = "cpf-feedback";
            feedbackDiv.className = "invalid-feedback";
            feedbackDiv.style.display = "block";
            cpfField.parentNode.appendChild(feedbackDiv);

            // Função para validar CPF usando algoritmo oficial
            function validarCPF(cpf) {
                // Remove caracteres não numéricos
                cpf = cpf.replace(/[^\d]/g, '');
                
                // Verifica se tem 11 dígitos
                if (cpf.length !== 11) return false;
                
                // Verifica se todos os dígitos são iguais
                if (/^(\d)\1{10}$/.test(cpf)) return false;
                
                // Valida primeiro dígito verificador
                var soma = 0;
                for (var i = 0; i < 9; i++) {
                    soma += parseInt(cpf.charAt(i)) * (10 - i);
                }
                var resto = 11 - (soma % 11);
                var digito1 = (resto === 10 || resto === 11) ? 0 : resto;
                
                if (digito1 !== parseInt(cpf.charAt(9))) return false;
                
                // Valida segundo dígito verificador
                soma = 0;
                for (var i = 0; i < 10; i++) {
                    soma += parseInt(cpf.charAt(i)) * (11 - i);
                }
                resto = 11 - (soma % 11);
                var digito2 = (resto === 10 || resto === 11) ? 0 : resto;
                
                if (digito2 !== parseInt(cpf.charAt(10))) return false;
                
                return true;
            }

            // Função para formatar CPF (000.000.000-00)
            function formatarCPF(valor) {
                // Remove tudo que não é número
                valor = valor.replace(/\D/g, '');
                
                // Limita a 11 dígitos
                valor = valor.substring(0, 11);
                
                // Aplica formatação
                if (valor.length > 9) {
                    valor = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
                } else if (valor.length > 6) {
                    valor = valor.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
                } else if (valor.length > 3) {
                    valor = valor.replace(/(\d{3})(\d{1,3})/, '$1.$2');
                }
                
                return valor;
            }

            // Event listener para formatação durante digitação
            cpfField.addEventListener("input", function(e) {
                var cursorPos = this.selectionStart;
                var valorAnterior = this.value;
                
                // Formatar o valor
                this.value = formatarCPF(this.value);
                
                // Ajustar posição do cursor após formatação
                if (this.value.length < valorAnterior.length) {
                    // Se deletou, mantém posição
                    this.setSelectionRange(cursorPos, cursorPos);
                }
            });

            // Event listener para validação ao sair do campo
            cpfField.addEventListener("blur", function() {
                var cpfLimpo = this.value.replace(/\D/g, '');
                
                if (cpfLimpo.length === 0) {
                    // Campo vazio - remover feedback
                    cpfField.classList.remove("is-valid", "is-invalid");
                    feedbackDiv.textContent = "";
                    return;
                }
                
                if (validarCPF(cpfLimpo)) {
                    // CPF válido
                    cpfField.classList.remove("is-invalid");
                    cpfField.classList.add("is-valid");
                    feedbackDiv.className = "valid-feedback";
                    feedbackDiv.style.display = "block";
                    feedbackDiv.textContent = "✓ CPF válido";
                } else {
                    // CPF inválido
                    cpfField.classList.remove("is-valid");
                    cpfField.classList.add("is-invalid");
                    feedbackDiv.className = "invalid-feedback";
                    feedbackDiv.style.display = "block";
                    
                    if (cpfLimpo.length < 11) {
                        feedbackDiv.textContent = "CPF incompleto. Digite os 11 dígitos.";
                    } else {
                        feedbackDiv.textContent = "CPF inválido. Verifique os números digitados.";
                    }
                }
            });

            // Validação ao focar (para casos de formulário preenchido)
            cpfField.addEventListener("focus", function() {
                if (this.value.length > 0) {
                    // Garantir que está formatado
                    this.value = formatarCPF(this.value);
                }
            });

            // Validação no submit do formulário
            var form = cpfField.closest("form");
            if (form) {
                form.addEventListener("submit", function(e) {
                    var tipoAcao = document.getElementById("tipo_acao");
                    if (tipoAcao && tipoAcao.value === "cadastro") {
                        var cpfLimpo = cpfField.value.replace(/\D/g, '');
                        
                        if (cpfLimpo.length === 0) {
                            e.preventDefault();
                            cpfField.classList.add("is-invalid");
                            feedbackDiv.className = "invalid-feedback";
                            feedbackDiv.style.display = "block";
                            feedbackDiv.textContent = "Por favor, informe o CPF.";
                            cpfField.focus();
                            return false;
                        }
                        
                        if (!validarCPF(cpfLimpo)) {
                            e.preventDefault();
                            cpfField.classList.add("is-invalid");
                            feedbackDiv.className = "invalid-feedback";
                            feedbackDiv.style.display = "block";
                            feedbackDiv.textContent = "CPF inválido. Corrija antes de enviar.";
                            cpfField.focus();
                            return false;
                        }
                    }
                });
            }

            console.log("Validação de CPF configurada com sucesso!");
        }

    }, 1000); // Aguardar 1 segundo
});