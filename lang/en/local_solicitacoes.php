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
 * Language strings for the Solicitações plugin (English).
 *
 * @package    local_solicitacoes
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Course Management Requests';
$string['menu_settings'] = 'Request Settings';
$string['menu_manage'] = 'Manage Requests';

// Capabilities
$string['solicitacoes:view']    = 'View own requests';
$string['solicitacoes:viewall'] = 'View all requests';
$string['solicitacoes:submit']  = 'Submit course management requests';
$string['solicitacoes:manage']  = 'Manage course requests (approve/deny/delete)';

$string['request_form_title']   = 'Course Management Request';
$string['tipo_acao']            = 'Action Type';
$string['curso_nome']           = 'Course Name';
$string['curso_nome_help']      = 'Type course name and select from suggestions';
$string['usuarios_busca']       = 'Search Users';
$string['usuarios_busca_help']  = 'Type first name, last name, username, or email to search. Search works with any part of fullname, username, or email address. You can select multiple users.';
$string['usuarios_nomes']       = 'Selected Users';
$string['usuarios_nomes_help']  = 'List of users selected for this request.';
$string['papel']                = 'Course Role';
$string['papel_help']           = 'Select the role users should have in the course (for enrollments only).';
$string['papel_label']          = 'Course Role';
$string['papel_help_dinamico']  = 'Select the role users will have in the course';
$string['select_role']          = 'Select a role...';
$string['observacoes']          = 'Comments';
$string['observacoes_help']     = 'Additional information about the request (optional).';
$string['request_submit']       = 'Submit Request';
$string['no_courses_found']     = 'No courses found';
$string['searching_courses']    = 'Searching courses...';
$string['professores_curso']    = 'Course Teachers (optional)';
$string['professores_curso_help'] = 'Select the teachers to be enrolled in this course. If no teacher is specified, the requester will be enrolled as teacher. You can select multiple users.';
$string['aviso_professores_curso'] = 'If no teacher is selected, the requester will be enrolled as teacher for this course.';

$string['acao_inscricao']       = 'Enrollment';
$string['acao_remocao']         = 'Removal';
$string['acao_cadastro']        = 'User Registration';
$string['acao_suspensao']       = 'Suspension';
$string['acao_remover_curso']   = 'Remove Course';

$string['papel_student']        = 'Student';
$string['papel_teacher']        = 'Teacher';
$string['papel_editingteacher'] = 'Editing Teacher';
$string['papel_manager']        = 'Manager';

$string['list_title']           = 'Course Management Requests';
$string['access_all_requests']  = 'Access all requests';
$string['status_pendente']      = 'Pending';
$string['status_aprovado']      = 'Approved';
$string['status_negado']        = 'Denied';
$string['approve']              = 'Approve';
$string['deny']                 = 'Deny';
$string['aprovar']              = 'Approve';
$string['negar']                = 'Deny';
$string['delete']               = 'Delete';
$string['confirm_delete']       = 'Are you sure you want to delete this request?';
$string['success_delete']       = 'Request deleted successfully.';

$string['no_requests']          = 'No requests found.';
$string['created_at']           = 'Created';
$string['user']                 = 'Requester';
$string['course']               = 'Course';
$string['action_type']          = 'Action';
$string['target_users']         = 'Users';
$string['role']                 = 'Role';
$string['status']               = 'Status';
$string['actions']              = 'Actions';
$string['view']                 = 'View';
$string['update_status']        = 'Update status';
$string['success_submit']       = 'Your request has been sent successfully.';
$string['error_submit']         = 'Error submitting request. Please try again.';
$string['success_update']       = 'Status updated.';
$string['details']              = 'Request Details';
$string['back_to_list']         = 'Back to list';
$string['handled_by']           = 'Handled by';
$string['last_modified']        = 'Last modified';

// Thank you page
$string['thankyou_title']       = 'Request Submitted';
$string['thankyou_message']     = 'Your request has been successfully submitted and is awaiting administrator approval.';
$string['thankyou_next_steps']  = 'Next Steps';
$string['thankyou_info']        = 'You will receive a notification when your request is processed. You can track the status through the requests dashboard.';
$string['thankyou_new_request'] = 'New Request';
$string['thankyou_back_home']   = 'Back to Home';

