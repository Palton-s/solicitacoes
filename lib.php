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

/**
 * Library functions for the Solicitações plugin.
 *
 * @package    local_solicitacoes
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// ─── Utilitário de strings seguro ────────────────────────────────────────────

/**
 * Dicionário completo de strings padrão em português para o plugin.
 * Usado como fallback quando get_string() retorna [[key]] (cache desatualizado).
 */
function local_solicitacoes_str_defaults(): array {
    return [
        // Geral
        'pluginname'              => 'Solicitações de Gerenciamento de Curso',
        'menu_settings'           => 'Configurações de Solicitações',
        'menu_manage'             => 'Gerenciar Solicitações',
        'request_form_title'      => 'Solicitação de Gerenciamento de Curso',
        'request_submit'          => 'Enviar Solicitação',
        'back_to_list'            => 'Voltar para lista',
        'details'                 => 'Detalhes da Solicitação',
        'my_requests'             => 'Minhas Solicitações',
        'access_all_requests'     => 'Acessar todas as solicitações',
        'thankyou_new_request'    => 'Nova Solicitação',
        'no_requests'             => 'Nenhuma solicitação encontrada.',
        // Tabela de solicitações
        'user'                    => 'Solicitante',
        'action_type'             => 'Ação',
        'course'                  => 'Curso',
        'target_users'            => 'Usuários',
        'role'                    => 'Papel',
        'created_at'              => 'Criado em',
        'status'                  => 'Status',
        'actions'                 => 'Ações',
        'approve'                 => 'Aprovar',
        'deny'                    => 'Negar',
        'view'                    => 'Ver',
        'delete'                  => 'Excluir',
        'confirm_delete'          => 'Tem certeza que deseja excluir esta solicitação?',
        'success_delete'          => 'Solicitação excluída com sucesso.',
        'success_update'          => 'Status atualizado.',
        // Status
        'status_pendente'         => 'Pendente',
        'status_aprovado'         => 'Aprovado',
        'status_negado'           => 'Negado',
        // Tipos de ação
        'acao_inscricao'          => 'Inscrição',
        'acao_remocao'            => 'Remoção',
        'acao_suspensao'          => 'Suspensão',
        'acao_cadastro'           => 'Cadastro de Usuário',
        'acao_criar_curso'        => 'Criação de Curso',
        'acao_remover_curso'      => 'Remover Curso',
        // Campos de formulário comuns
        'observacoes'             => 'Justificativa',
        'observacoes_help'        => 'Descreva o motivo da sua solicitação.',
        'observacoes_placeholder' => 'descreva detalhadamente o motivo da sua solicitação',
        'papel'                   => 'Papel no Curso',
        'papel_help'              => 'Selecione o papel que os usuários devem ter no curso.',
        'select_role'             => 'Selecione um papel...',
        'curso_nome'              => 'Selecionar Curso',
        'curso_nome_help'         => 'Digite o nome do curso e selecione da lista.',
        'usuarios_busca'          => 'Selecionar Usuários',
        'usuarios_busca_help'     => 'Digite o nome, CPF ou e-mail para buscar.',
        // Campos de criação de curso
        'codigo_disciplina'       => 'Código da Disciplina',
        'nome_disciplina'         => 'Nome da Disciplina',
        'turma'                   => 'Turma',
        'ano_semestre'            => 'Ano/Semestre',
        'unidade_academica'       => 'Unidade Acadêmica',
        'course_summary'          => 'Sumário',
        'razoes_criacao'          => 'Razões para criar a disciplina',
        'codigo_sigaa'            => 'Código SIGAA',
        'course_shortname'        => 'Nome curto da disciplina',
        'professor_usuario'       => 'Selecionar Professores',
        'papel_professor'         => 'Papel do Professor',
        // Campos de cadastro de usuário
        'firstname'               => 'Primeiro Nome',
        'lastname'                => 'Sobrenome',
        'cpf'                     => 'CPF',
        'email_novo_usuario'      => 'E-mail',
        'novo_usuario'            => 'Novo usuário',
        // Observação / similaridade
        'observacao_column'       => 'Observação',
        'disciplinas_similares'   => 'Disciplinas similares já criadas:',
        'checking_similar_courses' => 'Verificando disciplinas similares...',
        // Página de seleção de ação
        'selecionar_acao_titulo'    => 'O que você deseja fazer?',
        'selecionar_acao_subtitulo' => 'Escolha o tipo de solicitação que deseja enviar',
        // Ações em massa / bulk
        'back'                    => 'Voltar',
        'approve_selected'        => 'Aprovar selecionadas',
        'bulk_hint'               => 'Selecione solicitações pendentes para aprovar em massa.',
        'select_all'              => 'Selecionar todas',
        // Negação
        'motivo_negacao'          => 'Motivo da Negação',
        'motivo_negacao_label'    => 'Informe o motivo da negação',
        'deny_request_title'      => 'Negar Solicitação',
        'confirm_deny'            => 'Confirmar Negação',
        // Histórico / detalhes
        'handled_by'              => 'Tratada por',
        'last_modified'           => 'Última modificação',
        'requester'               => 'Solicitante',
    ];
}

