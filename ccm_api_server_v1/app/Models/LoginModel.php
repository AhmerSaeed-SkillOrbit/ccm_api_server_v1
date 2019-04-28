<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use App\Models\GenericModel;
use App\Models\HelperModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use PhpParser\Node\Stmt\Return_;
use PHPUnit\Util\RegularExpressionTest;
use App\Models\UserModel;

use Exception;
use Mail;

class LoginModel
{

    static public function getLoginTrans(Request $request)
    {

        $email = Input::get('email');
        $password = Input::get('password');

        $hashedPassword = md5($password);

        error_log($email);
        error_log($password);
        error_log($hashedPassword);

        DB::beginTransaction();
        try {

            // ('ID', 'FirstName', 'LastName','EmailAddress','MobileNumber','TelephoneNumber','Gender','FunctionalTitle','FunctionalTitle')
            $login = DB::table('user')
                ->select('Id')
                ->where('EmailAddress', '=', $email)
                ->where('Password', '=', $hashedPassword)
                ->where('IsActive', '=', 1)
                ->get();

            $checkLogin = json_decode(json_encode($login), true);

            //Checking user if it is blocked or not
//            $checkUser = UserModel::GetSingleUserViaIdNewFunction($checkLogin[0]['Id']);
//
//            if ($checkUser != null || $checkUser != false) {
//                error_log('user data fetched');
//                error_log('$checkUser->IsBlock ' . $checkUser->IsBlock);
//                if ($checkUser->IsBlock == true) {
//                    return array("status" => "failed", "data" => null, "message" => "User is blocked");
//                }
//                error_log('$checkUser->IsActive ' . $checkUser->IsActive);
//                if ($checkUser->IsActive == false) {
//                    return array("status" => "failed", "data" => null, "message" => "User is not active");
//                }
//            }

            if (count($checkLogin) > 0) {

                error_log("correct");
                //Checking user if it is blocked or not
                $checkUser = UserModel::GetSingleUserViaIdNewFunction($checkLogin[0]['Id']);

                if ($checkUser != null || $checkUser != false) {
                    error_log('user data fetched');
                    error_log('$checkUser->IsBlock ' . $checkUser->IsBlock);
                    if ($checkUser->IsBlock == true) {
                        return array("status" => "failed", "data" => null, "message" => "User is blocked");
                    }
                    error_log('$checkUser->IsActive ' . $checkUser->IsActive);
                    if ($checkUser->IsActive == false) {
                        return array("status" => "failed", "data" => null, "message" => "User is not active");
                    }
                }
                // $session = LoginModel::createLoginSession($request, $checkLogin);
                // return redirect( $homeRedirect )->with($session);

                $token = md5(uniqid(rand(), true));
                // $token = LoginModel::generateAccessToken();

                if ($token != null) {

                    $date = HelperModel::getDate();

                    $insertData = array(
                        "UserId" => $checkLogin[0]['Id'],
                        "AccessToken" => $token,
                        "CreatedOn" => $date["timestamp"]
                    );

                    $checkInsertTokenId = DB::table("access_token")->insertGetId($insertData);

                    if ($checkInsertTokenId) {

                        $tokenData = DB::table('access_token')
                            ->select()
                            ->where('Id', '=', $checkInsertTokenId)
                            ->get();

                        $checkTokenData = json_decode(json_encode($tokenData), true);
                        if (count($checkTokenData) > 0) {

                            ### now updating IsCurrentlyLoggedIn field to 1  -Start ###

                            $IsCurrentlyLoggedInData = array(
                                "IsCurrentlyLoggedIn" => 1,
                                "LastLoggedIn" => $date["timestamp"]
                            );

                            DB::table('user')
                                ->where('Id', $checkLogin[0]['Id'])
                                ->update($IsCurrentlyLoggedInData);

                            ### now updating IsCurrentlyLoggedIn field to 1  - End###

//                          ### now adding entry in login history table -start ###
                            $insertLoginHistoryData = array(
                                "UserId" => $checkLogin[0]['Id'],
                                "CreatedOn" => $date["timestamp"]
                            );

                            DB::table("user_login_history")->insertGetId($insertLoginHistoryData);

                            $data = array(
                                "userId" => $checkTokenData[0]["UserId"],
                                "accessToken" => $checkTokenData[0]["AccessToken"],
                                "expiryTime" => $checkTokenData[0]["ExpiryTime"]
                            );

                            ### now adding entry in login history table -end ###

                            DB::commit();
                            // return array("status" => true, "data" => $data);
                            return array("status" => "success", "data" => $data);

                            // return response()->json(['data' => $checkLogin, 'message' => 'Successfully Login'], 200);
                        } else {
                            DB::rollBack();
                            error_log("Get token data failed");
                            return array("status" => "failed", "data" => null, "message" => "Something went wrong");
                        }
                    } else {
                        // return response()->json(['data' => null, 'message' => 'something went wrong'], 400);
                        DB::rollBack();
                        error_log("Token failed to save");
                        return array("status" => "failed", "data" => null, "message" => "Something went wrong");
                    }

                } else {
                    // return response()->json(['data' => null, 'message' => 'something went wrong'], 400);
                    DB::rollBack();
                    error_log("Token Generation failed");
                    return array("status" => "failed", "data" => null, 'message' => "Something went wrong");
                }
            } else {
                error_log("in-correct");
                // return redirect($loginRedirect)->withErrors(['email or password is incorrect']);
                DB::rollBack();
                return array("status" => "failed", "data" => null, 'message' => "Email or password is incorrect");

                // return response()->json(['data' => null, 'message' => 'email or password is incorrect'], 400);
            }

        } catch (Exception $e) {

            error_log('in exception');

            DB::rollBack();
            return array("status" => "error", "data" => null, 'message' => "Something went wrong");
            //   return $e;
        }
    }

