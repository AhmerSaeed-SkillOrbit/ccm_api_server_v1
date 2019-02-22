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
Route::get('/role/list/search', 'PageController@roleListViaPagination');
//Role list without pagination
Route::get('/role/list', 'PageController@roleList');
//Role list count
Route::get('/role/count', 'PageController@roleCount');

Route::get('/', function (){
    return 'Hello';
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});