/**
 * Versão segura de get_string() com fallback em português.
 *
 * Evita que strings apareçam como [[key]] quando o cache do Moodle
 * estiver desatualizado. Funciona em todos os contextos PHP do plugin.
 *
 * @param string     $key Chave da string
 * @param mixed|null $a   Parâmetros de substituição (passados para get_string)
 * @return string
 */
function local_solicitacoes_str(string $key, $a = null): string {
    $result = get_string($key, 'local_solicitacoes', $a);
    if (strpos($result, '[[') === 0) {
        $defaults = local_solicitacoes_str_defaults();
        return $defaults[$key] ?? $key;
    }
    return $result;
}

// ─────────────────────────────────────────────────────────────────────────────

/**
 * Verifica se o usuário pode enviar solicitações.
 *
 * @param context|null $context Contexto (padrão: system)
 * @return bool
 */
function local_solicitacoes_can_submit($context = null) {
    if ($context === null) {
        $context = context_system::instance();
    }
    return has_capability('local/solicitacoes:submit', $context);
}

/**
 * Verifica se o usuário pode gerenciar solicitações.
 *
 * @param context|null $context Contexto (padrão: system)
 * @return bool
 */
function local_solicitacoes_can_manage($context = null) {
    if ($context === null) {
        $context = context_system::instance();
    }
    return has_capability('local/solicitacoes:manage', $context);
}

/**
 * Verifica se o usuário pode ver todas as solicitações.
 *
 * @param context|null $context Contexto (padrão: system)
 * @return bool
 */
function local_solicitacoes_can_viewall($context = null) {
    if ($context === null) {
        $context = context_system::instance();
    }
    return has_capability('local/solicitacoes:viewall', $context);
}

/**
 * Verifica se o usuário pode ver uma solicitação específica.
 *
 * @param int $requestid ID da solicitação
 * @param int|null $userid ID do usuário (padrão: usuário atual)
 * @param context|null $context Contexto (padrão: system)
 * @return bool
 */
function local_solicitacoes_can_view_request($requestid, $userid = null, $context = null) {
    global $DB, $USER;
    
    if ($context === null) {
        $context = context_system::instance();
    }
    
    if ($userid === null) {
        $userid = $USER->id;
    }
    
    // Pode gerenciar ou ver todas?
    if (local_solicitacoes_can_manage($context) || local_solicitacoes_can_viewall($context)) {
        return true;
    }
    
    // É a própria solicitação?
    if (has_capability('local/solicitacoes:view', $context)) {
        $request = $DB->get_record('local_solicitacoes', ['id' => $requestid], 'userid', MUST_EXIST);
        return ($request->userid == $userid);
    }
    
    return false;
}

/**
 * Adiciona itens ao menu de navegação.
 *
 * @param global_navigation $navigation
 */
