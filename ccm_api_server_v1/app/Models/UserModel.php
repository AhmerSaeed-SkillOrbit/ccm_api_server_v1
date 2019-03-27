<?php

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HelperModel;

use Mail;

class UserModel
{

    static function addUser()
    {

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

    static function addPatient()
    {

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
        $data = array("name" => $name, "password" => $hashedPassword, "email" => $email,
            "created_date" => Carbon::now(), "created_by" => 1);

        $genericModel = new GenericModel;
        $userID = $genericModel->insertGenericAndReturnID('patients', $data);

        $data = array("user_type_id" => 1, "first_name" => $name, "password" => $hashedPassword, "email" => $email,
            "status_id" => 3, "created_date" => Carbon::now(), "created_by" => 1);

        $userID = $genericModel->insertGenericAndReturnID('users', $data);

        if ($userID > 0)
            return 'success';
        else
            return 'failed';

    }

    static function updateUser(Request $request)
    {
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

    static function searchUser()
    {

        $userName = Input::get('userName');
        $phone = Input::get('phone');
        $email = Input::get('email');

        $query = DB::table('user');

        if (empty($userName) && empty($phone) && empty($email))
            return array();

        if (isset($userName) && !empty($userName))
            $query->where(DB::raw("CONCAT(FirstName,' ', LastName)"), 'LIKE', $userName . '%');
        if (isset($phone) && !empty($phone))
            $query->where('Phone1', 'LIKE', $phone . '%')->orWhere('Phone2', 'LIKE', $phone . '%');
        if (isset($email) && !empty($email))
            $query->where('Email', 'LIKE', $email . '%');

        $searched = $query->select('user.*', DB::raw('GROUP_CONCAT(role.RoleID SEPARATOR "," ) as RoleID'), DB::raw('GROUP_CONCAT(role.Name SEPARATOR "," ) as RoleName'))
            ->leftjoin('userrole', 'userrole.UserID', '=', 'user.UserID')
            ->leftjoin('role', 'role.RoleID', '=', 'userrole.RoleID')
            ->get();
        return json_decode(json_encode($searched), true);
    }

    static private function addUserRoleToTable($userID, $selectedRoles)
    {
        $createUserArray = array();
        foreach ($selectedRoles as $roles) {
            array_push($createUserArray, array("UserID" => $userID, "RoleID" => $roles));
        }
        $genericModel = new GenericModel;
        $row = $genericModel->insertGeneric('userrole', $createUserArray);
        return $row;
    }

    static private function updateUserRoleToTable($userID, $selectedRoles)
    {
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

    static private function isDuplicateName($firstName, $lastName)
    {
//        $isDuplicate = DB::table('users')->select('user_id')->where('FirstName', '=', $firstName)->where('last_name', '=', $lastName)->get();
        $isDuplicate = DB::table('users')->select('user_id')->where('email', '=', $firstName)->get();
        if (count($isDuplicate)) {
            return true;
        }
        return false;
    }

    static private function isDuplicateNameForUpdate($firstName, $lastName, $id)
    {
        $isDuplicate = DB::table('users')->select('UserID')->where('FirstName', '=', $firstName)->where('LastName', '=', $lastName)->where('UserID', '!=', $id)->get();
        if (count($isDuplicate)) {
            return true;
        }
        return false;
    }

    static private function convertLockToInteger($value)
    {
        if (isset($value))
            return 1;
        else
            return 0;
    }

    static function getUsersList()
    {
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

    static function find($id)
    {
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

    static function lock($id, $value)
    {

        if ($value['Status'] == '1') {
            $data = array("Status" => '0');
        } else {
            $data = array("Status" => '1');
        }
        $genericModel = new GenericModel;
        $userUpdated = $genericModel->updateGeneric('user', 'UserID', $id, $data);
        if (isset($userUpdated))
            return 'success';
        else
            return 'failed';
    }

    static public function FetchUserFacilitatorListForDoctorWithSearchAndPagination
    ($tableName, $operator, $columnName, $data, $offset, $limit, $orderBy, $keyword, $destinationUserId)
    {
        error_log('in model ');
        if ($keyword != null && $keyword != "null") {
            error_log('Keyword NOT NULL');
            $query = DB::table('user')
                ->join('user_access', 'user_access.UserId', 'user.Id')
                ->join('role', 'user_access.RoleId', 'role.Id')
                ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
                ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
                ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
                ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName',
                    'sourceUser.FirstName as SourceUserFirstName', 'sourceUser.LastName as SourceUserLastName', 'sourceUser.EmailAddress as SourceUserEmailAddress', 'user_association.AssociationType',
                    'destinationUser.FirstName as DestinationUserFirstName', 'destinationUser.LastName as DestinationUserLastName',
                    'destinationUser.EmailAddress as DestinationUserEmailAddress')
                ->where($tableName . '.' . $columnName, $operator, $data)
                ->whereIn('user.Id', $destinationUserId)
                ->Where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%')
                ->orderBy($tableName . '.' . $orderBy, 'DESC')
                ->groupBy('user.Id')
//                ->offset($offset)->limit($limit)
                ->skip($offset * $limit)->take($limit)
                ->groupBy('user.Id')
                ->get();

            error_log($query);

            return $query;
        } else {
            error_log('keyword is NULL');
            $query = DB::table('user')
                ->join('user_access', 'user_access.UserId', 'user.Id')
                ->join('role', 'user_access.RoleId', 'role.Id')
                ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
                ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
                ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
                ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName', 'sourceUser.FirstName as SourceUserFirstName',
                    'sourceUser.LastName as SourceUserLastName', 'sourceUser.EmailAddress as SourceUserEmailAddress', 'user_association.AssociationType',
                    'destinationUser.FirstName as DestinationUserFirstName', 'destinationUser.LastName as DestinationUserLastName',
                    'destinationUser.EmailAddress as DestinationUserEmailAddress')
                ->where($tableName . '.' . $columnName, $operator, $data)
                ->whereIn('user.Id', $destinationUserId)
                ->groupBy('user.Id')
                ->orderBy($tableName . '.' . $orderBy, 'DESC')
//                ->offset($offset)->limit($limit)
                ->skip($offset * $limit)->take($limit)
                ->groupBy('user.Id')
                ->get();

            error_log($query);

            return $query;
        }
    }

    static public function FetchUserFacilitatorListForDoctorWithSearchCount
    ($tableName, $operator, $columnName, $data, $keyword, $destinationUserId)
    {
        error_log('in model ');
        if ($keyword != null && $keyword != "null") {
            error_log('Keyword NOT NULL');
            $query = DB::table('user')
                ->join('user_access', 'user_access.UserId', 'user.Id')
                ->join('role', 'user_access.RoleId', 'role.Id')
                ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
                ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
                ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
                ->where($tableName . '.' . $columnName, $operator, $data)
                ->whereIn('user.Id', $destinationUserId)
                ->Where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%')
                ->groupBy('user.Id')
                ->count();

            error_log($query);

            return $query;
        } else {
            error_log('keyword is NULL');
            $query = DB::table('user')
                ->join('user_access', 'user_access.UserId', 'user.Id')
                ->join('role', 'user_access.RoleId', 'role.Id')
                ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
                ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
                ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
                ->where($tableName . '.' . $columnName, $operator, $data)
                ->whereIn('user.Id', $destinationUserId)
                ->groupBy('user.Id')
                ->count();

            error_log($query);

            return $query;
        }
    }

    static public function FetchUserWithSearchAndPagination
    ($tableName, $operator, $columnName, $data, $offset, $limit, $orderBy, $keyword, $roleCode)
    {
        error_log('$roleCode ' . $roleCode);
        if ($roleCode != null && $roleCode != "null") {
            if ($keyword != null && $keyword != "null") {
                error_log('Both are NOT NULL');
                $query = DB::table('user')
                    ->join('user_access', 'user_access.UserId', 'user.Id')
                    ->join('role', 'user_access.RoleId', 'role.Id')
                    ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
                    ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
                    ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
                    ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName',
                        'sourceUser.FirstName as SourceUserFirstName', 'sourceUser.LastName as SourceUserLastName', 'sourceUser.EmailAddress as SourceUserEmailAddress', 'user_association.AssociationType',
                        'destinationUser.FirstName as DestinationUserFirstName', 'destinationUser.LastName as DestinationUserLastName',
                        'destinationUser.EmailAddress as DestinationUserEmailAddress')
                    ->where($tableName . '.' . $columnName, $operator, $data)
                    ->Where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
                    ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
                    ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
                    ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
                    ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
                    ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%')
                    ->where('role.CodeName', '=', $roleCode)
//                    ->offset($offset)->limit($limit)
                    ->skip($offset * $limit)->take($limit)
                    ->orderBy($tableName . '.' . $orderBy, 'DESC')
                    ->groupBy('user.Id')
                    ->get();

                error_log($query);

                return $query;
            } else {
                error_log('keyword is NULL and role is NOT NULL');
                $query = DB::table('user')
                    ->join('user_access', 'user_access.UserId', 'user.Id')
                    ->join('role', 'user_access.RoleId', 'role.Id')
                    ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
                    ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
                    ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
                    ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName', 'sourceUser.FirstName as SourceUserFirstName',
                        'sourceUser.LastName as SourceUserLastName', 'sourceUser.EmailAddress as SourceUserEmailAddress', 'user_association.AssociationType',
                        'destinationUser.FirstName as DestinationUserFirstName', 'destinationUser.LastName as DestinationUserLastName',
                        'destinationUser.EmailAddress as DestinationUserEmailAddress')
                    ->where($tableName . '.' . $columnName, $operator, $data)
                    ->where('role.CodeName', '=', $roleCode)
//                    ->offset($offset)->limit($limit)
                    ->skip($offset * $limit)->take($limit)
                    ->orderBy($tableName . '.' . $orderBy, 'DESC')
                    ->groupBy('user.Id')
                    ->get();

                error_log($query);

                return $query;
            }
        } else {
            if ($keyword != null && $keyword != "null") {
                error_log('Role is NULL and keyword is NOT NULL');
                return DB::table('user')
                    ->join('user_access', 'user_access.UserId', 'user.Id')
                    ->join('role', 'user_access.RoleId', 'role.Id')
                    ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
                    ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
                    ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
                    ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName', 'sourceUser.FirstName as SourceUserFirstName',
                        'sourceUser.LastName as SourceUserLastName', 'sourceUser.EmailAddress as SourceUserEmailAddress', 'user_association.AssociationType',
                        'destinationUser.FirstName as DestinationUserFirstName', 'destinationUser.LastName as DestinationUserLastName',
                        'destinationUser.EmailAddress as DestinationUserEmailAddress')
                    ->where('user.IsActive', '=', true)
                    ->Where('FirstName', 'like', '%' . $keyword . '%')
                    ->orWhere('LastName', 'like', '%' . $keyword . '%')
                    ->orWhere('EmailAddress', 'like', '%' . $keyword . '%')
                    ->orWhere('MobileNumber', 'like', '%' . $keyword . '%')
                    ->orWhere('TelephoneNumber', 'like', '%' . $keyword . '%')
                    ->orWhere('FunctionalTitle', 'like', '%' . $keyword . '%')
//                    ->offset($offset)->limit($limit)
                    ->skip($offset * $limit)->take($limit)
                    ->orderBy($orderBy, 'DESC')
                    ->groupBy('user.Id')
                    ->get();

            } else {
                error_log('Role is NULL and keyword also NULL');
                return DB::table('user')
                    ->join('user_access', 'user_access.UserId', 'user.Id')
                    ->join('role', 'user_access.RoleId', 'role.Id')
                    ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
                    ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
                    ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
                    ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName', 'sourceUser.FirstName as SourceUserFirstName',
                        'sourceUser.LastName as SourceUserLastName', 'sourceUser.EmailAddress as SourceUserEmailAddress', 'user_association.AssociationType',
                        'destinationUser.FirstName as DestinationUserFirstName', 'destinationUser.LastName as DestinationUserLastName',
                        'destinationUser.EmailAddress as DestinationUserEmailAddress')
                    ->where('user.IsActive', '=', true)
//                    ->offset($offset)->limit($limit)
                    ->skip($offset * $limit)->take($limit)
                    ->orderBy($orderBy, 'DESC')
                    ->groupBy('user.Id')
                    ->get();
            }
        }
    }

    static public function FetchDoctorUserListWithFacilitatorSearchAndPagination
    ($tableName, $operator, $columnName, $data, $offset, $limit, $orderBy, $keyword, $roleCode)
    {
        error_log('$roleCode ' . $roleCode);
        if ($keyword != null && $keyword != "null") {
            error_log('keyword is NOT NULL');
            $query = DB::table('user')
                ->join('user_access', 'user_access.UserId', 'user.Id')
                ->join('role', 'user_access.RoleId', 'role.Id')
                ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
                ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
                ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
                ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName',
                    'sourceUser.FirstName as SourceUserFirstName', 'sourceUser.LastName as SourceUserLastName', 'sourceUser.EmailAddress as SourceUserEmailAddress', 'user_association.AssociationType',
                    'destinationUser.FirstName as DestinationUserFirstName', 'destinationUser.LastName as DestinationUserLastName',
                    'destinationUser.EmailAddress as DestinationUserEmailAddress')
                ->where($tableName . '.' . $columnName, $operator, $data)
                ->Where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
                ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%')
                ->where('role.CodeName', '=', $roleCode)
//                ->offset($offset)->limit($limit)
                ->skip($offset * $limit)->take($limit)
                ->orderBy($tableName . '.' . $orderBy, 'DESC')
                ->first();

            error_log($query);

            return $query;
        } else {
            error_log('keyword is NULL and role is NOT NULL');
            $query = DB::table('user')
                ->join('user_access', 'user_access.UserId', 'user.Id')
                ->join('role', 'user_access.RoleId', 'role.Id')
                ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
                ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
                ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
                ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName', 'sourceUser.FirstName as SourceUserFirstName',
                    'sourceUser.LastName as SourceUserLastName', 'sourceUser.EmailAddress as SourceUserEmailAddress', 'user_association.AssociationType',
                    'destinationUser.FirstName as DestinationUserFirstName', 'destinationUser.LastName as DestinationUserLastName',
                    'destinationUser.EmailAddress as DestinationUserEmailAddress')
                ->where($tableName . '.' . $columnName, $operator, $data)
                ->where('role.CodeName', '=', $roleCode)
