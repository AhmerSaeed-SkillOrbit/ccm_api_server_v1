<?php

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HelperModel;

use Mail;
use mysql_xdevapi\Exception;

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
    ($tableName, $operator, $columnName, $data, $offset, $limit, $orderBy, $keyword, $destinationUserId, $roleCode)
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
//                ->where($tableName . '.' . $columnName, $operator, $data)
//                ->whereIn('user.Id', $destinationUserId)
//                ->Where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
//                ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
//                ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
//                ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
//                ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
//                ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%')
//                ->orderBy($tableName . '.' . $orderBy, 'DESC')
//                ->groupBy('user.Id')
////                ->offset($offset)->limit($limit)
//                ->skip($offset * $limit)->take($limit)
//                ->groupBy('user.Id')
//                ->get();

                ->where($tableName . '.' . $columnName, $operator, $data)
                ->where('role.CodeName', '=', $roleCode)
                ->whereIn('user.Id', $destinationUserId)
                ->where(function ($query) use ($tableName, $keyword) {
                    $query->where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
                        ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
                        ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
                        ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
                        ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
                        ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%');
                })
                ->orderBy($tableName . '.' . $orderBy, 'DESC')
                ->groupBy('user.Id')
                ->skip($offset * $limit)->take($limit)
                ->get();
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
                ->where('role.CodeName', '=', $roleCode)
                ->whereIn('user.Id', $destinationUserId)
                ->groupBy('user.Id')
                ->orderBy($tableName . '.' . $orderBy, 'DESC')
                ->skip($offset * $limit)->take($limit)
                ->get();

            return $query;
        }
    }

    static public function FetchUserFacilitatorListForDoctorWithSearchCount
    ($tableName, $operator, $columnName, $data, $keyword, $destinationUserId, $roleCode)
    {
        error_log('in model ');
        if ($keyword != null && $keyword != "null") {
            error_log('Keyword NOT NULL');
            $query = DB::table('user')
                ->join('user_access', 'user_access.UserId', 'user.Id')
                ->join('role', 'user_access.RoleId', 'role.Id')
                ->where($tableName . '.' . $columnName, $operator, $data)
                ->where('role.CodeName', '=', $roleCode)
                ->whereIn('user.Id', $destinationUserId)
                ->where(function ($query) use ($tableName, $keyword) {
                    $query->where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
                        ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
                        ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
                        ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
                        ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
                        ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%');
                })
                ->count();
            return $query;
        } else {
            error_log('keyword is NULL ' . count($destinationUserId));

            $query = DB::table('user')
                ->join('user_access', 'user_access.UserId', 'user.Id')
                ->join('role', 'user_access.RoleId', 'role.Id')
                ->where($tableName . '.' . $columnName, $operator, $data)
                ->where('role.CodeName', '=', $roleCode)
                ->whereIn('user.Id', $destinationUserId)
                ->count();
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
                    ->where('role.CodeName', '=', $roleCode)
                    ->where(function ($query) use ($tableName, $keyword) {
                        $query->where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%');
                    })
//                    ->where($tableName . '.' . $columnName, $operator, $data)
//                    ->Where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
//                    ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
//                    ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
//                    ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
//                    ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
//                    ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%')
                    ->skip($offset * $limit)->take($limit)
                    ->orderBy($tableName . '.' . $orderBy, 'DESC')
                    ->groupBy('user.Id')
                    ->get();


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
                    ->skip($offset * $limit)->take($limit)
                    ->orderBy($tableName . '.' . $orderBy, 'DESC')
                    ->groupBy('user.Id')
                    ->get();


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
                    ->where($tableName . '.' . $columnName, $operator, $data)
                    ->where(function ($query) use ($tableName, $keyword) {
                        $query->where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%');
                    })
//                    ->Where('FirstName', 'like', '%' . $keyword . '%')
//                    ->orWhere('LastName', 'like', '%' . $keyword . '%')
//                    ->orWhere('EmailAddress', 'like', '%' . $keyword . '%')
//                    ->orWhere('MobileNumber', 'like', '%' . $keyword . '%')
//                    ->orWhere('TelephoneNumber', 'like', '%' . $keyword . '%')
//                    ->orWhere('FunctionalTitle', 'like', '%' . $keyword . '%')
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
                    ->where(function ($query) use ($tableName, $keyword) {
                        $query->where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%');
                    })
//                    ->Where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
//                    ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
//                    ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
//                    ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
//                    ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
//                    ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%')
                    ->count();
                return $query;
            } else {
                error_log('role code not null and keyword null');
                $query = DB::table($tableName)
                    ->join('user_access', $tableName . '.Id', '=', 'user_access.UserId')
                    ->join('role', 'user_access.RoleId', '=', 'role.Id')
                    ->where($tableName . '.' . $columnName, $operator, $data)
                    ->where('role.CodeName', '=', $roleCode)
                    ->count();
                return $query;
            }
        } else {
            if ($keyword != null && $keyword != "null") {
                error_log('Role NULL and keyword not null');
                return DB::table($tableName)
                    ->where($columnName, $operator, $data)
                    ->where(function ($query) use ($tableName, $keyword) {
                        $query->where($tableName . '.FirstName', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.LastName', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.EmailAddress', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.MobileNumber', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.TelephoneNumber', 'like', '%' . $keyword . '%')
                            ->orWhere($tableName . '.FunctionalTitle', 'like', '%' . $keyword . '%');
                    })
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
            ->leftjoin('patient_type as ps', 'ps.Id', 'user.PatientTypeId')
            ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName', 'sourceUser.FirstName as SourceUserFirstName',
                'sourceUser.LastName as SourceUserLastName', 'sourceUser.EmailAddress as SourceUserEmailAddress', 'user_association.AssociationType',
                'destinationUser.FirstName as DestinationUserFirstName', 'destinationUser.LastName as DestinationUserLastName',
                'destinationUser.EmailAddress as DestinationUserEmailAddress', 'ps.Id as PatientTypeId', 'ps.Name', 'ps.Code')
            ->where('user.Id', '=', $id)
            ->first();

        return $query;
    }

    static public function GetPatientViaMobileNum($mobileNum, $patientRoleCode)
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

    static public function GetPatientViaEmail($email, $patientRoleCode)
    {
        error_log('in GetUserViaMobileNum function - Model');
        error_log($email);
        error_log($patientRoleCode);

        $query = DB::table('user')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->select('user.*', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
            ->where('user.EmailAddress', '=', $email)
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
        try {
            $urlForEmail = url($url);

            Mail::raw($emailMessage, function ($message) use ($email) {
                $message->from("no-reply@connectcareplus.com")->to($email)->subject("CCM Email");
            });
            return true;
        } catch (Exception $ex) {
            error_log("Sending Mail Exception");
            return false;
        }
    }

    public static function sendEmailWithTemplate($toEmail, $emailSubject, $emailContent)
    {
        try {
            error_log("Sending Mail With Template New");

            Mail::send([], [], function ($message) use ($toEmail, $emailSubject, $emailContent) {
                $message->from("no-reply@connectcareplus.com")
                    ->to($toEmail)
                    ->subject($emailSubject)
                    ->setBody($emailContent, 'text/html');


                //OLD Method
//            Mail::raw($emailContent, function ($message) use ($toEmail,$emailSubject,$emailContent) {
//                $message->from("no-reply@connectcareplus.com")
//                    ->to($toEmail)
//                    ->subject($emailSubject)
//                    ->setBody('text/html');
            });
            return true;
        } catch (Exception $ex) {
            error_log("Sending Mail Exception");
            return false;
        }
    }

    /*
     * $toEmail = array
     * $emailSubject = string
     * $emailBody = array
     *
     */
    public static function sendEmailWithTemplateThree($toEmail, $emailSubject, $emailBody)
    {
        $emailContent = "<!DOCTYPE html>" .
            "<html>" .
            "<head>" .
            "<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0'>" .
            "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>" .
            "<title>" . $emailSubject . "</title>" .
            "<link rel='stylesheet' href='http://businessdirectory360.com/assets/email_assets/geomanist-fonts.css'>" .
            "</head>" .
            "<body style=\"-webkit-font-smoothing:antialiased; font-family: \'Geomanist-Light\',\'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif; -webkit-text-size-adjust:none; word-wrap:break-word; background-color:#ffffff; margin:0; padding:0;\">" .
//        Main Template
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\"> " .
            "<tr>" .
            "<td align=\"center\" bgcolor=\"#ffffff\">" .
//        '<!-- Background -->"+
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">" .
//        <!-- Wrapper -->
//        <!-- BODY -->
            "<tr>" .
            "<td width=\"600\" style=\"padding-top:30px;\">" .
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" .
//        <!-- Header -->
            "<tr>" .
            "<td style='background: yellow;' width=\"600\" style='padding-top:0px;padding-left:30px;padding-right:30px;padding-bottom:0px;background-color:#ffffff;border-bottom:1px solid #e5e8e5;'>" .
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" .
            "<tr>" .
            "<td width=\"540\" valign=\"middle\" style=\"padding-top:15px;padding-bottom:15px;\">" .
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" .
            "<tr>" .
            "<td width=\"0\" align=\"left\" valign=\"middle\" style=\"text-align:left;font-size: 20px;font-weight: 900;color: #000000;\">" .
            "</td>" .
            "<td width=\"0\" align=\"left\" valign=\"middle\" style=\"text-align:left;font-size: 25px;font-weight: 900;color: #000000;\">" .
            "<p style='margin-left: 200px;'>Chronic Care Management</p>" .
            "</td>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
//    <!-- Header -->
//        <!-- Content -->
            "<tr>" .
            "<td width='600' style='padding-top:0px;padding-left:30px;padding-right:30px;padding-bottom:0px;background-color:#ffffff;'>" .
            "<table cellspacing='0' cellpadding='0' border='0' style='border-collapse:collapse;'>" .
            "<tr>" .
            "<td width=\"540\" valign=\"middle\" align=\"left\" style=\"padding-top:20px;padding-bottom:0px;text-align:left;font-size:16px;color:#2c2d30;font-family: \'Geomanist-Regular\',\'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;\">" .
            $emailBody .
            "</td>" .
            "</tr>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
            "<tr>" .
            "<td width=\"540\" valign=\"middle\" align=\"center\" style=\"color: #6e6e6e; padding-top: 15px; padding-bottom: 10px; text-align: center !important;\">" .
            "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"table-layout: fixed;margin-left: -40px;\">" .
            "<tr>" .
            "<td width=\"270\" valign=\"middle\" align=\"right\" style=\"text-align: right !important;\">" .
            "<img width=\"0\" height=\"0\" alt=\"Chronic Care Management\" src=\"http://businessdirectory360.com/assets/images/logo.png\" style=\"border-style:none; width: 300px; height: 55px;margin-left: -50px;\">" .
            "</td>" .
            "<td width=\"270\" valign=\"middle\" align=\"left\" style=\"text-align: left !important;\">" .
            "<img width=\"0\" height=\"0\" alt=\"Business Service Solution\" src=\"http://businessdirectory360.com/assets/images/BSSVector1.png\" style=\"border-style:none; width: 178px; height: 55px; margin-left: 150px;\">" .
            "</td>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
            "<tr>" .
            "<td width=\"600\" style=\"padding-top:0px;padding-left:30px;padding-right:30px;padding-bottom:0px;background-color:#ffffff;\">" .
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" .
            "<tr>" .
            "<td width=\"540\" valign=\"middle\" align=\"left\" style=\"padding-top:20px;padding-bottom:40px;text-align:left;font-size:16px;color:#151515;font-family: \'Geomanist-Regular\',\'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;\">" .
            "<br>This is a mandatory service communication<br>This message was sent from an unmonitored e-mail address. Please do not reply to this message<br><br>" .
            "Care Connect Plus<br><br>" .
            "One Microsoft Way<br>" .
            "Redmond, WA<br>" .
            "98052-6399 USA<br><br>" .
            "</td>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
//        "<!-- Content -->"+
            "</table>" .
            "</td>" .
            "</tr>" .
//        "<!-- Footer -->" .
//        "<!-- App link section -->"+
            "<tr>" .
            "<td width=\"540\" valign=\"middle\" align=\"center\" style=\"color: #6e6e6e; padding-top: 15px; text-align: center !important;font-family:helvetica, arial, sans-serif;font-family: \'Geomanist-Book\', \'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;font-size:16px;\">" .
            "</td>" .
            "</tr>" .
            "<td style=\"padding-top: 10px; padding-bottom: 20px; text-align: center;font-family:helvetica, arial, sans-serif;font-size:15px;font-family: \'Geomanist-Regular\', \'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;font-size:13px;color: #666666 !important; line-height: 16px;\">" .
            "</td>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
            "</table>" .
//        "<!-- Background -->
            "</td>" .
            "</tr>" .
            "</table>" .
//        "<!-- Main Template -->' +
            "</body>" .
            "</html>";

        try {
            error_log("Sending Mail With Template");

            Mail::send([], [], function ($message) use ($toEmail, $emailSubject, $emailContent) {
                $message->from(getenv("FROM_ADDRESS"))
                    ->to($toEmail)
                    ->subject($emailSubject)
                    ->setBody($emailContent, 'text/html');
            });
            error_log("Email Sent Successfully along with Template");
            return true;
        } catch (Exception $ex) {
            error_log("Sending Mail Exception");
            return false;
            error_log("Email Failed to sent along with Template");
        }
    }

    /*
    * $toEmail = array
    * $emailSubject = string
    * $emailBody = array
    *
    */
    public static function sendEmailWithTemplateTwo($toEmail, $emailSubject, $emailBody)
    {
        $emailContent = "<!DOCTYPE html>" .
            "<html>" .
            "<head>" .
            "<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0'>" .
            "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>" .
            "<title>" . $emailSubject . "</title>" .
            "<link rel='stylesheet' href='http://businessdirectory360.com/assets/email_assets/geomanist-fonts.css'>" .
            "</head>" .
            "<body style=\"-webkit-font-smoothing:antialiased; font-family: \'Geomanist-Light\',\'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif; -webkit-text-size-adjust:none; word-wrap:break-word; background-color:#ffffff; margin:0; padding:0;\">" .
//        Main Template
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\"> " .
            "<tr>" .
            "<td align=\"center\" bgcolor=\"#ffffff\">" .
//        '<!-- Background -->"+
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">" .
//        <!-- Wrapper -->
//        <!-- BODY -->
            "<tr>" .
            "<td width=\"600\" style=\"padding-top:30px;\">" .
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" .
//        <!-- Header -->
            "<tr>" .
            "<td width=\"600\" style='padding-top:0px;padding-left:30px;padding-right:30px;padding-bottom:0px;background-color:#ffffff;border-bottom:1px solid #e5e8e5;'>" .
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" .
            "<tr>" .
            "<td width=\"540\" valign=\"middle\" style=\"padding-top:15px;padding-bottom:15px;\">" .
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" .
            "<tr>" .
            "<td width=\"0\" align=\"left\" valign=\"middle\" style=\"text-align:left;font-size: 20px;font-weight: 900;color: #000000;\">" .
            "<img width=\"0\" height=\"22\" alt=\"Chronic Care Management\" src=\"http://businessdirectory360.com/assets/images/logo.png\" style=\"border-style:none; width: 178px; height: 55px;\">" .
            "</td>" .
            "<td width=\"0\" align=\"right\" valign=\"middle\" style=\"text-align:left;font-size: 20px;font-weight: 900;color: #000000;\">" .
            "<img width=\"0\" height=\"22\" alt=\"Chronic Care Management\" src=\"http://businessdirectory360.com/assets/images/BSSVector1.png\" style=\"border-style:none; width: 178px; height: 150px; margin-left: 150px;\">" .
            "</td>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
//    <!-- Header -->
//        <!-- Content -->
            "<tr>" .
            "<td width='600' style='padding-top:0px;padding-left:30px;padding-right:30px;padding-bottom:0px;background-color:#ffffff;'>" .
            "<table cellspacing='0' cellpadding='0' border='0' style='border-collapse:collapse;'>" .
            "<tr>" .
            "<td width=\"540\" valign=\"middle\" align=\"left\" style=\"padding-top:20px;padding-bottom:0px;text-align:left;font-size:16px;color:#2c2d30;font-family: \'Geomanist-Regular\',\'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;\">" .
            $emailBody .
            "</td>" .
            "</tr>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
            "<tr>" .
            "<td width=\"600\" style=\"padding-top:0px;padding-left:30px;padding-right:30px;padding-bottom:0px;background-color:#ffffff;\">" .
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" .
            "<tr>" .
            "<td width=\"540\" valign=\"middle\" align=\"left\" style=\"padding-top:20px;padding-bottom:40px;text-align:left;font-size:16px;color:#151515;font-family: \'Geomanist-Regular\',\'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;\">" .
            "<br><b>This is a mandatory service communication<br>This message was sent from an unmonitored e-mail address. Please do not reply to this message</b><br><br><b>Privacy | Legal</b><br><br>" .
            "<b>Care Connect Plus</b><br><br>" .
            "<b>One Microsoft Way</b><br>" .
            "<b>Redmond, WA</b><br>" .
            "<b>98052-6399 USA</b><br><br>" .
            "</td>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
//        "<!-- Content -->"+
            "</table>" .
            "</td>" .
            "</tr>" .
//        "<!-- Footer -->" .
//        "<!-- App link section -->"+
            "<tr>" .
            "<td width=\"540\" valign=\"middle\" align=\"center\" style=\"color: #6e6e6e; padding-top: 15px; text-align: center !important;font-family:helvetica, arial, sans-serif;font-family: \'Geomanist-Book\', \'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;font-size:16px;\">" .
            "</td>" .
            "</tr>" .
            "<tr>" .
            "<td width=\"540\" valign=\"middle\" align=\"center\" style=\"color: #6e6e6e; padding-top: 15px; padding-bottom: 10px; text-align: center !important;\">" .
            "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"table-layout: fixed;\">" .
            "<tr>" .
            "<td width=\"270\" valign=\"middle\" align=\"right\" style=\"text-align: right !important;\">" .
            "</td>" .
            "<td width=\"270\" valign=\"middle\" align=\"left\" style=\"text-align: left !important;\">" .
            "</td>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
            "<td style=\"padding-top: 10px; padding-bottom: 20px; text-align: center;font-family:helvetica, arial, sans-serif;font-size:15px;font-family: \'Geomanist-Regular\', \'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;font-size:13px;color: #666666 !important; line-height: 16px;\">" .
            "</td>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
            "</table>" .
            "</td>" .
            "</tr>" .
            "</table>" .
//        "<!-- Background -->
            "</td>" .
            "</tr>" .
            "</table>" .
//        "<!-- Main Template -->' +
            "</body>" .
            "</html>";

        try {
            error_log("Sending Mail With Template");

            Mail::send([], [], function ($message) use ($toEmail, $emailSubject, $emailContent) {
                $message->from(getenv("FROM_ADDRESS"))
                    ->to($toEmail)
                    ->subject($emailSubject)
                    ->setBody($emailContent, 'text/html');
            });
            error_log("Email Sent Successfully along with Template");
            return true;
        } catch (Exception $ex) {
            error_log("Sending Mail Exception");
            return false;
            error_log("Email Failed to sent along with Template");
        }
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
        $tableName = 'account_invitation';
        if ($keyword != null && $keyword != "null") {
            return DB::table('account_invitation')
                ->leftjoin('user', 'account_invitation.ByUserId', 'user.Id')
                ->leftjoin('user_access', 'user_access.UserId', 'user.Id')
                ->leftjoin('role', 'user_access.RoleId', 'role.Id')
                ->select('user.EmailAddress as ByUserEmail', 'user.FirstName as ByUserFirstName', 'user.LastName as ByUserLastName',
                    'account_invitation.ToEmailAddress', 'account_invitation.ToMobileNumber', 'account_invitation.Status_')
                ->where('account_invitation.IsActive', '=', true)
                ->where(function ($query) use ($tableName, $keyword) {
                    $query->Where('account_invitation.ToEmailAddress', 'like', '%' . $keyword . '%')
                        ->orWhere('account_invitation.ToMobileNumber', 'like', '%' . $keyword . '%')
                        ->orWhere('account_invitation.Status_', 'like', '%' . $keyword . '%')
                        ->orWhere('user.FirstName', 'like', '%' . $keyword . '%')
                        ->orWhere('user.LastName', 'like', '%' . $keyword . '%')
                        ->orWhere('user.EmailAddress', 'like', '%' . $keyword . '%');
                })
//                ->Where('account_invitation.ToEmailAddress', 'like', '%' . $keyword . '%')
//                ->orWhere('account_invitation.ToMobileNumber', 'like', '%' . $keyword . '%')
//                ->orWhere('account_invitation.Status_', 'like', '%' . $keyword . '%')
//                ->orWhere('user.FirstName', 'like', '%' . $keyword . '%')
//                ->orWhere('user.LastName', 'like', '%' . $keyword . '%')
//                ->orWhere('user.EmailAddress', 'like', '%' . $keyword . '%')
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
        $tableName = 'account_invitation';
        if ($keyword != null && $keyword != "null") {
            return DB::table('account_invitation')
                ->leftjoin('user', 'account_invitation.ByUserId', 'user.Id')
                ->leftjoin('user_access', 'user_access.UserId', 'user.Id')
                ->leftjoin('role', 'user_access.RoleId', 'role.Id')
                ->where('account_invitation.IsActive', '=', true)
                ->where(function ($query) use ($tableName, $keyword) {
                    $query->Where('account_invitation.ToEmailAddress', 'like', '%' . $keyword . '%')
                        ->orWhere('account_invitation.ToMobileNumber', 'like', '%' . $keyword . '%')
                        ->orWhere('account_invitation.Status_', 'like', '%' . $keyword . '%')
                        ->orWhere('user.FirstName', 'like', '%' . $keyword . '%')
                        ->orWhere('user.LastName', 'like', '%' . $keyword . '%')
                        ->orWhere('user.EmailAddress', 'like', '%' . $keyword . '%');
                })
//                ->Where('account_invitation.ToEmailAddress', 'like', '%' . $keyword . '%')
//                ->orWhere('account_invitation.ToMobileNumber', 'like', '%' . $keyword . '%')
//                ->orWhere('account_invitation.Status_', 'like', '%' . $keyword . '%')
//                ->orWhere('user.FirstName', 'like', '%' . $keyword . '%')
//                ->orWhere('user.LastName', 'like', '%' . $keyword . '%')
//                ->orWhere('user.EmailAddress', 'like', '%' . $keyword . '%')
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

    static public function getUserInvitationListViaDoctorId($doctorId, $offset, $limit, $keyword, $belongTo)
    {
        $tableName = 'account_invitation';
        if ($keyword != null && $keyword != "null") {
            return DB::table('account_invitation')
                ->where('account_invitation.IsActive', '=', true)
                ->where('account_invitation.ByUserId', '=', $doctorId)
                ->where('account_invitation.Status_', '=', env("INVITATION_PENDING"))
                ->where('account_invitation.BelongTo', '=', $belongTo)
                ->where(function ($query) use ($tableName, $keyword) {
                    $query->Where('account_invitation.ToEmailAddress', 'like', '%' . $keyword . '%')
                        ->orWhere('account_invitation.ToMobileNumber', 'like', '%' . $keyword . '%');
                })
//                ->Where('account_invitation.ToEmailAddress', 'like', '%' . $keyword . '%')
//                ->orWhere('account_invitation.ToMobileNumber', 'like', '%' . $keyword . '%')
//                ->orWhere('account_invitation.Status_', 'like', '%' . $keyword . '%')
                ->skip($offset * $limit)->take($limit)
                ->orderBy('account_invitation.Id', 'DESC')
                ->get();
        } else {
            return DB::table('account_invitation')
                ->where('account_invitation.IsActive', '=', true)
                ->where('account_invitation.ByUserId', '=', $doctorId)
                ->where('account_invitation.Status_', '=', env("INVITATION_PENDING"))
                ->where('account_invitation.BelongTo', '=', $belongTo)
                ->skip($offset * $limit)->take($limit)
                ->orderBy('account_invitation.Id', 'DESC')
                ->get();
        }
    }

    static public function getUserInvitationListCountViaDoctorId($doctorId, $keyword, $belongTo)
    {
        $tableName = 'account_invitation';
        if ($keyword != null && $keyword != "null") {
            return DB::table('account_invitation')
                ->where('account_invitation.IsActive', '=', true)
                ->where('account_invitation.ByUserId', '=', $doctorId)
                ->where('account_invitation.Status_', '=', env("INVITATION_PENDING"))
                ->where('account_invitation.BelongTo', '=', $belongTo)
                ->where(function ($query) use ($tableName, $keyword) {
                    $query->Where('account_invitation.ToEmailAddress', 'like', '%' . $keyword . '%')
                        ->orWhere('account_invitation.ToMobileNumber', 'like', '%' . $keyword . '%');
                })
//                ->Where('account_invitation.ToEmailAddress', 'like', '%' . $keyword . '%')
//                ->orWhere('account_invitation.ToMobileNumber', 'like', '%' . $keyword . '%')
//                ->orWhere('account_invitation.Status_', 'like', '%' . $keyword . '%')
                ->count();
        } else {
            return DB::table('account_invitation')
                ->leftjoin('user', 'account_invitation.ByUserId', 'user.Id')
                ->leftjoin('user_access', 'user_access.UserId', 'user.Id')
                ->leftjoin('role', 'user_access.RoleId', 'role.Id')
                ->where('account_invitation.IsActive', '=', true)
                ->where('account_invitation.ByUserId', '=', $doctorId)
                ->where('account_invitation.Status_', '=', env("INVITATION_PENDING"))
                ->where('account_invitation.BelongTo', '=', $belongTo)
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
        error_log('$associationType ' . $associationType);

        $query = DB::table('user_association')
            ->select('DestinationUserId')
            ->where('SourceUserId', '=', $userId)
            ->where('AssociationType', '=', $associationType)
            ->where('IsActive', '=', true)
            ->get();

        return $query;
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
            ->select('user.EmailAddress', 'user.Id', 'user.FirstName', 'user.LastName', 'user.MobileNumber', 'user.CountryPhoneCode')
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
        error_log("Here 2");
        error_log($roleCode);

        $query = DB::table('user')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->select('user.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
            ->where('role.CodeName', '=', $roleCode)
            ->where('user.IsActive', '=', true)
            ->orderBy('user.Id', 'DESC')
            ->get();
        return $query;
    }

    static public function getPatientLastUniqueId()
    {
        error_log('in model, fetching last ticket number');

        $query = DB::table("user")
            ->select('PatientUniqueId')
            ->where("IsActive", "=", true)
            ->where("PatientUniqueId", "!=", 0)
            ->orderBy('Id', 'desc')
            ->first();

        return $query;
    }

    static public function GetRoleNameViaUserId($userId)
    {
        error_log("userId");
        error_log($userId);

        return DB::table('user')
            ->select('user.Id', 'user.FirstName', 'user.LastName', 'user.EmailAddress', 'role.Name', 'role.CodeName')
            ->leftjoin('user_access', 'user_access.UserId', '=', 'user.Id')
            ->leftjoin('role', 'role.Id', '=', 'user_access.RoleId')
            ->where('user.Id', '=', $userId)
            ->get();
    }

    static public function GetUserCountCountViaRoleCode($roleCode)
    {
        return DB::table('user')
            ->select('user.Id', 'user.EmailAddress', 'role.Name', 'role.CodeName')
            ->leftjoin('user_access', 'user_access.UserId', '=', 'user.Id')
            ->leftjoin('role', 'role.Id', '=', 'user_access.RoleId')
            ->where('role.CodeName', '=', $roleCode)
            ->where('user.IsActive', '=', 1)
            ->count();
    }
}
