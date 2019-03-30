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
            } else {
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

                    //custom check for
                    //email already exist

                    $isEmailAvailable = LoginModel::checkEmailAvailable($request->EmailAddress);

                    if (count($isEmailAvailable) > 0) {
                        return response()->json(['data' => 0, 'message' => "Email is already taken"], 200);
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
                }
            } else {
                return response()->json(['data' => null, 'message' => 'Code type is missing'], 400);
            }

        } catch (Exception $e) {
            return response()->json(['data' => null, 'message' => 'Something went wrong'], 500);
        }
    }

    function forgetPass(Request $request)
    {
        try {

            error_log('In controller');

            $emailAddress = $request->post('EmailAddress');

            if ($emailAddress) {
                //First get and check if email record exists or not
                $checkEmail = LoginModel::checkEmailAvailable($emailAddress);

                error_log('Checking email bit' . $checkEmail);

                if (count($checkEmail) == 0) {
                    return response()->json(['data' => null, 'message' => 'Email not found'], 400);
                } else {

//                    return response()->json(['data' => null, 'message' => 'Test Break'], 400);
//                    //Binding data to variable.

                    $token = md5(uniqid(rand(), true));
                    // $token = LoginModel::generateAccessToken();


                    if ($token != null) {


                        DB::beginTransaction();

                        //Now making data for user_access
                        $dataToUpdate = array(
                            "IsActive" => false
                        );

                        $updateDataCheck = GenericModel::updateGeneric('verification_token', 'UserId', $checkEmail[0]->Id, $dataToUpdate);

                        if ($updateDataCheck >= 0) {

                            $mobileNumber = $checkEmail[0]->MobileNumber;

                            $dataToInsert = array(
                                "UserId" => $checkEmail[0]->Id,
                                "Email" => $checkEmail[0]->EmailAddress,
                                "Token" => $token,
                                "IsActive" => true
                            );

                            $insertedRecord = GenericModel::insertGenericAndReturnID('verification_token', $dataToInsert);
                            error_log('Inserted record id ' . $insertedRecord);

                            if ($insertedRecord == 0) {
                                DB::rollback();
                                return response()->json(['data' => null, 'message' => 'something went wrong'], 400);
                            }

                            $url = env('WEB_URL') . '/#/reset-password?token=' . $token;

//                            $emailMessage = "To reset your password use this code " . $token . "";
                            $emailMessage = "To reset your password click the link below.";

                            DB::commit();
                            //Now sending email
                            LoginModel::sendEmail($emailAddress, "Reset Password", $emailMessage, $url);

                            //Now sending sms
                            if ($mobileNumber != null) {
                                $url = env('WEB_URL') . '/#/';
                                $toNumber = array();
                                $phoneCode = getenv("PAK_NUM_CODE");//fetch from front-end
                                $mobileNumber = $phoneCode . $mobileNumber;
                                array_push($toNumber, $mobileNumber);
                                try {
//                                    HelperModel::sendSms($toNumber, 'Verification link has been sent to your email address', $url);
                                    HelperModel::sendSms($toNumber, 'Verification link has been sent to your email address.', null);
                                } catch (Exception $ex) {
//                                    return response()->json(['data' => $insertedRecord, 'message' => 'User successfully registered. ' . $ex], 200);
                                    return response()->json(['data' => $insertedRecord, 'message' => 'Verification link has been sent to your email address. '], 200);
                                }
                            }
                            return response()->json(['data' => $insertedRecord, 'message' => 'Verification link has been sent to your email address.'], 200);


                        } else {
                            DB::rollback();
                            return response()->json(['data' => null, 'message' => 'Something went wrong'], 400);
                        }

                    } else {
                        return response()->json(['data' => null, 'message' => 'something went wrong'], 400);
                    }


                }

            } else {
                return response()->json(['data' => null, 'message' => 'Code type is missing'], 400);
            }

        } catch (Exception $e) {
            error_log('error ' . $e);
            return response()->json(['data' => null, 'message' => 'Something went wrong'], 500);
        }

    }

    function resetPass(Request $request)
    {
        try {
            DB::beginTransaction();

            error_log('In controller');

            $token = $request->post('VerificationKey');
            $password = $request->post('UserPassword');

            if ($token && $password) {
                //First get and check if email record exists or not
                $checkToken = LoginModel::checkTokenAvailableForResetPass($token);

                error_log('Checking token bittt' . $checkToken);

                if (count($checkToken) == 0) {
                    return response()->json(['data' => null, 'message' => 'Invalid link'], 400);
                }
                else {


//                    return response()->json(['data' => null, 'message' => 'Test Break'], 400);
//                    //Binding data to variable.
                    error_log('fectching User Record ');

                    $checkUserData = GenericModel::simpleFetchGenericByWhere("user", "=", "Id", $checkToken[0]->UserId);
                    error_log('fectched User Record ' . $checkUserData);
                    if (count($checkUserData) == 0) {
                        return response()->json(['data' => null, 'message' => 'User not found'], 400);
                    } else {



                        $hashedPassword = md5($password);
                        //Now making data for user_access
                        $dataToUpdate = array(
                            "Password" => $hashedPassword
                        );

                        $updateDataCheck = GenericModel::updateGeneric('user', 'Id', $checkUserData[0]->Id, $dataToUpdate);

                        error_log('updating password ');

                        if ($updateDataCheck >= 0) {

                            $vDataToUpdate = array(
                                "IsActive" => false
                            );

                            error_log('updating token');
                            $updateVerificationDataCheck = GenericModel::updateGeneric('verification_token', 'Id', $checkToken[0]->Id, $vDataToUpdate);

                            if ($updateVerificationDataCheck >= 0) {

                                DB::commit();
                                $mobileNumber = $checkUserData[0]->MobileNumber;

                                $emailAddress = $checkUserData[0]->EmailAddress;


                                $emailMessage = "Your password has been updated.";

                                //Now sending email
                                LoginModel::sendEmail($emailAddress, "Update Password", $emailMessage, "");

                                //Now sending sms
                                if ($mobileNumber != null) {
                                    $url = env('WEB_URL') . '/#/';
                                    $toNumber = array();
                                    $phoneCode = getenv("PAK_NUM_CODE");//fetch from front-end
                                    $mobileNumber = $phoneCode . $mobileNumber;
                                    array_push($toNumber, $mobileNumber);
                                    try {
//                                    HelperModel::sendSms($toNumber, 'Verification link has been sent to your email address', $url);
                                        HelperModel::sendSms($toNumber, 'Your password has been updated.', null);
                                    } catch (Exception $ex) {
//                                    return response()->json(['data' => $insertedRecord, 'message' => 'User successfully registered. ' . $ex], 200);
                                        return response()->json(['data' => null, 'message' => 'Your password has been updated. '], 200);
                                    }
                                }
                                return response()->json(['data' => null, 'message' => 'Your password has been updated.'], 200);

                            } else {
                                DB::rollback();
                                return response()->json(['data' => null, 'message' => 'Something went wrong'], 400);
                            }

                        } else {
                            DB::rollback();
                            return response()->json(['data' => null, 'message' => 'Something went wrong'], 400);
                        }

                    }


                }

            } else {

                if (!$token) {
                    return response()->json(['data' => null, 'message' => 'Invalid link'], 400);
                } else {
                    return response()->json(['data' => null, 'message' => 'Password is required'], 400);
                }
//                return response()->json(['data' => null, 'message' => 'Code type is missing'], 400);
            }

        } catch (Exception $e) {
            DB::rollback();
            error_log('error ' . $e);
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
    protected
    function registerValidator(array $data)
    {
        return Validator::make($data, [
            'EmailAddress' => ['required', 'string', 'email', 'max:255'],
//            'BelongTo' => ['required'],
        ]);
    }

}