// Permission errors
$string['error_nopermission_submit'] = 'Sorry, you do not have access to create requests. Please contact your system administrator if you need this functionality.';
$string['error_nopermission_manage'] = 'Sorry, you do not have access to manage requests. This function is available only for administrators.';
$string['error_nopermission_view']   = 'Sorry, you do not have access to view requests.';
$string['error_nopermission_viewrequest'] = 'Sorry, you do not have access to view this request.';
$string['motivo_negacao']            = 'Denial Reason';
$string['motivo_negacao_label']      = 'Please provide the reason for denial';
$string['motivo_negacao_required']   = 'Please provide a reason for the denial.';

// Notifications
$string['messageprovider:solicitacao_criada'] = 'Request created notification';
$string['messageprovider:solicitacao_aprovada'] = 'Request approved notification';
$string['messageprovider:solicitacao_negada'] = 'Request denied notification';
$string['notification_criada_subject'] = 'Request Created Successfully';
$string['notification_criada_body'] = 'Your {$a->tipo_acao} request for the course "{$a->curso}" has been created successfully and is awaiting approval.';
$string['notification_aprovada_subject'] = 'Request Approved';
$string['notification_aprovada_body'] = 'Your {$a->tipo_acao} request for the course "{$a->curso}" has been approved!';
$string['notification_negada_subject'] = 'Request Denied';
$string['notification_negada_body'] = 'Your {$a->tipo_acao} request for the course "{$a->curso}" has been denied.\n\nReason: {$a->motivo}';

$string['my_requests']               = 'My Requests';

// Validation error messages
$string['error_curso_required']      = 'Please select a course.';
$string['error_usuarios_required']   = 'Please select at least one user.';
$string['error_papel_required']      = 'Please select a role for the enrollment.';
$string['error_papel_invalid']       = 'The selected role is invalid.';

// User registration fields
$string['firstname']                 = 'First Name';
$string['firstname_help']            = 'First name of the new user';
$string['lastname']                  = 'Last Name';
$string['lastname_help']             = 'Last name of the new user';
$string['cpf']                       = 'CPF';
$string['cpf_help']                  = 'CPF will be used as username. Formatting is automatic.';
$string['email_novo_usuario']        = 'Email';
$string['email_novo_usuario_help']   = 'Email of the new user';
$string['error_firstname_required']  = 'Please enter the first name.';
$string['error_lastname_required']   = 'Please enter the last name.';
$string['error_cpf_required']        = 'Please enter the CPF.';
$string['error_cpf_invalid']         = 'Invalid CPF. Enter numbers only (11 digits).';
$string['error_cpf_exists']          = 'CPF already registered in the system.';
$string['error_email_required']      = 'Please enter the email.';
$string['error_email_novo_required'] = 'Please enter the email.';
$string['error_email_invalid']       = 'Invalid email.';
$string['error_email_exists']        = 'Email already registered in the system.';
$string['error_motivo_required']     = 'Please provide the reason for the request.';
$string['novo_usuario']              = 'New user';
$string['usuario_criado']            = 'User {$a} created successfully.';

