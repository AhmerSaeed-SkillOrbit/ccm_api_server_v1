<?php
/**
 * Created by PhpStorm.
 * User: Ahsan Vohra
 * Date: 3/2/2019
 * Time: 2:07 PM
 */

namespace App\Http\Controllers;

use App\User;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use App\Models\UserModel;
use App\Models\GenericModel;
use App\Models\DoctorScheduleModel;
use App\Models\HelperModel;
use Carbon\Carbon;


class DoctorScheduleController extends Controller
{
    function AddDoctorSchedule(Request $request)
    {
        $doctorRole = env('ROLE_DOCTOR');

        $doctorId = $request->get('doctorId');
        $scheduleDetail = $request->ScheduleDetail;

        error_log('in controller');
        //First check if logged in user role is doctor or not.

        $doctorData = UserModel::GetSingleUserViaId($doctorId);

        if (count($doctorData) == 0) {
            return response()->json(['data' => null, 'message' => 'Doctor record not found'], 400);
        }
        //Means doctor record exist.
        //Now checking it's role
        if ($doctorData[0]->RoleCodeName != $doctorRole) {
            return response()->json(['data' => null, 'message' => 'User does not belong to doctor'], 400);
        }

        $date = HelperModel::getDate();

        // First check if doctors schedule already exists or not
        //If exists then get doctor detail record and delete it.
        //And add the new one

        $val = DoctorScheduleModel::getDoctorSchedule($doctorId);

        DB::beginTransaction();

        if (count($val) > 0) {
            //Means doctor schedule is there.
            //So now we will get it's schedule details and will delete that details
            error_log('doctors schedule already exists');

            $doctorScheduleDetail = DoctorScheduleModel::getDoctorScheduleDetail($val[0]->Id);
            if (count($doctorScheduleDetail) > 0) {
                $result = GenericModel::deleteGeneric('doctor_schedule_detail', 'DoctorScheduleId', $val[0]->Id);
                if ($result == false) {
                    DB::rollBack();
                    return response()->json(['data' => null, 'message' => 'Error in deleting doctor schedule detail data'], 400);
                } else {
                    error_log('doctor schedule detail record successfully deleted');
                    //Now making data to upload in doctor schedule and doctor schedule detail table
                    $doctorScheduleData = array(
                        "DoctorId" => $doctorId,
                        "StartDate" => $request->post('StartDate'),
                        "EndDate" => $request->post('EndDate'),
                        "UpdatedOn" => $date["timestamp"],
                        "IsActive" => true
                    );

                    //First insert doctor schedule data and then get id of that record
                    $update = GenericModel::updateGeneric('doctor_schedule', 'Id', $val[0]->Id, $doctorScheduleData);
                    if ($update == 0) {
                        DB::rollBack();
                        return response()->json(['data' => null, 'message' => 'Error in updating doctor schedule data'], 400);
                    }

                    //Now making data for doctor schedule detail

                    $doctorScheduleDetailData = array();

                    foreach ($scheduleDetail as $item) {
                        array_push
                        (
                            $doctorScheduleDetailData,
                            array(
                                "DoctorScheduleId" => $val[0]->Id,
                                "ScheduleDate" => $item['ScheduleDate'],
                                "StartTime" => $item['StartTime'],
                                "EndTime" => $item['EndTime'],
                                "ShiftType" => $item['ShiftType'],
                                "IsOffDay" => $item['IsOffDay'],
                                "CreatedOn" => $date["timestamp"],
                                "IsActive" => true
                            )
                        );
                    }

                    //Now inserting data
                    $checkInsertedData = GenericModel::insertGeneric('doctor_schedule_detail', $doctorScheduleDetailData);
                    error_log('Check updated data ' . $checkInsertedData);
                    if ($checkInsertedData == true) {
                        DB::commit();
                        return response()->json(['data' => $doctorId, 'message' => 'Doctor schedule updated successfully'], 200);
                    } else {
                        DB::rollBack();
                        return response()->json(['data' => null, 'message' => 'Error in scheduling doctor'], 400);
                    }
                }
            }
        } else {
            error_log('doctor schedule not found');
            //Now making data to upload in doctor schedule and doctor schedule detail table
            $doctorScheduleData = array(
                "DoctorId" => $doctorId,
                "StartDate" => $request->post('StartDate'),
                "EndDate" => $request->post('EndDate'),
                "CreatedOn" => $date["timestamp"],
                "IsActive" => true
            );

            //First insert doctor schedule data and then get id of that record

            $insertDoctorScheduleData = GenericModel::insertGenericAndReturnID('doctor_schedule', $doctorScheduleData);
            if ($insertDoctorScheduleData == 0) {
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in adding doctor schedule data'], 400);
            }

            //Now making data for doctor schedule detail

            $doctorScheduleDetailData = array();

            foreach ($scheduleDetail as $item) {
                array_push
                (
                    $doctorScheduleDetailData,
                    array(
                        "DoctorScheduleId" => $insertDoctorScheduleData,
                        "ScheduleDate" => $item['ScheduleDate'],
                        "StartTime" => $item['StartTime'],
                        "EndTime" => $item['EndTime'],
                        "ShiftType" => $item['ShiftType'],
                        "IsOffDay" => $item['IsOffDay'],
                        "CreatedOn" => $date["timestamp"],
                        "IsActive" => true
                    )
                );
            }

            //Now inserting data
            $checkInsertedData = GenericModel::insertGeneric('doctor_schedule_detail', $doctorScheduleDetailData);
            error_log('Check inserted data ' . $checkInsertedData);
            if ($checkInsertedData == true) {
                DB::commit();
                return response()->json(['data' => $doctorId, 'message' => 'Doctor schedule created successfully'], 200);
            } else {
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in scheduling doctor'], 400);
            }
        }
    }

