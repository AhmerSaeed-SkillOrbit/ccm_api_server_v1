<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use PhpParser\Node\Stmt\Return_;
use PHPUnit\Util\RegularExpressionTest;

class LoginModel {

    static public function getLogin(Request $request) {
        $email = Input::get('email');
        $password = Input::get('password');

        $hashedPassword = md5($password);
        $loginRedirect = url('/login');
        $homeRedirect = url('/');

        $login = DB::table('users')
            ->select('user_id', 'email', 'password')
            ->where('email', '=', $email)->where('password', '=', $hashedPassword)
            ->get();

        $checkLogin = json_decode(json_encode($login), true);

        if (count($checkLogin) > 0) {
            $session = LoginModel::createLoginSession($request, $checkLogin);
            return redirect( $homeRedirect )->with($session);
        }
        return redirect($loginRedirect)->withErrors(['email or password is incorrect']);
    }

    static public function getAdminLogin(Request $request) {
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
            return redirect( $homeRedirect )->with($session);
        }
        return redirect($loginRedirect)->withErrors(['email or password is incorrect']);
    }

    static private function createLoginSession($request, $checkLogin) {
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

    static private function updateLastLogin($userID) {
        $genericModel = new GenericModel;
        $updated = $genericModel->updateGeneric('user', 'UserID', $userID, [ "LastLogin" => Carbon::now()]);
        return $updated;
    }

    static private function getValidateRules() {
        return array("email" => "required", "password" => "required");
    }

    static function getlogout(Request $request) {
        session()->forget('sessionLoginData');
        session()->flush();
        return redirect(url('/login'));

    }

    static function getAdminlogout(Request $request) {
        session()->forget('sessionLoginData');
        session()->flush();
        return redirect(url('/admin/login'));

    }


}