    static public function getLogin(Request $request)
    {
        $email = Input::get('email');
        $password = Input::get('password');

        $hashedPassword = md5($password);

        // ('ID', 'FirstName', 'LastName','EmailAddress','MobileNumber','TelephoneNumber','Gender','FunctionalTitle','FunctionalTitle')
        $login = DB::table('user')
            ->select('ID')
            ->where('EmailAddress', '=', $email)->where('Password', '=', $hashedPassword)
            ->get();

        $checkLogin = json_decode(json_encode($login), true);

        if (count($checkLogin) > 0) {
            // $session = LoginModel::createLoginSession($request, $checkLogin);
            // return redirect( $homeRedirect )->with($session);

            return array("status" => true, "data" => $checkLogin[0]);

            // return response()->json(['data' => $checkLogin, 'message' => 'Successfully Login'], 200);
        } else {
            // return redirect($loginRedirect)->withErrors(['email or password is incorrect']);

            return array("status" => false, "data" => null);

            // return response()->json(['data' => null, 'message' => 'email or password is incorrect'], 400);
        }


    }

    public static function generateAccessToken()
    {
        // return Session::get('sessionLoginData');

        // $hash = md5(uniqid(rand(), true));
        $attemp = 0;
        do {
            $token = md5(uniqid(rand(), true));
            // $user_access_token = DB::table('access_token')->where('AccessToken', $token)->get();
            // $user_access_token = GenericModel::simpleFetchGenericByWhere('access_token',"=","AccessToken", $token);
            $user_access_token = DB::table('access_token')
                ->where('AccessToken', '=', $token)
                ->get();
            $attemp++;
        } while ($attemp < 5);

        // while(!empty($user_access_token) );

        // while(!empty($user_access_token) || $attemp > 5);

        if (!empty($user_access_token)) {
            // return $token;
            return $user_access_token;
        } else {
            return null;
        }

    }

