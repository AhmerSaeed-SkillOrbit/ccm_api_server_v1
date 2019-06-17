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

            error_log("register function");

            $invite = $request->input('InviteCode');
            $data = $request->all();

            if ($invite) {
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
                    $token = md5(uniqid(rand(), true));
                    if ($token != null) {
                        DB::beginTransaction();
                        //Now making data for user_access
                        $dataToUpdate = array(
                            "IsActive" => false
                        );
                        $updateDataCheck = GenericModel::updateGeneric('verification_token', 'UserId', $checkEmail[0]->Id, $dataToUpdate);
                        if ($updateDataCheck >= 0) {
                            $mobileNumber = $checkEmail[0]->MobileNumber;
                            $countyPhoneCode = $checkEmail[0]->CountryPhoneCode;
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
                                return response()->json(['data' => null, 'message' => '1'], 400);
                            }

                            $url = env('WEB_URL') . '/#/reset-password?token=' . $token;

//                            $emailMessage = "To reset your password use this code " . $token . "";
                            $emailMessage = "To reset your password click the link below.";

                            DB::commit();

                            //create email with template
                            $emailBody = "<p style='width: 800px;'>Dear " . $checkEmail[0]->FirstName . " " . $checkEmail[0]->LastName . "<br>" .
                                "<br>Your recently requested to reset your password for your Connect Care Plus account a Chronic Care Management system developed by Business Services Solutions, LLC.<br><br>" .
                                "Click the link below to reset the password.<br><br>" . $url . "<br><br>" .
                                "If you did not request for the reset password, Please ignore this email or write us at info@connectcareplus.com.</p>";

                            UserModel::sendEmailWithTemplateTwo($checkEmail[0]->EmailAddress, "Forget Password", $emailBody);

                            //create sms
                            //Now sending sms
                            if ($mobileNumber != null) {
                                $toNumber = array();
                                $mobileNumber = $countyPhoneCode . $mobileNumber;

                                array_push($toNumber, $mobileNumber);
                                try {
                                    HelperModel::sendSms($toNumber, 'Your password reset link ' . $url . ' for  Care Connect Plus, Chronic Care Management system developed by Business Services Solutions, LLC', null);
                                } catch (Exception $ex) {
                                    return response()->json(['data' => $insertedRecord, 'message' => 'Reset Password - Verification link has been sent to your email address. '], 200);
                                }
                            }
                            return response()->json(['data' => $insertedRecord, 'message' => 'Reset Password - Verification link has been sent to your email address.'], 200);

                        } else {
                            DB::rollback();
                            return response()->json(['data' => null, 'message' => '2'], 400);
                        }

                    } else {
                        return response()->json(['data' => null, 'message' => '3'], 400);
                    }
                }
            } else {
                return response()->json(['data' => null, 'message' => 'Code type is missing'], 400);
            }

        } catch (Exception $e) {
            error_log('error ' . $e);
            return response()->json(['data' => null, 'message' => '4'], 500);
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
                } else {


//                    return response()->json(['data' => null, 'message' => 'Test Break'], 400);
//                    //Binding data to variable.
                    error_log('fetching User Record ');

                    $checkUserData = GenericModel::simpleFetchGenericByWhere("user", "=", "Id", $checkToken[0]->UserId);
                    error_log('fetched User Record ' . $checkUserData);
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
                                $countryPhoneCode = $checkUserData[0]->CountryPhoneCode;

                                $emailAddress = $checkUserData[0]->EmailAddress;


                                $emailMessage = "Your password has been updated.";

                                //Now sending email
//                                LoginModel::sendEmail($emailAddress, "Update Password", $emailMessage, "");

                                //create email with template
                                $emailBody = "<p style='width: 800px;'>Dear " . $checkUserData[0]->FirstName . " " . $checkUserData[0]->LastName . "<br>" .
                                    "<br>You recently reset the password for your Connect Care Plus account a Chronic Care Management system developed by Business Services Solutions, LLC.<br><br>" .
                                    "If you did not reset the password, Please write us at info@connectcareplus.com.</p>";

                                UserModel::sendEmailWithTemplateTwo($emailAddress, "Reset Password", $emailBody);

                                //create sms
                                //Now sending sms
                                if ($mobileNumber != null) {
                                    $toNumber = array();
                                    $mobileNumber = $countryPhoneCode . $mobileNumber;
                                    array_push($toNumber, $mobileNumber);
                                    try {
                                        HelperModel::sendSms($toNumber, 'Your password is recently reset, Please check the portal for the update.  Care Connect Plus, Chronic Care Management system developed by Business Services Solutions, LLC', null);
                                    } catch (Exception $ex) {
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

    function changePassword(Request $request)
    {
        $loginUserId = $request->post('id'); //login user id
        $oldPassword = $request->post('oldPassword');
        $newPassword = $request->post('newPassword');

        if ($oldPassword != null && $newPassword != null && $loginUserId != null) {
            error_log('fetching User Record ');

            $checkUserData = GenericModel::simpleFetchGenericByWhere("user", "=", "Id", $loginUserId);
            error_log('fetched User Record ' . $checkUserData);
            if (count($checkUserData) == 0) {
                return response()->json(['data' => null, 'message' => 'Invalid User'], 400);
            } else {
                $hashedPasswordOld = md5($oldPassword);
                if ($hashedPasswordOld != $checkUserData[0]->Password) {
                    return response()->json(['data' => null, 'message' => 'Old Password does not match'], 400);
                } else {
                    try {
                        error_log('In controller');
                        error_log('comparing old password with user record password');

                        //Now making data for user_access
                        $dataToUpdate = array(
                            "Password" => md5($newPassword)
                        );

                        $updateDataCheck = GenericModel::updateGeneric('user', 'Id', $checkUserData[0]->Id, $dataToUpdate);

                        error_log('password is changed successfully');

                        if ($updateDataCheck >= 0) {
                            $mobileNumber = $checkUserData[0]->MobileNumber;
                            $countryPhoneCode = $checkUserData[0]->CountryPhoneCode;
                            $emailAddress = $checkUserData[0]->EmailAddress;
                            $emailMessage = "Your password has been changed.";

                            error_log("now sending email and sms");

                            //create email with template
                            $emailBody = "<p style='width: 800px;'>Dear " . $checkUserData[0]->FirstName . " " . $checkUserData[0]->LastName . "<br>" .
                                "<br>You recently change your password for your Connect Care Plus account a Chronic Care Management system developed by Business Services Solutions, LLC.<br><br>" .
                                "If you did not change the password, Please write us at info@connectcareplus.com.</p>";
                            UserModel::sendEmailWithTemplateTwo($emailAddress, "Password Changed", $emailBody);

                            //create sms
                            //Now sending sms
                            if ($mobileNumber != null) {
                                $url = env('WEB_URL') . '/#/';
                                $toNumber = array();
                                $mobileNumber = $countryPhoneCode . $mobileNumber;
                                array_push($toNumber, $mobileNumber);
                                try {
                                    HelperModel::sendSms($toNumber, 'Your have recently change the password for Care Connect Plus account. Chronic Care Management system developed by Business Services Solutions, LLC', null);
                                } catch (Exception $ex) {
                                    return response()->json(['data' => null, 'message' => 'Your password has been changed'], 200);
                                }
                            }
                            return response()->json(['data' => null, 'message' => 'Your password has been changed'], 200);

                        } else {
                            return response()->json(['data' => null, 'message' => 'Something went wrong'], 400);
                        }

                    } catch (Exception $e) {
                        error_log('error ' . $e);
                        return response()->json(['data' => null, 'message' => 'Something went wrong'], 500);
                    }
                }
            }
        } else {
            return response()->json(['data' => null, 'message' => 'Old password and new password is required'], 400);
        }
    }

    function adminLogin(Request $request)
    {
        return LoginModel::getAdminLogin($request);
    }

    function logout(Request $request)
    {
        try {
            $check = LoginModel::getLogout($request);

            if ($check['status'] == "success") {

                return response()->json(['data' => $check['data'], 'message' => 'User Successfully Logs out'], 200);
            } else if ($check['status'] == "failed") {

                return response()->json(['data' => null, 'message' => $check['message']], 400);
            } else {
                return response()->json(['data' => null, 'message' => 'Something went wrong'], 500);
            }
        } catch (Exception $e) {
            return response()->json(['data' => null, 'message' => 'Something went wrong'], 500);
        }
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
            'EmailAddress' => ['required', 'string', 'email', 'max:255']
//            'BelongTo' => ['required'],
        ]);
    }

    function LoginHistoryCount(Request $request)
    {
        $byUserId = $request->get('byUserId');
        $ofUserId = $request->get('ofUserId');

        //first fetch role of ofUserId
        //to apply further checks such as
        //associations

//        UserModel::GetUserRoleViaUserId($ofUserId);

        $count = LoginModel::FetchLoginHistoryCount($ofUserId);

        error_log('Count of data is : ' . $count);

        return response()->json(['data' => $count, 'message' => 'Login User History count'], 200);
    }


    function LoginHistoryList(Request $request)
    {
        //first fetch role of ofUserId
        //to apply further checks such as
        //associations

        $byUserId = $request->get('byUserId');
        $ofUserId = $request->get('ofUserId');
        $offset = $request->get('p');
        $limit = $request->get('c');

        $loginHistory = array();

        $list = LoginModel::FetchLoginHistoryListViaPagination($ofUserId, $offset, $limit);
        if (count($list) > 0) {
            foreach ($list as $item) {
                $itemArray = array(
                    'Id' => $item->Id,
                    'FirstName' => $item->FirstName,
                    'LastName' => $item->LastName,
                    'EmailAddress' => $item->EmailAddress,
                    'CountryPhoneCode' => $item->CountryPhoneCode,
                    'MobileNumber' => $item->MobileNumber,
                    'TelephoneNumber' => $item->TelephoneNumber,
                    'PatientUniqueId' => $item->PatientUniqueId,
                    'Gender' => $item->Gender,
                    'FunctionalTitle' => $item->FunctionalTitle,
                    'Age' => $item->Age,
                    'AgeGroup' => $item->AgeGroup,
                    'AccountVerified' => $item->AccountVerified,
                    'CreatedBy' => $item->CreatedBy,
                    'CreatedOn' => $item->CreatedOn,
                    'UpdatedOn' => $item->UpdatedOn,
                    'UpdatedBy' => $item->UpdatedBy,
                    'IsActive' => $item->IsActive,
                    'IsBlock' => $item->IsBlock,
                    'BlockReason' => $item->BlockReason,
                    'InActiveReason' => $item->InActiveReason,
                    'CityId' => $item->CityId,
                    'MiddleName' => $item->MiddleName,
                    'LastLoggedIn' => $item->LastLoggedIn,
                    'IsCurrentlyLoggedIn' => $item->IsCurrentlyLoggedIn,
                    'LoginHistoryId' => $item->LoginHistoryId,
                    'LoginDateTime' => date('d-M-Y h:m a', $item->LoginDateTime)
                );
                array_push($loginHistory, $itemArray);
            }
            return response()->json(['data' => $loginHistory, 'message' => 'Login User History'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Login User History is empty'], 200);
        }
    }

    function AddPatientDirect(Request $request)
    {
        try {

            error_log("add patient direct");
            $data = $request->all();

            $validator = LoginController::registerValidator($data);

            if ($validator->fails()) {
                return response()->json(['data' => $data, 'error' => $validator->errors(), 'message' => 'validation failed'], 400);
            } else {

                //custom check for
                //email n mobile already exist

                $data = LoginModel::checkEmailAndMobileAndUniqueIdAvailable($request->EmailAddress, $request->post('MobileNumber'), $request->post('PatientUniqueId'));
                if (count($data) > 0) {
                    if ($data[0]->EmailAddress == $request->EmailAddress) {
                        $message = "Email Address already exist";
                        return response()->json(['data' => null, 'message' => $message], 400);
                    }
                    if ($data[0]->MobileNumber == $request->post('MobileNumber')) {
                        $message = "Mobile Number already exist";
                        return response()->json(['data' => null, 'message' => $message], 400);
                    }
                    if ($data[0]->PatientUniqueId == $request->post('PatientUniqueId')) {
                        $message = "Patient Unique Id already exist";
                        return response()->json(['data' => null, 'message' => $message], 400);
                    }
                }

                //verifying the provided RoleCode is of patient
                $roleData = UserModel::getRoleViaRoleCode($request->RoleCode);
                if (count($roleData) > 0) {
                    $roleName = $roleData[0]->CodeName;
                    if ($roleName == env('ROLE_PATIENT')) {
                        $roleId = $roleData[0]->Id;
                    } else {
                        return response()->json(['data' => null, 'message' => 'Not Allowed, The User to be added is not Patient'], 400);
                    }
                } else {
                    return response()->json(['data' => null, 'message' => 'Not Allowed, The User to be added is not Patient'], 400);
                }

                //verifying the provided sourceUserId is of Doctor or not
                $roleData = UserModel::GetRoleNameViaUserId($request->SourceUserId);
                if (count($roleData) > 0) {
                    $roleName = $roleData[0]->CodeName;
                    if ($roleName != env('ROLE_DOCTOR')) {
                        return response()->json(['data' => null, 'message' => 'Not Allowed, Only Doctor can add Patient'], 400);
                    }
                } else {
                    return response()->json(['data' => null, 'message' => 'Not Allowed, Only Doctor can add Patient'], 400);
                }

                //means patient is registering
                //so generate Patient unique id here
                //calling table view
                $patientUniqueId = 0;
                try {
                    ## NOT REQUIRE NOW ##
//                    $getPatientCountResult = DB::table('get_patient_count_view')
//                        ->select('TotalPatient')
//                        ->take(1)
//                        ->get();
//                    if (count($getPatientCountResult) == 1) {
//                        $getPatientCountResult = $getPatientCountResult[0]->TotalPatient;
//                        if ($getPatientCountResult > 0) {
//                            $patientUniqueId = $getPatientCountResult + 1;
//                        }
//                    }

                    $hashedPassword = md5(env('DEFAULT_PWD'));
                    $date = HelperModel::getDate();

                    $insertData = array(
                        "PatientUniqueId" => $request->PatientUniqueId,
                        "FirstName" => $request->FirstName,
                        "LastName" => $request->LastName,
                        "EmailAddress" => $request->EmailAddress,
                        "CountryPhoneCode" => $request->CountryPhoneCode,
                        "MobileNumber" => $request->MobileNumber,
                        "IsMobileNumberVerified" => 1,
                        "AccountVerified" => 1,
                        "TelephoneNumber" => $request->TelephoneNumber,
                        "OfficeAddress" => $request->OfficeAddress,
                        "ResidentialAddress" => $request->ResidentialAddress,
                        "Password" => $hashedPassword,
                        "Gender" => $request->Gender,
                        "FunctionalTitle" => $request->FunctionalTitle,
                        "Age" => $request->Age,
                        "AgeGroup" => $request->AgeGroup,
                        "CreatedOn" => $date["timestamp"],
                        "CreatedBy" => $request->SourceUserId,
                        "IsActive" => 1
                    );

                    $checkInsertUserId = DB::table("user")->insertGetId($insertData);

                    if ($checkInsertUserId) {
                        $insertUserAssociationData = array(
                            "SourceUserId" => $request->SourceUserId,
                            "DestinationUserId" => $checkInsertUserId,
                            "AssociationType" => env('ASSOCIATION_DOCTOR_PATIENT'),
                            "IsActive" => 1
                        );

                        DB::table("user_association")->insertGetId($insertUserAssociationData);
                    }

                    $insertRoleData = array(
                        "UserId" => $checkInsertUserId,
                        "RoleId" => $roleId,
                        "IsActive" => 1
                    );

                    DB::table("user_access")->insertGetId($insertRoleData);

                    //create email with template

                    $emailBody = "<p><h3>Hi</h3>$request->EmailAddress<br><br>Welcome to the Chronic Care Management system. You are registered as a Patient into the connectcareplus. The connectcareplus is a one stop solution health solution. Please take some time and log into the portal with your registered id " . env('WEB_URL') . "The following will be the facilities. <br><br>" .
                        "1. Your health plan directly from the portal.<br>" .
                        "2. Instantly set a meeting with your provider.<br>" .
                        "3. Raise ticket for your queries. Either from the cell phone or directly from the portal.<br>" .
                        "4. Review your progress against the plan and much more.<br><br>" .
                        "use this Password " . env('DEFAULT_PWD') . " to Login</p><br>";

                    UserModel::sendEmailWithTemplateTwo($request->EmailAddress, "Welcome to CCM", $emailBody);

                    //create sms
                    //Now sending sms to patient
                    if ($request->MobileNumber != null) {
                        $url = env('WEB_URL') . '/#/';
                        $toNumber = array();
                        $request->MobileNumber = $request->CountryPhoneCode . $request->MobileNumber;
                        array_push($toNumber, $request->MobileNumber);

                        HelperModel::sendSms($toNumber, 'Welcome to the Chronic Care Management system developed by Business Services Solutions, LLC', $url);
                    }

                    return response()->json(['data' => $checkInsertUserId, 'message' => 'Patient successfully added'], 200);

                } catch (Exception $exception) {
                    error_log($exception);
                    return response()->json(['data' => $checkInsertUserId, 'message' => 'Failed to add Patient'], 500);
                }
            }

        } catch (Exception $e) {
            return response()->json(['data' => null, 'message' => 'Internal server error'], 500);
        }
    }
}
