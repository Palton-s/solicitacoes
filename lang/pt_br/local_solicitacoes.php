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
 * Language strings for the Solicitações plugin (Portuguese BR).
 *
 * @package    local_solicitacoes
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Solicitações de Gerenciamento de Curso';

// Capabilities
$string['solicitacoes:view']    = 'Ver próprias solicitações';
$string['solicitacoes:viewall'] = 'Ver todas as solicitações';
$string['solicitacoes:submit']  = 'Enviar solicitações de gerenciamento de curso';
$string['solicitacoes:manage']  = 'Gerenciar solicitações de curso (aprovar/negar/excluir)';

$string['request_form_title']   = 'Solicitação de Gerenciamento de Curso';
$string['tipo_acao']            = 'Tipo de Ação';
$string['curso_nome']           = 'Selecionar Curso';
$string['curso_nome_help']      = 'Digite o nome do curso e selecione da lista de cursos disponíveis. Você pode selecionar múltiplos cursos para inscrever os usuários.';
$string['usuarios_busca']       = 'Selecionar Usuários';
$string['usuarios_busca_help']  = 'Digite o nome ou email do usuário e selecione da lista. Você pode selecionar múltiplos usuários.';
$string['usuarios_nomes']       = 'Nomes dos Usuários';
$string['usuarios_nomes_help']  = 'Digite um nome de usuário por linha. Use o nome completo, nome de usuário ou email conforme aparece no Moodle.';
$string['papel']                = 'Papel no Curso';
$string['papel_help']           = 'Selecione o papel que os usuários devem ter no curso (apenas para inscrições).';
$string['papel_label']          = 'Papel no Curso';
$string['papel_help_dinamico']  = 'Selecione o papel que os usuários terão no curso';
$string['select_role']          = 'Selecione um papel...';
$string['observacoes']          = 'Observações';
$string['observacoes_help']     = 'Informações adicionais sobre a solicitação (opcional).';
$string['observacoes_placeholder'] = 'Digite observações adicionais sobre esta solicitação (opcional)...';
$string['request_submit']       = 'Enviar Solicitação';
$string['no_courses_found']     = 'Nenhum curso encontrado';
$string['searching_courses']    = 'Buscando cursos...';
$string['no_users_found']       = 'Nenhum usuário encontrado';
$string['searching_users']      = 'Buscando usuários...';
$string['error_usuarios_required'] = 'Por favor, selecione pelo menos um usuário da lista.';
$string['error_invalid_course']    = 'Curso inválido ou sem acesso.';
$string['form_inscricao_descricao'] = 'Você está solicitando inscrição de usuários em uma disciplina. Selecione o papel apropriado para os usuários.';

$string['acao_inscricao']       = 'Inscrição';
$string['acao_remocao']         = 'Remoção';
$string['acao_suspensao']       = 'Suspensão';
$string['acao_cadastro']        = 'Cadastro de Usuário';

$string['papel_student']        = 'Estudante';
$string['papel_teacher']        = 'Professor';
$string['papel_editingteacher'] = 'Professor Editor';
$string['papel_manager']        = 'Gerente';

$string['list_title']           = 'Solicitações de Gerenciamento de Curso';
$string['access_all_requests']  = 'Acessar todas as solicitações';
$string['status_pendente']      = 'Pendente';
$string['status_aprovado']      = 'Aprovado';
$string['status_negado']        = 'Negado';
$string['approve']              = 'Aprovar';
$string['deny']                 = 'Negar';
$string['aprovar']              = 'Aprovar';
$string['negar']                = 'Negar';
$string['delete']               = 'Excluir';
$string['confirm_delete']       = 'Tem certeza que deseja excluir esta solicitação?';
$string['success_delete']       = 'Solicitação excluída com sucesso.';
$string['status_em_andamento']  = 'Em andamento';
$string['status_concluido']     = 'Concluído';

$string['no_requests']          = 'Nenhuma solicitação encontrada.';
$string['created_at']           = 'Criado em';
$string['user']                 = 'Solicitante';
$string['course']               = 'Curso';
$string['action_type']          = 'Ação';
$string['target_users']         = 'Usuários';
$string['role']                 = 'Papel';
$string['status']               = 'Status';
$string['actions']              = 'Ações';
$string['view']                 = 'Ver';
$string['update_status']        = 'Atualizar status';
$string['success_submit']       = 'Sua solicitação foi enviada com sucesso.';
$string['error_submit']         = 'Erro ao enviar solicitação. Tente novamente.';
$string['success_update']       = 'Status atualizado.';
$string['details']              = 'Detalhes da Solicitação';
$string['back_to_list']         = 'Voltar para lista';
$string['handled_by']           = 'Tratada por';
$string['last_modified']        = 'Última modificação';