function local_solicitacoes_extend_navigation(global_navigation $navigation) {
    global $PAGE;

    // ── JS safety-net: substitui qualquer [[key]] visível na página ───────────
    // Só carrega nas páginas do próprio plugin para não impactar o resto do Moodle.
    $page_path = $PAGE->url->get_path();
    if (strpos($page_path, '/local/solicitacoes/') !== false) {
        $fb_json = json_encode(local_solicitacoes_str_defaults(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        $PAGE->requires->js_init_code(
            "(function(){
                var fb = $fb_json;
                function fix(el){
                    var nodes = el.childNodes;
                    for(var i=0;i<nodes.length;i++){
                        var n=nodes[i];
                        if(n.nodeType===3 && n.textContent.indexOf('[[')!==-1){
                            n.textContent=n.textContent.replace(/\\[\\[([^\\]]+)\\]\\]/g,function(m,k){
                                return fb[k.trim()]||m;
                            });
                        } else if(n.nodeType===1){ fix(n); }
                    }
                }
                document.addEventListener('DOMContentLoaded',function(){fix(document.body);});
                var obs=new MutationObserver(function(ms){
                    ms.forEach(function(m){m.addedNodes.forEach(function(n){if(n.nodeType===1)fix(n);});});
                });
                document.addEventListener('DOMContentLoaded',function(){
                    obs.observe(document.body,{childList:true,subtree:true});
                });
            }());",
            true
        );
    }

    $context = context_system::instance();
    
    // Adicionar link no menu apenas para usuários com permissões
    if (local_solicitacoes_can_submit($context) || local_solicitacoes_can_manage($context) || local_solicitacoes_can_viewall($context)) {
        $node = $navigation->add(
            get_string('pluginname', 'local_solicitacoes'),
            null,
            navigation_node::TYPE_CUSTOM,
            null,
            'local_solicitacoes',
            new pix_icon('i/navigationitem', '')
        );
        
        // Submenu: Nova Solicitação
        if (local_solicitacoes_can_submit($context)) {
            $node->add(
                get_string('request_form_title', 'local_solicitacoes'),
                new moodle_url('/local/solicitacoes/selecionar-acao.php'),
                navigation_node::TYPE_CUSTOM
            );
        }
        
        // Submenu: Minhas Solicitações
        if (has_capability('local/solicitacoes:view', $context)) {
            $node->add(
                get_string('my_requests', 'local_solicitacoes'),
                new moodle_url('/local/solicitacoes/minhas-solicitacoes.php'),
                navigation_node::TYPE_CUSTOM
            );
        }
        
        // Submenu: Gerenciar Solicitações
        if (local_solicitacoes_can_manage($context)) {
            $node->add(
                get_string('list_title', 'local_solicitacoes'),
                new moodle_url('/local/solicitacoes/gerenciar.php'),
                navigation_node::TYPE_CUSTOM
            );
        }
    }
}

/**
 * Envia notificação ao solicitante quando uma solicitação é criada.
 *
 * @param int $solicitacao_id ID da solicitação
 * @return bool
 */