    function AddDoctorScheduleLatest(Request $request)
    {
        $doctorRole = env('ROLE_DOCTOR');

        $doctorId = $request->get('doctorId');
        $scheduleDetail = $request->ScheduleDetail;

        error_log('in controller');
        //First check if logged in user role is doctor or not.

        $doctorData = UserModel::GetSingleUserViaId($doctorId);

        if (count($doctorData) == 0) {
            return response()->json(['data' => null, 'message' => 'Doctor record not found'], 400);
        }
        //Means doctor record exist.
        //Now checking it's role
        if ($doctorData[0]->RoleCodeName != $doctorRole) {
            return response()->json(['data' => null, 'message' => 'User does not belong to doctor'], 400);
        }

        $date = HelperModel::getDate();

        // First check if doctors schedule already exists or not
        //If exists then get doctor detail record and delete it.
        //And add the new one

        if ($request->post('StartDate') > $request->post('EndDate')) {
            error_log('start date is greater');
            return response()->json(['data' => null, 'message' => 'Start date should not exceed end date'], 400);
        }

        DB::beginTransaction();
        error_log('doctor schedule not found');
        //Now making data to upload in doctor schedule and doctor schedule detail table
        $doctorScheduleData = array(
            "DoctorId" => $doctorId,
            "StartDate" => $request->post('StartDate'),
            "EndDate" => $request->post('EndDate'),
            "CreatedOn" => $date["timestamp"],
            "IsActive" => true,
            "MonthName" => $request->post('MonthName'),
            "YearName" => $request->post('YearName')
        );

        //First insert doctor schedule data and then get id of that record

        $insertDoctorScheduleData = GenericModel::insertGenericAndReturnID('doctor_schedule', $doctorScheduleData);
        if ($insertDoctorScheduleData == 0) {
            DB::rollBack();
            return response()->json(['data' => null, 'message' => 'Error in adding doctor schedule data'], 400);
        }

        //Now making data for doctor schedule detail

        $doctorScheduleDetailData = array();

        foreach ($scheduleDetail as $item) {
            if ($item['ScheduleDate'] >= $request->post('StartDate') && $item['ScheduleDate'] <= $request->post('EndDate')) {
                if ($item['StartTime'] > $item['EndTime']) {
                    return response()->json(['data' => null, 'message' => 'Start time should not exceed end time'], 400);
                }
                array_push
                (
                    $doctorScheduleDetailData,
                    array(
                        "DoctorScheduleId" => $insertDoctorScheduleData,
                        "ScheduleDate" => $item['ScheduleDate'],
                        "StartTime" => $item['StartTime'],
                        "EndTime" => $item['EndTime'],
                        "ShiftType" => $item['ShiftType'],
                        "IsOffDay" => $item['IsOffDay'],
                        "CreatedOn" => $date["timestamp"],
                        "IsActive" => true
                    )
                );
            } else {
                return response()->json(['data' => null, 'message' => 'Invalid date of schedule detail'], 400);
            }
        }

        //Now inserting data
        $checkInsertedData = GenericModel::insertGeneric('doctor_schedule_detail', $doctorScheduleDetailData);
        error_log('Check inserted data ' . $checkInsertedData);
        if ($checkInsertedData == true) {
            DB::commit();
            return response()->json(['data' => $doctorId, 'message' => 'Doctor schedule created successfully'], 200);
        } else {
            DB::rollBack();
            return response()->json(['data' => null, 'message' => 'Error in scheduling doctor'], 400);
        }
    }

    function AddDoctorScheduleUpdatedCode(Request $request)
    {
        $doctorRole = env('ROLE_DOCTOR');

        $doctorId = $request->get('doctorId');
        $scheduleDetail = $request->ScheduleDetail;

        error_log('in controller');
        //First check if logged in user role is doctor or not.

        $doctorData = UserModel::GetSingleUserViaId($doctorId);

        if (count($doctorData) == 0) {
            return response()->json(['data' => null, 'message' => 'Doctor record not found'], 400);
        }
        //Means doctor record exist.
        //Now checking it's role
        if ($doctorData[0]->RoleCodeName != $doctorRole) {
            return response()->json(['data' => null, 'message' => 'User does not belong to doctor'], 400);
        }

        $getRange = DoctorScheduleModel::getDoctorScheduleAhmer($doctorId, $request->post('MonthName'), $request->post('YearName'));

        if ($getRange != null) {
            return response()->json(['data' => null, 'message' => 'Schedule of this dr with same time already exists'], 400);
        }

        $date = HelperModel::getDate();

        // First check if doctors schedule already exists or not
        //If exists then get doctor detail record and delete it.
        //And add the new one

        if ($request->post('StartDate') > $request->post('EndDate')) {
            error_log('start date is greater');
            return response()->json(['data' => null, 'message' => 'Start date should not exceed end date'], 400);
        }

        DB::beginTransaction();

        error_log('doctor schedule not found');
        //Now making data to upload in doctor schedule and doctor schedule detail table
        $doctorScheduleData = array(
            "DoctorId" => $doctorId,
            "StartDate" => $request->post('StartDate'),
            "EndDate" => $request->post('EndDate'),
            "CreatedOn" => $date["timestamp"],
            "IsActive" => true,
            "MonthName" => $request->post('MonthName'),
            "YearName" => $request->post('YearName')
        );

        //First insert doctor schedule data and then get id of that record

        $insertDoctorScheduleData = GenericModel::insertGenericAndReturnID('doctor_schedule_copy1', $doctorScheduleData);
        if ($insertDoctorScheduleData == 0) {
            DB::rollBack();
            return response()->json(['data' => null, 'message' => 'Error in adding doctor schedule data'], 400);
        }

        //Now making data for doctor schedule detail

        $doctorScheduleDetailData = array();

        $outerCounter = 0;

        foreach ($scheduleDetail as $item) {
            error_log('$outerCounter = ' . $outerCounter = $outerCounter + 1);
            if ($item['ScheduleDate'] >= $request->post('StartDate') && $item['ScheduleDate'] <= $request->post('EndDate')) {

                $doctorScheduleDetailData = array(
                    "DoctorScheduleId" => $insertDoctorScheduleData,
                    "ScheduleDate" => $item['ScheduleDate'],
                    "NoOfShift" => $item['NoOfShift'],
                    "IsOffDay" => $item['IsOffDay'],
                    "CreatedOn" => $date["timestamp"],
                    "IsActive" => true
                );
            } else {
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Invalid date of schedule detail'], 400);
            }
            $getInsertedDataId = GenericModel::insertGenericAndReturnID('doctor_schedule_detail_copy1', $doctorScheduleDetailData);
            error_log('$checkInsertedData of doctor schedule detail' . $getInsertedDataId);
            if ($getInsertedDataId == 0) {
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in inserting doctor schedule detail'], 400);
            }
            if (count($item['ScheduleShift']) > 0 || $item['ScheduleShift'] != []) {
                $InnerCounter = 0;
                error_log('Schedule shift is greater than 0 ');

                foreach ($item['ScheduleShift'] as $scheduleShift) {

                    error_log('$InnerCounter = ' . $InnerCounter = $InnerCounter + 1);
                    error_log('======================');

                    $doctorScheduleShiftData =
                        array(
                            "DoctorScheduleDetailId" => $getInsertedDataId,
                            "StartTime" => $scheduleShift['StartTime'],
                            "EndTime" => $scheduleShift['EndTime'],
                            "NoOfPatientAllowed" => $scheduleShift['NoOfPatientAllowed'],
                            "IsActive" => true
                        );

                    $checkInsertedData = GenericModel::insertGeneric('doctor_schedule_shift', $doctorScheduleShiftData);

                    error_log('$checkInsertedData  = ' . $checkInsertedData);
                    error_log('=========================');

                    if ($checkInsertedData == false) {
                        DB::rollBack();
                        return response()->json(['data' => null, 'message' => 'Error in inserting doctor schedule detail'], 400);
                    }
                }
            }
        }

        DB::commit();
        return response()->json(['data' => $doctorId, 'message' => 'Doctor schedule created successfully'], 200);

        //Now inserting data
//        $checkInsertedData = GenericModel::insertGeneric('doctor_schedule_detail', $doctorScheduleDetailData);
//        error_log('Check inserted data ' . $checkInsertedData);
//        if ($checkInsertedData == true) {
//            DB::commit();
//            return response()->json(['data' => $doctorId, 'message' => 'Doctor schedule created successfully'], 200);
//        } else {
//            DB::rollBack();
//            return response()->json(['data' => null, 'message' => 'Error in scheduling doctor'], 400);
//        }
    }

