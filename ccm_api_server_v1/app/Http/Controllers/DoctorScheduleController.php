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

        error_log('in controller  d');
        //First check if logged in user role is doctor or not.

        $doctorData = UserModel::GetSingleUserViaIds($doctorId);

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
        $doctorScheduleDetail = array();

        $getRange = DoctorScheduleModel::getDoctorScheduleAhmer($doctorId);

        if ($getRange == null) {
            return response()->json(['data' => null, 'message' => 'No schedule for this doctor'], 400);
        }

        $doctorScheduleDetail['StartDate'] = $getRange->StartDate;
        $doctorScheduleDetail['EndDate'] = $getRange->EndDate;
        $doctorScheduleDetail['Id'] = $getRange->Id;

        $getDetail = DoctorScheduleModel::getDoctorScheduleDetail($getRange->Id);
        if (count($getDetail) > 0) {
            $doctorScheduleDetail['DoctorScheduleDetails'] = $getDetail;
        }

        return response()->json(['data' => $doctorScheduleDetail, 'message' => 'Doctor schedule found'], 200);
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