function local_solicitacoes_notify_criada($solicitacao_id) {
    global $DB;
    
    $solicitacao = $DB->get_record('local_solicitacoes', ['id' => $solicitacao_id], '*', MUST_EXIST);
    $usuario = core_user::get_user($solicitacao->userid);
    
    // Buscar curso
    $sql_curso = "SELECT c.fullname FROM {local_curso_solicitacoes} cs
                  JOIN {course} c ON cs.curso_id = c.id
                  WHERE cs.solicitacao_id = :id LIMIT 1";
    $curso = $DB->get_record_sql($sql_curso, ['id' => $solicitacao_id]);
    $curso_nome = $curso ? $curso->fullname : $solicitacao->curso_nome;
    
    // Traduzir tipo de ação
    $acao_strings = [
        'inscricao' => get_string('acao_inscricao', 'local_solicitacoes'),
        'remocao' => get_string('acao_remocao', 'local_solicitacoes'),
        'suspensao' => get_string('acao_suspensao', 'local_solicitacoes'),
        'cadastro' => get_string('acao_cadastro', 'local_solicitacoes'),
        'criar_curso' => get_string('acao_criar_curso', 'local_solicitacoes'),
        'remove_course' => get_string('acao_remover_curso', 'local_solicitacoes')
    ];
    $tipo_acao = isset($acao_strings[$solicitacao->tipo_acao]) ? $acao_strings[$solicitacao->tipo_acao] : $solicitacao->tipo_acao;
    
    $message = new \core\message\message();
    $message->component = 'local_solicitacoes';
    $message->name = 'solicitacao_criada';
    $message->userfrom = core_user::get_noreply_user();
    $message->userto = $usuario;
    $message->subject = get_string('notification_criada_subject', 'local_solicitacoes');
    $message->fullmessage = get_string('notification_criada_body', 'local_solicitacoes', [
        'tipo_acao' => $tipo_acao,
        'curso' => $curso_nome
    ]);
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml = '';
    $message->smallmessage = get_string('notification_criada_subject', 'local_solicitacoes');
    $message->notification = 1;
    $message->contexturl = new moodle_url('/local/solicitacoes/detalhes.php', ['id' => $solicitacao_id]);
    $message->contexturlname = get_string('details', 'local_solicitacoes');
    
    return message_send($message);
}

/**
 * Envia notificação ao solicitante quando uma solicitação é aprovada.
 *
 * @param int $solicitacao_id ID da solicitação
 * @return bool
 */
function local_solicitacoes_notify_aprovada($solicitacao_id) {
    global $DB;
    
    $solicitacao = $DB->get_record('local_solicitacoes', ['id' => $solicitacao_id], '*', MUST_EXIST);
    $usuario = core_user::get_user($solicitacao->userid);
    
    // Buscar curso
    $sql_curso = "SELECT c.fullname FROM {local_curso_solicitacoes} cs
                  JOIN {course} c ON cs.curso_id = c.id
                  WHERE cs.solicitacao_id = :id LIMIT 1";
    $curso = $DB->get_record_sql($sql_curso, ['id' => $solicitacao_id]);
    $curso_nome = $curso ? $curso->fullname : $solicitacao->curso_nome;
    
    // Traduzir tipo de ação
    $acao_strings = [
        'inscricao' => get_string('acao_inscricao', 'local_solicitacoes'),
        'remocao' => get_string('acao_remocao', 'local_solicitacoes'),
        'suspensao' => get_string('acao_suspensao', 'local_solicitacoes'),
        'cadastro' => get_string('acao_cadastro', 'local_solicitacoes'),
        'criar_curso' => get_string('acao_criar_curso', 'local_solicitacoes'),
        'remove_course' => get_string('acao_remover_curso', 'local_solicitacoes')
    ];
    $tipo_acao = isset($acao_strings[$solicitacao->tipo_acao]) ? $acao_strings[$solicitacao->tipo_acao] : $solicitacao->tipo_acao;
    
    $message = new \core\message\message();
    $message->component = 'local_solicitacoes';
    $message->name = 'solicitacao_aprovada';
    $message->userfrom = core_user::get_noreply_user();
    $message->userto = $usuario;
    $message->subject = get_string('notification_aprovada_subject', 'local_solicitacoes');
    $message->fullmessage = get_string('notification_aprovada_body', 'local_solicitacoes', [
        'tipo_acao' => $tipo_acao,
        'curso' => $curso_nome
    ]);
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml = '';
    $message->smallmessage = get_string('notification_aprovada_subject', 'local_solicitacoes');
    $message->notification = 1;
    $message->contexturl = new moodle_url('/local/solicitacoes/detalhes.php', ['id' => $solicitacao_id]);
    $message->contexturlname = get_string('details', 'local_solicitacoes');
    
    return message_send($message);
}

/**
 * Envia notificação ao solicitante quando uma solicitação é negada.
 *
 * @param int $solicitacao_id ID da solicitação
 * @return bool
 */
