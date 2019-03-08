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
        $doctorScheduleShiftData = array();

        foreach ($scheduleDetail as $item) {
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
            $checkInsertedData = GenericModel::insertGenericAndReturnID('doctor_schedule_detail_copy1', $doctorScheduleDetailData);
            error_log('$checkInsertedData of doctor schedule detail' . $checkInsertedData);
            if ($checkInsertedData == false) {
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in inserting doctor schedule detail'], 400);
            }
            foreach ($item['ScheduleShift'] as $scheduleShift) {

                error_log('$scheduleShift[\'StartTime\'] is : ' . $scheduleShift['StartTime']);
                error_log('$scheduleShift[\'EndTime\'] is : ' . $scheduleShift['EndTime']);

//                if ($scheduleShift['StartTime'] > $scheduleShift['EndTime']) {
//                    DB::rollBack();
//                    return response()->json(['data' => null, 'message' => 'Start time should not exceed end time'], 400);
//                }
                array_push
                (
                    $doctorScheduleShiftData,
                    array(
                        "DoctorScheduleDetailId" => $checkInsertedData,
                        "StartTime" => $scheduleShift['StartTime'],
                        "EndTime" => $scheduleShift['EndTime'],
                        "IsActive" => true
                    )
                );
            }

            $checkInsertedData = GenericModel::insertGeneric('doctor_schedule_shift', $doctorScheduleShiftData);
            if ($checkInsertedData == false) {
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in inserting doctor schedule detail'], 400);
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

        $getDetail = DoctorScheduleModel::getDoctorScheduleDetail($getRange->Id);
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
            return response()->json(['data' => null, 'message' => 'No schedule found for this doctor'], 400);
        }

        $doctorScheduleDetail['Id'] = $getRange->Id;
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
}