    function UpdateDoctorSchedule(Request $request)
    {
        error_log('in controller');
        $doctorRole = env('ROLE_DOCTOR');

        $doctorId = $request->get('userId');

        $doctorScheduleDetailId = $request->post('DoctorScheduleDetailId');
        $noOfShift = $request->post('NoOfShift');
        $isOffDay = $request->post('IsOffDay');
        $scheduleShift = $request->ScheduleShift;

        error_log('in controller');
        //First check if logged in user role is doctor or not.

        $doctorData = UserModel::GetSingleUserViaId($doctorId);

        $date = HelperModel::getDate();

        if (count($doctorData) == 0) {
            return response()->json(['data' => null, 'message' => 'Doctor record not found'], 400);
        }
        //Means doctor record exist.
        //Now checking it's role
        if ($doctorData[0]->RoleCodeName != $doctorRole) {
            return response()->json(['data' => null, 'message' => 'User does not belong to doctor'], 400);
        }

        $getDoctorScheduleDetailData = DoctorScheduleModel::getDoctorScheduleDetailViaId($doctorScheduleDetailId);

        DB::beginTransaction();

        //First check if dr schedule exist
        if ($getDoctorScheduleDetailData == null) {
            return response()->json(['data' => null, 'message' => 'Doctor schedule not found'], 400);
        } else {
            error_log('Schedule detail data found');
            //Doctor schedule found
            //LOGIC
            //check if appointment is taken or not
            //If taken then don't update that record
            //If not taken then check if schedule is off day
            //If off day is true then remove record of that schedule shift and update record of schedule detail
            // else update that record

            if (count($scheduleShift) > 0) {
                error_log('Given schedule shift is > 0');
                foreach ($scheduleShift as $item) {
                    $checkAppointment = DoctorScheduleModel::getAppointmentViaShiftId($item['Id']);
                    if (count($checkAppointment) == 0) {
                        error_log('Appointment not scheduled');
                        //Now get records from doctor schedule detail and check if record exists
                        //if exists then update it
                        //else insert data
                        $getDoctorScheduleShiftData = DoctorScheduleModel::getDoctorScheduleShiftViaId($item['Id']);

                        if ($getDoctorScheduleShiftData == null) {
                            error_log('Schedule shift not exist');

                            if ($isOffDay == true) {
                                error_log('Off days is true and data is null');
                                //Now fetch the record of schedule shift
                                //if is exists then delete

                                $checkScheduleShiftRecord = GenericModel::simpleFetchGenericByWhere('doctor_schedule_shift', '=', 'DoctorScheduleDetailId', $doctorScheduleDetailId, 'Id');
                                if (count($checkScheduleShiftRecord) > 0) {
                                    error_log('Deleting all shift entries');
                                    $result = GenericModel::deleteGeneric('doctor_schedule_shift', 'DoctorScheduleDetailId', $doctorScheduleDetailId);
                                    if ($result == false) {
                                        DB::rollBack();
                                    }
                                }
                            } else {

                                error_log('Off day is false and data is inserting');

                                $doctorScheduleShiftData =
                                    array(
                                        "DoctorScheduleDetailId" => $doctorScheduleDetailId,
                                        "StartTime" => $item['StartTime'],
                                        "EndTime" => $item['EndTime'],
                                        "NoOfPatientAllowed" => $item['NoOfPatientAllowed'],
                                        "IsActive" => true
                                    );

                                $checkInsertedData = GenericModel::insertGeneric('doctor_schedule_shift', $doctorScheduleShiftData);
                                if ($checkInsertedData == false) {
                                    DB::rollBack();
                                }
                            }
                        } else {
                            error_log('Schedule shift exist');
                            //Now checking if off day is true
                            //If yes then we will remove all the schedule shift
                            error_log('$isOffDay ' . $isOffDay);
                            if ($isOffDay == true) {

                                error_log('Off days is true');
                                //Now fetch the record of schedule shift
                                //if is exists then delete

                                $checkScheduleShiftRecord = GenericModel::simpleFetchGenericByWhere('doctor_schedule_shift', '=', 'DoctorScheduleDetailId', $doctorScheduleDetailId, 'Id');
                                error_log('$checkScheduleShiftRecord ' . $checkScheduleShiftRecord);
                                if (count($checkScheduleShiftRecord) > 0) {
                                    error_log('Deleting all shift entries');
                                    $result = GenericModel::deleteGeneric('doctor_schedule_shift', 'DoctorScheduleDetailId', $doctorScheduleDetailId);
                                    if ($result == false) {
                                        DB::rollBack();
                                    }
                                }
                            } else {

                                error_log('Off day is false');

                                $dataToUpdate = array(
                                    "DoctorScheduleDetailId" => $doctorScheduleDetailId,
                                    "StartTime" => $item['StartTime'],
                                    "EndTime" => $item['EndTime'],
                                    "IsActive" => true
                                );
                                $update = GenericModel::updateGeneric('doctor_schedule_shift', 'Id', $item['Id'], $dataToUpdate);
                                if ($update == false) {
                                    DB::rollBack();
                                }
                            }
                        }
                        error_log('Now updating doctor schedule details');

                        $updateData = array(
                            "NoOfShift" => $noOfShift,
                            "IsOffDay" => $isOffDay,
                            "UpdatedOn" => $date['timestamp'],
                            "UpdatedBy" => $doctorId //fetch from doctor_schedule table
                        );

                        $update = GenericModel::updateGeneric('doctor_schedule_detail_copy1', 'Id', $doctorScheduleDetailId, $updateData);
                        if ($update == false) {
                            DB::rollBack();
                        }
                    }
                }
            }
        }
        DB::commit();
        return response()->json(['data' => null, 'message' => 'Doctor schedule shift updated successfully'], 200);
    }