    static public function getRegisterTrans(Request $request)
    {
        $data = $request->all();

        $inviteCode = Input::get('InviteCode');
        $email = Input::get('EmailAddress');
        $password = Input::get('Password');
        $hashedPassword = md5($password);
        $date = HelperModel::getDate();
        $patientUniqueId = 0;

        DB::beginTransaction();
        try {

            $inviteCode = DB::table('account_invitation')
                ->select('Id', 'Token', 'BelongTo', 'ByUserId', 'ToMobileNumber', 'CountryPhoneCode')
                ->where('Token', '=', $inviteCode)
                ->where('ToEmailAddress', '=', $email)
                ->where('Status_', '=', "ignored")
                ->where('IsActive', '=', 0)
                ->get();

            $checkInviteCode = json_decode(json_encode($inviteCode), true);

            if (count($checkInviteCode) > 0) {

                $belongTo = $checkInviteCode[0]['BelongTo'];
                $byUserId = $checkInviteCode[0]['ByUserId'];
                $countryPhoneCode = $checkInviteCode[0]['CountryPhoneCode'];
                $mobileNumber = $checkInviteCode[0]['ToMobileNumber'];

                error_log($belongTo);

                $inviteUpdateData = array(
                    "Status_" => "accepted",
                    "IsActive" => 1
                );

                $inviteUpdate = DB::table('account_invitation')
                    ->where('Token', $checkInviteCode[0]['Token'])
                    ->update($inviteUpdateData);

                if ($inviteUpdate > 0) {
                    if ($belongTo == "doctor_patient") {

                        error_log("YES");
                        //means patient is registering
                        //so generate Patient unique id here
                        //calling table view
                        try {
                            $getPatientCountResult = DB::table('get_patient_count_view')
                                ->select('TotalPatient')
                                ->take(1)
                                ->get();
                            if (count($getPatientCountResult) == 1) {
                                $getPatientCountResult = $getPatientCountResult[0]->TotalPatient;
                                if ($getPatientCountResult > 0) {
                                    $patientUniqueId = $getPatientCountResult + 1;
                                }
                            }
                        } catch (Exception $exception) {
                            error_log("exception in fetching totalpatient count");
                            error_log($exception);
                            return array("status" => "failed", "data" => null, "message" => "Failed to insert the data");
                        }
                    }

                    $insertData = array(
                        "PatientUniqueId" => $patientUniqueId,
                        "FirstName" => $data["FirstName"],
                        "LastName" => $data["LastName"],
                        "EmailAddress" => $data["EmailAddress"],
                        "CountryPhoneCode" => $countryPhoneCode,
                        "MobileNumber" => $mobileNumber,
                        "TelephoneNumber" => $data["TelephoneNumber"],
                        "OfficeAddress" => $data["OfficeAddress"],
                        "ResidentialAddress" => $data["ResidentialAddress"],
                        "Password" => $hashedPassword,
                        "Gender" => $data["Gender"],
                        "FunctionalTitle" => $data["FunctionalTitle"],
                        "Age" => $data["Age"],
                        "AgeGroup" => $data["AgeGroup"],
                        "CreatedOn" => $date["timestamp"],
                        "IsActive" => 1
                    );

                    $checkInsertUserId = DB::table("user")->insertGetId($insertData);

                    if ($checkInsertUserId) {

                        $insertUserAssociationData = array(
                            "SourceUserId" => $byUserId,
                            "DestinationUserId" => $checkInsertUserId,
                            "AssociationType" => $belongTo,
                            "IsActive" => 1
                        );

                        DB::table("user_association")->insertGetId($insertUserAssociationData);

                        $roleCode = "";
                        if ($belongTo == "superadmin_doctor") {
                            $roleCode = "doctor";
                        } else if ($belongTo == "doctor_patient") {
                            $roleCode = "patient";
                        } else if ($belongTo == "doctor_facilitator") {
                            $roleCode = "facilitator";
                        } else {
                            $roleCode = "noRole";
                        }

                        $roleData = DB::table('role')
                            ->select('Id')
                            ->where('CodeName', '=', $roleCode)
                            ->where('IsActive', '=', 1)
                            ->get();

                        $checkRoleData = json_decode(json_encode($roleData), true);

                        if (count($checkRoleData) > 0) {

                            $insertRoleData = array(
                                "UserId" => $checkInsertUserId,
                                "RoleId" => $checkRoleData[0]["Id"],
                                "IsActive" => 1
                            );

                            DB::table("user_access")->insertGetId($insertRoleData);

                            if ($checkInsertUserId) {

                                Mail::raw('Welcome, You are successfully registered to CCM', function ($message) use ($email) {
                                    $message->to($email)->subject("Registration Successful");
                                });

                                DB::commit();
                                return array("status" => "success", "data" => $checkInsertUserId, "message" => "You have successfully Signed up");

                            } else {
                                DB::rollBack();
                                return array("status" => "failed", "data" => null, "message" => "failed to insert role");
                            }
                        } else {
                            DB::rollBack();
                            return array("status" => "failed", "data" => null, "message" => "role not found");
                        }
                    } else {
                        DB::rollBack();
                        return array("status" => "failed", "data" => null, "message" => "Failed to insert the data");
                    }

                } else {
                    return array("status" => "failed", "data" => null, "message" => "Something went wrong");
                }
            } else {
                DB::rollBack();
                return array("status" => "failed", "data" => null, "message" => "Code not found or it is expired");

            }

        } catch (Exception $e) {

            echo "error";
            DB::rollBack();
            return array("status" => "error", "data" => null);
            //   return $e;
        }


    }

