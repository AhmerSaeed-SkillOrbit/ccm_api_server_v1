<?php

namespace App\Http\Controllers;

use App\Models\ServicesModel;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\LoginModel;
use App\Models\GenericModel;
use App\Models\HelperModel;
use Illuminate\Http\Request;

use Log;

// use mysql_xdevapi\Exception;
// use App\Exception\Handler as ExceptionHandler;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class ServicesController extends Controller
{

    function invite(Request $request)
    {
        try {
            // $data = $request->input('name');
            $data = $request->all();

            $validator = ServicesController::inviteValidator($data);

            if ($validator->fails()) {
                return response()->json(['data' => $data, 'error' => $validator->errors(), 'message' => 'validation failed'], 400);
            } else {
                $check = ServicesModel::sendInviteTrans($request);

                if ($check['status'] == "success") {
                    return response()->json(['data' => true, 'message' => 'Invite is successfully sent'], 200);
                } else if ($check['status'] == "failed") {
                    return response()->json(['data' => false, 'message' => $check['message']], 400);
                } else {
                    return response()->json(['data' => false, 'message' => 'something went wrong'], 500);
                }
            }
        } catch (Exception $e) {
            return response()->json(['data' => null, 'message' => 'error occur'], 500);
        }

    }

    function inviteUpdate(Request $request)
    {
       try {
             $token = $request->input('Token');

            if ($token) {
                $check = ServicesModel::inviteUpdate($request);

                if ($check['status'] == "success") {
                    // return response()->json(['data' => $check['data'], 'message' => 'Successfully Login'], 200);
                    // return response()->json(['data' => true, 'message' => $check['message']], 200);
                    return response()->json(['data' => $check['data'], 'message' => $check['message']], 200);
                } else if ($check['status'] == "failed") {
                    return response()->json(['data' => false, 'message' => $check['message']], 400);
                }
                else {
                    return response()->json(['data' => false, 'message' => 'something went wrong'], 500);
                }

            } else {
                return response()->json(['data' => null, 'message' => 'token not found'], 400);
            }
       } catch (Exception $e) {
           return response()->json(['data' => null, 'message' => 'error occur'], 500);
       }

    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function inviteValidator(array $data)
    {
        return Validator::make($data, [
            // 'EmailAddress' => ['required', 'string', 'email', 'max:255', 'unique:user'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'mobileNumber' => [],
            'type' => ['required', 'string'],
        ]);
    }


}
