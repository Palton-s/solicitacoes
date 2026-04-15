<?php
/**
 * Teste da funcionalidade de remoção automática de suspensões
 * Este script simula o processo de aprovação de inscrições
 * para verificar se suspensões são removidas automaticamente
 */

require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/solicitacoes_controller.php');

require_login();

$context = context_system::instance();

// Verificar permissão para gerenciar solicitações
if (!has_capability('local/solicitacoes:manage', $context)) {
    redirect(
        new moodle_url('/'),
        'Permissão negada para executar testes',
        null,
        \core\output\notification::NOTIFY_ERROR
    );
    exit;
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/solicitacoes/teste-remocao-suspensao.php'));
$PAGE->set_title('Teste - Remoção Automática de Suspensões');
$PAGE->set_heading('Teste - Remoção Automática de Suspensões');

echo $OUTPUT->header();

global $DB;

// Parâmetros para execução do teste
$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

echo '<div class="container-fluid">';
echo '<div class="alert alert-warning">
    <strong>ATENÇÃO:</strong> Este é um script de teste para desenvolvedores. 
    Use com cuidado em ambiente de produção.
</div>';

// Formulário para configurar teste
echo '<form method="get">';
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<div class="form-group">';
echo '<label>Usuário ID:</label>';
echo '<input type="number" name="userid" class="form-control" value="' . $userid . '" required>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-6">';
echo '<div class="form-group">';
echo '<label>Curso ID:</label>';
echo '<input type="number" name="courseid" class="form-control" value="' . $courseid . '" required>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '<div class="row">';
echo '<div class="col-md-12">';
echo '<button type="submit" name="action" value="check" class="btn btn-info">Verificar Status</button>';
echo '<button type="submit" name="action" value="suspend" class="btn btn-warning">Suspender Usuário</button>';
echo '<button type="submit" name="action" value="test_enrol" class="btn btn-success">Testar Inscrição (Remove Suspensão)</button>';
echo '</div>';
echo '</div>';
echo '</form>';

echo '<hr>';

if ($action && $userid && $courseid) {
    echo '<h3>Resultado do Teste</h3>';
    
    // Verificar se usuário e curso existem
    $user = $DB->get_record('user', ['id' => $userid]);
    $course = $DB->get_record('course', ['id' => $courseid]);
    
    if (!$user) {
        echo '<div class="alert alert-danger">Usuário não encontrado!</div>';
    } else if (!$course) {
        echo '<div class="alert alert-danger">Curso não encontrado!</div>';
    } else {
        echo '<div class="alert alert-info">';
        echo '<strong>Usuário:</strong> ' . fullname($user) . ' (ID: ' . $user->id . ')<br>';
        echo '<strong>Curso:</strong> ' . $course->fullname . ' (ID: ' . $course->id . ')';
        echo '</div>';
        
        switch ($action) {
            case 'check':
                check_enrolment_status($userid, $courseid);
                break;
                
            case 'suspend':
                suspend_test_user($userid, $courseid);
                break;
                
            case 'test_enrol':
                test_enrolment_with_suspension_removal($userid, $courseid);
                break;
        }
    }
}

echo '</div>';

/**
 * Verifica status de inscrição do usuário no curso
 */
function check_enrolment_status($userid, $courseid) {
    global $DB;
    
    $sql = "SELECT ue.*, e.enrol as enrol_type, r.shortname as role_shortname
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            LEFT JOIN {role_assignments} ra ON ra.userid = ue.userid 
                AND ra.contextid = (SELECT id FROM {context} WHERE instanceid = e.courseid AND contextlevel = 50)
            LEFT JOIN {role} r ON r.id = ra.roleid
            WHERE e.courseid = :courseid
            AND ue.userid = :userid";
    
    $enrolments = $DB->get_records_sql($sql, [
        'courseid' => $courseid,
        'userid' => $userid
    ]);
    
    if (empty($enrolments)) {
        echo '<div class="alert alert-info">Usuário não está inscrito neste curso.</div>';
    } else {
        echo '<div class="alert alert-success">';
        echo '<strong>Inscrições encontradas:</strong><br>';
        foreach ($enrolments as $enrolment) {
            $status_text = ($enrolment->status == 0) ? 'Ativo' : 'Suspenso';
            $status_class = ($enrolment->status == 0) ? 'success' : 'danger';
            
            echo '<span class="badge badge-' . $status_class . '">' . $status_text . '</span> ';
            echo 'Método: ' . $enrolment->enrol_type;
            if ($enrolment->role_shortname) {
                echo ', Papel: ' . $enrolment->role_shortname;
            }
            echo '<br>';
        }
        echo '</div>';
    }
}

/**
 * Suspende usuário para teste
 */
function suspend_test_user($userid, $courseid) {
    global $DB;
    
    // Verificar se está inscrito
    $sql = "SELECT ue.*
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            WHERE e.courseid = :courseid
            AND ue.userid = :userid";
    
    $enrolments = $DB->get_records_sql($sql, [
        'courseid' => $courseid,
        'userid' => $userid
    ]);
    
    if (empty($enrolments)) {
        echo '<div class="alert alert-warning">Usuário não está inscrito. Inscrevendo primeiro...</div>';
        
        // Inscrever usuário primeiro
        $enrol_instance = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => 'manual'
        ]);
        
        if ($enrol_instance) {
            $enrol_plugin = enrol_get_plugin('manual');
            $student_role = $DB->get_record('role', ['shortname' => 'student']);
            $enrol_plugin->enrol_user($enrol_instance, $userid, $student_role->id, time());
            echo '<div class="alert alert-info">Usuário inscrito como estudante.</div>';
        }
    }
    
    // Suspender todas as inscrições
    $sql = "UPDATE {user_enrolments} ue
            SET ue.status = 1, ue.timemodified = :timemodified
            WHERE ue.userid = :userid 
            AND ue.enrolid IN (SELECT id FROM {enrol} WHERE courseid = :courseid)";
    
    $DB->execute($sql, [
        'timemodified' => time(),
        'userid' => $userid,
        'courseid' => $courseid
    ]);
    
    echo '<div class="alert alert-warning">Usuário suspenso no curso.</div>';
    check_enrolment_status($userid, $courseid);
}

