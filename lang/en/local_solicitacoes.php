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
$string['usuarios_busca_help']  = 'Type to search and select users. You can select multiple users.';
$string['usuarios_nomes']       = 'Selected Users';
$string['usuarios_nomes_help']  = 'List of users selected for this request.';
$string['papel']                = 'Course Role';
$string['papel_help']           = 'Select the role users should have in the course (for enrollments only).';
$string['observacoes']          = 'Comments';
$string['observacoes_help']     = 'Additional information about the request (optional).';
$string['request_submit']       = 'Submit Request';
$string['no_courses_found']     = 'No courses found';
$string['searching_courses']    = 'Searching courses...';

$string['acao_inscricao']       = 'Enrollment';
$string['acao_remocao']         = 'Removal';
$string['acao_suspensao']       = 'Suspension';

$string['papel_student']        = 'Student';
$string['papel_teacher']        = 'Teacher';
$string['papel_editingteacher'] = 'Editing Teacher';
$string['papel_manager']        = 'Manager';

$string['list_title']           = 'Course Management Requests';
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
$string['my_requests']               = 'My Requests';