    function GetDoctorScheduleDetail(Request $request)
    {
        error_log('in controller');

        $doctorId = $request->get('doctorId');

        $val = DoctorScheduleModel::getDoctorSchedule($doctorId);
        if (count($val) == 0) {
            return response()->json(['data' => null, 'message' => 'No schedule for this doctor'], 400);
        }

        error_log(count($val));

        //Now schedule found.
        //So fetch that schedule details

        $doctorScheduleDetail = DoctorScheduleModel::getDoctorScheduleDetail($val[0]->Id);
        if (count($doctorScheduleDetail) > 0) {
            $val['DoctorScheduleDetails'] = $doctorScheduleDetail;
        }

        return response()->json(['data' => $val, 'message' => 'Doctor schedule found'], 200);
    }

    function GetDoctorScheduleDetailAhmer(Request $request)
    {
        error_log('in controller');

        $doctorId = $request->get('doctorId');
        $loggedInUserId = $request->get('userId');
        $month = $request->get('month');
        $year = $request->get('year');

        $patientRole = env('ROLE_PATIENT');
        $facilitatorRole = env('ROLE_FACILITATOR');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        $doctorScheduleDetail = array();

        $loggedInUserData = UserModel::GetSingleUserViaId($loggedInUserId);

        if (count($loggedInUserData) == 0) {
            return response()->json(['data' => null, 'message' => 'logged in user record not found'], 400);
        }

        if ($loggedInUserData[0]->RoleCodeName == $patientRole) {
            //Now check if logged in patient is associated with given doctor id or not
            $checkAssociatedPatient = UserModel::CheckAssociatedPatientAndFacilitator($doctorId, $doctorPatientAssociation, $loggedInUserId);
            error_log('$checkAssociatedPatient ' . $checkAssociatedPatient);
            if ($checkAssociatedPatient == null) {
                return response()->json(['data' => null, 'message' => 'logged in user is not associated to this doctor'], 400);
            }
        }

        if ($loggedInUserData[0]->RoleCodeName == $facilitatorRole) {
            //Now check if logged in patient is associated with given doctor id or not
            $checkAssociatedFacilitator = UserModel::CheckAssociatedPatientAndFacilitator($doctorId, $doctorFacilitatorAssociation, $loggedInUserId);
            error_log('$checkAssociatedFacilitator ' . $checkAssociatedFacilitator);
            if ($checkAssociatedFacilitator == null) {
                return response()->json(['data' => null, 'message' => 'logged in user is not associated to this doctor'], 400);
            }
        }


        $getRange = DoctorScheduleModel::getDoctorScheduleAhmer($doctorId, $month, $year);

        if ($getRange == null) {
            return response()->json(['data' => null, 'message' => 'No schedule for this doctor'], 400);
        }

        $doctorScheduleDetail['StartDate'] = $getRange->StartDate;
        $doctorScheduleDetail['EndDate'] = $getRange->EndDate;
        $doctorScheduleDetail['MonthName'] = $getRange->MonthName;
        $doctorScheduleDetail['YearName'] = $getRange->YearName;
        $doctorScheduleDetail['Id'] = $getRange->Id;

        $getDetail = DoctorScheduleModel::getDoctorScheduleGetDoctorScheduleDetailAhmerDetail($getRange->Id);
        if (count($getDetail) > 0) {
            $doctorScheduleDetail['DoctorScheduleDetails'] = $getDetail;
        }

        return response()->json(['data' => $doctorScheduleDetail, 'message' => 'Doctor schedule found'], 200);
    }

    function GetDoctorScheduleDetailAhmerUpdate(Request $request)
    {
        error_log('in controller');

        $doctorId = $request->get('doctorId');
        $loggedInUserId = $request->get('userId');
        $month = $request->get('month');
        $year = $request->get('year');

        $patientRole = env('ROLE_PATIENT');
        $facilitatorRole = env('ROLE_FACILITATOR');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        $doctorScheduleDetail = array();

        $loggedInUserData = UserModel::GetSingleUserViaId($loggedInUserId);

        if (count($loggedInUserData) == 0) {
            return response()->json(['data' => null, 'message' => 'logged in user record not found'], 400);
        }

        if ($loggedInUserData[0]->RoleCodeName == $patientRole) {
            //Now check if logged in patient is associated with given doctor id or not
            $checkAssociatedPatient = UserModel::CheckAssociatedPatientAndFacilitator($doctorId, $doctorPatientAssociation, $loggedInUserId);
            if ($checkAssociatedPatient == null) {
                return response()->json(['data' => null, 'message' => 'logged in user is not associated to this doctor'], 400);
            }
        }

        if ($loggedInUserData[0]->RoleCodeName == $facilitatorRole) {
            //Now check if logged in patient is associated with given doctor id or not
            $checkAssociatedFacilitator = UserModel::CheckAssociatedPatientAndFacilitator($doctorId, $doctorFacilitatorAssociation, $loggedInUserId);
            if ($checkAssociatedFacilitator == null) {
                return response()->json(['data' => null, 'message' => 'logged in user is not associated to this doctor'], 400);
            }
        }


        $getRange = DoctorScheduleModel::getDoctorScheduleAhmer($doctorId, $month, $year);

        if ($getRange == null) {
            return response()->json(['data' => null, 'message' => 'No schedule found for this doctor'], 200);
        }

        $doctorScheduleDetail['Id'] = $getRange->Id;
        $doctorScheduleDetail['FirstName'] = $loggedInUserData[0]->FirstName;
        $doctorScheduleDetail['LastName'] = $loggedInUserData[0]->LastName;
        $doctorScheduleDetail['StartDate'] = $getRange->StartDate;
        $doctorScheduleDetail['EndDate'] = $getRange->EndDate;
        $doctorScheduleDetail['MonthName'] = $getRange->MonthName;
        $doctorScheduleDetail['YearName'] = $getRange->YearName;

        $getDetail = DoctorScheduleModel::getDoctorScheduleDetailNew($getRange->Id);

        $scheduleDetailData = array();

        if (count($getDetail) > 0) {
            $counter = 0;
            foreach ($getDetail as $item) {

                error_log('loop iterating for : ' . $counter += 1);

                $data = array(
                    'Id' => $item->Id,
                    'ScheduleDate' => $item->ScheduleDate,
                    'NoOfShift' => $item->NoOfShift,
                    'IsOffDay' => $item->IsOffDay,
                    'ScheduleShifts' => array()
                );

                //Now get doc tor schedule shift detail with respect to loops id

                $doctorScheduleShiftData = DoctorScheduleModel::getDoctorScheduleShift($item->Id);
                if (count($doctorScheduleShiftData) > 0) {
                    $data['ScheduleShifts'] = $doctorScheduleShiftData;
                } else {
                    $data['ScheduleShifts'] = array();
                }

                array_push($scheduleDetailData, $data);
            }

            $doctorScheduleDetail['DoctorScheduleDetails'] = $scheduleDetailData;
        } else {

            $doctorScheduleDetail['DoctorScheduleDetails'] = null;
        }

        return response()->json(['data' => $doctorScheduleDetail, 'message' => 'Doctor schedule found'], 200);
    }