// Página de confirmação
$string['thankyou_title']       = 'Solicitação Registrada';
$string['thankyou_message']     = 'Sua solicitação foi registrada com sucesso e está aguardando aprovação do administrador.';
$string['thankyou_next_steps']  = 'Próximos Passos';
$string['thankyou_info']        = 'Você receberá uma notificação quando sua solicitação for processada. Você pode acompanhar o status através do painel de solicitações.';
$string['thankyou_new_request'] = 'Nova Solicitação';
$string['thankyou_back_home']   = 'Voltar ao Início';

// Mensagens de acesso
$string['error_nopermission_submit'] = 'Desculpe, você não tem acesso para criar solicitações. Entre em contato com o administrador do sistema se precisar desta funcionalidade.';
$string['error_nopermission_manage'] = 'Desculpe, você não tem acesso para gerenciar solicitações. Esta função está disponível apenas para administradores.';
$string['error_nopermission_view']   = 'Desculpe, você não tem acesso para visualizar solicitações.';
$string['error_nopermission_viewrequest'] = 'Desculpe, você não tem acesso para visualizar esta solicitação.';
$string['motivo_negacao']            = 'Motivo da Negação';
$string['motivo_negacao_label']      = 'Informe o motivo da negação';
$string['motivo_negacao_required']   = 'Por favor, informe o motivo da negação.';

// Notificações
$string['messageprovider:solicitacao_criada'] = 'Notificação de solicitação criada';
$string['messageprovider:solicitacao_aprovada'] = 'Notificação de solicitação aprovada';
$string['messageprovider:solicitacao_negada'] = 'Notificação de solicitação negada';
$string['notification_criada_subject'] = 'Solicitação Criada com Sucesso';
$string['notification_criada_body'] = 'Sua solicitação de {$a->tipo_acao} para o curso "{$a->curso}" foi criada com sucesso e está aguardando aprovação.';
$string['notification_aprovada_subject'] = 'Solicitação Aprovada';
$string['notification_aprovada_body'] = 'Sua solicitação de {$a->tipo_acao} para o curso "{$a->curso}" foi aprovada!';
$string['notification_negada_subject'] = 'Solicitação Negada';
$string['notification_negada_body'] = 'Sua solicitação de {$a->tipo_acao} para o curso "{$a->curso}" foi negada.\n\nMotivo: {$a->motivo}';

$string['my_requests']               = 'Minhas Solicitações';

// Mensagens de erro de validação
$string['error_curso_required']      = 'Por favor, selecione um curso.';
$string['error_course_required']     = 'Por favor, selecione pelo menos um curso.';
$string['error_invalid_courses']     = 'Os seguintes cursos são inválidos: {$a}';
$string['error_usuarios_required']   = 'Por favor, selecione pelo menos um usuário.';
$string['error_papel_required']      = 'Por favor, selecione um papel para a inscrição.';
$string['error_papel_invalid']       = 'O papel selecionado é inválido.';

// Campos de cadastro de usuário
$string['firstname']                 = 'Primeiro Nome';
$string['firstname_help']            = 'Nome do novo usuário';
$string['lastname']                  = 'Sobrenome';
$string['lastname_help']             = 'Sobrenome do novo usuário';
$string['cpf']                       = 'CPF';
$string['cpf_help']                  = 'CPF será usado como nome de usuário (username). A formatação é automática.';
$string['email_novo_usuario']        = 'E-mail';
$string['email_novo_usuario_help']   = 'E-mail do novo usuário';
$string['error_firstname_required']  = 'Por favor, informe o primeiro nome.';
$string['error_lastname_required']   = 'Por favor, informe o sobrenome.';
$string['error_cpf_required']        = 'Por favor, informe o CPF.';
$string['error_cpf_invalid']         = 'CPF inválido. Informe apenas números (11 dígitos).';
$string['error_cpf_exists']          = 'CPF já cadastrado no sistema.';
$string['error_email_required']      = 'Por favor, informe o e-mail.';
$string['error_email_novo_required'] = 'Por favor, informe o e-mail.';
$string['error_email_invalid']       = 'E-mail inválido.';
$string['error_email_exists']        = 'E-mail já cadastrado no sistema.';
$string['error_motivo_required']     = 'Por favor, informe o motivo da solicitação.';
$string['novo_usuario']              = 'Novo usuário';
$string['usuario_criado']            = 'Usuário {$a} criado com sucesso.';

