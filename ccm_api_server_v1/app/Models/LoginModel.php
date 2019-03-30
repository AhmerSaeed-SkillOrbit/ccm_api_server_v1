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

                    // return array("status" => "failed", "data" => $date, "message" => "Failed to insert the Token");
                    // return array("status" => "success", "data" => $date, "message" => "Failed to insert the Token");

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

                            $data = array(
                                "userId" => $checkTokenData[0]["UserId"],
                                "accessToken" => $checkTokenData[0]["AccessToken"],
                                "expiryTime" => $checkTokenData[0]["ExpiryTime"]
                            );
                            // return response()->json(['data' => $check['data'], 'message' => 'Successfully Login'], 200);
                            // return response()->json(['data' => ['User' => $data, 'accessToken' => "a123"], 'message' => 'Successfully Login'], 200);

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
        $belongTo = "";
        $data = $request->all();

        $inviteCode = Input::get('InviteCode');
        $email = Input::get('EmailAddress');
        $password = Input::get('Password');
        $hashedPassword = md5($password);
        $date = HelperModel::getDate();

        DB::beginTransaction();
        try {

            $inviteCode = DB::table('account_invitation')
                ->select('Id', 'Token', 'BelongTo', 'ByUserId')
                ->where('Token', '=', $inviteCode)
                ->where('ToEmailAddress', '=', $email)
                ->where('Status_', '=', "ignored")
                ->where('IsActive', '=', 0)
                ->get();

            $checkInviteCode = json_decode(json_encode($inviteCode), true);

            if (count($checkInviteCode) > 0) {

                $belongTo = $checkInviteCode[0]['BelongTo'];
                $byUserId = $checkInviteCode[0]['ByUserId'];

                $inviteUpdateData = array(
                    "Status_" => "accepted",
                    "IsActive" => 1
                );

                $inviteUpdate = DB::table('account_invitation')
                    ->where('Token', $checkInviteCode[0]['Token'])
                    ->update($inviteUpdateData);

                if ($inviteUpdate > 0) {

                    $insertData = array(
                        "FirstName" => $data["FirstName"],
                        "LastName" => $data["LastName"],
                        "EmailAddress" => $data["EmailAddress"],
                        "MobileNumber" => $data["MobileNumber"],
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

                                Mail::raw('Welcome to CCM', function ($message) use ($email) {
                                    $message->to($email)->subject("Invitation");
                                });

                                DB::commit();
                                // return array("status" => true, "data" => $data);
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
        session()->forget('sessionLoginData');
        session()->flush();
        return redirect(url('/login'));

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
}