    function GetDoctorScheduleListViaPagination(Request $request)
    {
        error_log('in controller');

        $offset = $request->get('offset');
        $limit = $request->get('limit');
        $loggedInUserId = $request->get('userId');

        $doctorRole = env('ROLE_DOCTOR');

        $loggedInUserData = UserModel::GetSingleUserViaId($loggedInUserId);

        if (count($loggedInUserData) == 0) {
            return response()->json(['data' => null, 'message' => 'logged in user record not found'], 400);
        }

        if ($loggedInUserData[0]->RoleCodeName != $doctorRole) {
            //Now check if logged in user is doctor or not
            return response()->json(['data' => null, 'message' => 'logged in user must be a doctor'], 400);
        }
        //Query to get doctor record
        $getDoctorScheduleData = DoctorScheduleModel::getDoctorScheduleAllViaPagination($loggedInUserId, $offset, $limit);

        if (count($getDoctorScheduleData) == 0) {
            return response()->json(['data' => null, 'message' => 'No schedule found for this doctor'], 400);
        }

        return response()->json(['data' => $getDoctorScheduleData, 'message' => 'Doctor schedule found'], 200);
    }

    function GetDoctorScheduleListCount(Request $request)
    {
        error_log('in controller');

        $loggedInUserId = $request->get('userId');

        $doctorRole = env('ROLE_DOCTOR');

        $loggedInUserData = UserModel::GetSingleUserViaId($loggedInUserId);

        if (count($loggedInUserData) == 0) {
            return response()->json(['data' => null, 'message' => 'logged in user record not found'], 400);
        }

        if ($loggedInUserData[0]->RoleCodeName != $doctorRole) {
            //Now check if logged in user is doctor or not
            return response()->json(['data' => null, 'message' => 'logged in user must be a doctor'], 400);
        }
        //Query to get doctor record
        $getDoctorScheduleData = DoctorScheduleModel::getDoctorScheduleAllCount($loggedInUserId);

        return response()->json(['data' => $getDoctorScheduleData, 'message' => 'Total Count'], 200);
    }

    function UpdateDoctorScheduleDetailSingle(Request $request)
    {
        error_log('in controller');

        $doctorScheduleDetailId = $request->get('DoctorScheduleDetailId');
        $doctorScheduleId = $request->post('DoctorScheduleId');

        error_log($doctorScheduleDetailId);
        error_log($doctorScheduleId);

        $getDoctorScheduleData = DoctorScheduleModel::getDoctorSchedule($doctorScheduleId);
        if (count($getDoctorScheduleData) == 0) {
            return response()->json(['data' => null, 'message' => 'Doctor schedule not found'], 400);
        }


        $scheduleDate = $request->post('ScheduleDate');
        $startTime = $request->post('StartTime');
        $endTime = $request->post('EndTime');
        $isOffDay = $request->post('IsOffDay');

        if ($scheduleDate >= $getDoctorScheduleData[0]->StartDate && $scheduleDate <= $getDoctorScheduleData[0]->EndDate) {
            //First get dr schedule data with respect to given schedule detail ID

            $date = HelperModel::getDate();

            $updateData = array(
                "ScheduleDate" => $scheduleDate,
                "StartTime" => $startTime,
                "EndTime" => $endTime,
                "ShiftType" => 1,
                "IsOffDay" => $isOffDay,
                "UpdatedOn" => $date['timestamp'],
                "UpdatedBy" => 1 //fetch from doctor_schedule table
            );

            $update = GenericModel::updateGeneric('doctor_schedule_detail', 'Id', $doctorScheduleDetailId, $updateData);

//        $update = DB::table('doctor_schedule_detail')
//            ->where('Id', $doctorScheduleDetailId)
//            ->where('DoctorScheduleId', $doctorScheduleId)
//            ->update($updateData);

            if ($update > 0) {
                return response()->json(['data' => $doctorScheduleDetailId, 'message' => 'Doctor schedule detail updated successfully'], 200);
            } else {
                return response()->json(['data' => null, 'message' => 'Doctor schedule detail failed to update'], 500);
            }
        } else {
            return response()->json(['data' => null, 'message' => 'Schedule date for a doctor should be in between start and end date'], 400);
        }
    }

    function GetDoctorScheduleShiftSingleViaId(Request $request)
    {
        $doctorScheduleShiftId = $request->get('doctorScheduleShiftId');

        $getDoctorScheduleShiftData = DoctorScheduleModel::getDoctorScheduleShiftViaId($doctorScheduleShiftId);

        if ($getDoctorScheduleShiftData == null) {
            return response()->json(['data' => null, 'message' => 'Doctor schedule shift not found'], 200);
        } else {
            error_log('doctor schedule shift found');
            //Getting doctor time slots
            $getDoctorScheduleShiftTimeSlot = DoctorScheduleModel::getDoctorScheduleShiftTimeSlotsViaDoctorScheduleShiftId($doctorScheduleShiftId);


            $doctorScheduleShiftData['Id'] = $getDoctorScheduleShiftData->Id;
            $doctorScheduleShiftData['StartTime'] = $getDoctorScheduleShiftData->StartTime;
            $doctorScheduleShiftData['EndTime'] = $getDoctorScheduleShiftData->EndTime;

            $doctorScheduleShiftData['TimeSlot'] = $getDoctorScheduleShiftTimeSlot;
        }
        return response()->json(['data' => $doctorScheduleShiftData, 'message' => 'Doctor schedule shift found'], 200);
    }