function local_solicitacoes_notify_negada($solicitacao_id) {
    global $DB;
    
    $solicitacao = $DB->get_record('local_solicitacoes', ['id' => $solicitacao_id], '*', MUST_EXIST);
    $usuario = core_user::get_user($solicitacao->userid);
    
    // Buscar curso
    $sql_curso = "SELECT c.fullname FROM {local_curso_solicitacoes} cs
                  JOIN {course} c ON cs.curso_id = c.id
                  WHERE cs.solicitacao_id = :id LIMIT 1";
    $curso = $DB->get_record_sql($sql_curso, ['id' => $solicitacao_id]);
    $curso_nome = $curso ? $curso->fullname : $solicitacao->curso_nome;
    
    // Traduzir tipo de ação
    $acao_strings = [
        'inscricao' => get_string('acao_inscricao', 'local_solicitacoes'),
        'remocao' => get_string('acao_remocao', 'local_solicitacoes'),
        'suspensao' => get_string('acao_suspensao', 'local_solicitacoes'),
        'cadastro' => get_string('acao_cadastro', 'local_solicitacoes'),
        'criar_curso' => get_string('acao_criar_curso', 'local_solicitacoes'),
        'remove_course' => get_string('acao_remover_curso', 'local_solicitacoes')
    ];
    $tipo_acao = isset($acao_strings[$solicitacao->tipo_acao]) ? $acao_strings[$solicitacao->tipo_acao] : $solicitacao->tipo_acao;
    
    $message = new \core\message\message();
    $message->component = 'local_solicitacoes';
    $message->name = 'solicitacao_negada';
    $message->userfrom = core_user::get_noreply_user();
    $message->userto = $usuario;
    $message->subject = get_string('notification_negada_subject', 'local_solicitacoes');
    $message->fullmessage = get_string('notification_negada_body', 'local_solicitacoes', [
        'tipo_acao' => $tipo_acao,
        'curso' => $curso_nome,
        'motivo' => $solicitacao->motivo_negacao
    ]);
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml = '';
    $message->smallmessage = get_string('notification_negada_subject', 'local_solicitacoes');
    $message->notification = 1;
    $message->contexturl = new moodle_url('/local/solicitacoes/detalhes.php', ['id' => $solicitacao_id]);
    $message->contexturlname = get_string('details', 'local_solicitacoes');
    
    return message_send($message);
}

/**
 * Retorna condição SQL e params para filtrar cursos pelas categorias permitidas nas configurações.
 * A query que usa esta função deve fazer JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = CONTEXT_COURSE.
 * Retorna ['1=1', []] se nenhuma categoria estiver configurada (sem restrição).
 */
function local_solicitacoes_get_allowed_categories_sql(string $ctx_alias = 'ctx'): array {
    global $DB;

    $config = get_config('local_solicitacoes', 'allowed_categories');
    if (empty($config)) {
        return ['1=1', []];
    }

    $cat_ids = array_values(array_filter(array_map('intval', explode(',', $config))));
    if (empty($cat_ids)) {
        return ['1=1', []];
    }

    // Caminhos dos contextos das categorias: em cache (a chave muda junto com a configuração).
    $cache = cache::make('local_solicitacoes', 'allowedcatpaths');
    $cachekey = 'p' . md5(implode(',', $cat_ids));
    $paths = $cache->get($cachekey);
    if ($paths === false) {
        list($in_sql, $in_params) = $DB->get_in_or_equal($cat_ids, SQL_PARAMS_NAMED, 'allowedcatid');
        $paths = array_values($DB->get_fieldset_sql(
            "SELECT ctx.path FROM {context} ctx
             WHERE ctx.contextlevel = :ctxcatlevel AND ctx.instanceid $in_sql",
            array_merge(['ctxcatlevel' => CONTEXT_COURSECAT], $in_params)
        ));
        $cache->set($cachekey, $paths);
    }

    if (empty($paths)) {
        return ['1=0', []];
    }

    $conditions = [];
    $params = [];
    foreach ($paths as $i => $path) {
        $params["allowedcatpath_{$i}"] = $path . '/%';
        $conditions[] = "{$ctx_alias}.path LIKE :allowedcatpath_{$i}";
    }

    return ['(' . implode(' OR ', $conditions) . ')', $params];
}