    static public function getAdminLogin(Request $request)
    {
        $email = Input::get('email');
        $password = Input::get('password');
        $hashedPassword = md5($password);
        $loginRedirect = url('/admin/login');
        $homeRedirect = url('/admin/home');

        $login = DB::table('users')
            ->select('user_id', 'email', 'password')
            ->where('email', '=', $email)->where('password', '=', $hashedPassword)
            ->get();

        $checkLogin = json_decode(json_encode($login), true);

        if (count($checkLogin) > 0) {
            $session = LoginModel::createLoginSession($request, $checkLogin);
            return redirect($homeRedirect)->with($session);
        }
        return redirect($loginRedirect)->withErrors(['email or password is incorrect']);
    }

    static private function createLoginSession($request, $checkLogin)
    {
//        $userRoles = DB::table('userrole')->select('role.TaskApprover', 'roleauth.RoleID','roleauth.MenuID as MenuID', 'roleauth.ReadAccess as ReadAccess', 'roleauth.ReadWriteAccess as ReadWirteAccess','roleauth.NoAccess as NoAccess')
//            ->leftJoin('roleauth', 'userrole.RoleID', '=', 'roleauth.RoleID')
//            ->leftJoin('role', 'roleauth.RoleID', '=', 'role.RoleID')
//            ->where('userrole.UserID', '=', $checkLogin[0]['UserID'])
//            ->get();
//        $roles = json_decode(json_encode($userRoles), true);

        $sessionData = array("UserID" => $checkLogin[0]['user_id'],
            "email" => $checkLogin[0]['email']);
        //"LastName" => $checkLogin[0]['LastName'],
        return $sessionData;
    }

    static private function updateLastLogin($userID)
    {
        $genericModel = new GenericModel;
        $updated = $genericModel->updateGeneric('user', 'UserID', $userID, ["LastLogin" => Carbon::now()]);
        return $updated;
    }

    static private function getValidateRules()
    {
        return array("email" => "required", "password" => "required");
    }

    static function getlogout(Request $request)
    {
        $userId = Input::get('Id');

        error_log("User Id is");
        error_log($userId);

        DB::beginTransaction();
        try {

            DB::table('access_token')->where('UserId', $userId)->delete();

            error_log("Access Token deleted");

            $IsCurrentlyLoggedInData = array(
                "IsCurrentlyLoggedIn" => 0
            );

            DB::table('user')
                ->where('Id', $userId)
                ->update($IsCurrentlyLoggedInData);

            DB::commit();

            return array("status" => "success", "data" => null);

        } catch (Exception $e) {

            error_log('in exception');
            error_log($e);

            DB::rollBack();
            return array("status" => "error", "data" => null, 'message' => "Something went wrong");
        }
    }

    static function getAdminlogout(Request $request)
    {
        session()->forget('sessionLoginData');
        session()->flush();
        return redirect(url('/admin/login'));

    }

    static function checkEmailAvailable(string $email)
    {
        $result = DB::table('user')
            ->select('*')
            ->where('EmailAddress', '=', $email)
            ->where('IsActive', '=', 1)
            ->get();
        return $result;
    }

    static function checkTokenAvailableForResetPass(string $token)
    {
        $result = DB::table('verification_token')
            ->select('*')
            ->where('Token', '=', $token)
            ->where('IsActive', '=', 1)
            ->get();
        return $result;
    }

    static function checkTokenWithTypeAvailableForResetPass(string $token,$type)
    {
        error_log("type");
        error_log($type);

        $result = DB::table('verification_token')
            ->select('*')
            ->where('Token', '=', $token)
            ->where('TokenType', '=', $type)
            ->where('IsActive', '=', 1)
            ->first();
        return $result;
    }

