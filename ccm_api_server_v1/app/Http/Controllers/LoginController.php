<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\LoginModel;
use App\Models\UserModel;
use App\Models\GenericModel;
use App\Models\HelperModel;
use Illuminate\Http\Request;

use Log;
// use mysql_xdevapi\Exception;
use Exception;

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
                    return response()->json(['data' => ['User' => $check['data'], 'accessToken' => "a123"], 'message' => 'User Successfully Logged In'], 200);
                } else {
                    return response()->json(['data' => null, 'message' => 'Something went wrong'], 400);
                }

            } else {
                return response()->json(['data' => null, 'message' => 'Something went wrong'], 400);
            }


            // return response()->json(['data' => $check['data'], 'message' => 'Successfully Login'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Email or password is incorrect'], 400);
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
                return response()->json(['data' => $check['data'], 'message' => 'User Successfully Logged In'], 200);
            } else if ($check['status'] == "failed") {
                return response()->json(['data' => null, 'message' => $check['message']], 400);
                // return response()->json(['data' => null, 'message' => "Email or password is incorrect"], 400);
            }  else {
                return response()->json(['data' => null, 'message' => 'Something went wrong'], 500);
            }
        } catch (Exception $e) {
            return response()->json(['data' => null, 'message' => 'Something went wrong'], 500);

        }


    }

    function register(Request $request)
    {
        try {

            $invite = $request->input('InviteCode');
            $code = $request->input('Type');

            $data = $request->all();

            if ($invite && $code) {
                $validator = LoginController::registerValidator($data);

                if ($validator->fails()) {
                    return response()->json(['data' => $data, 'error' => $validator->errors(), 'message' => 'validation failed'], 400);
                } else {

                    $check = LoginModel::getRegisterTrans($request);

                    if ($check['status'] == "success") {
                        return response()->json(['data' => $check['data'], 'message' => $check['message']], 200);
                    } else if ($check['status'] == "failed") {
                        return response()->json(['data' => null, 'message' => $check['message']], 400);
                    } else {
                        return response()->json(['data' => null, 'message' => 'Something went wrong'], 500);
                    }
                }
            } else {
                return response()->json(['data' => null, 'message' => 'Code type is missing'], 400);
            }

        } catch (Exception $e) {
            return response()->json(['data' => null, 'message' => 'Something went wrong'], 500);
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

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function registerValidator(array $data)
    {
        return Validator::make($data, [
            'EmailAddress' => ['required', 'string', 'email', 'max:255', 'unique:user'],
//            'BelongTo' => ['required'],
        ]);
    }

}
