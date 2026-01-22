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
                new moodle_url('/local/solicitacoes/index.php'),
                navigation_node::TYPE_CUSTOM
            );
        }
        
        // Submenu: Minhas Solicitações
        if (has_capability('local/solicitacoes:view', $context)) {
            $node->add(
                get_string('my_requests', 'local_solicitacoes'),
                new moodle_url('/local/solicitacoes/myrequests.php'),
                navigation_node::TYPE_CUSTOM
            );
        }
        
        // Submenu: Gerenciar Solicitações
        if (local_solicitacoes_can_manage($context)) {
            $node->add(
                get_string('list_title', 'local_solicitacoes'),
                new moodle_url('/local/solicitacoes/manage.php'),
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
        'suspensao' => get_string('acao_suspensao', 'local_solicitacoes')
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
    $message->contexturl = new moodle_url('/local/solicitacoes/view.php', ['id' => $solicitacao_id]);
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
        'suspensao' => get_string('acao_suspensao', 'local_solicitacoes')
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
    $message->contexturl = new moodle_url('/local/solicitacoes/view.php', ['id' => $solicitacao_id]);
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
        'suspensao' => get_string('acao_suspensao', 'local_solicitacoes')
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
    $message->contexturl = new moodle_url('/local/solicitacoes/view.php', ['id' => $solicitacao_id]);
    $message->contexturlname = get_string('details', 'local_solicitacoes');
    
    return message_send($message);
}
