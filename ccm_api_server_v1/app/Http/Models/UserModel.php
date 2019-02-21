<?php

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HelperModel;

class UserModel {

    static function addUser() {

        $sessionNotFoundRedirectUrl = url('/login');
        $redirectUserForm = url('/user_form/add/0');

        // $locked = UserModel::convertLockToInteger(Input::get('locked'));
        $locked = Input::get('locked');

        $firstName = Input::get('firstName');
        $lastName = Input::get('lastName');
        $password = Input::get('password');
        $confirmPassword = NULL;

        $email = Input::get('email');

//        $phoneNumber1 = Input::get('phoneNumber1');
//        $phoneNumber2 = Input::get('phoneNumber2');
//
//        $selectedRoles = Input::get('selectedRoles');

    //    $createdBy = HelperModel::getUserSessionID();

      //  if ($createdBy == -1)
        //    return redirect($sessionNotFoundRedirectUrl);


//        if ($password != $confirmPassword)
//            return 'unmatchPassword';

//            return redirect($redirectUserForm)->withErrors(['confirm password must match the password']);

        if (UserModel::isDuplicateName($email, $lastName))
            return 'duplicate';
//            return redirect($redirectUserForm)->withErrors(['Duplication Error! This First Name and Last Name is already exist']);

        $hashedPassword = md5($password);
        $data = array("user_type_id" => 1, "first_name" => $firstName, "last_name" => $lastName, "password" => $hashedPassword, "email" => $email,
            "status_id" => 3, "created_date" => Carbon::now(), "created_by" => 1);

        $genericModel = new GenericModel;
        $userID = $genericModel->insertGenericAndReturnID('users', $data);


//        if (count($selectedRoles) > 0) {
//            $affectedRow = UserModel::addUserRoleToTable($userID, $selectedRoles);
//
//            if ($affectedRow > 0)
//                return 'success';
//            else
//                return 'failed';

            if ($userID > 0)
                return 'success';
            else
                return 'failed';

    }

    static function addPatient() {

        $sessionNotFoundRedirectUrl = url('/login');
        $redirectUserForm = url('/admin/add/0');

        // $locked = UserModel::convertLockToInteger(Input::get('locked'));
//        $locked = Input::get('locked');

        $name = Input::get('name');
        $password = Input::get('password');
        $confirmPassword = NULL;

        $email = Input::get('email');

//        if (UserModel::isDuplicateName($email, null))
//            return 'duplicate';
//            return redirect($redirectUserForm)->withErrors(['Duplication Error! This First Name and Last Name is already exist']);

        $hashedPassword = md5($password);
        $data = array("name" => $name,"password" => $hashedPassword, "email" => $email,
            "created_date" => Carbon::now(), "created_by" => 1);

        $genericModel = new GenericModel;
        $userID = $genericModel->insertGenericAndReturnID('patients', $data);

        $data = array("user_type_id" => 1, "first_name" => $name,"password" => $hashedPassword, "email" => $email,
            "status_id" => 3, "created_date" => Carbon::now(), "created_by" => 1);

        $userID = $genericModel->insertGenericAndReturnID('users', $data);

            if ($userID > 0)
                return 'success';
            else
                return 'failed';

    }

    static function updateUser(Request $request) {
        $locked = UserModel::convertLockToInteger(Input::get('locked'));
        $userID = Input::get('userID');
        $firstName = Input::get('firstName');
        $lastName = Input::get('lastName');

        $email = Input::get('email');
        $phoneNumber1 = Input::get('phoneNumber1');
        $phoneNumber2 = Input::get('phoneNumber2');

        $selectedRoles = Input::get('selectedRoles');

        $UpdatedBy = $request->session()->get('sessionLoginData');
        $UpdatedBy = json_decode(json_encode($UpdatedBy['UserID']), true);

        if (UserModel::isDuplicateNameForUpdate($firstName, $lastName, $userID)) {
            return 'duplicate';
        } else {
            $data = array("Status" => $locked, "FirstName" => $firstName, "LastName" => $lastName, "Email" => $email, "Phone1" => $phoneNumber1, "Phone2" => $phoneNumber2, "UpdatedBy" => $UpdatedBy['UserID']);

            $genericModel = new GenericModel;
            $userUpdated = $genericModel->updateGeneric('user', 'UserID', $userID, $data);
            if ($userUpdated > 0) {
                $affectedRow = UserModel::updateUserRoleToTable($userID, $selectedRoles);
                if ($affectedRow > 0)
                    return 'success';
                else
                    return 'failed';
            } else {
                return 'failed';
            }
        }
    }