    function AddAppointment(Request $request)
    {

        $patientRole = env('ROLE_PATIENT');
        $doctorRole = env('ROLE_DOCTOR');

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        $defaultAppointmentNumber = env('DEFAULT_APPOINTMENT_NUMBER');

        $patientId = $request->get('PatientId');
        $doctorId = $request->get('DoctorId');

        $appointmentNumber = 0;


        $patientData = UserModel::GetSingleUserViaId($patientId);

        //First check if patient id is belonging to dr

        if (count($patientData) > 0) {
            if ($patientData[0]->RoleCodeName == $patientRole) {
                error_log('Role is patient');
                //Now check if logged in patient is associated with given doctor id or not
                $checkAssociatedPatient = UserModel::CheckAssociatedPatientAndFacilitator($doctorId, $doctorPatientAssociation, $patientId);
                error_log('checking patient association');
                if ($checkAssociatedPatient == null) {
                    return response()->json(['data' => null, 'message' => 'logged in user is not associated to this doctor'], 400);
                }
            } else {
                return response()->json(['data' => null, 'message' => 'logged in user is not patient'], 400);
            }
        } else {
            return response()->json(['data' => null, 'message' => 'Patient not found'], 400);
        }

        $DoctorData = UserModel::GetSingleUserViaId($doctorId);
        if (count($DoctorData) > 0) {
            if ($DoctorData[0]->RoleCodeName != $doctorRole) {
                //Now check if logged in user is doctor or not
                return response()->json(['data' => null, 'message' => 'logged in user must be a doctor'], 400);
            }
        } else {
            return response()->json(['data' => null, 'message' => 'Doctor not found'], 400);
        }

        //Now check if shift slot is available
        //Shift slot should not be booked
        $shiftTimeSlotData = DoctorScheduleModel::getShiftSlotViaId($request->post('ShiftTimeSlotId'));
        if ($shiftTimeSlotData != null) {
            error_log('Shift time slot found');
            //Now check if it is booked or not
            if ($shiftTimeSlotData->IsBooked == true) {
                return response()->json(['data' => null, 'message' => 'This time slot is already booked'], 400);
            } else {
                //Now get the shceudle date with respect to schedule shift id
                $getScheduleDate = DoctorScheduleModel::getDoctorScheduleShiftDataViaId($request->post('DoctorScheduleShiftId'));

                error_log('$getScheduleDate');
                error_log($getScheduleDate->ScheduleDate);

                if ($getScheduleDate == null) {
                    error_log('schedule date not found');
                    return response()->json(['data' => null, 'message' => 'Schedule date not found'], 400);
                } else {
                    error_log('schedule date found ' . $getScheduleDate->ScheduleDate);
                    //Now get check if particular patient has already taken appointment on this schedule date
                    $getPatientsScheduleDate = DoctorScheduleModel::getDoctorScheduleShiftDataViaPatientId($patientId);

                    if (count($getPatientsScheduleDate) > 0) {
                        error_log('patient appointment schedule date found ' . $getPatientsScheduleDate);
                        //We have now got the patient schedule date
                        //Now compare that schedule with the date which we got earlier via schedule shift id
                        foreach ($getPatientsScheduleDate as $item) {

                            error_log('$item');
                            error_log($item->ScheduleDate);

                            if ($item->ScheduleDate == $getScheduleDate->ScheduleDate) {
                                error_log('One of the date is equal to the appointment date which patient has already taken');
                                return response()->json(['data' => null, 'message' => 'You have already taken an appointment on this date'], 400);
                            }
                        }
                    }
                }
            }
        } else {
            return response()->json(['data' => null, 'message' => 'Invalid time slot'], 400);
        }
        //Now get the last appointment number
        //If appointment number exists then add 1 to that
        //else get default number from .env

        $getLastAppointmentNumber = DoctorScheduleModel::getLastAppointment();
        if ($getLastAppointmentNumber != null) {
            error_log('appointment number found');
            $appointmentNumber = 0000 . $getLastAppointmentNumber->AppointmentNumber + 1;
        } else {
            error_log('appointment number not found');
            $appointmentNumber = $defaultAppointmentNumber;
        }

        //Now making data to insert appointment

        $date = HelperModel::getDate();

        $dataToInsert = array(
            "AppointmentNumber" => $appointmentNumber,
            "PatientId" => $patientId,
            "DoctorId" => $doctorId,
            "DoctorScheduleShiftId" => $request->post('DoctorScheduleShiftId'),
            "ShiftTimeSlotId" => $request->post('ShiftTimeSlotId'),
            "Description" => $request->post('Description'),
            "IsActive" => true,
            "CreatedBy" => $patientId,
            "CreatedOn" => $date['timestamp']
        );

        DB::beginTransaction();

        $checkInsertedData = GenericModel::insertGenericAndReturnID('appointment', $dataToInsert);

        $emailMessageForPatient = "Dear Patient, Your appointment request is submitted successfully";
        $emailMessageForDoctor = "Dear Doctor, Your patient has request an appointment. View details from the following link";

        error_log('Check updated data ' . $checkInsertedData);
        if ($checkInsertedData == true) {
            //Now insert update data and make time slot is Booked to true
            $dataToUpdate = array(
                "IsBooked" => true
            );
            $update = GenericModel::updateGeneric('shift_time_slot', 'Id', $request->post('ShiftTimeSlotId'), $dataToUpdate);
            if ($update == 0) {
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in making appointment'], 400);
            } else {
                DB::commit();
                UserModel::sendEmail($patientData[0]->EmailAddress, $emailMessageForPatient, null);
                //Now sending sms to patient
                if ($patientData[0]->MobileNumber != null) {
                    $url = env('WEB_URL') . '/#/';
                    $toNumber = array();
                    $phoneCode = getenv("PAK_NUM_CODE");//fetch from front-end
                    $mobileNumber = $phoneCode . $patientData[0]->MobileNumber;
                    array_push($toNumber, $mobileNumber);
                    HelperModel::sendSms($toNumber, 'Dear Doctor, Your patient has request an appointment. View details from the following link', $url);
                }
                UserModel::sendEmail($DoctorData[0]->EmailAddress, $emailMessageForDoctor, null);

                //Now sending sms to doctor
                if ($DoctorData[0]->MobileNumber != null) {
                    $url = env('WEB_URL') . '/#/';
                    $toNumber = array();
                    $phoneCode = getenv("PAK_NUM_CODE");//fetch from front-end
                    $mobileNumber = $phoneCode . $DoctorData[0]->MobileNumber;
                    array_push($toNumber, $mobileNumber);
                    HelperModel::sendSms($toNumber, 'Dear Patient, Your appointment request is submitted successfully', $url);
                }
                return response()->json(['data' => $checkInsertedData, 'message' => 'Appointment request successfully created'], 200);
            }
        } else {
            DB::rollBack();
            return response()->json(['data' => null, 'message' => 'Error in creating Appointment request'], 400);
        }
    }

    function getDoctorAppointmentListViaPagination_OLD(Request $request)
    {
        error_log('in controller');

        $doctorRole = env('ROLE_DOCTOR');

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        $doctorId = $request->get('userId');
        $reqStatus = $request->get('rStatus'); //means 'accepted || pending || rejected'
        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');

        $patientIds = array();


        //First check if patient id is belonging to dr

        $DoctorData = UserModel::GetSingleUserViaId($doctorId);

        if (count($DoctorData) > 0) {
            error_log('user data fetched');
            if ($DoctorData[0]->RoleCodeName != $doctorRole) {
                error_log('login user is not doctor');
                //Now check if logged in user is doctor or not
                return response()->json(['data' => null, 'message' => 'logged in user must be a doctor'], 400);
            } else {
                error_log('login user is doctor');
                //Now get his associated patient ids

                $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($doctorId, $doctorPatientAssociation);
                if (count($getAssociatedPatients) > 0) {
                    //Now bind ids to an  array
                    foreach ($getAssociatedPatients as $item) {
                        array_push($patientIds, $item->DestinationUserId);
                    }

                    $getAppointmentData = DoctorScheduleModel::getMultipleAppointmentsViaDoctorAndPatientId($doctorId, $reqStatus, $patientIds, $pageNo, $limit);
                    if (count($getAppointmentData) > 0) {
                        return response()->json(['data' => $getAppointmentData, 'message' => 'Appointments fetched successfully'], 200);
                    } else {
                        return response()->json(['data' => null, 'message' => 'No appointment scheduled yet'], 200);
                    }
                } else {
                    return response()->json(['data' => null, 'message' => 'Patients not yet associated with this doctor'], 400);
                }
            }
        } else {
            return response()->json(['data' => null, 'message' => 'logged in user data not found'], 400);
        }
    }

