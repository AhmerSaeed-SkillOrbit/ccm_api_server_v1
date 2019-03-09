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

//Get doctor facilitator list
Route::get('/doctor/facilitator', 'UserController@GetAssociateFacilitator');
//Doctor schedule list
Route::get('/doctor/schedule/list', 'DoctorScheduleController@GetDoctorScheduleListViaPagination');
//Doctor schedule list count
Route::get('/doctor/schedule/list/count', 'DoctorScheduleController@GetDoctorScheduleListCount');


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



