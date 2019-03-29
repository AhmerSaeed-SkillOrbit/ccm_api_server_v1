<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/test', function (Request $request) {
    return response()->json(['data' => "Hello World", 'message' => 'Hello World'], 400);
});

//Role list with pagination
Route::get('/role/list/search', 'PageController@RoleListViaPagination');
//Role list without pagination
Route::get('/role/list', 'PageController@RoleList');
//Role list count
Route::get('/role/count', 'PageController@RoleCount');


//user list with pagination
Route::get('/user/list/search', 'UserController@UserListViaPagination');
//user list without pagination
Route::get('/user/list', 'UserController@UserList');
//user list count
Route::get('/user/count', 'UserController@UserCount');
//User update route
Route::post('/user/update', 'UserController@UserUpdate');
//Get single user via id
Route::get('/user/single', 'UserController@GetSingleUserViaId');
//User registration
Route::post('/user/add', 'UserController@UserRegistration');
//User delete route
Route::post('/user/delete', 'UserController@UserDelete');
//User invitation list with pagination and search
Route::get('/user/invitation', 'UserController@GetUserInvitationListWithPaginationAndSearch');
//User invitation list count with search
Route::get('/user/invitation/count', 'UserController@GetUserInvitationListCount');
//User block route
Route::post('/user/block', 'UserController@UserBlock');
//User unblock route
Route::post('/user/unblock', 'UserController@UserUnblock');

//get support staff list
Route::get('/user/via/role', 'UserController@GetUserViaRoleCode');

//Associate doctor to facilitator route
Route::post('/associate/doctor/facilitator', 'UserController@AssociateFacilitatorsWithDoctor');

//Dashboard API for super admin
Route::get('/dashboard/superadmin', 'UserController@SuperAdminDashboard');

//permission list with pagination
Route::get('/permission/list/search', 'PageController@PermissionListViaPagination');
//permission list without pagination
Route::get('/permission/list', 'PageController@PermissionList');
//permission list count
Route::get('/permission/count', 'PageController@PermissionCount');
//Role permission assign
Route::post('/role/permission/assign', 'PageController@RolePermissionAssign');
//Get permission via role Id
Route::get('/permission/via/role/id', 'UserController@PermissionViaRoleId');
//Get permission via user Id
Route::get('/permission/via/user/id', 'UserController@PermissionViaUserId');
//Test file upload
Route::post('/upload/file', 'DocumentUploadController@UploadFiles');

//Adding schedule of doctor
Route::post('/doctor/schedule/save', 'DoctorScheduleController@AddDoctorScheduleUpdatedCode');
//Updating schedule of doctor
Route::post('/doctor/schedule/update', 'DoctorScheduleController@UpdateDoctorSchedule');

//Adding schedule of doctor
Route::get('/doctor/schedule/single', 'DoctorScheduleController@GetDoctorScheduleDetailAhmerUpdate');

Route::get('/doctor/schedule/single/ahsan', 'DoctorScheduleController@GetDoctorScheduleDetail');

//Adding schedule of doctor
Route::get('/doctor/schedule/shift/single', 'DoctorScheduleController@GetDoctorScheduleShiftSingleViaId');

//Get doctor facilitator list
Route::get('/doctor/facilitator', 'UserController@GetAssociateFacilitator');
//Doctor schedule list
Route::get('/doctor/schedule/list', 'DoctorScheduleController@GetDoctorScheduleListViaPagination');
//Doctor schedule list count
Route::get('/doctor/schedule/list/count', 'DoctorScheduleController@GetDoctorScheduleListCount');

//Adding doctor appointment
Route::post('/appointment/add', 'DoctorScheduleController@AddAppointment');

//get doctor appointment list
Route::get('/appointment/list', 'DoctorScheduleController@getDoctorAppointmentListViaPagination');

//get doctor appointment list
Route::get('/appointment/single', 'DoctorScheduleController@getDoctorAppointmentSingleViaId');

//get doctor schedule count
Route::get('/appointment/list/count', 'DoctorScheduleController@getDoctorAppointmentListCount');