/**
 * Adiciona ao formulário um campo de cursos com busca assíncrona (AJAX).
 *
 * Nenhum curso é carregado na página: a busca acontece conforme o usuário
 * digita, via web service local_solicitacoes_search_courses.
 *
 * @param MoodleQuickForm $mform
 * @param string $scope Um dos \local_solicitacoes\local\course_search::SCOPE_*
 * @param string $name Nome do campo.
 */
function local_solicitacoes_add_course_selector($mform, string $scope, string $name = 'curso_nome'): void {
    $mform->addElement('autocomplete', $name, get_string('curso_nome', 'local_solicitacoes'), [], [
        'multiple'          => true,
        'ajax'              => 'local_solicitacoes/course_selector',
        'data-scope'        => $scope,
        'placeholder'       => get_string('search_courses_placeholder', 'local_solicitacoes'),
        'noselectionstring' => get_string('no_courses_selected', 'local_solicitacoes'),
        'casesensitive'     => false,
        'valuehtmlcallback' => function($courseid) {
            return \local_solicitacoes\local\course_search::get_label((int)$courseid);
        },
    ]);
    $mform->setType($name, PARAM_SEQUENCE);
    $mform->addRule($name, null, 'required', null, 'client');
    $mform->addHelpButton($name, 'curso_nome_help', 'local_solicitacoes');
}

/**
 * Adiciona ao formulário um campo de usuários com busca assíncrona (AJAX).
 *
 * @param MoodleQuickForm $mform
 * @param string $name Nome do campo.
 * @param string $label Rótulo já traduzido.
 * @param int $paramtype Tipo PARAM_* do campo.
 */
function local_solicitacoes_add_user_selector($mform, string $name, string $label, $paramtype = PARAM_SEQUENCE): void {
    $mform->addElement('autocomplete', $name, $label, [], [
        'multiple'          => true,
        'ajax'              => 'local_solicitacoes/user_selector',
        'placeholder'       => get_string('usuarios_busca_help', 'local_solicitacoes'),
        'noselectionstring' => get_string('no_users_selected', 'local_solicitacoes'),
        'casesensitive'     => false,
        'valuehtmlcallback' => function($userid) {
            return \local_solicitacoes\local\user_search::get_label((int)$userid);
        },
    ]);
    $mform->setType($name, $paramtype);
    $mform->addRule($name, null, 'required', null, 'client');
}

/**
 * Obtém os papéis de curso permitidos configurados no plugin.
 * 
 * @return array Array com shortname => name dos papéis permitidos
 */
function local_solicitacoes_get_allowed_roles() {
    global $DB;
    
    // Obter papéis configurados
    $allowed_roles_config = get_config('local_solicitacoes', 'allowed_roles');
    
    // Se não há configuração, usar papéis padrão
    if (empty($allowed_roles_config)) {
        $allowed_shortnames = ['student', 'teacher', 'editingteacher'];
    } else {
        $allowed_shortnames = explode(',', $allowed_roles_config);
        $allowed_shortnames = array_map('trim', $allowed_shortnames); // Remove espaços
        $allowed_shortnames = array_filter($allowed_shortnames); // Remove vazios
    }
    
    // Obter todos os papéis de contexto de curso
    $course_role_ids = get_roles_for_contextlevels(CONTEXT_COURSE);
    $roles_options = array();
    
    foreach ($course_role_ids as $roleid) {
        $role = $DB->get_record('role', ['id' => $roleid], 'shortname, name');
        if ($role && in_array($role->shortname, $allowed_shortnames)) {
            $roles_options[$role->shortname] = role_get_name($role);
        }
    }
    
    return $roles_options;
}

