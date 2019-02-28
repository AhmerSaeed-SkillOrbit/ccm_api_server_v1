<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use View;
use App\Models\UserModel;
use App\Models\GenericModel;
use App\Models\HelperModel;
use Config;
use Carbon\Carbon;

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

                $resultArray = json_decode(json_encode($val), true);
                $data = $resultArray;
                error_log(count($data));
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
                }
                else if ($roleCode == $facilitatorRole) {
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
                    ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $destinationIds);

                    $resultArray = json_decode(json_encode($val), true);

                    $data = $resultArray;

                    error_log(count($data));
                    if (count($data) > 0) {
                        return response()->json(['data' => $data, 'message' => 'Facilitators fetched successfully'], 200);
                    } else {
                        return response()->json(['data' => null, 'message' => 'Facilitators not found'], 200);
                    }
                }
                else if ($roleCode == $patientRole) {
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
                    ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $destinationIds);

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
            }
            else if ($userData[0]->RoleCodeName == $facilitatorRole)
            {
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
                    ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $doctorIds);

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
                    ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $patientIds);

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
            }
            else if ($userData[0]->RoleCodeName == $patientRole) {
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
                    ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $facilitatorIds);

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
                    ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $doctorIds);

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
            }
            else if ($userData[0]->RoleCodeName == $supportStaffRole) {
                error_log('logged in user role is support staff');
                if ($roleCode == $superAdminRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                } else {
                    if ($roleCode == null || $roleCode == "null") {
                        return response()->json(['data' => null, 'message' => 'Role code should not be empty'], 404);
                    } else {

                        $val = UserModel::FetchUserWithSearchAndPagination
                        ('user', '=', 'IsActive', true, $offset, $limit, 'Id', $keyword, $roleCode);

                        $resultArray = json_decode(json_encode($val), true);
                        $data = $resultArray;
                        error_log(count($data));
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
            return response()->json(['data' => $data, 'message' => 'Users fetched successfully'], 200);
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
                }
                else if ($roleCode == $facilitatorRole) {
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
                    ('user', '=', 'IsActive', true, $keyword, $destinationIds);
                    return response()->json(['data' => $val, 'message' => 'Users count'], 200);
                }
                else if ($roleCode == $patientRole) {
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
                    ('user', '=', 'IsActive', true, $keyword, $destinationIds);

                    return response()->json(['data' => $val, 'message' => 'Users count'], 200);
                }
                else {
                    return response()->json(['data' => null, 'message' => 'Invalid user role'], 400);
                }
            }
            else if ($userData[0]->RoleCodeName == $facilitatorRole) {
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
                    ('user', '=', 'IsActive', true, $keyword, $doctorIds);

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
                    ('user', '=', 'IsActive', true, $keyword, $patientIds);

                    return response()->json(['data' => $val, 'message' => 'Users count'], 200);
                } else {
                    return response()->json(['data' => null, 'message' => 'Invalid user role'], 400);
                }
            }
            else if ($userData[0]->RoleCodeName == $patientRole) {
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
                    ('user', '=', 'IsActive', true, $keyword, $facilitatorIds);

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
                    ('user', '=', 'IsActive', true, $keyword, $doctorIds);

                    return response()->json(['data' => $val, 'message' => 'Users count'], 200);
                } else {
                    return response()->json(['data' => null, 'message' => 'Invalid user role'], 400);
                }
            }
            else if ($userData[0]->RoleCodeName == $supportStaffRole) {
                error_log('logged in user role is support staff');
                if ($roleCode == $superAdminRole) {
                    return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                }
                else {
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

        function UserUpdate(Request $request)
        {
            $id = $request->get('id');

            //First get and check if record exists or not
            $data = UserModel::GetSingleUserViaId($id);

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
            $ageGroup = $request->post('AgeGroup');

            $dataToUpdate = array(
                "FirstName" => $firstName,
                "LastName" => $lastName,
                "MobileNumber" => $mobileNumber,
                "TelephoneNumber" => $telephoneNumber,
                "OfficeAddress" => $officeAddress,
                "ResidentialAddress" => $residentialAddress,
                "Gender" => $gender,
                "FunctionalTitle" => $functionalTitle,
                "Age" => $age,
                "AgeGroup" => $ageGroup,
            );
            $emailMessage = "Dear User <br/>Update is made on your records";

            $update = GenericModel::updateGeneric('user', 'Id', $id, $dataToUpdate);

            if ($update == true) {
                DB::commit();
                UserModel::sendEmail($data[0]->EmailAddress, $emailMessage, null);
                return response()->json(['data' => null, 'message' => 'User successfully updated'], 200);
            } else {
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in updating user record'], 400);
            }
        }

        function GetSingleUserViaId(Request $request)
        {
            $id = $request->get('id');

            $val = UserModel::GetSingleUserViaId($id);

            if (!empty($val)) {
                return response()->json(['data' => $val[0], 'message' => 'User detail fetched successfully'], 200);
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

            //Binding data to variable.
            $firstName = $request->get('FirstName');
            $lastName = $request->get('LastName');
            $mobileNumber = $request->get('MobileNumber');
            $telephoneNumber = $request->get('TelephoneNumber');
            $officeAddress = $request->get('OfficeAddress');
            $residentialAddress = $request->get('ResidentialAddress');
            $gender = $request->get('Gender');
            $functionalTitle = $request->get('FunctionalTitle');
            $age = $request->get('Age');
            $ageGroup = $request->get('AgeGroup');
            $hashedPassword = md5('ccm1!');
            $roleCode = $request->get('RoleCode');

            $roleCode = UserModel::getRoleViaRoleCode($roleCode);

            if (count($roleCode) == 0) {
                DB::rollback();
                return response()->json(['data' => null, 'message' => 'Role not found'], 400);
            }
            $roleId = $roleCode[0]->Id;

            error_log('$roleId' . $roleId);

            $dataToInsert = array(
                "EmailAddress" => $emailAddress,
                "FirstName" => $firstName,
                "LastName" => $lastName,
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

            $emailMessage = "You have been invited to Chronic Management System. 
        Your email has been created. You may login by using : ccm1! as your password.";

            if ($insertUserAccessRecord == 0) {
                DB::rollback();
                //Now sending email
                UserModel::sendEmail($emailAddress, $emailMessage, null);
                return response()->json(['data' => null, 'message' => 'Error in user assigning role'], 400);
            } else {
                DB::commit();
                return response()->json(['data' => $insertedRecord, 'message' => 'User successfully registered'], 200);
            }
        }

        function UserDelete(Request $request)
        {
            error_log('in controller');
            $id = $request->get('id');

            //First get and check if record exists or not
            $data = UserModel::GetSingleUserViaId($id);

            if (count($data) == 0) {
                return response()->json(['data' => null, 'message' => 'User not found'], 400);
            }

            //Binding data to variable.

            $dataToUpdate = array(
                "IsActive" => false
            );

            $update = GenericModel::updateGeneric('user', 'Id', $id, $dataToUpdate);

            if ($update == true) {
                return response()->json(['data' => $id, 'message' => 'User successfully deleted'], 200);
            } else {
                return response()->json(['data' => null, 'message' => 'Error in deleting user record'], 400);
            }
        }

        function SuperAdminDashboard(Request $request)
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

            return response()->json(['data' => $data, 'message' => 'Role wise user count'], 200);
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
    }
}