    static function searchUser(){

        $userName = Input::get('userName');
        $phone = Input::get('phone');
        $email = Input::get('email');

        $query = DB::table('user');

        if( empty($userName) && empty($phone) && empty($email) )
            return array();

        if(isset($userName) && !empty($userName) )
            $query -> where( DB::raw( "CONCAT(FirstName,' ', LastName)" ), 'LIKE', $userName.'%');
        if(isset($phone) && !empty($phone) )
            $query -> where('Phone1', 'LIKE', $phone. '%') -> orWhere('Phone2', 'LIKE', $phone. '%');
        if(isset($email) && !empty($email) )
            $query -> where('Email', 'LIKE', $email. '%');

        $searched = $query -> select('user.*', DB::raw('GROUP_CONCAT(role.RoleID SEPARATOR "," ) as RoleID'), DB::raw('GROUP_CONCAT(role.Name SEPARATOR "," ) as RoleName'))
            -> leftjoin('userrole', 'userrole.UserID', '=', 'user.UserID')
            -> leftjoin('role', 'role.RoleID', '=', 'userrole.RoleID')
            -> get();
        return json_decode(json_encode($searched), true);
    }

    static private function addUserRoleToTable($userID, $selectedRoles) {
        $createUserArray = array();
        foreach ($selectedRoles as $roles) {
            array_push($createUserArray, array("UserID" => $userID, "RoleID" => $roles));
        }
        $genericModel = new GenericModel;
        $row = $genericModel->insertGeneric('userrole', $createUserArray);
        return $row;
    }

    static private function updateUserRoleToTable($userID, $selectedRoles) {
        $genericModel = new GenericModel;
        $del = $genericModel->deleteGeneric('userrole', 'UserID', $userID);
        if (count($selectedRoles) > 0) {
            $createUserArray = array();
            foreach ($selectedRoles as $roles) {
                array_push($createUserArray, array("UserID" => $userID, "RoleID" => $roles));
            }
            $genericModel = new GenericModel;
            $row = $genericModel->insertGeneric('userrole', $createUserArray);
            if ($row) {
                return $row;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    static private function isDuplicateName($firstName, $lastName) {
//        $isDuplicate = DB::table('users')->select('user_id')->where('FirstName', '=', $firstName)->where('last_name', '=', $lastName)->get();
        $isDuplicate = DB::table('users')->select('user_id')->where('email', '=', $firstName)->get();
        if (count($isDuplicate)) {
            return true;
        }
        return false;
    }

    static private function isDuplicateNameForUpdate($firstName, $lastName, $id) {
        $isDuplicate = DB::table('users')->select('UserID')->where('FirstName', '=', $firstName)->where('LastName', '=', $lastName)->where('UserID', '!=', $id)->get();
        if (count($isDuplicate)) {
            return true;
        }
        return false;
    }

    static private function convertLockToInteger($value) {
        if (isset($value))
            return 1;
        else
            return 0;
    }

    static function getUsersList() {
        $result = DB::table('user')->select(DB::raw("user.*,GROUP_CONCAT(role.RoleID SEPARATOR ',') as `RoleID`,GROUP_CONCAT(role.Name SEPARATOR ',') as `roleName`"))
            ->leftjoin('userrole', 'userrole.UserID', '=', 'user.UserID')
            ->leftjoin('role', 'role.RoleID', '=', 'userrole.RoleID')
            ->groupBy('user.UserID')
            ->get();
        if (count($result) > 0)
            return $result;
        else
            return null;
    }

    static function find($id) {
        $result = DB::table('user')->select(DB::raw("user.*,GROUP_CONCAT(role.RoleID SEPARATOR ',') as `RoleID`,GROUP_CONCAT(role.Name SEPARATOR ',') as `roleName`"))
            ->leftjoin('userrole', 'userrole.UserID', '=', 'user.UserID')
            ->leftjoin('role', 'role.RoleID', '=', 'userrole.RoleID')
            ->where('user.UserID', '=', $id)
            ->groupBy('user.UserID')
            ->get();
        if (count($result) > 0)
            return $result;
        else
            return null;
    }

    static function lock($id,$value){

        if($value['Status']== '1'){
            $data = array("Status" => '0');
        }
        else{
            $data = array("Status" => '1');
        }
        $genericModel = new GenericModel;
        $userUpdated = $genericModel->updateGeneric('user', 'UserID', $id, $data);
        if (isset($userUpdated))
            return 'success';
        else
            return 'failed';
    }

}