Route::post('/appointment/request/status/update', 'DoctorScheduleController@updateAppointmentRequestStatus');

Route::post('/appointment/cancel/', 'DoctorScheduleController@MarkAppointmentCancel');

//Add tag
Route::post('/tag/add', 'ForumController@AddTag');

//get tag list
Route::get('/tag/list', 'ForumController@getTagList');

//Add forum topic
Route::post('/forum/topic/add', 'ForumController@AddForumTopic');

//Update forum topic
Route::post('/forum/topic/update', 'ForumController@UpdateForumTopic');

//Delete forum topic
Route::post('/forum/topic/delete', 'ForumController@DeleteForumTopic');

//Get single forum topic
Route::get('/forum/topic/single', 'ForumController@GetSingleForumTopic');

//Get forum topic list
Route::get('/forum/topic/list', 'ForumController@GetForumTopicListViaPagination');

Route::get('/forum/topic/list/count', 'ForumController@GetForumTopicListCount');

//Add forum comment
Route::post('/forum/topic/comment/add', 'ForumController@AddForumTopicComment');

//Update forum comment
Route::post('/forum/topic/comment/update', 'ForumController@UpdateForumTopicComment');

//Delete forum comment
Route::post('/forum/topic/comment/delete', 'ForumController@DeleteForumTopicComment');

//get single forum comment
Route::get('/forum/topic/comment/single', 'ForumController@GetSingleForumTopicComment');

//get forum comment list
Route::get('/forum/topic/comment/list', 'ForumController@GetForumTopicCommentsViaPagination');

//get forum comment list count
Route::get('/forum/topic/comment/list/count', 'ForumController@GetForumTopicCommentsCount');

//Create ticket
Route::post('/ticket/create', 'TicketController@CreateTicket');
//Update ticket
Route::post('/ticket/update', 'TicketController@UpdateTicket');

//get ticket single
Route::get('/ticket/single', 'TicketController@TicketSingle');

//get ticket list via pagination
Route::get('/ticket/list', 'TicketController@TicketListViaPaginationAndSearch');

//get ticket list count
Route::get('/ticket/list/count', 'TicketController@TicketListCount');

//get ticket list count
Route::get('/ticket/priority/list', 'TicketController@GetTicketPriorities');
Route::get('/ticket/type/list', 'TicketController@GetTicketTypes');
Route::get('/ticket/track/status/list', 'TicketController@GetTicketTrackStauses');


//Create ticket reply
Route::post('/ticket/reply/add', 'TicketController@AddTicketReply');

//get ticket single
Route::get('/ticket/reply/single', 'TicketController@TicketReplySingle');

//Update ticket reply
Route::post('/ticket/reply/update', 'TicketController@UpdateTicketReply');

//ticket assign
Route::post('/ticket/assign', 'TicketController@AssignTicket');

//ticket status update
Route::post('/ticket/track/status/update', 'TicketController@TicketTrackStatusUpdate');

##
# CCM PLAN APIS
# ##
// get questions list API
Route::get('/question/list', 'CcmPlanController@GetQuestionsList');

Route::get('/answer/type/list', 'CcmPlanController@GetAnswerTypeList');

// give answer to questions
Route::post('/give/answer', 'CcmPlanController@GiveAnswerToQuestion');

// update answer
Route::post('/update/answer', 'CcmPlanController@UpdateAnswer');

//Get all question and answers
Route::get('/question/answer/all', 'CcmPlanController@GetAllQuestionAnswers');

//Get all question and answers
Route::get('/question/answer/single', 'CcmPlanController@GetQuestionAnswerSingle');

// add active medicine
Route::post('/add/active/medicine', 'CcmPlanController@AddActiveMedicine');
// update active medicine
Route::post('/update/active/medicine', 'CcmPlanController@UpdateActiveMedicine');
//Get all prescribed medicine
Route::get('/active/medicine/all', 'CcmPlanController@GetAllActiveMedicine');
//Get single active medicine
Route::get('/active/medicine/single', 'CcmPlanController@GetSingleActiveMedicine');

