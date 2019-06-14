<?php

namespace App\Http\Controllers;

use App\Models\CcmModel;
use App\User;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Exception;
use View;
use App\Models\UserModel;
use App\Models\GenericModel;
use App\Models\HelperModel;
use App\Models\DocumentUploadModel;
use App\Models\ForumModel;
use App\Models\TicketModel;
use App\Models\ReportModel;
use App\Models\LoginModel;
use App\Models\DoctorScheduleModel;
use Config;
use Carbon\Carbon;
use Excel;

class UserController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        //    Input::merge(array_map('trim', Input::except('selectedRoles')));
        //   $validationRules = UserController::getValidateRules();
        //  $validator = Validator::make($request->all(), $validationRules);

        $redirectUserForm = url('/user/add/0');
        $redirectUser = url('/');

        //      if ($validator->fails())
        //           return redirect($redirectUserForm)->withErrors($validator)->withInput(Input::all());


        $isInserted = UserModel::addUser();
        //   if ($isInserted == 'unmatchPassword')
        //        return redirect($redirectUserForm)->withErrors(['confirm password must match the password']);
        if ($isInserted == 'duplicate')
            return redirect($redirectUserForm)->withErrors(['Duplication Error! This First Name and Last Name is already exist'])->withInput(Input::all());
        else if ($isInserted == 'success')
            return redirect($redirectUser)->with(['success' => Config::get('settings.form_save_success_message')]);
        else
            return redirect($redirectUser)->withErrors([Config::get('settings.form_save_failed_message')]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function storePatient(Request $request)
    {

        $redirectUserForm = url('/admin/home');
        $redirectUser = url('/admin/home');
        $isInserted = UserModel::addPatient();
        //   if ($isInserted == 'unmatchPassword')addPatient
        //        return redirect($redirectUserForm)->withErrors(['confirm password must match the password']);
        if ($isInserted == 'duplicate')
            return redirect($redirectUserForm)->withErrors(['Duplication Error! This First Name and Last Name is already exist'])->withInput(Input::all());
        else if ($isInserted == 'success')
            return redirect($redirectUser)->with(['success' => Config::get('settings.form_save_success_message')]);
        else
            return redirect($redirectUser)->withErrors([Config::get('settings.form_save_failed_message')]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //
        Input::merge(array_map('trim', Input::except('selectedRoles')));
        $validationRules = UserController::getValidateRulesForUpdate();
        $validator = Validator::make($request->all(), $validationRules);
        $id = $request->input('userID');

        $redirectUserForm = url('/user_form/update/' . $id);
        $redirectUser = url('/user');

        if ($validator->fails())
            return redirect($redirectUserForm)->withErrors($validator)->withInput(Input::all());


        $isUpdated = UserModel::updateUser($request);
        if ($isUpdated == 'duplicate')
            return redirect(url('/user_form/update/' . $id))->withErrors(['Duplication Error! This First Name and Last Name is already exist']);
        else if ($isUpdated == 'success' && HelperModel::getUserSessionID() != $id)
            return redirect($redirectUser)->with(['success' => Config::get('settings.form_update_success_message ')]);
        else if ($isUpdated == 'success' && HelperModel::getUserSessionID() == $id)
            return redirect($redirectUser)->with(['success' => Config::get('settings.form_update_success_message ') . '.Login again to see changes']);
        else
            return redirect($redirectUser)->withErrors([Config::get('settings.form_update_failed_message')]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $genericModel1 = new GenericModel;
        $row1 = $genericModel1->deleteGeneric('userrole', 'UserID', $id);
        $genericModel = new GenericModel;
        $row = $genericModel->deleteGeneric('user', 'UserID', $id);

        if ($row > 0 && $row1 > 0)
            return redirect(url('/user'))->with(['success' => Config::get('settings.form_delete_success_message')]);
        else
            return redirect(url('/user'))->with(['success' => Config::get('settings.form_delete_failed_message')]);
        //
    }

    public function lock($id)
    {
        $result = UserModel::find($id);
        if (isset($result)) {
            $fetchresult = json_decode(json_encode($result[0]), true);
//            echo ' '.$fetchresult['Status'].' ';
            $row = UserModel::lock($id, $fetchresult);

            if ($row == 'success' && HelperModel::getUserSessionID() != $id) {
                return redirect(url('/user'))->with(['success' => 'Lock status successfully changed']);
            } else if ($row == 'success' && HelperModel::getUserSessionID() == $id) {
                return redirect(url('/user'))->with(['success' => 'Lock status successfully changed, login again to see changes']);
            } else {
                return redirect(url('/user'))->with(['success' => 'Lock status failed to changed']);
            }
        } else {
            return "Problem in fetching data in view";
        }
    }

    public function find()
    {
        return UserModel::searchUser();
    }

    private function getValidateRules()
    {
        $rules = array('firstName' => 'required|alpha|min:2|max:25',
            'lastName' => 'required|alpha|min:2|max:25',
            'password' => ['required', 'regex:/^(?=.*[a-zA-Z])(?=.*[-=!@#$%^&*_<>?|,.;:\(){}]).{8,}$/'],
            'confirmPassword' => ['required', 'regex:/^(?=.*[a-zA-Z])(?=.*[-=!@#$%^&*_<>?|,.;:\(){}]).{8,}$/'],
            'email' => 'required|email');

        return $rules;
    }

    private function getValidateRulesForUpdate()
    {
        $rules = array('firstName' => 'required|alpha|min:2|max:25',
            'lastName' => 'required|alpha|min:2|max:25',
            'email' => 'required|email');

        return $rules;
    }

    //user list via pagination
    function UserListViaPagination(Request $request)
    {

        error_log('in controller');

        $offset = $request->input('p');
        $limit = $request->input('c');
        $keyword = $request->input('s');
        $roleCode = $request->input('r');
        $userId = $request->input('userId');

        $superAdminRole = env('ROLE_SUPER_ADMIN');
        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $supportStaffRole = env('ROLE_SUPPORT_STAFF');
        $patientRole = env('ROLE_PATIENT');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        //Fetching user if looged in user is belonging to admin
        $userData = UserModel::GetSingleUserViaId($userId);
        if (count($userData) == 0) {
            return response()->json(['data' => null, 'message' => 'User not found'], 400);
        } else {
            //Means user data fetched
            //Now checking if user belongs to super admin
            if ($userData[0]->RoleCodeName == $superAdminRole) {
                error_log('User is from super admin');

                $val = UserModel::FetchUserWithSearchAndPagination
                ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $roleCode);

                foreach ($val as $key) {
                    $key->IsCurrentlyLoggedIn = ((bool)$key->IsCurrentlyLoggedIn ? true : false);
                    $key->LastLoggedIn = ForumModel::calculateTopicAnCommentTime($key->LastLoggedIn);
                }

                $resultArray = json_decode(json_encode($val), true);
                $data = $resultArray;

                if (count($data) > 0) {
                    return response()->json(['data' => $data, 'message' => 'Users fetched successfully'], 200);
                } else {
                    return response()->json(['data' => null, 'message' => 'Users not found'], 200);
                }
            } //Now checking if user belongs to doctor
            else if ($userData[0]->RoleCodeName == $doctorRole) {
                error_log('logged in user role is doctor');

                if ($roleCode == $superAdminRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $supportStaffRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $doctorRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $facilitatorRole) {
                    //Getting ids of associated facilitator
                    $getAssociatedFacilitatorId = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($userId, $doctorFacilitatorAssociation);

                    if (count($getAssociatedFacilitatorId) == 0) {
                        return response()->json(['data' => null, 'message' => 'No facilitator associated yet'], 400);
                    }
                    $destinationIds = array();
                    foreach ($getAssociatedFacilitatorId as $item) {
                        array_push($destinationIds, $item->DestinationUserId);
                    }

                    $val = UserModel::FetchUserFacilitatorListForDoctorWithSearchAndPagination
                    ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $destinationIds, $roleCode);

                    foreach ($val as $key) {
                        $key->IsCurrentlyLoggedIn = ((bool)$key->IsCurrentlyLoggedIn ? true : false);
                        $key->LastLoggedIn = ForumModel::calculateTopicAnCommentTime($key->LastLoggedIn);
                    }

                    $resultArray = json_decode(json_encode($val), true);

                    $data = $resultArray;

                    error_log(count($data));
                    if (count($data) > 0) {
                        return response()->json(['data' => $data, 'message' => 'Facilitators fetched successfully'], 200);
                    } else {
                        return response()->json(['data' => null, 'message' => 'Facilitators not found'], 200);
                    }
                } else if ($roleCode == $patientRole) {
                    //Getting ids of associated facilitator
                    $getAssociatedPatientId = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($userId, $doctorPatientAssociation);

                    if (count($getAssociatedPatientId) == 0) {
                        return response()->json(['data' => null, 'message' => 'No patient associated yet'], 400);
                    }
                    $destinationIds = array();
                    foreach ($getAssociatedPatientId as $item) {
                        array_push($destinationIds, $item->DestinationUserId);
                    }

                    $val = UserModel::FetchUserFacilitatorListForDoctorWithSearchAndPagination
                    ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $destinationIds, $roleCode);

                    foreach ($val as $key) {
                        $key->IsActive = (bool)$key->IsActive;
                        $key->IsCurrentlyLoggedIn = ((bool)$key->IsCurrentlyLoggedIn ? true : false);
                        $key->LastLoggedIn = ForumModel::calculateTopicAnCommentTime($key->LastLoggedIn);
                    }
                    $resultArray = json_decode(json_encode($val), true);
                    $data = $resultArray;

                    if (count($data) > 0) {
                        return response()->json(['data' => $data, 'message' => 'Patients fetched successfully'], 200);
                    } else {
                        return response()->json(['data' => null, 'message' => 'Patients not found'], 200);
                    }
                } else {
                    return response()->json(['data' => null, 'message' => 'Invalid user role'], 400);
                }
            } else if ($userData[0]->RoleCodeName == $facilitatorRole) {
                error_log('logged in user role is facilitator');

                if ($roleCode == $superAdminRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $supportStaffRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $facilitatorRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $doctorRole) {
                    $getAssociatedDoctorsId = UserModel::getSourceUserIdViaLoggedInUserId($userId);

                    if (count($getAssociatedDoctorsId) == 0) {
                        return response()->json(['data' => null, 'message' => 'No doctor associated yet'], 400);
                    }
                    $doctorIds = array();
                    foreach ($getAssociatedDoctorsId as $item) {
                        array_push($doctorIds, $item->SourceUserId);
                    }

                    $val = UserModel::FetchUserFacilitatorListForDoctorWithSearchAndPagination
                    ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $doctorIds, $roleCode);

                    foreach ($val as $key) {
                        $key->IsCurrentlyLoggedIn = ((bool)$key->IsCurrentlyLoggedIn ? true : false);
                        $key->LastLoggedIn = ForumModel::calculateTopicAnCommentTime($key->LastLoggedIn);
                    }

                    $resultArray = json_decode(json_encode($val), true);

                    $data = $resultArray;

                    error_log(count($data));
                    if (count($data) > 0) {
                        return response()->json(['data' => $data, 'message' => 'Doctors fetched successfully'], 200);
                    } else {
                        return response()->json(['data' => null, 'message' => 'Doctors not found'], 200);
                    }
                } else if ($roleCode == $patientRole) {
                    //First get associated doctors id.
                    $getAssociatedDoctorsId = UserModel::getSourceUserIdViaLoggedInUserId($userId);

                    if (count($getAssociatedDoctorsId) == 0) {
                        return response()->json(['data' => null, 'message' => 'No doctor associated yet'], 400);
                    }
                    $doctorIds = array();
                    foreach ($getAssociatedDoctorsId as $item) {
                        array_push($doctorIds, $item->SourceUserId);
                    }

                    $getAssociatedPatientIds = UserModel::getAssociatedPatientsUserId($doctorIds, $doctorPatientAssociation);

                    if (count($getAssociatedPatientIds) == 0) {
                        return response()->json(['data' => null, 'message' => 'No patient associated yet'], 400);
                    }
                    $patientIds = array();
                    foreach ($getAssociatedPatientIds as $item) {
                        array_push($patientIds, $item->DestinationUserId);
                    }

                    $val = UserModel::FetchUserFacilitatorListForDoctorWithSearchAndPagination
                    ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $patientIds, $roleCode);

                    foreach ($val as $key) {
                        $key->IsCurrentlyLoggedIn = ((bool)$key->IsCurrentlyLoggedIn ? true : false);
                        $key->LastLoggedIn = ForumModel::calculateTopicAnCommentTime($key->LastLoggedIn);
                    }

                    $resultArray = json_decode(json_encode($val), true);

                    $data = $resultArray;

                    error_log(count($data));
                    if (count($data) > 0) {
                        return response()->json(['data' => $data, 'message' => 'Patients fetched successfully'], 200);
                    } else {
                        return response()->json(['data' => null, 'message' => 'Patients not found'], 200);
                    }
                } else {
                    return response()->json(['data' => null, 'message' => 'Invalid user role'], 400);
                }
            } else if ($userData[0]->RoleCodeName == $patientRole) {
                error_log('logged in user role is patient');

                if ($roleCode == $superAdminRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $supportStaffRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $patientRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $facilitatorRole) {
                    //First get associated doctors id
                    $getAssociatedDoctorsId = UserModel::getSourceIdViaLoggedInUserIdAndAssociationType($userId, $doctorPatientAssociation);

                    if (count($getAssociatedDoctorsId) == 0) {
                        return response()->json(['data' => null, 'message' => 'No doctor associated yet'], 400);
                    }
                    $doctorIds = array();
                    foreach ($getAssociatedDoctorsId as $item) {
                        array_push($doctorIds, $item->SourceUserId);
                    }
                    //Now get associated facilitators id with respect to doctors

                    $getFacilitatorIds = UserModel::getAssociatedPatientsUserId($doctorIds, $doctorFacilitatorAssociation);

                    if (count($getFacilitatorIds) == 0) {
                        return response()->json(['data' => null, 'message' => 'No facilitator associated yet'], 400);
                    }
                    $facilitatorIds = array();
                    foreach ($getFacilitatorIds as $item) {
                        array_push($facilitatorIds, $item->DestinationUserId);
                    }

                    $val = UserModel::FetchUserFacilitatorListForDoctorWithSearchAndPagination
                    ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $facilitatorIds, $roleCode);

                    foreach ($val as $key) {
                        $key->IsCurrentlyLoggedIn = ((bool)$key->IsCurrentlyLoggedIn ? true : false);
                        $key->LastLoggedIn = ForumModel::calculateTopicAnCommentTime($key->LastLoggedIn);
                    }

                    $resultArray = json_decode(json_encode($val), true);

                    $data = $resultArray;

                    error_log(count($data));
                    if (count($data) > 0) {
                        return response()->json(['data' => $data, 'message' => 'Facilitators fetched successfully'], 200);
                    } else {
                        return response()->json(['data' => null, 'message' => 'Facilitators not found'], 200);
                    }
                } else if ($roleCode == $doctorRole) {
                    $getAssociatedDoctorsId = UserModel::getSourceIdViaLoggedInUserIdAndAssociationType($userId, $doctorPatientAssociation);

                    if (count($getAssociatedDoctorsId) == 0) {
                        return response()->json(['data' => null, 'message' => 'No doctor associated yet'], 400);
                    }
                    $doctorIds = array();
                    foreach ($getAssociatedDoctorsId as $item) {
                        array_push($doctorIds, $item->SourceUserId);
                    }

                    $val = UserModel::FetchUserFacilitatorListForDoctorWithSearchAndPagination
                    ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $doctorIds, $roleCode);

                    foreach ($val as $key) {
                        $key->IsCurrentlyLoggedIn = ((bool)$key->IsCurrentlyLoggedIn ? true : false);
                        $key->LastLoggedIn = ForumModel::calculateTopicAnCommentTime($key->LastLoggedIn);
                    }

                    $resultArray = json_decode(json_encode($val), true);

                    $data = $resultArray;

                    error_log(count($data));
                    if (count($data) > 0) {
                        return response()->json(['data' => $data, 'message' => 'Doctors fetched successfully'], 200);
                    } else {
                        return response()->json(['data' => null, 'message' => 'Doctors not found'], 200);
                    }
                } else {
                    return response()->json(['data' => null, 'message' => 'Invalid user role'], 400);
                }
            } else if ($userData[0]->RoleCodeName == $supportStaffRole) {
                error_log('logged in user role is support staff');
                if ($roleCode == $superAdminRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else {
                    if ($roleCode == null || $roleCode == "null") {
                        return response()->json(['data' => null, 'message' => 'Role code should not be empty'], 404);
                    } else {

                        $val = UserModel::FetchUserWithSearchAndPagination
                        ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $roleCode);

                        foreach ($val as $key) {
                            $key->IsCurrentlyLoggedIn = ((bool)$key->IsCurrentlyLoggedIn ? true : false);
                            $key->LastLoggedIn = ForumModel::calculateTopicAnCommentTime($key->LastLoggedIn);
                        }

                        $resultArray = json_decode(json_encode($val), true);
                        $data = $resultArray;
                        if (count($data) > 0) {
                            return response()->json(['data' => $data, 'message' => 'Users fetched successfully'], 200);
                        } else {
                            return response()->json(['data' => null, 'message' => 'Users not found'], 200);
                        }
                    }
                }
            }
        }
    }

    //user list for combo box

    function UserList()
    {

        $val = UserModel::getUserList();

        $resultArray = json_decode(json_encode($val), true);
        $data = $resultArray;
        if (count($data) > 0) {
            return response()->UserRegistrationjson(['data' => $data, 'message' => 'Users fetched successfully'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Users not found'], 200);
        }
    }

    function GetUserViaRoleCode(Request $request)
    {
        $roleCode = $request->get('roleCode');

        $val = UserModel::GetUserViaRoleCode($roleCode);
        $userData = array();
        foreach ($val as $item) {
            $data = array(
                'Id' => $item->Id,
                'FirstName' => $item->FirstName,
                'LastName' => $item->LastName,
                'EmailAddress' => $item->EmailAddress,
                'MobileNumber' => $item->MobileNumber,
                'TelephoneNumber' => $item->TelephoneNumber,
                'Gender' => $item->Gender,
                'FunctionalTitle' => $item->FunctionalTitle,
                'Role' => array()
            );

            $data['Role']['Id'] = $item->RoleId;
            $data['Role']['Name'] = $item->RoleName;
            $data['Role']['CodeName'] = $item->RoleCodeName;

            array_push($userData, $data);
        }

        if (count($val) > 0) {
            return response()->json(['data' => $userData, 'message' => 'Users fetched successfully'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Users not found'], 200);
        }
    }

    //user list count API

    function UserCount(Request $request)
    {

        error_log('in controller');

        $keyword = $request->input('s');
        $roleCode = $request->input('r');
        $userId = $request->input('userId');

        $superAdminRole = env('ROLE_SUPER_ADMIN');
        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $supportStaffRole = env('ROLE_SUPPORT_STAFF');
        $patientRole = env('ROLE_PATIENT');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        //Fetching user if looged in user is belonging to admin
        $userData = UserModel::GetSingleUserViaId($userId);
        if (count($userData) == 0) {
            return response()->json(['data' => null, 'message' => 'User not found'], 400);
        } else {
            //Means user data fetched
            //Now checking if user belongs to super admin
            if ($userData[0]->RoleCodeName == $superAdminRole) {
                error_log('User is from super admin');
                $val = UserModel::UserCountWithSearch
                ('user', '=', 'IsActive', true, $keyword, $roleCode);

                error_log("val");
                error_log($val);

                return response()->json(['data' => $val, 'message' => 'Users count'], 200);
            } //Now checking if user belongs to doctor
            else if ($userData[0]->RoleCodeName == $doctorRole) {
                error_log('logged in user role is doctor');

                if ($roleCode == $superAdminRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $supportStaffRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $doctorRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $facilitatorRole) {
                    //Getting ids of associated facilitator
                    $getAssociatedFacilitatorId = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($userId, $doctorFacilitatorAssociation);

                    if (count($getAssociatedFacilitatorId) == 0) {
                        return response()->json(['data' => null, 'message' => 'No facilitator associated yet'], 400);
                    }
                    $destinationIds = array();
                    foreach ($getAssociatedFacilitatorId as $item) {
                        array_push($destinationIds, $item->DestinationUserId);
                    }

                    $val = UserModel::FetchUserFacilitatorListForDoctorWithSearchCount
                    ('user', '=', 'IsActive', true, $keyword, $destinationIds, $roleCode);
                    return response()->json(['data' => $val, 'message' => 'Users count'], 200);
                } else if ($roleCode == $patientRole) {
                    //Getting ids of associated facilitator
                    $getAssociatedPatientId = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($userId, $doctorPatientAssociation);

                    if (count($getAssociatedPatientId) == 0) {
                        return response()->json(['data' => null, 'message' => 'No patient associated yet'], 400);
                    }
                    $destinationIds = array();
                    foreach ($getAssociatedPatientId as $item) {
                        array_push($destinationIds, $item->DestinationUserId);
                    }

                    $val = UserModel::FetchUserFacilitatorListForDoctorWithSearchCount
                    ('user', '=', 'IsActive', true, $keyword, $destinationIds, $roleCode);

                    return response()->json(['data' => $val, 'message' => 'Users count'], 200);
                } else {
                    return response()->json(['data' => null, 'message' => 'Invalid user role'], 400);
                }
            } else if ($userData[0]->RoleCodeName == $facilitatorRole) {
                error_log('logged in user role is facilitator');

                if ($roleCode == $superAdminRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $supportStaffRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $facilitatorRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $doctorRole) {
                    $getAssociatedDoctorsId = UserModel::getSourceUserIdViaLoggedInUserId($userId);

                    if (count($getAssociatedDoctorsId) == 0) {
                        return response()->json(['data' => null, 'message' => 'No doctor associated yet'], 400);
                    }
                    $doctorIds = array();
                    foreach ($getAssociatedDoctorsId as $item) {
                        array_push($doctorIds, $item->SourceUserId);
                    }

                    $val = UserModel::FetchUserFacilitatorListForDoctorWithSearchCount
                    ('user', '=', 'IsActive', true, $keyword, $doctorIds, $roleCode);

                    return response()->json(['data' => $val, 'message' => 'Users count'], 200);
                } else if ($roleCode == $patientRole) {
                    //First get associated doctors id.
                    $getAssociatedDoctorsId = UserModel::getSourceUserIdViaLoggedInUserId($userId);

                    if (count($getAssociatedDoctorsId) == 0) {
                        return response()->json(['data' => null, 'message' => 'No doctor associated yet'], 400);
                    }
                    $doctorIds = array();
                    foreach ($getAssociatedDoctorsId as $item) {
                        array_push($doctorIds, $item->SourceUserId);
                    }

                    $getAssociatedPatientIds = UserModel::getAssociatedPatientsUserId($doctorIds, $doctorPatientAssociation);

                    if (count($getAssociatedPatientIds) == 0) {
                        return response()->json(['data' => null, 'message' => 'No patient associated yet'], 400);
                    }
                    $patientIds = array();
                    foreach ($getAssociatedPatientIds as $item) {
                        array_push($patientIds, $item->DestinationUserId);
                    }

                    $val = UserModel::FetchUserFacilitatorListForDoctorWithSearchCount
                    ('user', '=', 'IsActive', true, $keyword, $patientIds, $roleCode);

                    return response()->json(['data' => $val, 'message' => 'Users count'], 200);
                } else {
                    return response()->json(['data' => null, 'message' => 'Invalid user role'], 400);
                }
            } else if ($userData[0]->RoleCodeName == $patientRole) {
                error_log('logged in user role is patient');

                if ($roleCode == $superAdminRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $supportStaffRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $patientRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else if ($roleCode == $facilitatorRole) {
                    //First get associated doctors id
                    $getAssociatedDoctorsId = UserModel::getSourceIdViaLoggedInUserIdAndAssociationType($userId, $doctorPatientAssociation);

                    if (count($getAssociatedDoctorsId) == 0) {
                        return response()->json(['data' => null, 'message' => 'No doctor associated yet'], 400);
                    }
                    $doctorIds = array();
                    foreach ($getAssociatedDoctorsId as $item) {
                        array_push($doctorIds, $item->SourceUserId);
                    }
                    //Now get associated facilitators id with respect to doctors

                    $getFacilitatorIds = UserModel::getAssociatedPatientsUserId($doctorIds, $doctorFacilitatorAssociation);

                    if (count($getFacilitatorIds) == 0) {
                        return response()->json(['data' => null, 'message' => 'No facilitator associated yet'], 400);
                    }
                    $facilitatorIds = array();
                    foreach ($getFacilitatorIds as $item) {
                        array_push($facilitatorIds, $item->DestinationUserId);
                    }

                    $val = UserModel::FetchUserFacilitatorListForDoctorWithSearchCount
                    ('user', '=', 'IsActive', true, $keyword, $facilitatorIds, $roleCode);

                    return response()->json(['data' => $val, 'message' => 'Users count'], 200);
                } else if ($roleCode == $doctorRole) {
                    $getAssociatedDoctorsId = UserModel::getSourceIdViaLoggedInUserIdAndAssociationType($userId, $doctorPatientAssociation);

                    if (count($getAssociatedDoctorsId) == 0) {
                        return response()->json(['data' => null, 'message' => 'No doctor associated yet'], 400);
                    }
                    $doctorIds = array();
                    foreach ($getAssociatedDoctorsId as $item) {
                        array_push($doctorIds, $item->SourceUserId);
                    }

                    $val = UserModel::FetchUserFacilitatorListForDoctorWithSearchCount
                    ('user', '=', 'IsActive', true, $keyword, $doctorIds, $roleCode);

                    return response()->json(['data' => $val, 'message' => 'Users count'], 200);
                } else {
                    return response()->json(['data' => null, 'message' => 'Invalid user role'], 400);
                }
            } else if ($userData[0]->RoleCodeName == $supportStaffRole) {
                error_log('logged in user role is support staff');
                if ($roleCode == $superAdminRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else {
                    if ($roleCode == null || $roleCode == "null") {
                        return response()->json(['data' => null, 'message' => 'Role code should not be empty'], 404);
                    } else {
                        $val = UserModel::UserCountWithSearch
                        ('user', '=', 'IsActive', true, $keyword, $roleCode);

                        return response()->json(['data' => $val, 'message' => 'Users count'], 200);
                    }
                }
            }
        }
    }

    function UserUpdate(Request $request)
    {
        $id = $request->get('id');

        //First get and check if record exists or not
        $data = UserModel::GetSingleUserViaId($id);

//        RoleCodeName

        if (count($data) == 0) {
            return response()->json(['data' => null, 'message' => 'User not found'], 400);
        }

        //We have get the data.
        //Now insert that data in log table to maitain old record of that user

        error_log('first name is : ' . $data[0]->FirstName);

        $dataToInsert = array(
            "UserId" => $id,
            "FirstName" => $data[0]->FirstName,
            "LastName" => $data[0]->LastName,
            "MobileNumber" => $data[0]->MobileNumber,
            "TelephoneNumber" => $data[0]->TelephoneNumber,
            "OfficeAddress" => $data[0]->OfficeAddress,
            "ResidentialAddress" => $data[0]->ResidentialAddress,
            "Gender" => $data[0]->Gender,
            "FunctionalTitle" => $data[0]->FunctionalTitle,
            "Age" => $data[0]->Age,
            "AgeGroup" => $data[0]->AgeGroup,
            "CreatedBy" => $data[0]->CreatedBy,
            "CreatedOn" => $data[0]->CreatedOn,
            "CityId" => $data[0]->CityId,
        );

        DB::beginTransaction();
        $insertedRecord = GenericModel::insertGenericAndReturnID('change_log_user', $dataToInsert);

        if ($insertedRecord == false) {
            DB::rollBack();
            return response()->json(['data' => null, 'message' => 'Error in maintaining user log'], 400);
        }


        //Binding data to variable.

        $firstName = $request->post('FirstName');
        $lastName = $request->post('LastName');
        $mobileNumber = $request->post('MobileNumber');
        $telephoneNumber = $request->post('TelephoneNumber');
        $officeAddress = $request->post('OfficeAddress');
        $residentialAddress = $request->post('ResidentialAddress');
        $gender = $request->post('Gender');
        $functionalTitle = $request->post('FunctionalTitle');
        $age = $request->post('Age');
        $profileSummary = $request->post('ProfileSummary');
        $patientUniqueId = null;

        if ($data[0]->RoleCodeName == env('ROLE_PATIENT')) {
            $patientUniqueId = $request->post('PatientUniqueId');
            if ($patientUniqueId != null || $patientUniqueId != "") {
                error_log(" ## checking is provided patient id is unique or not  ## ");
                $data = LoginModel::checkUniqueIdAvailableForUpdateScenario($id, $patientUniqueId);
                if (count($data) > 0) {
                    error_log("## provided unique id is not Unique ##");
                    $message = "Patient Unique Id already exist";
                    return response()->json(['data' => null, 'message' => $message], 400);
                }
                error_log("## provided unique id is Unique ## ");
            } else {
                return response()->json(['data' => null, 'message' => 'Patient Unique Id is require'], 400);
            }
        }

        $dataToUpdate = array(
            "PatientUniqueId" => $patientUniqueId,
            "FirstName" => $firstName,
            "LastName" => $lastName,
            "TelephoneNumber" => $telephoneNumber,
            "OfficeAddress" => $officeAddress,
            "ResidentialAddress" => $residentialAddress,
            "Gender" => $gender,
            "FunctionalTitle" => $functionalTitle,
            "Age" => $age,
            "ProfileSummary" => $profileSummary
        );
        $emailMessage = "Dear User <br/>Update is made on your records";

        $update = GenericModel::updateGeneric('user', 'Id', $id, $dataToUpdate);

        if ($update == 1) {
            DB::commit();

            //create email with template
            $emailBody = "<p style='width: 800px;'>Dear " . $firstName . " " . $lastName . "<br>" .
                "<br>Your account information is updated in the Chronic Care Management system.<br><br>" .
                "The message is for information purpose only. If you have any concerns please contact with the provider or check the same by logging into the portal http://connectcareplus.com</p>";

            UserModel::sendEmailWithTemplateTwo($data[0]->EmailAddress, "User Account Information Update", $emailBody);

            return response()->json(['data' => null, 'message' => 'User successfully updated'], 200);
        } else if ($update == 0) {
            DB::rollBack();
            return response()->json(['data' => null, 'message' => 'User records already updated'], 200);
        } else {
            DB::rollBack();
            return response()->json(['data' => null, 'message' => 'Error in updating User records'], 500);
        }
    }

    public function TestEmailThree()
    {


        $emailContent = "<!DOCTYPE html>" .
            "<html>" +
            "<head>" +
            "<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0'>" +
            "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>" +
            "<title>' abc '</title>" +
            "<link rel='stylesheet' href='http://skillorbit.co/socialcxn_revamp/resources/email_assets/geomanist-fonts.css'>" +
            "</head>" +
            "<body style=\"-webkit-font-smoothing:antialiased; font-family: \'Geomanist-Light\',\'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif; -webkit-text-size-adjust:none; word-wrap:break-word; background-color:#ffffff; margin:0; padding:0;\">" +
//        Main Template
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\"> " +
            "<tr>" +
            "<td align=\"center\" bgcolor=\"#ffffff\">" +
//        '<!-- Background -->"+
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">" +
//        <!-- Wrapper -->
//        <!-- BODY -->
            "<tr>" +
            "<td width=\"600\" style=\"padding-top:30px;\">" +
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" +
//        <!-- Header -->
            "<tr>" +
            "<td width=\"600\" style='padding-top:0px;padding-left:30px;padding-right:30px;padding-bottom:0px;background-color:#ffffff;border-bottom:1px solid #e5e8e5;'>" +
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" +
            "<tr>" +
            "<td width=\"540\" valign=\"middle\" style=\"padding-top:15px;padding-bottom:15px;\">" +
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" +
            "<tr>" +
            "<td width=\"180\" align=\"left\" valign=\"middle\" style=\"text-align:left;font-size: 20px;font-weight: 900;color: #3a796a;\">" +
            "<img width=\"178\" height=\"22\" alt=\"Linkagoal android app\" src=\"http://skillorbit.co/socialcxn_revamp/resources/email_assets/green-logo.png\" style=\"border-style:none; width: 178px; height: 22px;\">" +
            "</td>" +
            "<td width=\"360\" align=\"right\" valign=\"middle\" style=\"text-align:right;font-size:12px;color:#5c6470;font-family: \'Geomanist-Regular\',\'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;\">" +
            "<span style=\"background-color:#5c6470;color:#ffffff;padding:5px;\">&nbsp;' + title + '&nbsp;</span>" +
            "</td>" +
            "</tr>" +
            "</table>" +
            "</td>" +
            "</tr>" +
            "</table>" +
            "</td>" +
            "</tr>" +
//    <!-- Header -->
//        <!-- Content -->
            "<tr>" +
            "<td width=\"600\" style=\"padding-top:0px;padding-left:30px;padding-right:30px;padding-bottom:0px;background-color:#ffffff;\">" +
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" +
            "<tr>" +
            "<td width=\"540\" valign=\"middle\" align=\"left\" style=\"padding-top:40px;padding-bottom:20px;text-align:left;font-size:16px;color:#151515;font-family: \'Geomanist-Regular\',\'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;\">" +
            "Hi" +
            "</td>" +
            "</tr>" +
            "</table>" +
            "</td>" +
            "</tr>" +
            "<tr>" +
            "<td width='600' style='padding-top:0px;padding-left:30px;padding-right:30px;padding-bottom:0px;background-color:#ffffff;'>" +
            "<table cellspacing='0' cellpadding='0' border='0' style='border-collapse:collapse;'>" +
            "<tr>" +
            "<td width=\"540\" valign=\"middle\" align=\"left\" style=\"padding-top:20px;padding-bottom:0px;text-align:left;font-size:16px;color:#2c2d30;font-family: \'Geomanist-Regular\',\'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;\">\n" +
            "content" +
            "</td>" +
            "</tr>" +
            "</table>" +
            "</td>" +
            "</tr>" +
            "<tr>" +
            "<td width=\"600\" style=\"padding-top:0px;padding-left:30px;padding-right:30px;padding-bottom:0px;background-color:#ffffff;\">" +
            "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"border-collapse:collapse;\">" +
            "<tr>" +
            "<td width=\"540\" valign=\"middle\" align=\"left\" style=\"padding-top:20px;padding-bottom:40px;text-align:left;font-size:16px;color:#151515;font-family: \'Geomanist-Regular\',\'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;\">" +
            "Thank You,<br>Team SOCIALCxN" +
            "</td>" +
            "</tr>" +
            "</table>" +
            "</td>" +
            "</tr>" +
//        "<!-- Content -->"+
            "</table>" +
            "</td>" +
            "</tr>" +
//        "<!-- Footer -->" +
            "<tr>" +
            "<td width='600'>" +
            "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\" width=\"100%\" class=\"module footer\" role=\"module\" data-type=\"footer\">" +
            "<tr>" +
            "<td style=\"padding: 15px 30px 15px 30px;\" bgcolor=\"#f6f6f6\">" +
            "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"table-layout: fixed;\">" +
//        "<!-- App link section -->"+
            "<tr>" +
            "<td width=\"540\" valign=\"middle\" align=\"center\" style=\"color: #6e6e6e; padding-top: 15px; text-align: center !important;font-family:helvetica, arial, sans-serif;font-family: \'Geomanist-Book\', \'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;font-size:16px;\">" +
            "</td>" +
            "</tr>" +
            "<tr>" +
            "<td width=\"540\" valign=\"middle\" align=\"center\" style=\"color: #6e6e6e; padding-top: 15px; padding-bottom: 10px; text-align: center !important;\">" +
            "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"table-layout: fixed;\">" +
            "<tr>" +
            "<td width=\"270\" valign=\"middle\" align=\"right\" style=\"text-align: right !important;\">" +
            "<a href=\"\" style=\"text-decoration:none;padding-right: 10px;display:inline-block;\"><img width=\"28\" height=\"28\" alt=\"SocialCxN ios app\" src=\"http://skillorbit.co/socialcxn_revamp/resources/email_assets/appstore.png\" style=\"border-style:none; width: 28px; height: 28px;\"></a>" +
            "</td>" +
            "<td width=\"270\" valign=\"middle\" align=\"left\" style=\"text-align: left !important;\">" +
            "<a href=\"https://play.google.com/apps/testing/com.socialcxn.socialcxnapp\" style=\"text-decoration:none;display:inline-block;\"><img width=\"28\" height=\"28\" alt=\"SocialCxN android app\" src=\"http://skillorbit.co/socialcxn_revamp/resources/email_assets/playstore.png\" style=\"border-style:none; width: 28px; height: 28px;\"></a>" +
            "</td>" +
            "</tr>" +
            "</table>" +
            "</td>" +
            "</tr>" +
            "<!-- <tr>" +
            "<td style=\"padding-top: 10px; padding-bottom: 20px; text-align: center;font-family:helvetica, arial, sans-serif;font-size:15px;font-family: \'Geomanist-Regular\', \'Helvetica Neue\', Helvetica, \'Segoe UI\', \'Lucida Grande\', Arial, sans-serif;font-size:13px;color: #666666 !important; line-height: 16px;\">" +
            "F-17, Ground Floor, Business Executive Center, Block-8, Clifton, Karachi, Pakistan" +
            "</td>" +
            "</tr>" +
            "</table>" +
            "</td>" +
            "</tr>" +
            "</table>" +
            "</td>" +
            "</tr>" +
            "</table>" +
//        "<!-- Background --> +
            "</td>" +
            "</tr>" +
            "</table>" +
//        "<!-- Main Template -->' +
            "</body>" +
            "</html>";
        $result = UserModel::sendEmailWithTemplate("ahmer.saeed.office@gmail.com", "Email with Template", $emailContent);
//        $result = ForgetPasswordModel::forgetPassword();
        return response()->json(['data' => $result, 'message' => 'Check Email'], 200);
    }

    function GetSingleUserViaId(Request $request)
    {
        $id = $request->get('id');

        $doctorRole = env('ROLE_DOCTOR');
        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        $baseUrl = env('BASE_URL');
        $profilePicAPIPrefix = env('PROFILE_PIC_API_PREFIX');

        $val = UserModel::GetSingleUserViaIdNewFunction($id);

        $userDetails = array();

        $userDetails['Id'] = $val->Id;
        $userDetails['PatientUniqueId'] = $val->PatientUniqueId;
        $userDetails['FirstName'] = $val->FirstName;
        $userDetails['LastName'] = $val->LastName;
        $userDetails['EmailAddress'] = $val->EmailAddress;
        $userDetails['MobileNumber'] = $val->MobileNumber;
        $userDetails['TelephoneNumber'] = $val->TelephoneNumber;
        $userDetails['OfficeAddress'] = $val->OfficeAddress;
        $userDetails['ResidentialAddress'] = $val->ResidentialAddress;
        $userDetails['Gender'] = $val->Gender;
        $userDetails['FunctionalTitle'] = $val->FunctionalTitle;
        $userDetails['Age'] = $val->Age;
        $userDetails['AgeGroup'] = $val->AgeGroup;
        $userDetails['IsBlock'] = $val->IsBlock;
        $userDetails['BlockReason'] = $val->BlockReason;
        $userDetails['ProfileSummary'] = $val->ProfileSummary;
        $userDetails['Role'] = array();
        $userDetails['Role']['Id'] = $val->RoleId;
        $userDetails['Role']['RoleName'] = $val->RoleName;
        $userDetails['Role']['RoleCodeName'] = $val->RoleCodeName;
        $userDetails['IsCurrentlyLoggedIn'] = ((bool)$val->IsCurrentlyLoggedIn ? true : false);
        $userDetails['LastLoggedIn'] = ForumModel::calculateTopicAnCommentTime($val->LastLoggedIn); //timestamp
        $userDetails['ProfileSummary'] = $val->ProfileSummary;

        $userDetails['ProfilePicture'] = null;

//        $data = array();
//        //Pushing logged in user basic inforamtion
//        array_push($data, $val);

        if ($val->RoleCodeName == $doctorRole) {
            error_log('logged in user is doctor');
            //Now fetch it's patients which are registered
            $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($id, $doctorPatientAssociation);
            error_log('$getAssociatedPatients are ' . $getAssociatedPatients);
            if (count($getAssociatedPatients) > 0) {
                //Means associated patients are there
                $getAssociatedPatientsIds = array();
                foreach ($getAssociatedPatients as $item) {
                    array_push($getAssociatedPatientsIds, $item->DestinationUserId);
                }
                $getAssociatedPatientsData = UserModel::getMultipleUsers($getAssociatedPatientsIds);

                if (count($getAssociatedPatientsData) > 0) {
//                    $val['associatedPatients'] = $getAssociatedPatientsData;
                    $userDetails['AssociatedPatients'] = $getAssociatedPatientsData;
                }
            }

            //Now get associated facilitators

            $getAssociatedFacilitators = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($id, $doctorFacilitatorAssociation);
            error_log('$getAssociatedFacilitators are ' . $getAssociatedFacilitators);
            if (count($getAssociatedFacilitators) > 0) {
                //Means associated patients are there
                $getAssociatedFacilitatorIds = array();
                foreach ($getAssociatedFacilitators as $item) {
                    array_push($getAssociatedFacilitatorIds, $item->DestinationUserId);
                }
                $getAssociatedFacilitatorsData = UserModel::getMultipleUsers($getAssociatedFacilitatorIds);

                if (count($getAssociatedFacilitatorsData) > 0) {
//                    $val['associatedFacilitators'] = $getAssociatedFacilitatorsData;
                    $userDetails['AssociatedFacilitators'] = $getAssociatedFacilitatorsData;
                }
            }
        }

        //Now fetching uploaded file data
        error_log("val->ProfilePictureId");
        error_log($val->ProfilePictureId);
        if ($val->ProfilePictureId != null) {

            $checkDocument = DocumentUploadModel::GetDocumentData($val->ProfilePictureId);
            if ($checkDocument != null) {

                error_log($checkDocument->FileName . '' . $checkDocument->FileExtension);
                //Now checking if document name is same as it is given in parameter
                error_log('document name is valid');

                $userDetails['ProfilePicture']['Id'] = $checkDocument->Id;
                $userDetails['ProfilePicture']['Path'] = $baseUrl . '' . $profilePicAPIPrefix . '/' . $checkDocument->Id . '/' . $checkDocument->FileName . '' . $checkDocument->FileExtension;
                $userDetails['ProfilePicture']['FileOriginalName'] = $checkDocument->FileOriginalName;
                $userDetails['ProfilePicture']['FileName'] = $checkDocument->FileName;
                $userDetails['ProfilePicture']['FileExtension'] = $checkDocument->FileExtension;
            }
        } else {
            //binding default avatar picture
            $defaultProfilePicAPIPrefix = env('DEFAULT_PROFILE_PIC_API_PREFIX');

            if (strtolower($val->Gender) == "male") {
                $defaultImageName = env('DEFAULT_MALE_PROFILE_PIC');
                $defaultImageExtension = env('DEFAULT_MALE_PROFILE_PIC_Ext');
            } else if (strtolower($val->Gender) == "female") {
                $defaultImageName = env('DEFAULT_FEMALE_PROFILE_PIC');
                $defaultImageExtension = env('DEFAULT_FEMALE_PROFILE_PIC_Ext');
            } else {
                $defaultImageName = env('DEFAULT_MALE_PROFILE_PIC');
                $defaultImageExtension = env('DEFAULT_MALE_PROFILE_PIC_Ext');
            }

            $userDetails['ProfilePicture']['Id'] = 0;
            $userDetails['ProfilePicture']['Path'] = $baseUrl . '' . $defaultProfilePicAPIPrefix . $defaultImageName . '' . $defaultImageExtension;
            $userDetails['ProfilePicture']['FileOriginalName'] = $defaultImageName;
            $userDetails['ProfilePicture']['FileName'] = $defaultImageName;
            $userDetails['ProfilePicture']['FileExtension'] = $defaultImageExtension;;
        }

        if ($userDetails != null) {
            return response()->json(['data' => $userDetails, 'message' => 'User detail fetched successfully'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'User detail not found'], 200);
        }
    }

    function UserRegistration(Request $request)
    {
        error_log('In controller');

        $emailAddress = $request->post('EmailAddress');
        //First get and check if email record exists or not
        $checkEmail = UserModel::isDuplicateEmail($emailAddress);

        error_log('Checking email bit' . $checkEmail);

        if (count($checkEmail) > 0) {
            return response()->json(['data' => null, 'message' => 'Email already exists'], 400);
        }

        $defaultPassword = getenv("DEFAULT_PWD");

        //Binding data to variable.
        $firstName = $request->get('FirstName');
        $lastName = $request->get('LastName');
        $mobileNumber = $request->get('MobileNumber');
        $countryPhoneCode = $request->get('CountryPhoneCode');
        $telephoneNumber = $request->get('TelephoneNumber');
        $officeAddress = $request->get('OfficeAddress');
        $residentialAddress = $request->get('ResidentialAddress');
        $gender = $request->get('Gender');
        $functionalTitle = $request->get('FunctionalTitle');
        $age = $request->get('Age');
        $ageGroup = $request->get('AgeGroup');
        $hashedPassword = md5($defaultPassword);
        $roleCode = $request->get('RoleCode');

        $roleCode = UserModel::getRoleViaRoleCode($roleCode);

        if (count($roleCode) == 0) {
            DB::rollback();
            return response()->json(['data' => null, 'message' => 'Role not found'], 400);
        }
        $roleId = $roleCode[0]->Id;
        $roleName = $roleCode[0]->Name;

        error_log('$roleId' . $roleId);

        $dataToInsert = array(
            "EmailAddress" => $emailAddress,
            "FirstName" => $firstName,
            "LastName" => $lastName,
            "CountryPhoneCode" => $countryPhoneCode,
            "MobileNumber" => $mobileNumber,
            "TelephoneNumber" => $telephoneNumber,
            "OfficeAddress" => $officeAddress,
            "ResidentialAddress" => $residentialAddress,
            "Password" => $hashedPassword,
            "Gender" => $gender,
            "FunctionalTitle" => $functionalTitle,
            "Age" => $age,
            "AgeGroup" => $ageGroup,
            "IsActive" => true
        );

        DB::beginTransaction();

        $insertedRecord = GenericModel::insertGenericAndReturnID('user', $dataToInsert);
        error_log('Inserted record id ' . $insertedRecord);

        if ($insertedRecord == 0) {
            DB::rollback();
            return response()->json(['data' => null, 'message' => 'Error in user registration'], 400);
        }

        //Now making data for user_access
        $userAccessData = array(
            "UserId" => $insertedRecord,
            "RoleId" => $roleId,
            "IsActive" => true
        );

        $insertUserAccessRecord = GenericModel::insertGenericAndReturnID('user_access', $userAccessData);

        $emailMessage = "Welcome, You are successfully registered to CCM as .' '. $roleName.' '., use this password to login .' '. $defaultPassword";

        if ($insertUserAccessRecord == 0) {
            DB::rollback();
            return response()->json(['data' => null, 'message' => 'Error in user assigning role'], 400);
        } else {
            DB::commit();
            //Now sending email

            //create email with template
            $emailBody = "<p><h3>Hi</h3>$emailAddress<br><br>Welcome to the Chronic Care Management system. You are registered as a " . $roleName . " into the connectcareplus. The connectcareplus is a one stop solution health solution. Please take some time and log into the portal with your registered id " . env('WEB_URL') . "<br><br>" .
                "use this Password " . $defaultPassword . " to Login<br></p><br>";
            UserModel::sendEmailWithTemplateTwo($emailAddress, "Welcome to CCM", $emailBody);

            //Now sending sms
            if ($mobileNumber != null) {
                $url = env('WEB_URL') . '/#/';
                $toNumber = array();
                $mobileNumber = $countryPhoneCode . $mobileNumber;
                array_push($toNumber, $mobileNumber);
                try {
                    HelperModel::sendSms($toNumber, 'Welcome, You are successfully registered to CCM as "' . $roleName . '", use this password to login ' . $defaultPassword, $url);
                } catch (Exception $ex) {
                    return response()->json(['data' => $insertedRecord, 'message' => 'User successfully registered. ' . $ex], 200);
                }
            }
            return response()->json(['data' => $insertedRecord, 'message' => 'User successfully registered'], 200);
        }
    }

    function UserDelete(Request $request)
    {
        error_log('in controller');
        $id = $request->get('id');
        //First get and check if record exists or not
        //$getUser = UserModel::GetSingleUserViaIdNewFunction($id);

        //verifying the provided sourceUserId is of super admin or not
        //as we have to stop the super admin
        //to delete if num of super admin is 1 only
        $roleData = UserModel::GetRoleNameViaUserId($id);
        if (count($roleData) > 0) {
            error_log("Role Code exist");
            if ($roleData[0]->CodeName == env('ROLE_SUPER_ADMIN')) {
                error_log("User Role is " . $roleData[0]->CodeName);
                $userCount = UserModel::GetUserCountCountViaRoleCode($roleData[0]->CodeName);
                if ($userCount <= 1) {
                    error_log("Super Admin count is less than equals to 1");
                    return response()->json(['data' => null, 'message' => 'Not Allowed, There should be at-least 1 Super Admin user'], 400);
                } else {
                    error_log("Super Admin count is more than 1");
                    //Binding data to variable.
                    $dataToUpdate = array(
                        "IsActive" => false
                    );

                    $update = GenericModel::updateGeneric('user', 'Id', $id, $dataToUpdate);

                    error_log($update);

                    if ($update == 1) {

                        //create email with template
                        $emailBody = "<p style='width: 800px;'>Dear " . $roleData[0]->FirstName . " " . $roleData[0]->LastName . "<br>" .
                            "<br>Your account is deleted form the Chronic Care Management system<br><br>" .
                            "The message is for information purpose only. If you have any concerns please contact with the provider.</p>";

                        UserModel::sendEmailWithTemplateTwo($roleData[0]->EmailAddress, "User Delete", $emailBody);

                        error_log("Super Admin deleted successfully");
                        return response()->json(['data' => $id, 'message' => 'Deleted successfully'], 200);
                    } else if ($update == 0) {
                        error_log("Super Admin already deleted");
                        return response()->json(['data' => null, 'message' => 'Already deleted'], 400);
                    } else if ($update > 1) {
                        error_log("Super Admin fails to delete");
                        return response()->json(['data' => null, 'message' => 'Error in deleting'], 500);
                    }
                }
            } else if ($roleData[0]->CodeName == env('ROLE_FACILITATOR') || $roleData[0]->CodeName == env('ROLE_PATIENT')) {
                error_log("User Role is " . $roleData[0]->CodeName);
                $getUser = UserModel::GetSingleUserViaIdNewFunction($id);

                if ($getUser == null) {
                    return response()->json(['data' => null, 'message' => 'User not found'], 400);
                }
                //Binding data to variable.
                $dataToUpdate = array(
                    "IsActive" => false
                );

                $update = GenericModel::updateGeneric('user', 'Id', $id, $dataToUpdate);

                //now delete the account_invitation
                //of this email

                GenericModel::updateGeneric('account_invitation', 'ToEmailAddress', $getUser->EmailAddress, $dataToUpdate);
                GenericModel::updateGeneric('user_association', 'DestinationUserId', $id, $dataToUpdate);

                error_log($update);

                if ($update == 1) {

                    //create email with template
                    $emailBody = "<p style='width: 800px;'>Dear " . $roleData[0]->FirstName . " " . $roleData[0]->LastName . "<br>" .
                        "<br>Your account is deleted form the Chronic Care Management system<br><br>" .
                        "The message is for information purpose only. If you have any concerns please contact with the provider.</p>";

                    UserModel::sendEmailWithTemplateTwo($roleData[0]->EmailAddress, "User Delete", $emailBody);

                    return response()->json(['data' => $id, 'message' => 'Deleted successfully'], 200);
                } else if ($update == 0) {
                    return response()->json(['data' => null, 'message' => 'Already deleted'], 400);
                } else if ($update > 1) {
                    return response()->json(['data' => null, 'message' => 'Error in deleting'], 500);
                }
            } else if ($roleData[0]->CodeName == env('ROLE_DOCTOR')) {
                error_log("User Role is " . $roleData[0]->CodeName);
                $getUser = UserModel::GetSingleUserViaIdNewFunction($id);

                if ($getUser == null) {
                    return response()->json(['data' => null, 'message' => 'User not found'], 400);
                }
                //Binding data to variable.
                $dataToUpdate = array(
                    "IsActive" => false
                );

                $update = GenericModel::updateGeneric('user', 'Id', $id, $dataToUpdate);

                //now delete the account_invitation
                //of this email

                GenericModel::updateGeneric('account_invitation', 'ToEmailAddress', $getUser->EmailAddress, $dataToUpdate);

                error_log($update);

                if ($update == 1) {

                    //create email with template
                    $emailBody = "<p style='width: 800px;'>Dear " . $roleData[0]->FirstName . " " . $roleData[0]->LastName . "<br>" .
                        "<br>Your account is deleted form the Chronic Care Management system<br><br>" .
                        "The message is for information purpose only. If you have any concerns please contact with the provider.</p>";

                    UserModel::sendEmailWithTemplateTwo($roleData[0]->EmailAddress, "User Delete", $emailBody);

                    return response()->json(['data' => $id, 'message' => 'Deleted successfully'], 200);
                } else if ($update == 0) {
                    return response()->json(['data' => null, 'message' => 'Already deleted'], 400);
                } else if ($update > 1) {
                    return response()->json(['data' => null, 'message' => 'Error in deleting'], 500);
                }
            } else {
                //means role is Support Staff
                error_log("User Role is " . $roleData[0]->CodeName);
                $getUser = UserModel::GetSingleUserViaIdNewFunction($id);

                if ($getUser == null) {
                    return response()->json(['data' => null, 'message' => 'User not found'], 400);
                }
                //Binding data to variable.
                $dataToUpdate = array(
                    "IsActive" => false
                );

                $update = GenericModel::updateGeneric('user', 'Id', $id, $dataToUpdate);

                error_log($update);

                if ($update == 1) {

                    //create email with template
                    $emailBody = "<p style='width: 800px;'>Dear " . $roleData[0]->FirstName . " " . $roleData[0]->LastName . "<br>" .
                        "<br>Your account is deleted form the Chronic Care Management system<br><br>" .
                        "The message is for information purpose only. If you have any concerns please contact with the provider.</p>";

                    UserModel::sendEmailWithTemplateTwo($roleData[0]->EmailAddress, "User Delete", $emailBody);

                    return response()->json(['data' => $id, 'message' => 'Deleted successfully'], 200);
                } else if ($update == 0) {
                    return response()->json(['data' => null, 'message' => 'Already deleted'], 400);
                } else if ($update > 1) {
                    return response()->json(['data' => null, 'message' => 'Error in deleting'], 500);
                }
            }
        } else {
            error_log("Role Code not exist");
            return response()->json(['data' => null, 'message' => 'Invalid User'], 400);
        }
    }

    function SuperAdminDashboard()
    {
        error_log('in controller');

        $superAdminRole = env('ROLE_SUPER_ADMIN');
        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $supportStaffRole = env('ROLE_SUPPORT_STAFF');
        $patientRole = env('ROLE_PATIENT');

        $superAdminCount = UserModel::getUserCountViaRoleCode($superAdminRole);
        $doctorCount = UserModel::getUserCountViaRoleCode($doctorRole);
        $facilitatorCount = UserModel::getUserCountViaRoleCode($facilitatorRole);
        $supperStaffCount = UserModel::getUserCountViaRoleCode($supportStaffRole);
        $patientCount = UserModel::getUserCountViaRoleCode($patientRole);

        $data = array(
            "SuperAdmin" => $superAdminCount,
            "Doctor" => $doctorCount,
            "Facilitator" => $facilitatorCount,
            "SupportStaff" => $supperStaffCount,
            "Patient" => $patientCount
        );

        $patientIds = array();
        $doctorIds = array();
        $facilitatorIds = array();

        //Now get last 10 patients logged in history
        //First of all get users via role code
        $getPatientUsers = UserModel::GetUserViaRoleCode($patientRole);
        if (count($getPatientUsers) > 0) {
            foreach ($getPatientUsers as $item) {
                array_push($patientIds, $item->Id);
            }
            //Now we will fetch login history of patients
            //Last 5 logins history
            $list = LoginModel::FetchLastLoginHistoryList($patientIds, 10);
            if (count($list) > 0) {
                $loginHistory = array();
                foreach ($list as $item) {
                    $itemArray = array(
                        'Id' => $item->Id,
                        'FirstName' => $item->FirstName,
                        'LastName' => $item->LastName,
                        'EmailAddress' => $item->EmailAddress,
                        'MiddleName' => $item->MiddleName,
                        'LastLoggedIn' => $item->LastLoggedIn,
                        'IsCurrentlyLoggedIn' => $item->IsCurrentlyLoggedIn,
                        'LoginHistoryId' => $item->LoginHistoryId,
                        'LoginDateTime' => date('d-M-Y h:m a', $item->LoginDateTime)
                    );
                    array_push($loginHistory, $itemArray);
                }
                $data['LastPatientLoggedInHistory'] = $loginHistory;
            } else {
                $data['LastPatientLoggedInHistory'] = null;
            }
        } else {
            $data['LastPatientLoggedIn'] = null;
        }

        //Now get last 10 doctor logged in history
        //First of all get users via role code
        $getDoctorUsers = UserModel::GetUserViaRoleCode($doctorRole);
        if (count($getDoctorUsers) > 0) {
            foreach ($getDoctorUsers as $item) {
                array_push($doctorIds, $item->Id);
            }
            //Now we will fetch login history of patients
            //Last 5 logins history
            $list = LoginModel::FetchLastLoginHistoryList($doctorIds, 10);
            if (count($list) > 0) {
                $loginHistory = array();
                foreach ($list as $item) {
                    $itemArray = array(
                        'Id' => $item->Id,
                        'FirstName' => $item->FirstName,
                        'LastName' => $item->LastName,
                        'EmailAddress' => $item->EmailAddress,
                        'MiddleName' => $item->MiddleName,
                        'LastLoggedIn' => $item->LastLoggedIn,
                        'IsCurrentlyLoggedIn' => $item->IsCurrentlyLoggedIn,
                        'LoginHistoryId' => $item->LoginHistoryId,
                        'LoginDateTime' => date('d-M-Y h:m a', $item->LoginDateTime)
                    );
                    array_push($loginHistory, $itemArray);
                }
                $data['LastDoctorLoggedInHistory'] = $loginHistory;
            } else {
                $data['LastDoctorLoggedInHistory'] = null;
            }
        } else {
            $data['LastDoctorLoggedInHistory'] = null;
        }

        //Now get last 10 facilitator logged in history
        //First of all get users via role code
        $getFacilitatorUsers = UserModel::GetUserViaRoleCode($facilitatorRole);
        if (count($getFacilitatorUsers) > 0) {
            foreach ($getFacilitatorUsers as $item) {
                array_push($facilitatorIds, $item->Id);
            }
            //Now we will fetch login history of patients
            //Last 5 logins history
            $list = LoginModel::FetchLastLoginHistoryList($facilitatorIds, 10);
            if (count($list) > 0) {
                $loginHistory = array();
                foreach ($list as $item) {
                    $itemArray = array(
                        'Id' => $item->Id,
                        'FirstName' => $item->FirstName,
                        'LastName' => $item->LastName,
                        'EmailAddress' => $item->EmailAddress,
                        'MiddleName' => $item->MiddleName,
                        'LastLoggedIn' => $item->LastLoggedIn,
                        'IsCurrentlyLoggedIn' => $item->IsCurrentlyLoggedIn,
                        'LoginHistoryId' => $item->LoginHistoryId,
                        'LoginDateTime' => date('d-M-Y h:m a', $item->LoginDateTime)
                    );
                    array_push($loginHistory, $itemArray);
                }
                $data['LastFacilitatorLoggedInHistory'] = $loginHistory;
            } else {
                $data['LastFacilitatorLoggedInHistory'] = null;
            }
        } else {
            $data['LastFacilitatorLoggedInHistory'] = null;
        }

        //Now get active ccm plans till yet
        //Now fetching active CCM plans
        $currentTime = Carbon::now("UTC");
        error_log('$currentTime ' . $currentTime);
        $getActiveCcmPlansCount = CcmModel::getActiveCcmPlansViaPatientIds(null, $currentTime);
        $data['ActiveCcmPlan'] = $getActiveCcmPlansCount;

        return response()->json(['data' => $data, 'message' => 'Super admin dashboard stats'], 200);
    }

    function DoctorDashboard(Request $request)
    {
        error_log('in controller');

        $doctorId = (int)$request->get('userId');

        if ($doctorId == "null" || $doctorId == 0) {
            return response()->json(['data' => null, 'message' => 'Doctor id should not be null'], 400);
        }

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');
        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $appointmentRequestPending = env('APPOINTMENT_PENDING_REQUEST_STATUS');
        $invitationPending = env('INVITATION_PENDING');

        $getRegisteredPatientsCount = 0;
        $getAssociatedPatientsIds = array();

        $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($doctorId, $doctorPatientAssociation);
        error_log('$getAssociatedPatients are ' . $getAssociatedPatients);
        if (count($getAssociatedPatients) > 0) {
            //Means associated patients are there
            foreach ($getAssociatedPatients as $item) {
                array_push($getAssociatedPatientsIds, $item->DestinationUserId);
            };
            //Getting all registered user
            $getRegisteredPatientsCount = ReportModel::getMultipleUsersCount($getAssociatedPatientsIds, "null", "null");
            $data['RegisteredPatient'] = $getRegisteredPatientsCount;
        } else {
            $data['RegisteredPatient'] = $getRegisteredPatientsCount;
        }

        //Now getting patients count who are in pending
        $getPendingInvitationData = ReportModel::getUsersInvitationViaInvitedType($doctorId, $invitationPending, "null", "null", $doctorPatientAssociation);
        $data['PendingInvitation'] = count($getPendingInvitationData);

        //Now fetching associated facilitator
        $getAssociatedFacilitator = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($doctorId, $doctorFacilitatorAssociation);
        $data['AssociatedFacilitator'] = count($getAssociatedFacilitator);

        //Now fetching active CCM plans
        $currentTime = Carbon::now("UTC");
        error_log('$currentTime ' . $currentTime);
        $getActiveCcmPlansCount = CcmModel::getActiveCcmPlansViaPatientIds($getAssociatedPatientsIds, $currentTime);
        $data['ActiveCcmPlan'] = $getActiveCcmPlansCount;

        //Now getting pending appointments
        $getPendingAppointmentCount = DoctorScheduleModel::getMultipleAppointmentsCountViaDoctorAndPatientId($doctorId, $appointmentRequestPending, $getAssociatedPatientsIds);
        $data['PendingAppointment'] = $getPendingAppointmentCount;

        $userIds = array();
        array_push($userIds, $doctorId);

        //Last 5 logins history
        $list = LoginModel::FetchLastLoginHistoryList($userIds, 5);
        if (count($list) > 0) {
            $loginHistory = array();
            foreach ($list as $item) {
                $itemArray = array(
                    'Id' => $item->Id,
                    'FirstName' => $item->FirstName,
                    'LastName' => $item->LastName,
                    'EmailAddress' => $item->EmailAddress,
                    'MiddleName' => $item->MiddleName,
                    'LastLoggedIn' => $item->LastLoggedIn,
                    'IsCurrentlyLoggedIn' => $item->IsCurrentlyLoggedIn,
                    'LoginHistoryId' => $item->LoginHistoryId,
                    'LoginDateTime' => date('d-M-Y h:m a', $item->LoginDateTime)
                );
                array_push($loginHistory, $itemArray);
            }
            $data['DoctorLoggedInHistory'] = $loginHistory;
        } else {
            $data['DoctorLoggedInHistory'] = null;
        }

        // "2019-09-09"

        //Now getting upcoming appointments
        $getUpcomingAppointment = DoctorScheduleModel::getUpcomingAppointmentViaDoctorId($doctorId, $currentTime);
        $data['UpcomingAppointment'] = $getUpcomingAppointment;

        return response()->json(['data' => $data, 'message' => 'Doctor dashboard stats'], 200);
    }

    function FacilitatorDashboard(Request $request)
    {
        error_log('in controller');

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');
        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');

        $userId = (int)$request->get('userId');

        if ($userId == "null" || $userId == 0) {
            return response()->json(['data' => null, 'message' => 'Facilitator id should not be null'], 400);
        }

        $superAdminRole = env('ROLE_SUPER_ADMIN');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $supportStaffRole = env('ROLE_SUPPORT_STAFF');
        $patientRole = env('ROLE_PATIENT');

        $doctorRole = env('ROLE_DOCTOR');
        $userData = UserModel::GetSingleUserViaId($userId);

        if (count($userData) > 0) {
            error_log('user data fetched');
            if ($userData[0]->RoleCodeName != $facilitatorRole) {
                error_log('login user is not facilitator');
                //Now check if logged in user is doctor or not
                return response()->json(['data' => null, 'message' => 'logged in user must be a facilitator'], 400);
            }
        }

        //Fetching Active CCM pLans
        //First get associated doctors id with respect to facilitator Id
        $getAssociatedDoctorsId = UserModel::getSourceIdViaLoggedInUserIdAndAssociationType($userId, $doctorFacilitatorAssociation);

        if (count($getAssociatedDoctorsId) > 0) {
            error_log('doctors associated');
            $data['AssociatedDoctor'] = count($getAssociatedDoctorsId);
            $doctorIds = array();
            foreach ($getAssociatedDoctorsId as $item) {
                array_push($doctorIds, $item->SourceUserId);
            }
            //Now fetch associated patients of that doctor
            $getAssociatedPatientIds = UserModel::getAssociatedPatientsUserId($doctorIds, $doctorPatientAssociation);
            if (count($getAssociatedPatientIds) > 0) {
                $patientIds = array();
                foreach ($getAssociatedPatientIds as $item) {
                    array_push($patientIds, $item->DestinationUserId);
                }
                //Now get active ccm plans till yet
                //Now fetching active CCM plans
                $currentTime = Carbon::now("UTC");
                error_log('$currentTime ' . $currentTime);
                $getActiveCcmPlansCount = CcmModel::getActiveCcmPlansViaPatientIds($patientIds, $currentTime);
                error_log('$getActiveCcmPlansCount ' . $getActiveCcmPlansCount);

                $data['ActiveCcmPlan'] = $getActiveCcmPlansCount;

            } else {
                $data['ActiveCcmPlan'] = 0;
            }
        } else {
            $data['ActiveCcmPlan'] = 0;
            $data['AssociatedDoctor'] = 0;
        }

        $facilitatorIds = array();
        array_push($facilitatorIds, $userId);
        //Now we will fetch login history of patients
        //Last 5 logins history
        $list = LoginModel::FetchLastLoginHistoryList($facilitatorIds, 5);
        if (count($list) > 0) {
            $loginHistory = array();
            foreach ($list as $item) {
                $itemArray = array(
                    'Id' => $item->Id,
                    'FirstName' => $item->FirstName,
                    'LastName' => $item->LastName,
                    'EmailAddress' => $item->EmailAddress,
                    'MiddleName' => $item->MiddleName,
                    'LastLoggedIn' => $item->LastLoggedIn,
                    'IsCurrentlyLoggedIn' => $item->IsCurrentlyLoggedIn,
                    'LoginHistoryId' => $item->LoginHistoryId,
                    'LoginDateTime' => date('d-M-Y h:m a', $item->LoginDateTime)
                );
                array_push($loginHistory, $itemArray);
            }
            $data['LoggedInHistory'] = $loginHistory;
        } else {
            $data['LoggedInHistory'] = null;
        }

        //Getting total reviews given by this facilitator
        $totalReviews = CcmModel::GetTotalReviews($userId);
        $data['TotalReviewsOnGoals'] = $totalReviews;

        return response()->json(['data' => $data, 'message' => 'Facilitator dashboard stats'], 200);
    }

    function PatientDashboard(Request $request)
    {
        error_log('in controller');

        $userId = (int)$request->get('userId');

        if ($userId == "null" || $userId == 0) {
            return response()->json(['data' => null, 'message' => 'Patient id should not be null'], 400);
        }

        $superAdminRole = env('ROLE_SUPER_ADMIN');
        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $supportStaffRole = env('ROLE_SUPPORT_STAFF');
        $patientRole = env('ROLE_PATIENT');

        $openTrackStatus = env('TICKET_TRACK_STATUS_OPEN');

        $userData = UserModel::GetSingleUserViaId($userId);

        if (count($userData) > 0) {
            error_log('user data fetched');
            if ($userData[0]->RoleCodeName != $patientRole) {
                error_log('login user is not patient');
                //Now check if logged in user is doctor or not
                return response()->json(['data' => null, 'message' => 'logged in user must be patient'], 400);
            }
        }

        $patientIds = array();
        array_push($patientIds, $userId);

        //Now get active ccm plans till yet
        //Now fetching active CCM plans
        $currentTime = Carbon::now("UTC");
        error_log('$currentTime ' . $currentTime);
        $getActiveCcmPlansCount = CcmModel::getActiveCcmPlansViaPatientIds($patientIds, $currentTime);
        error_log('$getActiveCcmPlansCount ' . $getActiveCcmPlansCount);

        $data['ActiveCcmPlan'] = $getActiveCcmPlansCount;

        //Now we will fetch login history of patients
        //Last 5 logins history
        $list = LoginModel::FetchLastLoginHistoryList($patientIds, 5);
        if (count($list) > 0) {
            $loginHistory = array();
            foreach ($list as $item) {
                $itemArray = array(
                    'Id' => $item->Id,
                    'FirstName' => $item->FirstName,
                    'LastName' => $item->LastName,
                    'EmailAddress' => $item->EmailAddress,
                    'MiddleName' => $item->MiddleName,
                    'LastLoggedIn' => $item->LastLoggedIn,
                    'IsCurrentlyLoggedIn' => $item->IsCurrentlyLoggedIn,
                    'LoginHistoryId' => $item->LoginHistoryId,
                    'LoginDateTime' => date('d-M-Y h:m a', $item->LoginDateTime)
                );
                array_push($loginHistory, $itemArray);
            }
            $data['LoggedInHistory'] = $loginHistory;
        } else {
            $data['LoggedInHistory'] = null;
        }

        //Now fetching Open tickets
        $getOpenTickets = TicketModel::GetTicketCountViaStatus($userId, $openTrackStatus);
        $data['OpenTicket'] = $getOpenTickets;

        //Now getting upcoming appointments
        $getUpcomingAppointment = DoctorScheduleModel::getUpcomingAppointmentViaPatientId($userId, $currentTime);
        $data['UpcomingAppointment'] = $getUpcomingAppointment;

        return response()->json(['data' => $data, 'message' => 'Patient dashboard stats'], 200);
    }

    function SupportStaffDashboard(Request $request)
    {
        error_log('in controller');

        $userId = (int)$request->get('userId');

        if ($userId == "null" || $userId == 0) {
            return response()->json(['data' => null, 'message' => 'Support staff id should not be null'], 400);
        }

        $superAdminRole = env('ROLE_SUPER_ADMIN');
        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $supportStaffRole = env('ROLE_SUPPORT_STAFF');
        $patientRole = env('ROLE_PATIENT');

        $userData = UserModel::GetSingleUserViaId($userId);

        if (count($userData) > 0) {
            error_log('user data fetched');
            if ($userData[0]->RoleCodeName != $supportStaffRole) {
                error_log('login user is not support staff');
                //Now check if logged in user is doctor or not
                return response()->json(['data' => null, 'message' => 'logged in user must be from support staff'], 400);
            }
        }

        //Now getting tickets created by this sipport staff person
        $getCreatedTickets = TicketModel::GetTicketCreatedBy($userId);
        //Now getting how many tickets he has responded to
        $getTicketsRespondedTo = TicketModel::GetTicketRepliedBy($userId);
        //Now getting closed tickets
        $getTicketsClosedBy = TicketModel::GetTicketClosedBy($userId);

        $data = array(
            "TicketCreated" => $getCreatedTickets,
            "TicketResponded" => $getTicketsRespondedTo,
            "TicketClosed" => $getTicketsClosedBy
        );

        return response()->json(['data' => $data, 'message' => 'Support staff dashboard stats'], 200);
    }

    function GetUserInvitationListWithPaginationAndSearch(Request $request)
    {
        error_log('In controller');

        $pageNo = $request->get('p');
        $limit = $request->get('c');
        $searchKeyword = $request->get('s');

        $data = UserModel::getUserInvitationLink($pageNo, $limit, $searchKeyword);

        error_log('Count of data is : ' . count($data));

        if (count($data) > 0) {
            return response()->json(['data' => $data, 'message' => 'User invitation list found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'User invitation list not found'], 200);
        }
    }

    function GetUserInvitationListCount(Request $request)
    {
        error_log('In controller');

        $searchKeyword = $request->get('s');

        $data = UserModel::getUserInvitationLinkCount($searchKeyword);

        return response()->json(['data' => $data, 'message' => 'User invitation count'], 200);
    }

    function GetUserInvitationListForDoctorWithPaginationAndSearch(Request $request)
    {
        error_log('In controller');

        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');
        $searchKeyword = $request->get('search');
        $doctorId = (int)$request->get('doctorId');

        //First check if logged in user is doctor
        $doctorRole = env('ROLE_DOCTOR');
        $userData = UserModel::GetSingleUserViaId($doctorId);
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        if (count($userData) > 0) {
            error_log('user data fetched');
            if ($userData[0]->RoleCodeName != $doctorRole) {
                error_log('login user is not doctor');
                //Now check if logged in user is doctor or not
                return response()->json(['data' => null, 'message' => 'logged in user must be a doctor'], 400);
            }
        }

        $allInvitedPatientsData = UserModel::getUserInvitationListViaDoctorId($doctorId, $pageNo, $limit, $searchKeyword, $doctorPatientAssociation);

        if (count($allInvitedPatientsData) > 0) {

            $userData = array();

            foreach ($allInvitedPatientsData as $item) {

                error_log("Timestamp");
                error_log(Carbon::createFromDate($item->CreatedOn));

                $data = array(
                    'Id' => (int)$item->Id,
                    'ToEmailAddress' => $item->ToEmailAddress,
                    'CountryPhoneCode' => $item->CountryPhoneCode,
                    'ToMobileNumber' => $item->ToMobileNumber,
                    'InvitedStatus' => $item->Status_,
                    'InvitedOn' => (string)Carbon::createFromTimestampUTC($item->CreatedOn),
                    'InvitationLink' => env('WEB_URL') . '' . env('FRONT_END_PAGE') . '?token=' . $item->Token
                );
                array_push($userData, $data);
            }
            return response()->json(['data' => $userData, 'message' => 'User invitation list found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'User invitation list not found'], 200);
        }
    }

    function GetUserInvitationListCountForDoctor(Request $request)
    {
        error_log('In controller');

        $searchKeyword = $request->get('search');
        $doctorId = (int)$request->get('doctorId');

        //First check if logged in user is doctor
        $doctorRole = env('ROLE_DOCTOR');
        $userData = UserModel::GetSingleUserViaId($doctorId);
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        if (count($userData) > 0) {
            error_log('user data fetched');
            if ($userData[0]->RoleCodeName != $doctorRole) {
                error_log('login user is not doctor');
                //Now check if logged in user is doctor or not
                return response()->json(['data' => null, 'message' => 'logged in user must be a doctor'], 400);
            }
        }

        $data = UserModel::getUserInvitationListCountViaDoctorId($doctorId, $searchKeyword, $doctorPatientAssociation);

        return response()->json(['data' => $data, 'message' => 'User invitation count'], 200);
    }

    function UserBlock(Request $request)
    {
        error_log('in controller');
        $id = $request->get('id');

        //First get and check if record exists or not
        $data = UserModel::GetSingleUserViaId($id);

        if (count($data) == 0) {
            return response()->json(['data' => null, 'message' => 'User not found'], 400);
        }

        if ($data[0]->IsBlock == true) {
            return response()->json(['data' => null, 'message' => 'User is already blocked'], 400);
        }

        //Binding data to variable.

        $dataToUpdate = array(
            "IsBlock" => true,
            "BlockReason" => $request->get('BlockReason')
        );

        $update = GenericModel::updateGeneric('user', 'Id', $id, $dataToUpdate);

        if ($update == true) {
            return response()->json(['data' => $id, 'message' => 'User successfully blocked'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Error in blocking user'], 400);
        }
    }

    function UserUnblock(Request $request)
    {
        error_log('in controller');
        $id = $request->get('id');

        //First get and check if record exists or not
        $data = UserModel::GetSingleUserViaId($id);

        if (count($data) == 0) {
            return response()->json(['data' => null, 'message' => 'User not found'], 400);
        }

        if ($data[0]->IsBlock == false) {
            return response()->json(['data' => null, 'message' => 'User is already unblocked'], 400);
        }

        //Binding data to variable.

        $dataToUpdate = array(
            "IsBlock" => false
        );

        $update = GenericModel::updateGeneric('user', 'Id', $id, $dataToUpdate);

        if ($update == true) {
            return response()->json(['data' => $id, 'message' => 'User successfully unblocked'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Error in unblocking user'], 400);
        }
    }


    function PermissionViaRoleId(Request $request)
    {
        error_log('in controller');

        $roleId = $request->get('RoleId');

        $result = UserModel::getPermissionViaRoleId($roleId);
        if (count($result) > 0) {
            return response()->json(['data' => $result, 'message' => 'Permission successfully fetched'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Permission not found'], 400);
        }
    }

    function PermissionViaUserId(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('UserId');

        $data = UserModel::GetUserRoleViaUserId($userId);
        if (count($data) == 0) {
            return response()->json(['data' => null, 'message' => 'User has not yet assigned with any role'], 400);
        }
        $roleId = $data[0]->RoleId;

        error_log('$roleId' . $roleId);

        $result = UserModel::getPermissionViaRoleId($roleId);

        if (count($result) > 0) {
            return response()->json(['data' => $result, 'message' => 'Permission successfully fetched'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Permission not found'], 400);
        }
    }


    function AssociateFacilitatorsWithDoctor(Request $request)
    {
        error_log('In controller');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorRole = env('ROLE_DOCTOR');

        $doctorId = $request->DoctorId;
        $facilitators = $request->Facilitator;

        //First check if this doctor is belonging to role doctor or not
        $doctorsData = UserModel::GetSingleUserViaId($doctorId);
        if (count($doctorsData) == 0) {
            return response()->json(['data' => null, 'message' => 'User not found'], 400);
        } else {
            if ($doctorsData[0]->RoleCodeName != $doctorRole) {
                return response()->json(['data' => null, 'message' => 'Logged in user is not doctor'], 400);
            }
        }

        DB::beginTransaction();

        //First get the record of role permission with respect to that given role id
        $checkDoctorFacilitator = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($doctorId, $doctorFacilitatorAssociation);
        error_log('$checkDoctorFacilitator ' . $checkDoctorFacilitator);
        //Now check the permission if it exists
        if (count($checkDoctorFacilitator) > 0) {
            //then delete it from role_permission
            $result = UserModel::deleteAssociatedFacilitators($doctorId, $doctorFacilitatorAssociation);
            if ($result == false) {
                DB::rollBack();
            }
        }
        $userIds = array();
        $data = array();

        foreach ($facilitators as $item) {
            array_push
            (
                $data,
                array(
                    "SourceUserId" => $doctorId,
                    "DestinationUserId" => $item['Id'],
                    "AssociationType" => $doctorFacilitatorAssociation,
                    "IsActive" => true
                )
            );

            array_push($userIds, $item['Id']);
        }

        //Now get all facilitator email address
        //And then shoot email to them that they are now associated with XYZ dr.

        $getFacilitatorEmails = UserModel::getMultipleUsers($userIds);
        if (count($getFacilitatorEmails) == 0) {
            return response()->json(['data' => null, 'message' => 'Facilitator(s) not found'], 400);
        }


        //Now inserting data
        $checkInsertedData = GenericModel::insertGeneric('user_association', $data);
        error_log('$checkInsertedData ' . $checkInsertedData);

        if ($checkInsertedData == true) {
            DB::commit();

            $emailMessage = "You have been associated with Dr. " . $doctorsData[0]->FirstName . ".";

            error_log($emailMessage);

            error_log(count($getFacilitatorEmails));

            $toNumber = array();

            foreach ($getFacilitatorEmails as $item) {

                //pushing mobile number
                //in array for use in sending sms
                array_push($toNumber, $item->CountryPhoneCode . $item->MobileNumber);

                error_log('$item' . $item->EmailAddress);
                error_log('$item' . $item->MobileNumber);

//                Not required
//                UserModel::sendEmail($item->EmailAddress, $emailMessage, null);
            }

            ## Preparing Data for SMS  - START ##
            if (count($toNumber) > 0) {
                HelperModel::sendSms($toNumber, $emailMessage, null);
            }
            ## Preparing Data for SMS  - END ##

            return response()->json(['data' => $doctorId, 'message' => 'Facilitator(s) successfully associated'], 200);

        } else {
            DB::rollBack();
            return response()->json(['data' => null, 'message' => 'Error in associating facilitator(s)'], 400);
        }
    }

    function GetAssociateFacilitator(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('doctorId');
        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');

        $data = UserModel::GetUserRoleViaUserId($userId);
        if (count($data) == 0) {
            return response()->json(['data' => null, 'message' => 'User data not found'], 400);
        }

        $getAssociatedFacilitators = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($userId, $doctorFacilitatorAssociation);
        if (count($getAssociatedFacilitators) == 0) {
            return response()->json(['data' => null, 'message' => 'Facilitator not associated yet'], 400);
        } else {
            $getAssociatedFacilitatorIds = array();
            foreach ($getAssociatedFacilitators as $item) {
                array_push($getAssociatedFacilitatorIds, $item->DestinationUserId);
            }
            $getAssociatedFacilitatorData = UserModel::getMultipleUsers($getAssociatedFacilitatorIds);

            if (count($getAssociatedFacilitatorData) > 0) {
                return response()->json(['data' => $getAssociatedFacilitatorData, 'message' => 'Associated facilitators fetched successfully'], 200);
            } else {
                return response()->json(['data' => null, 'message' => 'No Facilitator associated with the Doctor'], 200);
            }
        }
    }

    function BulkUserRegister(Request $request)
    {
        error_log("### BULK REGISTER PATIENTS");

//        $this->validate($request, [
//            'file' => 'required|mimes:xls,xlsx,csv'
//        ]);
        $date = HelperModel::getDate();
        $createdById = $request->post('id');
        $type = $request->post('type');
        $path = $request->file('file')->getRealPath();
        $data = Excel::load($path)->get();

        $roleData = UserModel::GetRoleNameViaUserId($createdById);
        if (count($roleData) > 0) {
            $roleName = $roleData[0]->CodeName;
            $createdByEmail = $roleData[0]->EmailAddress;
            if ($roleName == env('ROLE_PATIENT') || $roleName == env('ROLE_SUPPORT_STAFF')) {
                return response()->json(['data' => null, 'message' => 'Not Allowed'], 400);
            }
        } else {
            return response()->json(['data' => null, 'message' => ' Not Allowed'], 400);
        }

        if ($type != env('ROLE_SUPER_ADMIN') && $type != env('ROLE_PATIENT') && $type != env('ROLE_DOCTOR') && $type != env('ROLE_FACILITATOR') && $type != env('ROLE_SUPPORT_STAFF')) {
            return response()->json(['data' => null, 'message' => 'Not Allowed'], 400);
        }

        if ($data->count() > 0) {
            error_log("count is greater than zero");

            try {
                foreach ($data->toArray() as $key => $value) {
                    if ($value['emailaddress'] != null && (string)$value['mobilenumber'] != null) {

                        error_log("check mobile number");
                        error_log((string)$value['mobilenumber']);

                        $insert_data[] = array(
                            'PatientUniqueId' => $value['patientuniqueid'],
                            'FirstName' => $value['firstname'],
                            'MiddleName' => $value['middlename'],
                            'LastName' => $value['lastname'],
                            'EmailAddress' => $value['emailaddress'],
                            'CountryPhoneCode' => $value['countryphonecode'],
                            'MobileNumber' => (string)$value['mobilenumber'],
                            'TelephoneNumber' => (string)($value['telephonenumber']),
                            'IsMobileNumberVerified' => true,
                            'OfficeAddress' => $value['officeaddress'],
                            'ResidentialAddress' => $value['residentialaddress'],
                            'Gender' => $value['gender'],
                            'Age' => $value['age'],
                            'CreatedBy' => $createdById,
                            'CreatedByEmail' => $createdByEmail,
                            'CreatedOn' => $date["timestamp"],
                            'IsActive' => true,
                            'ProfileSummary' => $value['profilesummary'],
                            'DateOfBirth' => $value['dateofbirth'],
                            'Role' => $type,
                            'CreatedByRole' => $roleName
                        );
                    }
                }
            } catch (Exception $ex) {
                error_log("Exception occur in add in temp bulk upload");
                error_log($ex);
                return response()->json(['data' => null, 'message' => 'Internal Server Error occurred'], 500);
            }
        }

        try {
            DB::table('temp_bulk_user')->insert($insert_data);
            return response()->json(['data' => null, 'message' => 'Bulk User file is successfully uploaded,background operation is in process once users are updated you will receive an email on your registered email address'], 200);
        } catch (\Illuminate\Database\QueryException $exception) {
            return response()->json(['data' => null, 'message' => "Email Address and Mobile Number should be unique"], 500);
        }

        //            Bulk-1
//                    One
//                     Person
//                     bulk.1@xyz.com
//                     92
//                    3122410823
//                    211234567
//                    XYZ Street
//                     ABC Street
//                     Male
//                     69
//                    2019-04-29 00:00:00
//                    Lorem Ipsum is simply dummy text of the printing
    }

    function BackgroundBulkUserRegister()
    {
        //first fetch record from temp table
        //if record exist move ahead
        //other wise stop here

        $tempUser = GenericModel::simpleFetchGenericByWhere('temp_bulk_user', '=', 'IsActive', true, null);
        $tempUserCount = count($tempUser);

        //log table variables
        $exception = "none";
        $createdBy = "";
        $uploadStatus = "success";
        $tempTableRecordDelete = "deleted";
        $emailSent = "yes";
        $related = "none";

        error_log('User Count');
        error_log($tempUserCount);

        if (count($tempUser) == 0) {
            return response()->json(['data' => null, 'message' => 'Data not exist'], 200);
        } else {
            //fetch all roles from table
            //to be use it in comparison within loop
            $deleteRecordFromTempTable = array();
            $registeredEmailAddress = array();
            $isUniqueEmail = true;
            $roleList = GenericModel::simpleFetchGenericByWhere('role', '=', 'IsActive', true, 'SortOrder');
            $existingUserList = GenericModel::simpleFetchGenericByWhere('user', '=', 'IsActive', true, null);

            try {

                for ($i = 0; $i < $tempUserCount; $i++) {

                    error_log("### ITERATION START ###");
                    $createdBy = $tempUser[$i]->CreatedBy;
                    $createdByEmail = $tempUser[$i]->CreatedByEmail;
                    $related = $tempUser[$i]->Role;

                    //first verifying the unique email address
                    //and mobile number as well in-case of patient

                    //verifying unique email address
                    error_log("verifying unique email address");

                    if (strtolower($tempUser[$i]->Role) == env('ROLE_PATIENT')) {
                        //Role is Patient
                        error_log("Role is Patient");

                        foreach ($existingUserList as $item) {
                            if ($item->EmailAddress == $tempUser[$i]->EmailAddress) {
                                //verifying unique email break
                                error_log('This email is already exist');
                                $isUniqueEmail = false;
                                break;
                            } else {
                                //verifying unique email continue
                                error_log('This email is not exist');
                            }

                            if ($item->MobileNumber == $tempUser[$i]->MobileNumber) {
                                //verifying unique mobile number break
                                error_log('This mobile number is already exist');
                                $isUniqueEmail = false;
                                break;
                            } else {
                                //verifying unique mobile number continue
                                error_log('This mobile number is not exist');
                            }

                            if ($item->PatientUniqueId == $tempUser[$i]->PatientUniqueId) {
                                //verifying unique Patient Unique Id number break
                                error_log('This Patient Unique Id number is already exist');
                                $isUniqueEmail = false;
                                break;
                            } else {
                                //verifying unique mobile number continue
                                error_log('This Patient Unique Id is not exist');
                            }
                        }

                    } else {
                        //Role is other an Patient
                        error_log("Role is other than Patient");

                        foreach ($existingUserList as $item) {
                            if ($item->EmailAddress == $tempUser[$i]->EmailAddress) {
                                //verifying unique email break
                                error_log('This email is already exist');
                                $isUniqueEmail = false;
                                break;
                            } else {
                                //verifying unique email continue
                                error_log('This email is not exist');
                            }
                        }
                    }

                    error_log($tempUser[$i]->MiddleName);
                    error_log($tempUser[$i]->LastName);
                    error_log($tempUser[$i]->EmailAddress);

                    $defaultPassword = md5(getenv("DEFAULT_PWD"));

                    error_log("isUniqueEmail");
                    error_log($isUniqueEmail);

                    if ($isUniqueEmail) {

                        $insertData = array(
                            'PatientUniqueId' => $tempUser[$i]->PatientUniqueId,
                            'FirstName' => $tempUser[$i]->FirstName,
                            'MiddleName' => $tempUser[$i]->MiddleName,
                            'LastName' => $tempUser[$i]->LastName,
                            'EmailAddress' => $tempUser[$i]->EmailAddress,
                            'CountryPhoneCode' => $tempUser[$i]->CountryPhoneCode,
                            'MobileNumber' => $tempUser[$i]->MobileNumber,
                            'IsMobileNumberVerified' => ($tempUser[$i]->MobileNumber == null || "" ? false : true),
                            'TelephoneNumber' => $tempUser[$i]->TelephoneNumber,
                            'OfficeAddress' => $tempUser[$i]->OfficeAddress,
                            'ResidentialAddress' => $tempUser[$i]->ResidentialAddress,
                            'Password' => $defaultPassword,
                            'Gender' => $tempUser[$i]->Gender,
                            'Age' => $tempUser[$i]->Age,
                            'AccountVerified' => true,
                            'CreatedBy' => $tempUser[$i]->CreatedBy,
                            'CreatedOn' => $tempUser[$i]->CreatedOn,
                            'IsActive' => true,
                            'ProfileSummary' => $tempUser[$i]->ProfileSummary,
                            'DateOfBirth' => $tempUser[$i]->DateOfBirth,
                            'IsBlock' => false,
                            'PatientUniqueId' => 1, //generate if role is patient
                            'IsCurrentlyLoggedIn' => false,
                        );
                        $insertedUserId = GenericModel::insertGenericAndReturnID('user', $insertData);
                        error_log($insertedUserId);

                        if ($insertedUserId == 0) {
                            error_log("Insert Id is zero");
                        } else {
                            error_log("Insert Id is :");

                            $newUserId = $insertedUserId;
                            $roleId = null;
                            foreach ($roleList as $key) {

                                if (strtolower($key->CodeName) == strtolower($tempUser[$i]->Role)) {
                                    $roleId = $key->Id;
                                    //finding role break
                                    error_log('finding role break');
                                    break;
                                } else {
                                    //finding role continue
                                    error_log('finding role continue');
                                }
                            }

                            //insert in user_access
                            $insertUserAccessData = array(
                                'RoleId' => $roleId,
                                'UserId' => $newUserId,
                                'IsActive' => 1
                            );
                            GenericModel::insertGenericAndReturnID('user_access', $insertUserAccessData);

                            //checking the possibility of
                            //User association

                            if (strtolower($tempUser[$i]->Role) == env('ROLE_SUPER_ADMIN') || strtolower($tempUser[$i]->Role) == env('ROLE_SUPPORT_STAFF') || strtolower($tempUser[$i]->Role) == env('ROLE_DOCTOR')) {
                                error_log("No Need to insert in user_association");
                            } else if (strtolower($tempUser[$i]->Role) == env('ROLE_PATIENT')) {
                                error_log("Insert in user_association now");

//                            ASSOCIATION_DOCTOR_PATIENT=doctor_patient
//                            ASSOCIATION_DOCTOR_FACILITATOR=doctor_facilitator

                                //insert in user_association
                                $insertUserAssociationData = array(
                                    'SourceUserId' => $tempUser[$i]->CreatedBy,
                                    'DestinationUserId' => $newUserId,
                                    'AssociationType' => env('ASSOCIATION_DOCTOR_PATIENT'),
                                    'IsActive' => 1
                                );
                                GenericModel::insertGenericAndReturnID('user_association', $insertUserAssociationData);

                            } else if (strtolower($tempUser[$i]->Role) == env('ROLE_FACILITATOR')) {
                                //insert in user_association
                                $insertUserAssociationData = array(
                                    'SourceUserId' => $tempUser[$i]->CreatedBy,
                                    'DestinationUserId' => $newUserId,
                                    'AssociationType' => env('ASSOCIATION_DOCTOR_FACILITATOR'),
                                    'IsActive' => 1
                                );
                                GenericModel::insertGenericAndReturnID('user_association', $insertUserAssociationData);
                            } else {
                                //none
                                return true;
                            }
                        }

                        array_push($registeredEmailAddress, $tempUser[$i]->EmailAddress);
                    }

                    array_push($deleteRecordFromTempTable, $tempUser[$i]->Id);

                    error_log("### ITERATION ENDS ### -- " . $i);

                    $isUniqueEmail = true;
                }

                //as import completes
                //delete the data

                error_log('Deleting Temp records from the table');

                DB::table('temp_bulk_user')->whereIn('id', $deleteRecordFromTempTable)->delete();

                error_log('Temp records are deleted');

                error_log("count registered email address");
                error_log(count($registeredEmailAddress));

                for ($j = 0; $j < count($registeredEmailAddress); $j++) {
                    error_log("## NOW Sending Email to newly Registered User ##");
                    //create email with template
                    $emailBody = "<p><h3>Hi</h3>$registeredEmailAddress[$j]<br><br>Welcome to the Chronic Care Management system. You are registered as a " . $tempUser[$j]->Role . " into the connectcareplus. The connectcareplus is a one stop solution health solution. Please take some time and log into the portal with your registered id " . env('WEB_URL') . "<br><br>" .
                        "use this Password " . getenv("DEFAULT_PWD") . " to Login<br></p><br>";
                    UserModel::sendEmailWithTemplateTwo($registeredEmailAddress[$j], "Welcome to CCM", $emailBody);
                }
                error_log("## Here Sending Success Email to Uploader ##");

                $currentDateTime = Carbon\Carbon::now();

                //fetch User data
                //to be use in sending email
                $userData = GenericModel::simpleFetchGenericByWhere("user", "=", "Id", $createdBy);

                //create email with template
                $emailBody = "<p style='width: 800px;'>Dear " . $userData[0]->FirstName . " " . $userData[0]->LastName . "<br>" .
                    "<br>The bulk upload made by you is successfully completed. All the records are successful uploaded. <br><br>" .
                    "<br>Upload Completion Date: " . $currentDateTime . "<br><br>" .
                    "<br>Upload Completion Time: 10 minutes</p>";

                UserModel::sendEmailWithTemplateTwo($userData[0]->EmailAddress, "Bulk Upload User Complete", $emailBody);

            } catch (Exception $ex) {
                $exception = $ex;
                $uploadStatus = "failed";
                $emailSent = "failed";
                $tempTableRecordDelete = "not required";

                error_log("## Here Sending Failure Email to Uploader ##");

                //fetch User data
                //to be use in sending email
                $userData = GenericModel::simpleFetchGenericByWhere("user", "=", "Id", $createdBy);

                //create email with template
                $emailBody = "<p style='width: 800px;'>Dear " . $userData[0]->FirstName . " " . $userData[0]->LastName . "<br>" .
                    "<br>We are sorry the bulk upload made by you is failed completed. Please try again. </p>";

                UserModel::sendEmailWithTemplateTwo($userData[0]->EmailAddress, "Bulk Upload User Complete", $emailBody);

                return response()->json(['data' => null, 'message' => 'Internal Server Error'], 500);
            }
        }

        $date = HelperModel::getDate();

        //Insert into log table now
        $insertData = array(
            'TotalRecord' => $tempUserCount,
            'Exception' => $exception,
            'UploadStatus' => $uploadStatus,
            'TempTableRecordDelete' => $tempTableRecordDelete,
            'EmailSent' => $emailSent,
            'UploadBy' => $createdBy,
            'UploadOn' => $date["timestamp"],
            'Related' => $related,
            'IsActive' => 1
        );
        $insertedUserId = GenericModel::insertGenericAndReturnID('bulk_upload_log', $insertData);
        if ($insertedUserId > 0) {
            error_log("## successfully created the log ##");
            return response()->json(['data' => null, 'message' => 'successfully created the log'], 200);
        } else {
            error_log("## failed to create the log ##");
            return response()->json(['data' => null, 'message' => 'failed to create log'], 500);
        }
    }
}
