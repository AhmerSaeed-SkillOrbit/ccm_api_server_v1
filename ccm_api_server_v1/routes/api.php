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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/role/list', 'PageController@testFunction', function (Request $request){
    return response()->json(['data' => $request, 'message' => 'Roles fetched'], 400);
});

Route::get('/', function (){
    return 'Hello';
});