/**
 * Testa a funcionalidade de remoção automática de suspensão
 */
function test_enrolment_with_suspension_removal($userid, $courseid) {
    global $DB;
    
    echo '<div class="alert alert-info">Testando remoção automática de suspensão...</div>';
    
    // Status antes do teste
    echo '<h4>Status ANTES:</h4>';
    check_enrolment_status($userid, $courseid);
    
    // Criar uma solicitação de teste para usar o sistema completo
    try {
        // Criar solicitação de inscrição
        $record = new stdClass();
        $record->userid = get_admin()->id; // Usar admin como solicitante
        $record->timecreated = time();
        $record->timemodified = time();
        $record->tipo_acao = 'inscricao';
        $record->papel = 'student';
        $record->observacoes = 'Teste automático de remoção de suspensão';
        $record->status = 'pendente';
        $record->adminid = null;
        
        $solicitacao_id = $DB->insert_record('local_solicitacoes', $record);
        
        // Adicionar curso relacionado
        $curso_record = new stdClass();
        $curso_record->solicitacao_id = $solicitacao_id;
        $curso_record->curso_id = $courseid;
        $curso_record->timecreated = time();
        $DB->insert_record('local_curso_solicitacoes', $curso_record);
        
        // Adicionar usuário relacionado
        $user_record = new stdClass();
        $user_record->solicitacao_id = $solicitacao_id;
        $user_record->usuario_id = $userid;
        $user_record->timecreated = time();
        $DB->insert_record('local_usuarios_solicitacoes', $user_record);
        
        echo '<div class="alert alert-info">Solicitação de teste criada (ID: ' . $solicitacao_id . ')</div>';
        
        // Executar a ação usando o controller
        $result = \local_solicitacoes\solicitacoes_controller::execute_request_action($solicitacao_id);
        
        echo '<h4>Resultado da Execução:</h4>';
        if ($result['success']) {
            echo '<div class="alert alert-success">' . $result['message'] . '</div>';
        } else {
            echo '<div class="alert alert-danger">' . $result['message'] . '</div>';
        }
        
        echo '<h4>Status DEPOIS:</h4>';
        check_enrolment_status($userid, $courseid);
        
        // Limpar dados de teste
        $DB->delete_records('local_usuarios_solicitacoes', ['solicitacao_id' => $solicitacao_id]);
        $DB->delete_records('local_curso_solicitacoes', ['solicitacao_id' => $solicitacao_id]);
        $DB->delete_records('local_solicitacoes', ['id' => $solicitacao_id]);
        
        echo '<div class="alert alert-secondary">Dados de teste removidos.</div>';
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Erro: ' . $e->getMessage() . '</div>';
        
        // Tentar limpar dados mesmo em caso de erro
        if (isset($solicitacao_id)) {
            try {
                $DB->delete_records('local_usuarios_solicitacoes', ['solicitacao_id' => $solicitacao_id]);
                $DB->delete_records('local_curso_solicitacoes', ['solicitacao_id' => $solicitacao_id]);
                $DB->delete_records('local_solicitacoes', ['id' => $solicitacao_id]);
            } catch (Exception $cleanup_error) {
                echo '<div class="alert alert-warning">Erro ao limpar dados de teste: ' . $cleanup_error->getMessage() . '</div>';
            }
        }
    }
}

echo $OUTPUT->footer();