    function getDoctorAppointmentListViaPagination(Request $request)
    {
        error_log('in controller');

        $doctorRole = env('ROLE_DOCTOR');
        $patientRole = env('ROLE_PATIENT');

        $loggedInUserId = $request->get('userId');
        $reqStatus = $request->get('rStatus'); //means 'accepted || pending || rejected'
        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');


        $loggedInUserData = UserModel::GetSingleUserViaId($loggedInUserId);

        if (count($loggedInUserData) > 0) {
            error_log('user data fetched');
            if ($loggedInUserData[0]->RoleCodeName == $doctorRole) {
                error_log('login user is doctor');
                //Now check if logged in user is doctor or not
                $getAppointmentListForDoctor = DoctorScheduleModel::getAppointmentViaDoctorId($loggedInUserId, $reqStatus, $pageNo, $limit);
                if (count($getAppointmentListForDoctor) > 0) {
                    return response()->json(['data' => $getAppointmentListForDoctor, 'message' => 'Appointments found'], 200);
                } else {
                    return response()->json(['data' => null, 'message' => 'appointment not found'], 200);
                }
            } else if ($loggedInUserData[0]->RoleCodeName == $patientRole) {
                error_log('login user is patient');
                //Now check if logged in user is patient or not
                $getAppointmentListForPatient = DoctorScheduleModel::getAppointmentViaPatientId($loggedInUserId, $reqStatus, $pageNo, $limit);
                if (count($getAppointmentListForPatient) > 0) {
                    return response()->json(['data' => $getAppointmentListForPatient, 'message' => 'Appointments found'], 200);
                } else {
                    return response()->json(['data' => null, 'message' => 'appointment not found'], 200);
                }
            } else {
                error_log('login user is neither doctor or patient');
                return response()->json(['data' => null, 'message' => 'logged in user must be doctor or patient'], 400);
            }
        } else {
            return response()->json(['data' => null, 'message' => 'logged in user data not found'], 400);
        }
    }

    function getDoctorAppointmentListCount_OLD(Request $request)
    {
        error_log('in controller');

        $doctorRole = env('ROLE_DOCTOR');

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        $doctorId = $request->get('userId');
        $reqStatus = $request->get('rStatus'); //means 'accepted || pending || rejected'
        $patientIds = array();


        //First check if patient id is belonging to dr

        $DoctorData = UserModel::GetSingleUserViaId($doctorId);

        if (count($DoctorData) > 0) {
            error_log('user data fetched');
            if ($DoctorData[0]->RoleCodeName != $doctorRole) {
                error_log('login user is not doctor');
                //Now check if logged in user is doctor or not
                return response()->json(['data' => null, 'message' => 'logged in user must be a doctor'], 400);
            } else {
                error_log('login user is doctor');
                //Now get his associated patient ids

                $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($doctorId, $doctorPatientAssociation);
                if (count($getAssociatedPatients) > 0) {
                    //Now bind ids to an  array
                    foreach ($getAssociatedPatients as $item) {
                        array_push($patientIds, $item->DestinationUserId);
                    }

                    $getAppointmentData = DoctorScheduleModel::getMultipleAppointmentsCountViaDoctorAndPatientId($doctorId, $reqStatus, $patientIds);
                    return response()->json(['data' => $getAppointmentData, 'message' => 'Total Count'], 200);
                } else {
                    return response()->json(['data' => null, 'message' => 'Patients not yet associated with this doctor'], 400);
                }
            }
        } else {
            return response()->json(['data' => null, 'message' => 'logged in user data not found'], 400);
        }
    }

    function getDoctorAppointmentListCount(Request $request)
    {
        error_log('in controller');

        $doctorRole = env('ROLE_DOCTOR');
        $patientRole = env('ROLE_PATIENT');

        $loggedInUserId = $request->get('userId');
        $reqStatus = $request->get('rStatus'); //means 'accepted || pending || rejected'


        $loggedInUserData = UserModel::GetSingleUserViaId($loggedInUserId);

        if (count($loggedInUserData) > 0) {
            error_log('user data fetched');
            if ($loggedInUserData[0]->RoleCodeName == $doctorRole) {
                error_log('login user is doctor');
                //Now check if logged in user is doctor or not
                $getAppointmentListForDoctor = DoctorScheduleModel::getAppointmentCountViaDoctorId($loggedInUserId, $reqStatus);
                return response()->json(['data' => $getAppointmentListForDoctor, 'message' => 'Total count'], 200);
            } else if ($loggedInUserData[0]->RoleCodeName == $patientRole) {
                error_log('login user is patient');
                //Now check if logged in user is patient or not
                $getAppointmentListForPatient = DoctorScheduleModel::getAppointmentCountViaPatientId($loggedInUserId, $reqStatus);
                return response()->json(['data' => $getAppointmentListForPatient, 'message' => 'Total count'], 200);
            } else {
                error_log('login user is neither doctor or patient');
                return response()->json(['data' => null, 'message' => 'logged in user must be doctor or patient'], 400);
            }
        } else {
            return response()->json(['data' => null, 'message' => 'logged in user data not found'], 400);
        }
    }