// Course creation fields
$string['acao_criar_curso']          = 'Course Creation';
$string['codigo_sigaa']              = 'SIGAA Code - Class (e.g., NUT0028 - Class 01)';
$string['codigo_sigaa_help']         = 'Enter the SIGAA code followed by the class. Example: "NUT0028 - Class 01", "NUT0028 - Class 02"...';
$string['course_shortname']          = 'Course Short Name (Nickname_2026.1)';
$string['course_shortname_help']     = 'Enter a short name or nickname for the course, including year/semester. Example: "Apelido_2026.1"';
$string['unidade_academica']         = 'Academic Unit (Campus/Faculty/Institute/Center) *REQUIRED';
$string['unidade_academica_help']    = 'Search and select the academic unit where the course should be created';
$string['ano_semestre']              = 'Year/Semester';
$string['ano_semestre_help']         = 'Enter the year and semester. Example: "2026.1", "2026.2"';
$string['course_summary']            = 'Summary';
$string['course_summary_help']       = 'Course description or summary';
$string['razoes_criacao']            = 'Reasons for Creating the Course';
$string['razoes_criacao_help']       = 'Professor please inform: 1- Justification; 2- Registration/UnB; 3- Course code in SIGAA or MatriculaWeb (postgraduate); 4- Degree Level (Undergraduate, Master/Doctorate); 5- Date of request (dd/mm/yyyy)';
$string['aviso_criar_curso']         = '<strong>Professor attention!</strong><br>To avoid delay or refusal of your course creation request, correctly inform the SIGAA code and the class(es) of your course.<br>Example: "NUT0028 - Class 01", "NUT0028 - Class 02"...';
$string['error_codigo_sigaa_required'] = 'Please enter the SIGAA code and class.';
$string['error_course_shortname_required'] = 'Please enter the course short name.';
$string['error_course_shortname_duplicate'] = 'A course with this short name already exists. Please choose another name.';
$string['error_unidade_required']    = 'Please select an academic unit.';
$string['error_ano_semestre_required'] = 'Please enter the year/semester.';
$string['error_razoes_criacao_required'] = 'Please inform the reasons for creating the course.';
$string['course_created_success']    = 'Course "{$a}" created successfully!';
$string['no_categories_found']       = 'No categories found';
$string['searching_categories']      = 'Searching categories...';
$string['select_year_semester']      = '× SELECT YEAR/SEMESTER AND ACADEMIC UNIT';
$string['course_archived_success']   = '{$a} course(s) moved to the hidden category and hidden successfully.';
$string['course_archive_partial']    = 'Partial course removal: {$a->success} success(es), {$a->error} error(s).';
$string['error_hidden_category_not_configured'] = 'Hidden category is not configured in the plugin settings. Configure it in Site administration > Local plugins > Course Management Requests.';
$string['error_hidden_category_invalid'] = 'Configured hidden category is invalid or no longer exists.';
$string['hidden_course_category'] = 'Hidden category for course removal';
$string['hidden_course_category_desc'] = 'Category where courses are moved when a course removal request is approved. The course is also set to hidden.';

// Action selection page
$string['selecionar_acao_titulo']    = 'What would you like to do?';
$string['selecionar_acao_subtitulo'] = 'Choose the type of request you want to submit';
$string['selecionar_usuarios_titulo'] = 'User Actions';
$string['selecionar_usuarios_subtitulo'] = 'Choose a user management action';
$string['selecionar_cursos_titulo'] = 'Course Actions';
$string['selecionar_cursos_subtitulo'] = 'Choose a course management action';

// Action buttons
$string['btn_grupo_usuarios_titulo']  = 'Users';
$string['btn_grupo_usuarios_desc']    = 'Access enrollment, removal, suspension and user registration requests';
$string['btn_grupo_cursos_titulo']    = 'Courses';
$string['btn_grupo_cursos_desc']      = 'Access course creation and removal requests';
$string['btn_inscrever_usuario_titulo']  = 'Enroll User';
$string['btn_inscrever_usuario_desc']    = 'Enroll users in courses with any role';
$string['btn_remover_usuario_titulo']    = 'Remove User';
$string['btn_remover_usuario_desc']      = 'Remove users from a course';
$string['btn_suspender_usuario_titulo']  = 'Suspend User';
$string['btn_suspender_usuario_desc']    = 'Temporarily suspend user access';
$string['btn_cadastrar_usuario_titulo']  = 'Register New User';
$string['btn_cadastrar_usuario_desc']    = 'Create new user and link to course';
$string['btn_criar_curso_titulo']        = 'Create Course';
$string['btn_criar_curso_desc']          = 'Request creation of a new course';
$string['btn_remover_curso_titulo']      = 'Remove Course';
$string['btn_remover_curso_desc']        = 'Request logical course removal (move to hidden category)';

// Specific form titles
$string['form_inscricao_titulo']     = 'Request User Enrollment';
$string['form_inscricao_descricao']  = 'You are requesting user enrollment in a course. Select the appropriate role for the users.';
$string['form_remocao_titulo']       = 'Request User Removal';
$string['form_suspensao_titulo']     = 'Request User Suspension';
$string['form_cadastro_titulo']      = 'Request New User Registration';
$string['form_criar_curso_titulo']   = 'Request Course Creation';
$string['form_remover_curso_titulo'] = 'Request Course Removal';
$string['form_remover_curso_descricao'] = 'On approval, the course is not deleted. It is moved to the configured hidden category and set to hidden.';