/**
 * Obtém os papéis de curso permitidos configurados no plugin com opção vazia no início.
 * 
 * @param string $empty_option_text Texto para a opção vazia (padrão: 'Selecione um papel...')
 * @return array Array com '' => texto vazio e shortname => name dos papéis permitidos
 */
function local_solicitacoes_get_allowed_roles_with_empty($empty_option_text = null) {
    if ($empty_option_text === null) {
        $empty_option_text = get_string('select_role', 'local_solicitacoes');
    }
    
    $roles = local_solicitacoes_get_allowed_roles();
    return array_merge(['' => $empty_option_text], $roles);
}

/**
 * Gera o nome breve (shortname) do curso a partir dos campos da solicitação.
 *
 * Formato: CODIGO_INICIAIS_T_TURMA_ANO_SEMESTRE
 * Exemplo: CCA0081 - Estrutura e Organização do Estado - Turma 01 - 2026/1
 *          → CCA0081_EOOE_T_01_2026_1
 *
 * Palavras funcionais (artigos, preposições) são ignoradas ao calcular as iniciais.
 * Se o shortname gerado já existir no Moodle, acrescenta underscores extras até
 * encontrar um que seja único.
 *
 * @param string $codigo       Código da disciplina (ex: CCA0081)
 * @param string $nome         Nome da disciplina   (ex: Estrutura e Organização do Estado)
 * @param string $turma        Identificação da turma (ex: Turma 01)
 * @param string $ano_semestre Ano/semestre (ex: 2026/1)
 * @return string Nome breve único para o curso
 */
function local_solicitacoes_generate_shortname(string $codigo, string $nome, string $turma, string $ano_semestre): string {
    global $DB;

    // ── Iniciais do nome da disciplina ─────────────────────────────────────────
    // Ignora artigos e preposições comuns em português e inglês
    static $skip_words = [
        'e', 'ou', 'de', 'da', 'do', 'das', 'dos', 'des',
        'a', 'o', 'as', 'os', 'um', 'uma', 'uns', 'umas',
        'em', 'no', 'na', 'nos', 'nas', 'ao', 'aos', 'à', 'às',
        'por', 'para', 'com', 'sem', 'sob', 'sobre', 'entre',
        'the', 'of', 'and', 'in', 'to', 'for',
    ];
    $words    = preg_split('/\s+/u', trim($nome));
    $initials = '';
    foreach ($words as $word) {
        $clean = mb_strtolower(trim($word), 'UTF-8');
        if ($clean !== '' && !in_array($clean, $skip_words)) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
        }
    }
    if ($initials === '') {
        $initials = 'X'; // fallback para nome totalmente composto de palavras funcionais
    }

    // ── Número/letra da turma (remove o prefixo "Turma") ──────────────────────
    $turma_id = trim(preg_replace('/^Turma\s*/iu', '', trim($turma)));
    $turma_id = preg_replace('/\s+/', '', $turma_id); // ex: "01", "A", "02A"
    if ($turma_id === '') {
        $turma_id = '0';
    }

    // ── Ano e semestre ─────────────────────────────────────────────────────────
    $pos  = strpos($ano_semestre, '/');
    $year = $pos !== false ? substr($ano_semestre, 0, $pos) : $ano_semestre;
    $sem  = $pos !== false ? substr($ano_semestre, $pos + 1) : '';
    $year = preg_replace('/[^0-9]/', '', $year);
    $sem  = preg_replace('/[^0-9]/', '', $sem);

    // ── Monta o shortname base ─────────────────────────────────────────────────
    // Formato: CCA0081_EOOE_T_01_2026_1
    $parts = array_filter([$codigo, $initials, 'T', $turma_id, $year, $sem], fn($p) => $p !== '');
    $base  = implode('_', $parts);

    // ── Garante unicidade: adiciona underscores se já existir ──────────────────
    $shortname = $base;
    while ($DB->record_exists('course', ['shortname' => $shortname])) {
        $shortname .= '_';
    }

    return $shortname;
}
