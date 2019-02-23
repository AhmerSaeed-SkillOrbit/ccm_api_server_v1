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

class LoginModel
{

    static public function getLoginTrans(Request $request)
    {
        
        $email = Input::get('email');
        $password = Input::get('password');

        $hashedPassword = md5($password);

        DB::beginTransaction();
        try {

            // ('ID', 'FirstName', 'LastName','EmailAddress','MobileNumber','TelephoneNumber','Gender','FunctionalTitle','FunctionalTitle')
            $login = DB::table('user')
                ->select('ID')
                ->where('EmailAddress', '=', $email)->where('Password', '=', $hashedPassword)
                ->get();

            $checkLogin = json_decode(json_encode($login), true);

            if (count($checkLogin) > 0) {
                // $session = LoginModel::createLoginSession($request, $checkLogin);
                // return redirect( $homeRedirect )->with($session);

                $token = md5(uniqid(rand(), true));
//                $token = LoginModel::generateAccessToken();

                if ($token != null) {

                    $date = HelperModel::getDate();

                    // return array("status" => "failed", "data" => $date, "message" => "token insertion failed");
                    // return array("status" => "success", "data" => $date, "message" => "token insertion failed");

                    $insertData = array(
                        "UserId" => $checkLogin[0]['ID'],
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
//                            return response()->json(['data' => ['User' => $data, 'accessToken' => "a123"], 'message' => 'Successfully Login'], 200);

                            DB::commit();
                            return array("status" => true, "data" => $data);

                            // return response()->json(['data' => $checkLogin, 'message' => 'Successfully Login'], 200);
                        } else {
                            DB::rollBack();
                            return array("status" => "failed", "data" => null, "message" => "get token data failed");
                        }


                    } else {
//                        return response()->json(['data' => null, 'message' => 'something went wrong'], 400);
                        DB::rollBack();
                        return array("status" => "failed", "data" => null, "message" => "token insertion failed");
                    }

                } else {
//                    return response()->json(['data' => null, 'message' => 'something went wrong'], 400);
                    DB::rollBack();
                    return array("status" => "failed", "data" => null);
                }
            } else {
                // return redirect($loginRedirect)->withErrors(['email or password is incorrect']);

                DB::rollBack();
                return array("status" => "failed", "data" => null);

                // return response()->json(['data' => null, 'message' => 'email or password is incorrect'], 400);
            }

        } catch (Exception $e) {

            echo "error";
            DB::rollBack();
            return array("status" => "error", "data" => null);
            //   return $e;
        } catch (FatalThrowableError $e) {

            echo "error";
            DB::rollBack();
            return array("status" => "error", "data" => null);
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


}
