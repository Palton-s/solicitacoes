<?php
namespace local_solicitacoes;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * Controller para gerenciar solicitações
 *
 * @package    local_solicitacoes
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class solicitacoes_controller {

    /**
     * Processa os dados do formulário de solicitação e salva no banco
     * 
     * @param \stdClass $data Dados submetidos do formulário
     * @return bool True se sucesso, false caso contrário
     */
    public static function process_request_submission($data) {
        global $DB, $USER, $OUTPUT;
        
        error_log("process_request_submission: recebidos dados = " . print_r($data, true));
        
        try {
            // Debug - verificar dados recebidos
            error_log("Form data received: " . print_r($data, true));
            
            // Criar o registro da solicitação
            $record = self::build_request_record($data);
            
            // Debug - verificar record final
            error_log("Record to insert: " . print_r($record, true));

            // Salvar no banco
            $id = $DB->insert_record('local_solicitacoes', $record);
            
            error_log("process_request_submission: solicitação criada com ID = $id");
            
            if ($id) {
                // Salvar cursos relacionados (apenas para tipos de ação que não são criação de curso)
                if ($data->tipo_acao != 'criar_curso') {
                    error_log("process_request_submission: salvando cursos relacionados");
                    self::save_related_courses($id, $data);
                }
                
                // Salvar usuários relacionados (não aplicável para cadastro e criação de curso)
                if ($data->tipo_acao != 'cadastro' && $data->tipo_acao != 'criar_curso') {
                    error_log("process_request_submission: salvando usuários relacionados");
                    self::save_related_users($id, $data);
                }
                
                // Enviar notificação de solicitação criada
                local_solicitacoes_notify_criada($id);
                
                return true;
            } else {
                error_log("process_request_submission: FALHA ao criar solicitação");
                return false;
            }
            
        } catch (\Exception $e) {
            error_log("Erro ao processar formulário: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Constrói o objeto record para inserir no banco de dados
     * 
     * @param \stdClass $data Dados do formulário
     * @return \stdClass Record pronto para inserção
     */
    private static function build_request_record($data) {
        global $USER;
        
        $record = new \stdClass();
        $record->userid         = $USER->id;
        $record->timecreated    = time();
        $record->timemodified   = time();
        $record->tipo_acao      = $data->tipo_acao;
        $record->papel          = (($data->tipo_acao == 'inscricao' || $data->tipo_acao == 'cadastro') && !empty($data->papel)) ? $data->papel : '';
        
        // Construir observações com informações detalhadas para inscrições
        $observacoes_completas = !empty($data->observacoes) ? $data->observacoes : '';
        
        if ($data->tipo_acao == 'inscricao') {
            $observacoes_info = '';
            
            // Informações dos cursos selecionados
            if (!empty($data->curso_nome)) {
                $cursos_selecionados = is_array($data->curso_nome) ? $data->curso_nome : [$data->curso_nome];
                
                if (!empty($cursos_selecionados)) {
                    global $DB;
                    $cursos_info = array();
                    
                    foreach ($cursos_selecionados as $curso_id) {
                        $curso_id = (int)$curso_id;
                        if ($curso_id > 0) {
                            $curso = $DB->get_record('course', ['id' => $curso_id], 'id, fullname, shortname');
                            if ($curso) {
                                $cursos_info[] = $curso->fullname . ' (' . $curso->shortname . ') - ID: ' . $curso->id;
                            }
                        }
                    }
                    
                    if (!empty($cursos_info)) {
                        $observacoes_info .= "CURSOS SELECIONADOS:\n" . implode("\n", $cursos_info) . "\n";
                        $observacoes_info .= "IDS DOS CURSOS: " . implode(',', $cursos_selecionados) . "\n";
                    }
                }
            }
            
            // Informações dos usuários
            if (!empty($data->usuarios_busca)) {
                $usuarios_array = is_array($data->usuarios_busca) ? $data->usuarios_busca : explode(',', $data->usuarios_busca);
                
                if (count($usuarios_array) > 0) {
                    $usernames = array();
                    foreach ($usuarios_array as $userid) {
                        $userid = trim($userid);
                        if (!empty($userid) && is_numeric($userid)) {
                            $user = $DB->get_record('user', ['id' => $userid], 'firstname, lastname, email');
                            if ($user) {
                                $usernames[] = fullname($user) . ' (' . $user->email . ')';
                            }
                        }
                    }
                    if (!empty($usernames)) {
                        $observacoes_info .= "USUÁRIOS SELECIONADOS: " . implode(', ', $usernames) . "\n";
                        $observacoes_info .= "IDs DOS USUÁRIOS: " . implode(',', $usuarios_array) . "\n";
                    }
                }
            }
            
            // Combinar observações
            if (!empty($observacoes_info)) {
                if (!empty($observacoes_completas)) {
                    $observacoes_completas = $observacoes_info . "OBSERVAÇÕES ADICIONAIS: " . $observacoes_completas;
                } else {
                    $observacoes_completas = $observacoes_info;
                }
            }
        }
        
        $record->observacoes = $observacoes_completas;
        $record->status         = 'pendente';
        $record->adminid        = null;
        
        // Adicionar campos de cadastro de usuário se aplicável
        if ($data->tipo_acao == 'cadastro') {
            // Campos diretos do moodleform
            $record->firstname  = !empty($data->firstname) ? trim($data->firstname) : '';
            $record->lastname   = !empty($data->lastname) ? trim($data->lastname) : '';
            $record->cpf        = !empty($data->cpf) ? preg_replace('/[^0-9]/', '', $data->cpf) : '';
            $record->email      = !empty($data->email_novo_usuario) ? $data->email_novo_usuario : '';
            
            // Fallback para campos antigos (compatibilidade com templates antigos)
            if (empty($record->firstname) && !empty($data->nome_completo)) {
                $parts = explode(' ', trim($data->nome_completo), 2);
                $record->firstname = isset($parts[0]) ? $parts[0] : '';
                $record->lastname = isset($parts[1]) ? $parts[1] : '';
            }
            if (empty($record->email) && !empty($data->email)) {
                $record->email = $data->email;
            }
        }
        
        // Adicionar campos de criação de curso se aplicável
        if ($data->tipo_acao == 'criar_curso') {
            $record->codigo_sigaa           = !empty($data->codigo_sigaa) ? $data->codigo_sigaa : '';
            $record->course_shortname       = !empty($data->course_shortname) ? $data->course_shortname : '';
            $record->course_summary         = !empty($data->course_summary) ? $data->course_summary : '';
            $record->unidade_academica_id   = !empty($data->unidade_academica_id) ? (int)$data->unidade_academica_id : 0;
            $record->ano_semestre           = !empty($data->ano_semestre) ? $data->ano_semestre : '';
            $record->razoes_criacao         = !empty($data->razoes_criacao) ? $data->razoes_criacao : '';
        }
        
        return $record;
    }

    /**
     * Salva os cursos relacionados à solicitação na tabela de relacionamento
     * 
     * @param int $solicitacao_id ID da solicitação criada
     * @param \stdClass $data Dados do formulário
     */
    private static function save_related_courses($solicitacao_id, $data) {
        global $DB;
        
        error_log("save_related_courses: solicitacao_id = $solicitacao_id");
        error_log("save_related_courses: data = " . print_r($data, true));
        
        // Verificar primeiro o formato novo (moodleform) - múltiplos cursos
        if (!empty($data->curso_nome)) {
            $cursos_selecionados = is_array($data->curso_nome) ? $data->curso_nome : [$data->curso_nome];
            
            error_log("save_related_courses: cursos selecionados = " . print_r($cursos_selecionados, true));
            
            foreach ($cursos_selecionados as $curso_id) {
                $curso_id = (int)$curso_id;
                if ($curso_id > 0) {
                    $curso_record = new \stdClass();
                    $curso_record->solicitacao_id = $solicitacao_id;
                    $curso_record->curso_id = $curso_id;
                    $curso_record->timecreated = time();
                    
                    error_log("save_related_courses: inserindo curso_record = " . print_r($curso_record, true));
                    
                    $result = $DB->insert_record('local_curso_solicitacoes', $curso_record);
                    error_log("save_related_courses: resultado da inserção = " . ($result ? $result : 'FALHOU'));
                }
            }
        }
        // Fallback para formato antigo
        elseif (!empty($data->curso_id_selected)) {
            $curso_record = new \stdClass();
            $curso_record->solicitacao_id = $solicitacao_id;
            $curso_record->curso_id = (int)$data->curso_id_selected;
            $curso_record->timecreated = time();
            
            error_log("save_related_courses: inserindo curso_record (formato antigo) = " . print_r($curso_record, true));
            
            $result = $DB->insert_record('local_curso_solicitacoes', $curso_record);
            error_log("save_related_courses: resultado da inserção (formato antigo) = " . ($result ? $result : 'FALHOU'));
        } else {
            error_log("save_related_courses: NENHUM CURSO ENCONTRADO NOS DADOS");
        }
    }

    /**
     * Salva os usuários relacionados à solicitação na tabela de relacionamento
     * 
     * @param int $solicitacao_id ID da solicitação criada
     * @param \stdClass $data Dados do formulário
     */
    private static function save_related_users($solicitacao_id, $data) {
        global $DB;
        
        $usuarios_nomes = '';
        
        // Verificar primeiro o formato novo (moodleform)
        if (!empty($data->usuarios_busca)) {
            $usuarios_array = is_array($data->usuarios_busca) ? $data->usuarios_busca : explode(',', $data->usuarios_busca);
            
            error_log("Usuarios IDs recebidos (novo formato): " . print_r($usuarios_array, true));
            
            // Salvar cada usuário na tabela de relacionamento  
            $timecreated = time();
            foreach ($usuarios_array as $user_id) {
                $user_id = (int)trim($user_id);
                if ($user_id > 0) {
                    // Verificar se usuário existe
                    $user = $DB->get_record('user', array('id' => $user_id), 'id');
                    if ($user) {
                        $usuario_record = new \stdClass();
                        $usuario_record->solicitacao_id = $solicitacao_id;
                        $usuario_record->usuario_id = $user_id;
                        $usuario_record->timecreated = $timecreated;
                        $DB->insert_record('local_usuarios_solicitacoes', $usuario_record);
                        error_log("Usuario ID $user_id vinculado à solicitação $solicitacao_id");
                    }
                }
            }
        }
        // Fallback para formato antigo
        elseif (!empty($data->usuarios_ids_selected)) {
            error_log("Usuarios IDs recebidos: " . $data->usuarios_ids_selected);
            
            // Limpar e processar IDs
            $user_ids_string = trim($data->usuarios_ids_selected);
            if (!empty($user_ids_string)) {
                // Se for string separada por vírgulas
                $user_ids = array_filter(array_map('intval', explode(',', $user_ids_string)));
                
                error_log("User IDs processados: " . print_r($user_ids, true));
                
                // Salvar cada usuário na tabela de relacionamento
                $timecreated = time();
                foreach ($user_ids as $user_id) {
                    if ($user_id > 0) {
                        // Verificar se usuário existe
                        $user = $DB->get_record('user', array('id' => $user_id), 'id');
                        if ($user) {
                            $usuario_record = new \stdClass();
                            $usuario_record->solicitacao_id = $solicitacao_id;
                            $usuario_record->usuario_id = $user_id;
                            $usuario_record->timecreated = $timecreated;
                            $DB->insert_record('local_usuarios_solicitacoes', $usuario_record);
                            error_log("Usuario ID $user_id vinculado à solicitação $solicitacao_id");
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Busca informações dos cursos relacionados à solicitação
     * 
     * @param int $solicitacao_id ID da solicitação
     * @return array Array de cursos
     */
    public static function get_related_courses($solicitacao_id) {
        global $DB;
        
        error_log("get_related_courses: buscando cursos para solicitacao_id = $solicitacao_id");
        
        $sql = "SELECT c.id, c.fullname, c.shortname, cs.timecreated
                FROM {local_curso_solicitacoes} cs
                JOIN {course} c ON c.id = cs.curso_id
                WHERE cs.solicitacao_id = :solicitacao_id";
        
        $cursos = $DB->get_records_sql($sql, array('solicitacao_id' => $solicitacao_id));
        
        error_log("get_related_courses: encontrados " . count($cursos) . " cursos");
        error_log("get_related_courses: cursos = " . print_r($cursos, true));
        
        return $cursos;
    }
    
    /**
     * Busca informações dos usuários relacionados à solicitação
     * 
     * @param int $solicitacao_id ID da solicitação
     * @return array Array de usuários
     */
    public static function get_related_users($solicitacao_id) {
        global $DB;
        
        $sql = "SELECT u.id, u.firstname, u.lastname, u.username, u.email, us.timecreated
                FROM {local_usuarios_solicitacoes} us
                JOIN {user} u ON u.id = us.usuario_id
                WHERE us.solicitacao_id = :solicitacao_id";
        
        return $DB->get_records_sql($sql, array('solicitacao_id' => $solicitacao_id));
    }

    /**
     * Envia email de confirmação para o usuário
     * 
     * @param int $request_id ID da solicitação criada
     * @param \stdClass $record Dados da solicitação
     */
    private static function send_confirmation_email($request_id, $record) {
        global $DB, $USER;
        
        $user = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
        
        // Preparar dados para o email
        $tipo_acao_str = '';
        switch($record->tipo_acao) {
            case 'inscricao':
                $tipo_acao_str = get_string('acao_inscricao', 'local_solicitacoes');
                break;
            case 'remocao':
                $tipo_acao_str = get_string('acao_remocao', 'local_solicitacoes');
                break;
            case 'suspensao':
                $tipo_acao_str = get_string('acao_suspensao', 'local_solicitacoes');
                break;
            default:
                $tipo_acao_str = $record->tipo_acao;
        }
        
        $papel_str = '';
        if (!empty($record->papel)) {
            $papel_key = 'papel_' . $record->papel;
            $papel_str = get_string($papel_key, 'local_solicitacoes');
        }
        
        // Montar assunto e mensagem
        $subject = 'Solicitação #' . $request_id . ' - ' . $tipo_acao_str;
        
        $messagehtml = '<html><body>';
        $messagehtml .= '<h2>Confirmação de Solicitação</h2>';
        $messagehtml .= '<p>Olá ' . fullname($user) . ',</p>';
        $messagehtml .= '<p>Sua solicitação foi registrada com sucesso!</p>';
        $messagehtml .= '<hr>';
        $messagehtml .= '<p><strong>ID da Solicitação:</strong> #' . $request_id . '</p>';
        $messagehtml .= '<p><strong>Tipo de Ação:</strong> ' . $tipo_acao_str . '</p>';
        $messagehtml .= '<p><strong>Curso:</strong> ' . format_string($record->curso_nome) . '</p>';
        
        if (!empty($record->usuarios_nomes)) {
            $messagehtml .= '<p><strong>Usuários:</strong><br>' . nl2br(format_string($record->usuarios_nomes)) . '</p>';
        }
        
        if (!empty($papel_str)) {
            $messagehtml .= '<p><strong>Papel:</strong> ' . $papel_str . '</p>';
        }
        
        if (!empty($record->observacoes)) {
            $messagehtml .= '<p><strong>Observações:</strong><br>' . nl2br(format_string($record->observacoes)) . '</p>';
        }
        
        $messagehtml .= '<p><strong>Status:</strong> Pendente</p>';
        $messagehtml .= '<p><strong>Data:</strong> ' . userdate($record->timecreated) . '</p>';
        $messagehtml .= '<hr>';
        $messagehtml .= '<p>Você receberá uma notificação quando o status da sua solicitação for atualizado.</p>';
        $messagehtml .= '</body></html>';
        
        // Versão texto simples
        $messagetext = "Confirmação de Solicitação\n\n";
        $messagetext .= "Olá " . fullname($user) . ",\n\n";
        $messagetext .= "Sua solicitação foi registrada com sucesso!\n\n";
        $messagetext .= "ID da Solicitação: #" . $request_id . "\n";
        $messagetext .= "Tipo de Ação: " . $tipo_acao_str . "\n";
        $messagetext .= "Curso: " . format_string($record->curso_nome) . "\n";
        
        if (!empty($record->usuarios_nomes)) {
            $messagetext .= "Usuários:\n" . format_string($record->usuarios_nomes) . "\n";
        }
        
        if (!empty($papel_str)) {
            $messagetext .= "Papel: " . $papel_str . "\n";
        }
        
        if (!empty($record->observacoes)) {
            $messagetext .= "Observações:\n" . format_string($record->observacoes) . "\n";
        }
        
        $messagetext .= "Status: Pendente\n";
        $messagetext .= "Data: " . userdate($record->timecreated) . "\n\n";
        $messagetext .= "Você receberá uma notificação quando o status da sua solicitação for atualizado.\n";
        
        // Enviar email
        $from = \core_user::get_noreply_user();
        $success = email_to_user($user, $from, $subject, $messagetext, $messagehtml);
        
        if ($success) {
            error_log("Email enviado com sucesso para " . $user->email);
        } else {
            error_log("Falha ao enviar email para " . $user->email);
        }
    }

    /**
     * Executa a ação solicitada (inscrição, remoção ou suspensão)
     * 
     * @param int $solicitacao_id ID da solicitação
     * @return array Array com 'success' (bool) e 'message' (string)
     */
    public static function execute_request_action($solicitacao_id) {
        global $DB;
        
        error_log("execute_request_action: iniciando para solicitacao_id = $solicitacao_id");
        
        try {
            // Buscar a solicitação
            $solicitacao = $DB->get_record('local_solicitacoes', ['id' => $solicitacao_id], '*', MUST_EXIST);
            
            error_log("execute_request_action: solicitação encontrada: " . print_r($solicitacao, true));
            
            // Para criação de curso, não precisa de cursos ou usuários relacionados
            if ($solicitacao->tipo_acao == 'criar_curso') {
                return self::create_course($solicitacao);
            }
            
            // Buscar cursos relacionados
            $cursos = self::get_related_courses($solicitacao_id);
            
            error_log("execute_request_action: cursos encontrados = " . count($cursos));
            
            if (empty($cursos)) {
                error_log("execute_request_action: ERRO - Nenhum curso encontrado para esta solicitação");
                return ['success' => false, 'message' => 'Nenhum curso encontrado para esta solicitação.'];
            }
            
            // Para cadastro, não precisa buscar usuários existentes
            if ($solicitacao->tipo_acao == 'cadastro') {
                return self::create_and_enrol_user($solicitacao, $cursos);
            }
            
            // Buscar usuários relacionados (para outros tipos de ação)
            $usuarios = self::get_related_users($solicitacao_id);
            
            if (empty($usuarios)) {
                return ['success' => false, 'message' => 'Nenhum usuário encontrado para esta solicitação.'];
            }
            
            // Executar ação conforme o tipo
            switch ($solicitacao->tipo_acao) {
                case 'inscricao':
                    return self::enrol_users($cursos, $usuarios, $solicitacao->papel);
                    
                case 'remocao':
                    return self::unenrol_users($cursos, $usuarios);
                    
                case 'suspensao':
                    return self::suspend_users($cursos, $usuarios);
                    
                default:
                    return ['success' => false, 'message' => 'Tipo de ação desconhecido: ' . $solicitacao->tipo_acao];
            }
            
        } catch (\Exception $e) {
            error_log("Erro ao executar ação da solicitação #$solicitacao_id: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao executar ação: ' . $e->getMessage()];
        }
    }

    /**
     * Inscreve usuários em cursos com papel específico
     * 
     * @param array $cursos Array de cursos
     * @param array $usuarios Array de usuários
     * @param string $papel Papel/role a ser atribuído
     * @return array Array com 'success' e 'message'
     */
    private static function enrol_users($cursos, $usuarios, $papel) {
        global $DB;
        
        // Obter ID do papel
        $role = $DB->get_record('role', ['shortname' => $papel], '*', MUST_EXIST);
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($cursos as $curso) {
            $course = $DB->get_record('course', ['id' => $curso->id], '*', MUST_EXIST);
            $context = \context_course::instance($course->id);
            
            // Obter plugin de inscrição manual
            $enrol_instance = $DB->get_record('enrol', [
                'courseid' => $course->id,
                'enrol' => 'manual'
            ], '*', MUST_EXIST);
            
            $enrol_plugin = enrol_get_plugin('manual');
            
            foreach ($usuarios as $usuario) {
                try {
                    // Inscrever usuário
                    $enrol_plugin->enrol_user($enrol_instance, $usuario->id, $role->id, time());
                    $success_count++;
                    error_log("Usuário {$usuario->id} inscrito no curso {$course->id} como {$papel}");
                } catch (\Exception $e) {
                    $error_count++;
                    error_log("Erro ao inscrever usuário {$usuario->id} no curso {$course->id}: " . $e->getMessage());
                }
            }
        }
        
        if ($error_count > 0) {
            return [
                'success' => false,
                'message' => "Inscrição parcial: $success_count sucesso(s), $error_count erro(s)"
            ];
        }
        
        return [
            'success' => true,
            'message' => "Usuários inscritos com sucesso! Total: $success_count"
        ];
    }

    /**
     * Remove usuários de cursos
     * 
     * @param array $cursos Array de cursos
     * @param array $usuarios Array de usuários
     * @return array Array com 'success' e 'message'
     */
    private static function unenrol_users($cursos, $usuarios) {
        global $DB;
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($cursos as $curso) {
            $course = $DB->get_record('course', ['id' => $curso->id], '*', MUST_EXIST);
            
            // Obter plugin de inscrição manual
            $enrol_instance = $DB->get_record('enrol', [
                'courseid' => $course->id,
                'enrol' => 'manual'
            ]);
            
            if (!$enrol_instance) {
                error_log("Instância de inscrição manual não encontrada para o curso {$course->id}");
                continue;
            }
            
            $enrol_plugin = enrol_get_plugin('manual');
            
            foreach ($usuarios as $usuario) {
                try {
                    // Remover usuário
                    $enrol_plugin->unenrol_user($enrol_instance, $usuario->id);
                    $success_count++;
                    error_log("Usuário {$usuario->id} removido do curso {$course->id}");
                } catch (\Exception $e) {
                    $error_count++;
                    error_log("Erro ao remover usuário {$usuario->id} do curso {$course->id}: " . $e->getMessage());
                }
            }
        }
        
        if ($error_count > 0) {
            return [
                'success' => false,
                'message' => "Remoção parcial: $success_count sucesso(s), $error_count erro(s)"
            ];
        }
        
        return [
            'success' => true,
            'message' => "Usuários removidos com sucesso! Total: $success_count"
        ];
    }

    /**
     * Suspende inscrições de usuários em cursos
     * 
     * @param array $cursos Array de cursos
     * @param array $usuarios Array de usuários
     * @return array Array com 'success' e 'message'
     */
    private static function suspend_users($cursos, $usuarios) {
        global $DB;
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($cursos as $curso) {
            foreach ($usuarios as $usuario) {
                try {
                    // Buscar inscrição do usuário no curso
                    $sql = "SELECT ue.*
                            FROM {user_enrolments} ue
                            JOIN {enrol} e ON e.id = ue.enrolid
                            WHERE e.courseid = :courseid
                            AND ue.userid = :userid";
                    
                    $enrolments = $DB->get_records_sql($sql, [
                        'courseid' => $curso->id,
                        'userid' => $usuario->id
                    ]);
                    
                    if (empty($enrolments)) {
                        error_log("Usuário {$usuario->id} não está inscrito no curso {$curso->id}");
                        $error_count++;
                        continue;
                    }
                    
                    // Suspender todas as inscrições do usuário no curso
                    foreach ($enrolments as $enrolment) {
                        $enrolment->status = 1; // 1 = suspenso
                        $enrolment->timemodified = time();
                        $DB->update_record('user_enrolments', $enrolment);
                        $success_count++;
                        error_log("Inscrição do usuário {$usuario->id} suspensa no curso {$curso->id}");
                    }
                    
                } catch (\Exception $e) {
                    $error_count++;
                    error_log("Erro ao suspender usuário {$usuario->id} no curso {$curso->id}: " . $e->getMessage());
                }
            }
        }
        
        if ($error_count > 0) {
            return [
                'success' => false,
                'message' => "Suspensão parcial: $success_count sucesso(s), $error_count erro(s)"
            ];
        }
        
        return [
            'success' => true,
            'message' => "Usuários suspensos com sucesso! Total: $success_count"
        ];
    }

    /**
     * Cria um novo usuário e o inscreve nos cursos especificados
     * 
     * @param \stdClass $solicitacao Objeto da solicitação com dados do novo usuário
     * @param array $cursos Array de cursos para inscrever o usuário
     * @return array Array com 'success' e 'message'
     */
    private static function create_and_enrol_user($solicitacao, $cursos) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        
        try {
            // Validar se CPF (username) já existe
            if ($DB->record_exists('user', ['username' => $solicitacao->cpf])) {
                return [
                    'success' => false,
                    'message' => get_string('error_cpf_exists', 'local_solicitacoes')
                ];
            }
            
            // Validar se email já existe
            if ($DB->record_exists('user', ['email' => $solicitacao->email])) {
                return [
                    'success' => false,
                    'message' => get_string('error_email_exists', 'local_solicitacoes')
                ];
            }
            
            // Criar objeto do novo usuário
            $newuser = new \stdClass();
            $newuser->username      = $solicitacao->cpf;
            $newuser->firstname     = $solicitacao->firstname;
            $newuser->lastname      = $solicitacao->lastname;
            $newuser->email         = $solicitacao->email;
            $newuser->auth          = 'manual';
            $newuser->confirmed     = 1;
            $newuser->mnethostid    = $CFG->mnet_localhost_id;
            $newuser->password      = hash_internal_user_password(generate_password());
            
            // Criar usuário
            $newuserid = user_create_user($newuser, true, false);
            
            if (!$newuserid) {
                return [
                    'success' => false,
                    'message' => 'Erro ao criar usuário no sistema.'
                ];
            }
            
            // Criar array com o novo usuário para o método de inscrição
            $usuario = $DB->get_record('user', ['id' => $newuserid]);
            $usuarios = [$usuario];
            
            // Inscrever o usuário nos cursos com o papel especificado
            $enrol_result = self::enrol_users($cursos, $usuarios, $solicitacao->papel);
            
            if ($enrol_result['success']) {
                $username = $newuser->firstname . ' ' . $newuser->lastname;
                return [
                    'success' => true,
                    'message' => get_string('usuario_criado', 'local_solicitacoes', $username) . ' ' . $enrol_result['message']
                ];
            } else {
                // Usuário foi criado mas houve erro na inscrição
                return [
                    'success' => false,
                    'message' => "Usuário criado, mas houve erro na inscrição: " . $enrol_result['message']
                ];
            }
            
        } catch (\Exception $e) {
            error_log("Erro ao criar e inscrever usuário: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao criar usuário: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cria um novo curso no Moodle baseado na solicitação
     * 
     * @param \stdClass $solicitacao Objeto da solicitação com dados do curso
     * @return array Array com 'success' e 'message'
     */
    private static function create_course($solicitacao) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        
        try {
            // Validar se shortname já existe
            if ($DB->record_exists('course', ['shortname' => $solicitacao->course_shortname])) {
                return [
                    'success' => false,
                    'message' => get_string('error_course_shortname_duplicate', 'local_solicitacoes')
                ];
            }
            
            // Validar se a categoria existe
            if (!$DB->record_exists('course_categories', ['id' => $solicitacao->unidade_academica_id])) {
                return [
                    'success' => false,
                    'message' => 'Categoria/Unidade acadêmica não encontrada.'
                ];
            }
            
            // Criar objeto do novo curso
            $newcourse = new \stdClass();
            $newcourse->fullname        = $solicitacao->codigo_sigaa; // Nome completo será o código SIGAA
            $newcourse->shortname       = $solicitacao->course_shortname;
            $newcourse->summary         = !empty($solicitacao->course_summary) ? $solicitacao->course_summary : '';
            $newcourse->summaryformat   = FORMAT_HTML;
            $newcourse->category        = $solicitacao->unidade_academica_id;
            $newcourse->format          = 'topics'; // Formato de tópicos por padrão
            $newcourse->visible         = 0; // Oculto inicialmente
            $newcourse->startdate       = time();
            $newcourse->enddate         = 0;
            $newcourse->showgrades      = 1;
            $newcourse->enablecompletion = 1;
            
            // Criar o curso usando a API do Moodle
            $createdcourse = create_course($newcourse);
            
            if (!$createdcourse) {
                return [
                    'success' => false,
                    'message' => 'Erro ao criar curso no sistema.'
                ];
            }
            
            // Criar 6 tópicos por padrão (além da seção 0 geral)
            course_create_sections_if_missing($createdcourse, range(0, 6));
            
            // Salvar relação do curso criado com a solicitação
            $curso_record = new \stdClass();
            $curso_record->solicitacao_id = $solicitacao->id;
            $curso_record->curso_id = $createdcourse->id;
            $curso_record->timecreated = time();
            $DB->insert_record('local_curso_solicitacoes', $curso_record);
            
            // Inscrever o solicitante como professor editor
            $context = \context_course::instance($createdcourse->id);
            $role = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
            
            // Obter instância de inscrição manual
            $enrol_instance = $DB->get_record('enrol', [
                'courseid' => $createdcourse->id,
                'enrol' => 'manual'
            ]);
            
            if ($enrol_instance) {
                $enrol_plugin = enrol_get_plugin('manual');
                $enrol_plugin->enrol_user($enrol_instance, $solicitacao->userid, $role->id, time());
                error_log("Usuário {$solicitacao->userid} inscrito como editingteacher no curso {$createdcourse->id}");
            }
            
            return [
                'success' => true,
                'message' => get_string('course_created_success', 'local_solicitacoes', $createdcourse->fullname)
            ];
            
        } catch (\Exception $e) {
            error_log("Erro ao criar curso: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao criar curso: ' . $e->getMessage()
            ];
        }
    }
}
