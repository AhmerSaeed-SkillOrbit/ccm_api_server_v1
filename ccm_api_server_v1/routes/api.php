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

//Dashboard API for super admin
Route::get('/dashboard/superadmin', 'UserController@SuperAdminDashboard');


Route::get('/', function () {
    return 'Hello';
});

Route::get('/test/list', 'PageController@testFunction');

Route::get('/test/email', 'PageController@TestEmail');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', 'LoginController@login');
Route::post('/register', 'LoginController@register');
Route::post('/invite', 'ServicesController@invite');
Route::post('/invite/update', 'ServicesController@inviteUpdate');



