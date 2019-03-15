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
Route::post('/invite', 'ServicesController@invite');
Route::post('/invite/update', 'ServicesController@inviteUpdate');

//?doctorScheduleDetailId=1
Route::post('/doctor/schedule/detail/single/update', 'DoctorScheduleController@UpdateDoctorScheduleDetailSingle');

//temp api
Route::get('/patient/associated/doctor', 'DoctorScheduleController@GetPatientAssociatedDoctor');

Route::get('/add/time/slot', 'DoctorScheduleController@AddTimeSlotDynamically');