// Campos de criação de curso
$string['acao_criar_curso']          = 'Criação de Curso';
$string['codigo_sigaa']              = 'Código SIGAA - Turma (Ex.: NUT0028 - Turma 01)';
$string['codigo_sigaa_help']         = 'Informe o código SIGAA seguido da turma. Exemplo: "NUT0028 - Turma 01", "NUT0028 - Turma 02"...';
$string['course_shortname']          = 'Nome curto da disciplina (Apelido_2026.1)';
$string['course_shortname_help']     = 'Informe um nome curto ou apelido para a disciplina, incluindo ano/semestre. Exemplo: "Apelido_2026.1"';
$string['course_fullname']           = 'Nome completo da disciplina';
$string['course_fullname_help']      = 'Nome completo ou título da disciplina';
$string['unidade_academica']         = 'Unidade Acadêmica (Campus/Faculdade/Instituto/Centro) *OBRIGATÓRIO';
$string['unidade_academica_help']    = 'Busque e selecione a unidade acadêmica onde o curso deve ser criado';
$string['select_category']           = 'Selecione uma categoria...';
$string['select_option']             = 'Selecione uma opção...';
$string['ano_semestre']              = 'Ano/Semestre';
$string['ano_semestre_help']         = 'Informe o ano e semestre. Exemplo: "2026.1", "2026.2"';
$string['course_summary']            = 'Sumário';
$string['course_summary_help']       = 'Descrição ou sumário da disciplina';
$string['razoes_criacao']            = 'Razões para criar a disciplina';
$string['razoes_criacao_help']       = 'Professor favor informe: 1- Justificativa; 2- Matrícula/UnB; 3- Código da disciplina no SIGAA ou MatrículaWeb (pós-graduação); 4- Grau de Ensino (Graduação, Mestrado/Doutorado); 5-Data da solicitação (dd/mm/aaaa)';
$string['aviso_criar_curso']         = '<strong>Professor(a) Atenção!</strong><br>Para evitar atraso ou recusa do seu pedido de criação de disciplina, informe corretamente o código SIGAA e a(s) turma(s) da sua disciplina.<br>Exemplo: "NUT0028 - Turma 01", "NUT0028 - Turma 02"...';
$string['error_codigo_sigaa_required'] = 'Por favor, informe o código SIGAA e a turma.';
$string['error_codigo_sigaa_format'] = 'Formato de código SIGAA inválido. Use o padrão: ABC1234 - Turma XX';
$string['error_course_shortname_required'] = 'Por favor, informe o nome curto da disciplina.';
$string['error_course_shortname_duplicate'] = 'Já existe um curso com este nome curto. Por favor, escolha outro nome.';
$string['error_invalid_category']    = 'Categoria inválida ou sem permissão de acesso.';
$string['error_unidade_required']    = 'Por favor, selecione uma unidade acadêmica.';
$string['error_ano_semestre_required'] = 'Por favor, informe o ano/semestre.';
$string['error_razoes_criacao_required'] = 'Por favor, informe as razões para criar a disciplina.';
$string['course_created_success']    = 'Curso "{$a}" criado com sucesso!';
$string['no_categories_found']       = 'Nenhuma categoria encontrada';
$string['searching_categories']      = 'Buscando categorias...';
$string['select_year_semester']      = '× SELECIONE ANO/SEMESTRE E UNIDADE ACADÊМICA';

// Página de seleção de ações
$string['selecionar_acao_titulo']    = 'O que você deseja fazer?';
$string['selecionar_acao_subtitulo'] = 'Escolha o tipo de solicitação que deseja enviar';

// Botões de ação
$string['btn_inscrever_usuario_titulo']  = 'Inscrever Usuário';
$string['btn_inscrever_usuario_desc']    = 'Inscrever usuários em disciplinas com qualquer papel';
$string['btn_remover_usuario_titulo']    = 'Remover Usuário';
$string['btn_remover_usuario_desc']      = 'Remover usuários de uma disciplina';
$string['btn_suspender_usuario_titulo']  = 'Suspender Usuário';
$string['btn_suspender_usuario_desc']    = 'Suspender temporariamente acesso de usuários';
$string['btn_cadastrar_usuario_titulo']  = 'Cadastrar Novo Usuário';
$string['btn_cadastrar_usuario_desc']    = 'Criar novo usuário e vincular a disciplina';
$string['btn_criar_curso_titulo']        = 'Criar Disciplina';
$string['btn_criar_curso_desc']          = 'Solicitar criação de nova disciplina';

// Títulos dos formulários específicos
$string['form_inscricao_titulo']     = 'Solicitar Inscrição de Usuário';
$string['form_inscricao_descricao']  = 'Você está solicitando inscrição de usuários em uma disciplina. Selecione o papel apropriado para os usuários.';
$string['form_remocao_titulo']       = 'Solicitar Remoção de Usuários';
$string['form_remocao_descricao']    = 'Use este formulário para solicitar a remoção de usuários de um ou mais cursos.';
$string['form_suspensao_titulo']     = 'Solicitar Suspensão de Usuários';
$string['form_cadastro_titulo']      = 'Solicitar Cadastro de Novo Usuário';
$string['form_criar_curso_titulo']   = 'Solicitar Criação de Disciplina';
