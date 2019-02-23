<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use App\Models\LoginModel;
use App\Models\GenericModel;
use App\Models\HelperModel;
use Illuminate\Http\Request;

use Log;
use mysql_xdevapi\Exception;

class LoginController extends Controller
{

    function login_old(Request $request)
    {
        // return LoginModel::getLogin($request);
        // Log::info('hit login.');
        $check = LoginModel::getLogin($request);

        if ($check['status'] == true) {

            // now generate token
            // $token = HelperModel::generateAccessToken();
            $token = LoginModel::generateAccessToken();
            // return response()->json(['data' => $token, 'message' => 'token'], 200);
            // $token = null;
            // test
            // Log::info('This is some useful information.');
            // return response()->json(['data' => $check, 'message' => 'Successfully Login'], 200);
            if ($token != null) {
                $insertData = array("UserId" => $check['data']['ID'], "AccessToken" => $token);

                $checkInsertToken = GenericModel::insertGenericAndReturnID("access_token", $insertData);

                if ($checkInsertToken) {

                    // return response()->json(['data' => $check['data'], 'message' => 'Successfully Login'], 200);
                    return response()->json(['data' => ['User' => $check['data'], 'accessToken' => "a123"], 'message' => 'Successfully Login'], 200);
                } else {
                    return response()->json(['data' => null, 'message' => 'something went wrong'], 400);
                }

            } else {
                return response()->json(['data' => null, 'message' => 'something went wrong'], 400);
            }


            // return response()->json(['data' => $check['data'], 'message' => 'Successfully Login'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'email or password is incorrect'], 400);
        }

        // return LoginModel::getLogin($request);
    }

    function login(Request $request)
    {

        try {
            // Log::info('hit login.');

            // $name = $request->query('name');
            // $name = $request->input('name');
            // $email = $request->input('email');
            // return response()->json(['data' => $name, 'message' => 'Testing'], 200);

            $check = LoginModel::getLoginTrans($request);

            if ($check['status'] == "success") {

                // return response()->json(['data' => $check['data'], 'message' => 'Successfully Login'], 200);
                return response()->json(['data' => $check['data'], 'message' => 'Successfully Login'], 200);
            } else if ($check['status'] == "failed") {
                return response()->json(['data' => null, 'message' => 'email or password is incorrect'], 400);
            } else {
                return response()->json(['data' => null, 'message' => 'something went wrong'], 500);
            }
        } catch (Exception $e) {
            return response()->json(['data' => null, 'message' => 'something went wrong'], 500);

        }


    }

    function register(Request $request)
    {
        try {
            // Log::info('hit login.');

            $name = $request->query('name');
            // $name = $request->input('name');
            // $email = $request->input('email');
            
            return response()->json(['data' => $name, 'message' => 'Testing'], 200);

            $check = LoginModel::getLoginTrans($request);

            if ($check['status'] == "success") {
                // return response()->json(['data' => $check['data'], 'message' => 'Successfully Login'], 200);
                return response()->json(['data' => $check['data'], 'message' => 'Successfully Login'], 200);
            } else if ($check['status'] == "failed") {
                return response()->json(['data' => null, 'message' => 'email or password is incorrect'], 400);
            } else {
                return response()->json(['data' => null, 'message' => 'something went wrong'], 500);
            }
        } catch (Exception $e) {
            return response()->json(['data' => null, 'message' => 'something went wrong'], 500);
        }
    }

    function adminLogin(Request $request)
    {
        return LoginModel::getAdminLogin($request);
    }

    function logout(Request $request)
    {
        return LoginModel::getlogout($request);
    }

    function adminLogout(Request $request)
    {
        return LoginModel::getAdminlogout($request);
    }

}