    public static function sendEmail($email, $subject, $emailMessage, $url = "")
    {

        $urlForEmail = url($url);

        $subjectForEmail = $subject;
        $contentForEmail = " <b>Dear User</b>, <br><br>" .
            "  " . $emailMessage . " " .
            "<br>" . $urlForEmail . " ";


//        Mail::raw($contentForEmail, function ($message) use ($email, $subjectForEmail) {
//            $message->to($email)->subject($subjectForEmail);
//        });

        Mail::send([], [], function ($message) use ($email, $subjectForEmail, $contentForEmail) {
            $message->to($email)
                ->subject($subjectForEmail)
                // here comes what you want
                // ->setBody('Hi, welcome user!'); // assuming text/plain
                // or:
                ->setBody($contentForEmail, 'text/html'); // for HTML rich messages
        });

        return true;
    }

    public static function FetchLoginHistoryCount($userId)
    {
        error_log('getting count of login history for provided user');
        $query = DB::table('user_login_history')
            ->where('UserId', $userId)
            ->count();

        return $query;
    }


    public static function FetchLoginHistoryListViaPagination($userId, $offset, $limit)
    {
        error_log('getting list of login history for provided user');
        $query = DB::table('user')
            ->join('user_login_history', 'user.Id', 'user_login_history.UserId')
            ->where('user_login_history.UserId', $userId)
            ->skip($offset * $limit)->take($limit)
            ->select('user.*', 'user_login_history.Id as LoginHistoryId', 'user_login_history.CreatedOn as LoginDateTime')
            ->get();
        return $query;
    }

    public static function calculateFormattedTime($createdOn)
    {
        $formatMessage = null;
//
//        $timestamp = $request->get('t');
//        error_log($timestamp);

        $topicCreatedTime = Carbon::createFromTimestamp($createdOn);
        $currentTime = Carbon::now("UTC");

        $diffInYears = $currentTime->diffInYears($topicCreatedTime);
        $diffInMonths = $currentTime->diffInMonths($topicCreatedTime);
        $diffInWeeks = $currentTime->diffInWeeks($topicCreatedTime);
        $diffInDays = $currentTime->diffInDays($topicCreatedTime);
        $diffInHours = $currentTime->diffInHours($topicCreatedTime);
        $diffInMints = $currentTime->diffInMinutes($topicCreatedTime);
        $diffInSec = $currentTime->diffInSeconds($topicCreatedTime);

        error_log($topicCreatedTime);
        error_log($currentTime);
        error_log($diffInYears);
        error_log($diffInMonths);
        error_log($diffInWeeks);
        error_log($diffInDays);
        error_log($diffInHours);
        error_log($diffInMints);
        error_log($diffInSec);

        if ($diffInYears > 0) {
            $formatMessage = $diffInYears . ' y ago';
            return $formatMessage;
        } else if ($diffInMonths > 0) {
            $formatMessage = $diffInMonths . ' mon ago';
            return $formatMessage;
        } else if ($diffInWeeks > 0) {
            $formatMessage = $diffInWeeks . ' w ago';
            return $formatMessage;
        } else if ($diffInDays > 0) {
            $formatMessage = $diffInDays . ' d ago';
            return $formatMessage;
        } else if ($diffInHours > 0) {
            $formatMessage = $diffInHours . ' h ago';
            return $formatMessage;
        } else if ($diffInMints > 0) {
            $formatMessage = $diffInMints . ' min ago';
            return $formatMessage;
        } else if ($diffInSec >= 30) {
            $formatMessage = $diffInMints . ' sec ago';
            return $formatMessage;
        } else {
            //seconds
            $formatMessage = 'Now';
            return $formatMessage;
        }
    }

    public static function sendEmailAttach($email, $subject, $emailMessage, $url = "", $path)
    {
        $urlForEmail = url($url);
        $subjectForEmail = $subject;
        $contentForEmail = " <b>Dear User</b>, <br><br>" .
            "  " . $emailMessage . " " .
            "<br>" . $urlForEmail . " ";

        Mail::send([], [], function ($message) use ($email, $subjectForEmail, $contentForEmail) {
            $message->to($email)
                ->subject($subjectForEmail)
                ->attach('C:\\Users\\SO-LPT-028\\Downloads\\tuts_notes.pdf');
        });

        return true;
    }
}


