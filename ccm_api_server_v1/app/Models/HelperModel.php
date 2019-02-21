<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserRolesModel;
use Session;

class HelperModel {

    static public function getUserSessionID() {
        if (Session::has('sessionLoginData')) {
            $sessionData = HelperModel::getSessionObject();
            return $sessionData['UserID'];
        } else {
            return -1;
        }
    }

    static public function getUserSessionRoleAuth() {
        $sessionData = HelperModel::getSessionObject();
        return $sessionData['RoleAndAuth'];
    }

    public static function getMenuIDsFromDatabase($routeName) {
        $menuid = DB::table('menu')->select('MenuID')->where('page', '=', $routeName)->get();
        $id = json_decode(json_encode($menuid), true);
        return $id;
    }

    static function getRoleNameFromSessionData() {
        $roleAuthOfSessionUser = HelperModel::getUserSessionRoleAuth();
        $roleID = $roleAuthOfSessionUser[0]['RoleID'];
        $roleName = UserRolesModel::getRoleNameByID($roleID);
        return $roleName;
    }

    public static function getUserRoleIDFromSession() {
        $sessionObject = HelperModel::getSessionObject();
        $roleIDs = HelperModel::getUniqueRoleIDs($sessionObject['RoleAndAuth']);
        return array_unique($roleIDs);
    }

    public static function getTaskApproverFromSession()
    {
        $sessionObject = HelperModel::getSessionObject();
        $roles = $sessionObject['RoleAndAuth'];
        $taskApprover = $roles[0]['TaskApprover'];
        return $taskApprover;
    }

    private static function getUniqueRoleIDs($roleAndAuth) {
        $arr = array();
        foreach ($roleAndAuth as $role) {
            array_push($arr, $role['RoleID']);
        }
        return $arr;
    }

    public static function getSessionObject() {
        return Session::get('sessionLoginData');
    }

}