    function getDoctorAppointmentSingleViaId(Request $request)
    {
        error_log('in controller');

        $doctorRole = env('ROLE_DOCTOR');

        $appointmentId = $request->get('appointmentId');
        $doctorId = $request->get('userId');

        $getAppointmentData = DoctorScheduleModel::getSingleAppointmentViaId($appointmentId);
        if ($getAppointmentData != null) {
            return response()->json(['data' => $getAppointmentData, 'message' => 'Appointment fetched successfully'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Appointment not found'], 200);
        }
    }
    //function will update the request status mentioned in post
    //against the provided appointmentId
    function updateAppointmentRequestStatus(Request $request)
    {

        error_log('in controller');
        error_log('in updating appointment request status function');

        $doctorRole = env('ROLE_DOCTOR');

        $appointmentId = $request->get('aId');//refers to appointmentId
        $doctorId = $request->get('userId');//refers to logged in userId
        $reqStatus = $request->post('rStatus'); //means 'accepted || rejected'
        $reason = $request->post('reason'); //refers to reject reason

        //first apply following check
        //appointment id should belong to logged in user

        //if rStatus = accepted
        //if already rejected or pending do not update to accepted

        //if rStatus = rejected
        //if already accepted or pending do not update to rejected

        //if rStatus = pending not allow to update status as pending
        //status is a default status

//        First checking if appointment exists or not

        $emailMessageForPatient = "Dear Patient, Your appointment request has been " . $reqStatus . '.';
        $emailMessageForDoctor = "Dear Doctor, You have " . $reqStatus . " your patient appointment request. View details from the following link";

        $checkAppointment = DoctorScheduleModel::getSingleAppointmentViaId($appointmentId);
        if ($checkAppointment == null) {
            return response()->json(['data' => null, 'message' => 'Appointment not found'], 400);
        } else {
            //after checks update the status
            $dataToUpdate = array(
                "RequestStatus" => $reqStatus,
                "RequestStatusReason" => $reason
            );
            DB::beginTransaction();
            $update = GenericModel::updateGeneric('appointment', 'Id', $appointmentId, $dataToUpdate);
            if ($update <= 0) {
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Appointment request failed to update'], 500);
            } else {
                DB::commit();

                UserModel::sendEmail($checkAppointment->PatientEmailAddress, $emailMessageForPatient, null);
                //Now sending sms to patient
                if ($checkAppointment->PatientMobileNumber != null) {
                    $url = env('WEB_URL') . '/#/';
                    $toNumber = array();
                    $phoneCode = getenv("PAK_NUM_CODE");//fetch from front-end
                    $mobileNumber = $phoneCode . $checkAppointment->PatientMobileNumber;
                    array_push($toNumber, $mobileNumber);
                    HelperModel::sendSms($toNumber, 'Dear Patient, Your appointment request has been ' . $reqStatus . '.', $url);
                }
                UserModel::sendEmail($checkAppointment->DoctorEmailAddress, $emailMessageForDoctor, null);

                //Now sending sms to doctor
                if ($checkAppointment->DoctorMobileNumber != null) {
                    $url = env('WEB_URL') . '/#/';
                    $toNumber = array();
                    $phoneCode = getenv("PAK_NUM_CODE");//fetch from front-end
                    $mobileNumber = $phoneCode . $checkAppointment->DoctorMobileNumber;
                    array_push($toNumber, $mobileNumber);
                    HelperModel::sendSms($toNumber, 'Dear Doctor, You have ' . $reqStatus . ' your patient appointment request. View details from the following link', $url);
                }
                return response()->json(['data' => $appointmentId, 'message' => 'Appointment request successfully updated'], 200);
            }
        }
    }

    //function will update the appointment held status
    //to cancel, against the provided appointmentId
    //appointment can be cancelled by doctor or patient
    function MarkAppointmentCancel(Request $request)
    {
        error_log('in controller');
        error_log('in mark appointment cancel function');

        $doctorRoleCode = env('ROLE_DOCTOR');
        $patientRoleCode = env('ROLE_PATIENT');

        $appointmentCancelledByPatientStatus = env('APPOINTMENT_CANCELLED_BY_PATIENT');
        $appointmentCancelledByDoctorStatus = env('APPOINTMENT_CANCELLED_BY_DOCTOR');

        $appointmentRequestRejected = env('APPOINTMENT_REJECTED_REQUEST_STATUS');

        $appointmentStatusPatientVisited = env('APPOINTMENT_PATIENT_VISIT_STATUS');

        $appointmentId = $request->get('aId');//refers to appointmentId
        $userId = $request->get('userId');//refers to logged in userId can be patient or doctor
        $reason = $request->post('reason'); //refers to cancellation reason

        //first apply following check
        //appointment id should belong to logged in user


        $getAppointmentData = DoctorScheduleModel::getSingleAppointmentViaId($appointmentId);
        if ($getAppointmentData == null) {
            return response()->json(['data' => null, 'message' => 'Appointment not found'], 400);
        } else {

            //if appointment is already completed
            //it cannot be cancelled

            //if appointment request is already rejected
            //it cannot be cancelled

            if ($getAppointmentData->RequestStatus == $appointmentRequestRejected) {
                error_log('patient has already rejected');
                return response()->json(['data' => null, 'message' => 'Appointment cannot be cancelled because it is already ' . $getAppointmentData->RequestStatus . '.'], 400);
            }
            if ($getAppointmentData->AppointmentStatus == $appointmentStatusPatientVisited) {
                error_log('patient has already accepted');
                return response()->json(['data' => null, 'message' => 'Appointment cannot be cancelled because it has already accepted'], 400);
            }
        }
        //for patient appointment can only be
        //cancel before 48 hours of scheduled appointment
        //fetch this from .env

        //for doctor appointment can only be
        //cancel before 24 hours of scheduled appointment
        //fetch this from .env

        $userData = UserModel::GetSingleUserViaId($userId);

        if (count($userData) > 0) {
            if ($userData[0]->RoleCodeName == $patientRoleCode) {
                error_log('Role is patient');

                //after checks update the status
                $dataToUpdate = array(
                    "AppointmentStatus" => $appointmentCancelledByPatientStatus,
                    "AppointmentStatusReason" => $reason
                );
            } else if ($userData[0]->RoleCodeName == $doctorRoleCode) {
                error_log('Role is doctor');
                $dataToUpdate = array(
                    "AppointmentStatus" => $appointmentCancelledByDoctorStatus,
                    "AppointmentStatusReason" => $reason
                );
            } else {
                return response()->json(['data' => null, 'message' => 'logged in user must be doctor or patient'], 400);
            }
        } else {
            return response()->json(['data' => null, 'message' => 'User record not found'], 400);
        }


        DB::beginTransaction();
        $update = GenericModel::updateGeneric('appointment', 'Id', $appointmentId, $dataToUpdate);
        if ($update <= 0) {
            DB::rollBack();
            return response()->json(['data' => null, 'message' => 'Appointment failed to cancel'], 500);
        } else {
            DB::commit();
            return response()->json(['data' => $appointmentId, 'message' => 'Appointment successfully cancelled'], 200);
        }
    }

    //function to get associated doctor
    //against the provided patient id
    function GetPatientAssociatedDoctor(Request $request)
    {
        error_log('in controller');
        error_log('in GetPatientAssociatedDoctor function');

        $patientRole = env('ROLE_PATIENT');
        $patientId = $request->get('patientId');

        //apply check if provided patient id is
        //exactly of patient by using role

        $associatedDoctor = DoctorScheduleModel::fetAssociatedDoctor($patientId);

        if ($associatedDoctor != null) {
            return response()->json(['data' => $associatedDoctor, 'message' => 'Associated Doctor fetched successfully'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'No Associated Doctor for this Patient'], 200);
        }
    }
}