// add allergy medicine
Route::post('/add/allergy/medicine', 'CcmPlanController@AddAllergyMedicine');
// update allergy medicine
Route::post('/update/allergy/medicine', 'CcmPlanController@UpdateAllergyMedicine');
//Get all prescribed allergy medicine
Route::get('/allergy/medicine/all', 'CcmPlanController@GetAllAllergyMedicine');
//Get single allergy medicine
Route::get('/allergy/medicine/single', 'CcmPlanController@GetSingleAllergyMedicine');

// add non medicine
Route::post('/add/non/medicine', 'CcmPlanController@AddNonMedicine');
// update non medicine
Route::post('/update/non/medicine', 'CcmPlanController@UpdateNonMedicine');
//Get all prescribed non medicine
Route::get('/non/medicine/all', 'CcmPlanController@GetAllNonMedicine');
//Get single non medicine
Route::get('/non/medicine/single', 'CcmPlanController@GetSingleNonMedicine');

// add immunization vaccine
Route::post('/add/immunization/vaccine', 'CcmPlanController@AddImmunizationVaccine');
// update immunization vaccine
Route::post('/update/immunization/vaccine', 'CcmPlanController@UpdateImmunizationVaccine');
//Get all prescribed immunization vaccine
Route::get('/immunization/vaccine/all', 'CcmPlanController@GetAllImmunizationVaccine');
//Get single immunization vaccine
Route::get('/immunization/vaccine/single', 'CcmPlanController@GetSingleImmunizationVaccine');

// add health care history
Route::post('/add/health/care/history', 'CcmPlanController@AddHealthCareHistory');
// update health care history
Route::post('/update/health/care/history', 'CcmPlanController@UpdateHealthCareHistory');
//Get all health care history
Route::get('/health/care/history/all', 'CcmPlanController@GetAllHealthCareHistory');
//Get single health care history
Route::get('/health/care/history/single', 'CcmPlanController@GetSingleHealthCareHistory');

//Assistance APIS
//Get asistance organization
Route::get('/assistance/organization/via/assistance/type', 'CcmPlanController@GetAllAssistanceOrganization');
//Get asistance type
Route::get('/assistance/type/all', 'CcmPlanController@GetAllAssistanceType');

// add patient organization assistance
Route::post('/add/patient/organization/assistance', 'CcmPlanController@AddPatientOrganizationAssistance');
// update patient organization assistance
Route::post('/update/patient/organization/assistance', 'CcmPlanController@UpdatePatientOrganizationAssistance');
//Get all patient organization assistance
Route::get('/patient/organization/assistance/all', 'CcmPlanController@GetAllPatientOrganizationAssistance');
//Get single patient organization assistance
Route::get('/patient/organization/assistance/single', 'CcmPlanController@GetSinglePatientOrganizationAssistance');


Route::get('/', function () {
    return 'Hello';
});

Route::get('/test/list', 'PageController@testFunction');

Route::get('/test/email', 'PageController@TestEmail');

Route::get('/test/sms', 'PageController@TestSms');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', 'LoginController@login');
Route::post('/register', 'LoginController@register');
Route::post('/forgetPass', 'LoginController@forgetPass');
Route::post('/resetPass', 'LoginController@resetPass');
Route::post('/invite', 'ServicesController@invite');
Route::post('/invite/update', 'ServicesController@inviteUpdate');

//?doctorScheduleDetailId=1
Route::post('/doctor/schedule/detail/single/update', 'DoctorScheduleController@UpdateDoctorScheduleDetailSingle');

//temp api
Route::get('/patient/associated/doctor', 'DoctorScheduleController@GetPatientAssociatedDoctor');

Route::get('/add/time/slot', 'DoctorScheduleController@AddTimeSlotDynamically');

Route::get('/format/time/', 'DoctorScheduleController@FormatTime');

//$time = strtotime($dateInUTC.' UTC');
//$dateInLocal = date("Y-m-d H:i:s", $time);