//                ->offset($offset)->limit($limit)
                ->skip($offset * $limit)->take($limit)
                ->orderBy($tableName . '.' . $orderBy, 'DESC')
                ->groupBy('user.Id')
                ->get();

            error_log($query);

            return $query;
        }
    }

    static public function UserCountWithSearch
    ($tableName, $operator, $columnName, $data, $keyword, $roleCode)
    {

        if ($roleCode != null && $roleCode != "null") {
            if ($keyword != null && $keyword != "null") {
                error_log('role code and keyword both are not null');
                $query = DB::table($tableName)
                    ->join('user_access', $tableName . '.Id', '=', 'user_access.UserId')
                    ->join('role', 'user_access.RoleId', '=', 'role.Id')
                    ->where($tableName . '.' . $columnName, $operator, $data)
                    ->where('role.CodeName', '=', $roleCode)
                    ->Where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
                    ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
                    ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
                    ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
                    ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
                    ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%')
                    ->count();

                error_log($query);

                return $query;
            } else {
                error_log('role code not null and keyword null');
                $query = DB::table($tableName)
                    ->join('user_access', $tableName . '.Id', '=', 'user_access.UserId')
                    ->join('role', 'user_access.RoleId', '=', 'role.Id')
                    ->where($tableName . '.' . $columnName, $operator, $data)
                    ->where('role.CodeName', '=', $roleCode)
                    ->count();

                error_log($query);

                return $query;
            }
        } else {
            if ($keyword != null && $keyword != "null") {
                error_log('Role NULL and keyword not null');
                return DB::table($tableName)
                    ->where($columnName, $operator, $data)
                    ->Where('FirstName', 'like', '%' . $keyword . '%')
                    ->orWhere('LastName', 'like', '%' . $keyword . '%')
                    ->orWhere('EmailAddress', 'like', '%' . $keyword . '%')
                    ->orWhere('MobileNumber', 'like', '%' . $keyword . '%')
                    ->orWhere('TelephoneNumber', 'like', '%' . $keyword . '%')
                    ->orWhere('FunctionalTitle', 'like', '%' . $keyword . '%')
                    ->count();

            } else {
                error_log('Role NULL and keyword also null');
                return DB::table($tableName)
                    ->where($columnName, $operator, $data)
                    ->count();
            }
        }
    }

    static public function GetSingleUserViaId($id)
    {
        error_log('in model');


        $query = DB::table('user')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
            ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
            ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
            ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName', 'sourceUser.FirstName as SourceUserFirstName',
                'sourceUser.LastName as SourceUserLastName', 'sourceUser.EmailAddress as SourceUserEmailAddress', 'user_association.AssociationType',
                'destinationUser.FirstName as DestinationUserFirstName', 'destinationUser.LastName as DestinationUserLastName',
                'destinationUser.EmailAddress as DestinationUserEmailAddress')
            ->where('user.Id', '=', $id)
            ->where('user.IsActive', '=', true)
            ->get();

        error_log($query);

        return $query;
    }

    static public function GetSingleUserViaIdNewFunction($id)
    {
        error_log('## in model ##');
        error_log('## GetSingleUserViaIdNewFunction ##');
        error_log($id);

        $query = DB::table('user')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
            ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
            ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
            ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName', 'sourceUser.FirstName as SourceUserFirstName',
                'sourceUser.LastName as SourceUserLastName', 'sourceUser.EmailAddress as SourceUserEmailAddress', 'user_association.AssociationType',
                'destinationUser.FirstName as DestinationUserFirstName', 'destinationUser.LastName as DestinationUserLastName',
                'destinationUser.EmailAddress as DestinationUserEmailAddress')
            ->where('user.Id', '=', $id)
            ->first();

        return $query;
    }

    static public function GetPatientViaMobileNum($mobileNum,$patientRoleCode)
    {
        error_log('in GetUserViaMobileNum function - Model');
        error_log($mobileNum);
        error_log($patientRoleCode);

        $query = DB::table('user')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
            ->where('user.MobileNumber', '=', $mobileNum)
            ->where('role.CodeName', '=', $patientRoleCode)
            ->where('user.IsActive', '=', 1)
            ->first();

        return $query;
    }

    static public function isDuplicateEmail($userEmail)
    {
        $isDuplicate = DB::table('user')
            ->select('*')
            ->where('EmailAddress', '=', $userEmail)
            ->where('IsActive', '=', 1)
            ->get();

        return $isDuplicate;
    }

    public static function sendEmail($email, $emailMessage, $url)
    {
        $urlForEmail = url($url);

        Mail::raw($emailMessage, function ($message) use ($email) {
            $message->to($email)->subject("Invitation");
        });

        return true;
    }

    public static function getUserCountViaRoleCode($roleCode)
    {
        return DB::table('user')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->where('role.CodeName', '=', $roleCode)
            ->where('user.IsActive', '=', 1)
            ->count();
    }

    static public function getUserList()
    {
        return DB::table('user')
            ->leftjoin('user_access', 'user_access.UserId', 'user.Id')
            ->leftjoin('role', 'user_access.RoleId', 'role.Id')
            ->leftjoin('user_association', 'user_association.DestinationUserId', 'user.Id')
            ->leftjoin('user as sourceUser', 'user_association.SourceUserId', 'sourceUser.Id')
            ->leftjoin('user as destinationUser', 'user_association.DestinationUserId', 'destinationUser.Id')
            ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName', 'sourceUser.FirstName as SourceUserFirstName',
                'sourceUser.LastName as SourceUserLastName', 'sourceUser.EmailAddress as SourceUserEmailAddress', 'user_association.AssociationType',
                'destinationUser.FirstName as DestinationUserFirstName', 'destinationUser.LastName as DestinationUserLastName',
                'destinationUser.EmailAddress as DestinationUserEmailAddress')
            ->where('user.IsActive', '=', true)
            ->orderBy('user.Id', 'DESC')
            ->get();
    }

    static public function getUserInvitationLink($offset, $limit, $keyword)
    {
        if ($keyword != null && $keyword != "null") {
            return DB::table('account_invitation')
                ->leftjoin('user', 'account_invitation.ByUserId', 'user.Id')
                ->leftjoin('user_access', 'user_access.UserId', 'user.Id')
                ->leftjoin('role', 'user_access.RoleId', 'role.Id')
                ->select('user.EmailAddress as ByUserEmail', 'user.FirstName as ByUserFirstName', 'user.LastName as ByUserLastName',
                    'account_invitation.ToEmailAddress', 'account_invitation.ToMobileNumber', 'account_invitation.Status_')
                ->where('account_invitation.IsActive', '=', true)
                ->Where('account_invitation.ToEmailAddress', 'like', '%' . $keyword . '%')
                ->orWhere('account_invitation.ToMobileNumber', 'like', '%' . $keyword . '%')
                ->orWhere('account_invitation.Status_', 'like', '%' . $keyword . '%')
                ->orWhere('user.FirstName', 'like', '%' . $keyword . '%')
                ->orWhere('user.LastName', 'like', '%' . $keyword . '%')
                ->orWhere('user.EmailAddress', 'like', '%' . $keyword . '%')
//                ->offset($offset)->limit($limit)
                ->skip($offset * $limit)->take($limit)
                ->orderBy('account_invitation.Id', 'DESC')
                ->get();
        } else {
            return DB::table('account_invitation')
                ->leftjoin('user', 'account_invitation.ByUserId', 'user.Id')
                ->leftjoin('user_access', 'user_access.UserId', 'user.Id')
                ->leftjoin('role', 'user_access.RoleId', 'role.Id')
                ->select('user.EmailAddress as ByUserEmail', 'user.FirstName as ByUserFirstName', 'user.LastName as ByUserLastName',
                    'account_invitation.ToEmailAddress', 'account_invitation.ToMobileNumber', 'account_invitation.Status_')
                ->where('account_invitation.IsActive', '=', true)
//                ->offset($offset)->limit($limit)
                ->skip($offset * $limit)->take($limit)
                ->orderBy('account_invitation.Id', 'DESC')
                ->get();
        }
    }

    static public function getUserInvitationLinkCount($keyword)
    {
        if ($keyword != null && $keyword != "null") {
            return DB::table('account_invitation')
                ->leftjoin('user', 'account_invitation.ByUserId', 'user.Id')
                ->leftjoin('user_access', 'user_access.UserId', 'user.Id')
                ->leftjoin('role', 'user_access.RoleId', 'role.Id')
                ->where('account_invitation.IsActive', '=', true)
                ->Where('account_invitation.ToEmailAddress', 'like', '%' . $keyword . '%')
                ->orWhere('account_invitation.ToMobileNumber', 'like', '%' . $keyword . '%')
                ->orWhere('account_invitation.Status_', 'like', '%' . $keyword . '%')
                ->orWhere('user.FirstName', 'like', '%' . $keyword . '%')
                ->orWhere('user.LastName', 'like', '%' . $keyword . '%')
                ->orWhere('user.EmailAddress', 'like', '%' . $keyword . '%')
                ->count();
        } else {
            return DB::table('account_invitation')
                ->leftjoin('user', 'account_invitation.ByUserId', 'user.Id')
                ->leftjoin('user_access', 'user_access.UserId', 'user.Id')
                ->leftjoin('role', 'user_access.RoleId', 'role.Id')
                ->where('account_invitation.IsActive', '=', true)
                ->count();
        }
    }

    static public function getPermissionViaRoleId($roleId)
    {
        return DB::table('role_permission')
            ->leftJoin('permission', 'permission.Id', '=', 'role_permission.PermissionId')
            ->select('permission.Id', 'permission.Name as PermissionName', 'permission.CodeName as PermissionCodeName')
            ->where('role_permission.RoleId', '=', $roleId)
            ->where('role_permission.IsActive', '=', true)
            ->get();
    }

    static public function getRoleViaRoleCode($roleCodeName)
    {
        return DB::table('role')
            ->select('role.*')
            ->where('CodeName', '=', $roleCodeName)
            ->where('IsActive', '=', true)
            ->get();
    }

    static public function GetUserRoleViaUserId($userId)
    {
        return DB::table('user_access')
            ->select('user_access.RoleId')
            ->where('user_access.UserId', '=', $userId)
            ->get();
    }

    static public function getDestinationUserIdViaLoggedInUserIdAndAssociationType($userId, $associationType)
    {
        return DB::table('user_association')
            ->select('DestinationUserId')
            ->where('SourceUserId', '=', $userId)
            ->where('AssociationType', '=', $associationType)
            ->where('IsActive', '=', true)
            ->get();
    }

    static public function getAssociatedPatientViaDoctorId($userId, $associationType, $patientId)
    {
        return DB::table('user_association')
            ->where('SourceUserId', '=', $userId)
            ->where('AssociationType', '=', $associationType)
            ->where('DestinationUserId', '=', $patientId)
            ->where('IsActive', '=', true)
            ->get();
    }

    static public function getSourceUserIdViaLoggedInUserId($userId)
    {
        return DB::table('user_association')
            ->select('SourceUserId')
            ->where('DestinationUserId', '=', $userId)
            ->where('IsActive', '=', true)
            ->get();
    }

    static public function getAssociatedPatientsUserId($doctorIds, $associationType)
    {
        return DB::table('user_association')
            ->select('DestinationUserId')
            ->whereIn('SourceUserId', $doctorIds)
            ->where('AssociationType', '=', $associationType)
            ->where('IsActive', '=', true)
            ->get();
    }

    static public function getAssociatedPatientWithRespectToMultipleDoctorIds($doctorIds, $associationType, $patientId)
    {
        return DB::table('user_association')
            ->select('DestinationUserId')
            ->whereIn('SourceUserId', $doctorIds)
            ->where('AssociationType', '=', $associationType)
            ->where('DestinationUserId', '=', $patientId)
            ->where('IsActive', '=', true)
            ->get();
    }

    static public function getSourceIdViaLoggedInUserIdAndAssociationType($userId, $associationType)
    {
        return DB::table('user_association')
            ->select('SourceUserId')
            ->where('DestinationUserId', '=', $userId)
            ->where('AssociationType', '=', $associationType)
            ->where('IsActive', '=', true)
            ->get();
    }

    static public function deleteAssociatedFacilitators($doctorId, $associationType)
    {
        $result = DB::table('user_association')
            ->where('SourceUserId', '=', $doctorId)
            ->where('AssociationType', '=', $associationType)
            ->delete();
        return $result;
    }

    static public function getMultipleUsers($userIds)
    {
        $result = DB::table('user')
            ->select('user.EmailAddress', 'user.Id', 'user.FirstName', 'user.LastName', 'user.MobileNumber')
            ->whereIn('Id', $userIds)
            ->where('IsActive', '=', true)
            ->get();
        return $result;
    }

    static public function getUserViaId($userId)
    {
        $result = DB::table('user')
            ->select('user.EmailAddress')
            ->where('Id', $userId)
            ->get();
        return $result;
    }

    static public function CheckAssociatedPatientAndFacilitator($doctorId, $associationType, $userId)
    {
        $result = DB::table('user_association')
            ->where('SourceUserId', '=', $doctorId)
            ->where('DestinationUserId', '=', $userId)
            ->where('AssociationType', '=', $associationType)
            ->first();

        return $result;
    }

    static public function GetUserViaRoleCode($roleCode)
    {
        $query = DB::table('user')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->select('user.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
            ->where('role.CodeName', '=', $roleCode)
            ->where('user.IsActive', '=', true)
            ->orderBy('user.Id', 'DESC')
            ->get();

        error_log($query);

        return $query;
    }
}
