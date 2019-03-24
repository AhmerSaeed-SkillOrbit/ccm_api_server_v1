<?php
/**
 * Created by PhpStorm.
 * User: Ahsan Vohra
 * Date: 3/2/2019
 * Time: 2:08 PM
 */

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HelperModel;

use Mail;


class DoctorScheduleModel
{
    static public function getDoctorScheduleAhmer($doctorId, $month, $year)
    {
        error_log('in model');

        $query = DB::table('doctor_schedule_copy1')
            ->select('Id', 'StartDate', 'EndDate', 'MonthName', 'YearName')
            ->where('DoctorId', '=', $doctorId)
            ->where('MonthName', '=', $month)
            ->where('YearName', '=', $year)
            ->where('IsActive', '=', true)
            ->first();

        return $query;
    }

    static public function getDoctorScheduleAllViaPagination($doctorId, $offset, $limit)
    {
        error_log('in model');

        $query = DB::table('doctor_schedule_copy1')
            ->select('Id', 'StartDate', 'EndDate', 'MonthName', 'YearName')
            ->where('DoctorId', '=', $doctorId)
            ->where('IsActive', '=', true)
            ->orderBy('MonthName', 'ASC')
//            ->offset($offset)->limit($limit)
            ->skip($offset * $limit)->take($limit)
            ->get();

        return $query;
    }

    static public function getDoctorScheduleAllCount($doctorId)
    {
        error_log('in model');

        $query = DB::table('doctor_schedule_copy1')
            ->where('DoctorId', '=', $doctorId)
            ->where('IsActive', '=', true)
            ->count();

        return $query;
    }

    static public function getDoctorSchedule($doctorId)
    {
        error_log('in model');

        $query = DB::table('doctor_schedule_copy1')
            ->select('Id', 'StartDate', 'EndDate')
            ->where('DoctorId', '=', $doctorId)
            ->where('IsActive', '=', true)
            ->get();

        return $query;
    }

    static public function getDoctorScheduleDetailOld($doctorScheduleId)
    {
        error_log('in model');

//        $query = DB::table('doctor_schedule_detail')
//            ->select('Id', 'ScheduleDate', 'EndTime', 'ShiftType', 'IsOffDay')
//            ->where('DoctorScheduleId', '=', $doctorScheduleId)
//            ->where('IsActive', '=', true)
//            ->get();

        $query = DB::table("doctor_schedule_detail_copy1")
            ->select("Id", "ScheduleDate", "ShiftType",
                "IsOffDay", DB::raw('TIME_FORMAT(StartTime, "%H:%i %p") as StartTime'),
                DB::raw('TIME_FORMAT(EndTime, "%H:%i %p") as EndTime'))
            ->where("DoctorScheduleId", "=", $doctorScheduleId)
            ->where("IsActive", "=", true)
            ->get();

        return $query;
    }

    static public function getDoctorScheduleDetailNew($doctorScheduleId)
    {
        error_log('in model');

//        $query = DB::table('doctor_schedule_detail')
//            ->select('Id', 'ScheduleDate', 'EndTime', 'ShiftType', 'IsOffDay')
//            ->where('DoctorScheduleId', '=', $doctorScheduleId)
//            ->where('IsActive', '=', true)
//            ->get();

        $query = DB::table("doctor_schedule_detail_copy1")
            ->select("Id", "ScheduleDate", "NoOfShift",
                "IsOffDay")
            ->where("DoctorScheduleId", "=", $doctorScheduleId)
            ->where("IsActive", "=", true)
            ->orderBy('ScheduleDate', 'ASC')
            ->get();

        return $query;
    }

    static public function getDoctorScheduleDetailViaId($doctorScheduleDetailId)
    {
        error_log('in model');

        $query = DB::table("doctor_schedule_detail_copy1")
            ->select("Id", "ScheduleDate", "NoOfShift",
                "IsOffDay")
            ->where("Id", "=", $doctorScheduleDetailId)
            ->where("IsActive", "=", true)
            ->first();

        return $query;
    }

    static public function getDoctorScheduleShift($doctorScheduleDetailId)
    {
        error_log('in model');

        $query = DB::table("doctor_schedule_shift")
            ->select("Id", DB::raw('TIME_FORMAT(StartTime, "%H:%i") as StartTime'),
                DB::raw('TIME_FORMAT(EndTime, "%H:%i") as EndTime'), 'NoOfPatientAllowed')
            ->where("DoctorScheduleDetailId", "=", $doctorScheduleDetailId)
            ->where("IsActive", "=", true)
            ->get();

        return $query;
    }

    static public function getMultipleDoctorScheduleShift($doctorScheduleDetailId)
    {
        error_log('in model');

        $query = DB::table("doctor_schedule_shift")
            ->select("Id", 'DoctorScheduleDetailId', DB::raw('TIME_FORMAT(StartTime, "%H:%i %p") as StartTime'),
                DB::raw('TIME_FORMAT(EndTime, "%H:%i %p") as EndTime'), 'NoOfPatientAllowed')
            ->whereIn("DoctorScheduleDetailId", $doctorScheduleDetailId)
            ->where("IsActive", "=", true)
            ->get();

        return $query;
    }

    static public function getAppointmentViaShiftId($doctorScheduleShiftId)
    {
        error_log('in model');

        $query = DB::table("appointment")
            ->select("Id")
            ->where("DoctorScheduleShiftId", '=', $doctorScheduleShiftId)
            ->where("IsActive", "=", true)
            ->get();

        return $query;
    }

    static public function getDoctorScheduleShiftViaId($doctorScheduleShiftId)
    {
        error_log('in model');

        $query = DB::table("doctor_schedule_shift")
            ->select("Id", 'DoctorScheduleDetailId', DB::raw('TIME_FORMAT(StartTime, "%H:%i %p") as StartTime'),
                DB::raw('TIME_FORMAT(EndTime, "%H:%i %p") as EndTime'))
            ->where("Id", "=", $doctorScheduleShiftId)
            ->where("IsActive", "=", true)
            ->first();

        return $query;
    }

    static public function getDoctorScheduleShiftTimeSlotsViaDoctorScheduleShiftId($doctorScheduleShiftId)
    {
        error_log('in model');

        $query = DB::table("shift_time_slot")
            ->select('Id', 'DoctorScheduleShiftId', 'TimeSlot', 'IsBooked')
            ->where("DoctorScheduleShiftId", "=", $doctorScheduleShiftId)
            ->orderBy('Id', 'asc')
            ->get();

        return $query;
    }

    static public function getLastAppointment()
    {
        error_log('in model');

        $query = DB::table("appointment")
            ->select('AppointmentNumber')
            ->where("IsActive", "=", true)
            ->orderBy('Id', 'desc')
            ->first();

        return $query;
    }

    static public function getShiftSlotViaId($shiftSlotId)
    {
        error_log('in model');

        $query = DB::table("shift_time_slot")
            ->select('Id', 'DoctorScheduleShiftId', 'TimeSlot', 'IsBooked')
            ->where("Id", "=", $shiftSlotId)
            ->first();

        return $query;
    }

    static public function getMultipleAppointmentsViaDoctorAndPatientId($doctorId, $reqStatus, $patientIds, $pageNo, $limit)
    {
        error_log('in model');

        $query = DB::table("appointment")
            ->leftjoin('user as patient', 'appointment.PatientId', 'patient.Id')
            ->leftjoin('doctor_schedule_shift as ScheduleShift', 'appointment.DoctorScheduleShiftId', 'ScheduleShift.Id')
            ->leftjoin('doctor_schedule_detail_copy1 as ScheduleDetail', 'ScheduleShift.DoctorScheduleDetailId', 'ScheduleDetail.Id')
            ->leftjoin('shift_time_slot as ScheduleShiftTime', 'ScheduleShiftTime.DoctorScheduleShiftId', 'ScheduleShift.Id')
            ->select('appointment.Id', 'appointment.AppointmentNumber', 'patient.FirstName AS PatientFirstName',
                'patient.LastName AS PatientLastName', 'ScheduleDetail.ScheduleDate', 'ScheduleShiftTime.TimeSlot')
            ->where("appointment.IsActive", "=", true)
            ->where("appointment.DoctorId", "=", $doctorId)
            ->where("appointment.RequestStatus", "=", $reqStatus)
            ->whereIn("appointment.PatientId", $patientIds)
            ->orderBy('appointment.Id', 'desc')
            ->groupBy('appointment.Id')
            ->skip($pageNo * $limit)
            ->take($limit)
            ->get();

        return $query;
    }

    static public function getAppointmentViaPatientId($patientId, $searchKeyword, $reqStatus, $pageNo, $limit)
    {
        error_log('in model');

        if ($searchKeyword == "null" || $searchKeyword == null) {
            $query = DB::table("appointment")
                ->leftjoin('user as doctor', 'appointment.DoctorId', 'doctor.Id')
                ->leftjoin('user as patient', 'appointment.PatientId', 'patient.Id')
                ->leftjoin('doctor_schedule_shift as ScheduleShift', 'appointment.DoctorScheduleShiftId', 'ScheduleShift.Id')
                ->leftjoin('shift_time_slot as ScheduleShiftTime', 'appointment.ShiftTimeSlotId', 'ScheduleShiftTime.Id')
                ->leftjoin('doctor_schedule_detail_copy1 as ScheduleDetail', 'ScheduleShift.DoctorScheduleDetailId', 'ScheduleDetail.Id')
                ->select('appointment.Id', 'appointment.RequestStatus', 'appointment.AppointmentNumber',
                    'patient.FirstName AS PatientFirstName', 'patient.LastName AS PatientLastName', 'patient.EmailAddress AS PatientEmailAddress',
                    'patient.MobileNumber AS PatientMobileNumber',
                    'doctor.FirstName AS DoctorFirstName', 'doctor.LastName AS DoctorLastName',
                    'doctor.EmailAddress AS DoctorEmailAddress', 'doctor.MobileNumber AS DoctorMobileNumber',
                    'ScheduleDetail.ScheduleDate', 'ScheduleShiftTime.TimeSlot')
                ->where("appointment.IsActive", "=", true)
                ->where("appointment.RequestStatus", "=", $reqStatus)
                ->where("appointment.PatientId", '=', $patientId)
                ->orderBy('appointment.Id', 'desc')
                ->groupBy('appointment.Id')
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
        } else {
            $query = DB::table("appointment")
                ->leftjoin('user as doctor', 'appointment.DoctorId', 'doctor.Id')
                ->leftjoin('user as patient', 'appointment.PatientId', 'patient.Id')
                ->leftjoin('doctor_schedule_shift as ScheduleShift', 'appointment.DoctorScheduleShiftId', 'ScheduleShift.Id')
                ->leftjoin('shift_time_slot as ScheduleShiftTime', 'appointment.ShiftTimeSlotId', 'ScheduleShiftTime.Id')
                ->leftjoin('doctor_schedule_detail_copy1 as ScheduleDetail', 'ScheduleShift.DoctorScheduleDetailId', 'ScheduleDetail.Id')
                ->select('appointment.Id', 'appointment.RequestStatus', 'appointment.AppointmentNumber',
                    'patient.FirstName AS PatientFirstName', 'patient.LastName AS PatientLastName', 'patient.EmailAddress AS PatientEmailAddress',
                    'patient.MobileNumber AS PatientMobileNumber',
                    'doctor.FirstName AS DoctorFirstName', 'doctor.LastName AS DoctorLastName',
                    'doctor.EmailAddress AS DoctorEmailAddress', 'doctor.MobileNumber AS DoctorMobileNumber',
                    'ScheduleDetail.ScheduleDate', 'ScheduleShiftTime.TimeSlot')
                ->where("appointment.IsActive", "=", true)
                ->where("appointment.RequestStatus", "=", $reqStatus)
                ->where("appointment.PatientId", '=', $patientId)
                ->Where('doctor.FirstName', 'LIKE', '%' . $searchKeyword . '%')
                ->orWhere('doctor.LastName', 'LIKE', '%' . $searchKeyword . '%')
                ->orderBy('appointment.Id', 'desc')
                ->groupBy('appointment.Id')
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
        }

        return $query;
    }

    static public function getAppointmentViaDoctorId($doctorId, $searchKeyword, $reqStatus, $pageNo, $limit)
    {
        error_log('in model');
        if ($searchKeyword == "null" || $searchKeyword == null) {
            $query = DB::table("appointment")
                ->leftjoin('user as doctor', 'appointment.DoctorId', 'doctor.Id')
                ->leftjoin('user as patient', 'appointment.PatientId', 'patient.Id')
                ->leftjoin('doctor_schedule_shift as ScheduleShift', 'appointment.DoctorScheduleShiftId', 'ScheduleShift.Id')
                ->leftjoin('shift_time_slot as ScheduleShiftTime', 'appointment.ShiftTimeSlotId', 'ScheduleShiftTime.Id')
                ->leftjoin('doctor_schedule_detail_copy1 as ScheduleDetail', 'ScheduleShift.DoctorScheduleDetailId', 'ScheduleDetail.Id')
                ->select('appointment.Id', 'appointment.ShiftTimeSlotId', 'appointment.RequestStatus', 'appointment.AppointmentNumber',
                    'patient.FirstName AS PatientFirstName', 'patient.LastName AS PatientLastName', 'patient.EmailAddress AS PatientEmailAddress',
                    'patient.MobileNumber AS PatientMobileNumber',
                    'doctor.FirstName AS DoctorFirstName', 'doctor.LastName AS DoctorLastName',
                    'doctor.EmailAddress AS DoctorEmailAddress', 'doctor.MobileNumber AS DoctorMobileNumber',
                    'ScheduleDetail.ScheduleDate', 'ScheduleShiftTime.TimeSlot')
                ->where("appointment.IsActive", "=", true)
                ->where("appointment.RequestStatus", "=", $reqStatus)
                ->where("appointment.DoctorId", '=', $doctorId)
                ->orderBy('appointment.Id', 'desc')
                ->groupBy('appointment.Id')
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
        } else {
            $query = DB::table("appointment")
                ->leftjoin('user as doctor', 'appointment.DoctorId', 'doctor.Id')
                ->leftjoin('user as patient', 'appointment.PatientId', 'patient.Id')
                ->leftjoin('doctor_schedule_shift as ScheduleShift', 'appointment.DoctorScheduleShiftId', 'ScheduleShift.Id')
                ->leftjoin('shift_time_slot as ScheduleShiftTime', 'appointment.ShiftTimeSlotId', 'ScheduleShiftTime.Id')
                ->leftjoin('doctor_schedule_detail_copy1 as ScheduleDetail', 'ScheduleShift.DoctorScheduleDetailId', 'ScheduleDetail.Id')
                ->select('appointment.Id', 'appointment.RequestStatus', 'appointment.AppointmentNumber',
                    'patient.FirstName AS PatientFirstName', 'patient.LastName AS PatientLastName', 'patient.EmailAddress AS PatientEmailAddress',
                    'patient.MobileNumber AS PatientMobileNumber',
                    'doctor.FirstName AS DoctorFirstName', 'doctor.LastName AS DoctorLastName',
                    'doctor.EmailAddress AS DoctorEmailAddress', 'doctor.MobileNumber AS DoctorMobileNumber',
                    'ScheduleDetail.ScheduleDate', 'ScheduleShiftTime.TimeSlot')
                ->where("appointment.IsActive", "=", true)
                ->where("appointment.RequestStatus", "=", $reqStatus)
                ->where("appointment.DoctorId", '=', $doctorId)
                ->Where('patient.FirstName', 'LIKE', '%' . $searchKeyword . '%')
                ->orWhere('patient.LastName', 'LIKE', '%' . $searchKeyword . '%')
                ->orderBy('appointment.Id', 'desc')
                ->groupBy('appointment.Id')
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
        }

        return $query;
    }

    static public function getAppointmentCountViaPatientId($patientId, $searchKeyword, $reqStatus)
    {
        error_log('in model');

        if ($searchKeyword == "null" || $searchKeyword == null) {

            $query = DB::table("appointment")
                ->where("appointment.IsActive", "=", true)
                ->where("appointment.RequestStatus", "=", $reqStatus)
                ->where("appointment.PatientId", '=', $patientId)
                ->count();
        } else {

            $query = DB::table("appointment")
                ->leftjoin('user as doctor', 'appointment.DoctorId', 'doctor.Id')
                ->leftjoin('user as patient', 'appointment.PatientId', 'patient.Id')
                ->leftjoin('doctor_schedule_shift as ScheduleShift', 'appointment.DoctorScheduleShiftId', 'ScheduleShift.Id')
                ->leftjoin('shift_time_slot as ScheduleShiftTime', 'appointment.ShiftTimeSlotId', 'ScheduleShiftTime.Id')
                ->leftjoin('doctor_schedule_detail_copy1 as ScheduleDetail', 'ScheduleShift.DoctorScheduleDetailId', 'ScheduleDetail.Id')
                ->where("appointment.IsActive", "=", true)
                ->where("appointment.RequestStatus", "=", $reqStatus)
                ->where("appointment.PatientId", '=', $patientId)
                ->Where('doctor.FirstName', 'LIKE', '%' . $searchKeyword . '%')
                ->orWhere('doctor.LastName', 'LIKE', '%' . $searchKeyword . '%')
                ->count();
        }

        return $query;
    }

    static public function getAppointmentCountViaDoctorId($doctorId, $searchKeyword, $reqStatus)
    {
        error_log('in model');

        if ($searchKeyword == "null" || $searchKeyword == null) {

            $query = DB::table("appointment")
                ->where("appointment.IsActive", "=", true)
                ->where("appointment.RequestStatus", "=", $reqStatus)
                ->where("appointment.DoctorId", '=', $doctorId)
                ->count();
        } else {

            $query = DB::table("appointment")
                ->leftjoin('user as doctor', 'appointment.DoctorId', 'doctor.Id')
                ->leftjoin('user as patient', 'appointment.PatientId', 'patient.Id')
                ->leftjoin('doctor_schedule_shift as ScheduleShift', 'appointment.DoctorScheduleShiftId', 'ScheduleShift.Id')
                ->leftjoin('shift_time_slot as ScheduleShiftTime', 'appointment.ShiftTimeSlotId', 'ScheduleShiftTime.Id')
                ->leftjoin('doctor_schedule_detail_copy1 as ScheduleDetail', 'ScheduleShift.DoctorScheduleDetailId', 'ScheduleDetail.Id')
                ->where("appointment.IsActive", "=", true)
                ->where("appointment.RequestStatus", "=", $reqStatus)
                ->where("appointment.DoctorId", '=', $doctorId)
                ->Where('patient.FirstName', 'LIKE', '%' . $searchKeyword . '%')
                ->orWhere('patient.LastName', 'LIKE', '%' . $searchKeyword . '%')
                ->count();
        }

        return $query;
    }

    static public function getSingleAppointmentViaId($appointmentId)
    {
        error_log('in model');

        $query = DB::table("appointment")
            ->leftjoin('user as patient', 'appointment.PatientId', 'patient.Id')
            ->leftjoin('user as doctor', 'appointment.DoctorId', 'doctor.Id')
            ->leftjoin('doctor_schedule_shift as ScheduleShift', 'appointment.DoctorScheduleShiftId', 'ScheduleShift.Id')
            ->leftjoin('shift_time_slot as ScheduleShiftTime', 'ScheduleShiftTime.DoctorScheduleShiftId', 'ScheduleShift.Id')
            ->leftjoin('doctor_schedule_detail_copy1 as ScheduleDetail', 'ScheduleShift.DoctorScheduleDetailId', 'ScheduleDetail.Id')
            ->select('appointment.*', 'patient.FirstName AS PatientFirstName', 'patient.LastName AS PatientLastName', 'patient.EmailAddress AS PatientEmailAddress',
                'patient.MobileNumber AS PatientMobileNumber',
                'doctor.FirstName AS DoctorFirstName', 'doctor.LastName AS DoctorLastName', 'doctor.EmailAddress AS DoctorEmailAddress', 'doctor.MobileNumber AS DoctorMobileNumber',
                'ScheduleDetail.ScheduleDate', 'ScheduleShiftTime.TimeSlot')
            ->where("appointment.IsActive", "=", true)
            ->where('appointment.Id', '=', $appointmentId)
            ->first();

        return $query;
    }

    static public function getMultipleAppointmentsCountViaDoctorAndPatientId($doctorId, $reqStatus, $patientIds)
    {
        error_log('in model');


        $query = DB::table("appointment")
            ->where("appointment.IsActive", "=", true)
            ->where("appointment.DoctorId", "=", $doctorId)
            ->where("appointment.RequestStatus", "=", $reqStatus)
            ->whereIn("appointment.PatientId", $patientIds)
            ->count();

        return $query;
    }

    static public function fetAssociatedDoctor($patientId)
    {
        error_log('in model fetAssociatedDoctor');

        $query = DB::table("user_association as ua")
            ->leftjoin('user as doctor', 'ua.SourceUserId', 'doctor.Id')
            ->select('doctor.Id AS DoctorId', 'doctor.FirstName AS DoctorFirstName', 'doctor.LastName AS DoctorLastName',
                'doctor.FunctionalTitle AS DoctorFunctionalTitle')
            ->where("ua.DestinationUserId", "=", $patientId)
            ->first();

        return $query;
    }

    static public function getDoctorScheduleShiftDataViaId($doctorScheduleShiftId)
    {
        error_log('in model');

        $query = DB::table("doctor_schedule_shift as dcf")
            ->leftjoin('doctor_schedule_detail_copy1 as dcdc', 'dcf.DoctorScheduleDetailId', 'dcdc.Id')
//            ->select("dcdc.Id", 'DoctorScheduleDetailId', DB::raw('TIME_FORMAT(dcdc.ScheduleDate, "YYYY-MM-DD") as ScheduleDate'))
            ->select("dcdc.Id", 'DoctorScheduleDetailId', 'dcdc.ScheduleDate as ScheduleDate')
            ->where("dcf.Id", "=", $doctorScheduleShiftId)
            ->where("dcf.IsActive", "=", true)
            ->first();

        return $query;
    }

    //Function to check if patient has already taken an appointment on the same date

    static public function getDoctorScheduleShiftDataViaPatientId($patientId)
    {
        error_log('in model');

        $query = DB::table("appointment as app")
            ->leftjoin('doctor_schedule_shift as dcf', 'app.DoctorScheduleShiftId', 'dcf.Id')
            ->leftjoin('doctor_schedule_detail_copy1 as dcdc', 'dcf.DoctorScheduleDetailId', 'dcdc.Id')
//            ->select("dcdc.Id", 'DoctorScheduleDetailId', DB::raw('TIME_FORMAT(dcdc.ScheduleDate, "YYYY-MM-DD") as ScheduleDate'))
            ->select('dcdc.Id', 'DoctorScheduleDetailId', 'dcdc.ScheduleDate as ScheduleDate')
            ->where("app.PatientId", "=", $patientId)
            ->where("app.RequestStatus", "!=", "rejected")
            ->where("app.IsActive", "=", true)
            ->get();

        return $query;
    }

    static public function getTimeSlotTemp()
    {
        error_log('in model');

        $query = DB::table("doctor_schedule_shift as dsf")
            ->select("dsf.StartTime", 'dsf.EndTime')
            ->where("dsf.Id", "=", 2899)
            ->first();

        return $query;
    }

    static public function CalculateTimeSlotDynamically($startTime, $endTime, $patientAllowed)
    {
        $indexItem = null;
        $endLastTime = array();
        $timeSlots = array();
        $diff = 0;
        $min = 0;
        $endSlot1 = 0;
        $endSlot2 = 0;

        error_log($startTime);
        error_log($endTime);
        error_log($patientAllowed);

        if ($indexItem == null) {
            error_log("$indexItem is start");

            $diff = number_format((new Carbon($startTime))->diff(new Carbon($endTime))->format('%h'));

            if ($diff == 0) {
                $diff = number_format((new Carbon($startTime))->diff(new Carbon($endTime))->format('%i'));
                error_log('time difference in mints');
                error_log($diff);
                $avg = $diff / $patientAllowed;
                error_log('avg time in mints');
                error_log($avg);
                $min = $avg;//no need to convert in mints
            } else {
                error_log('time difference in hours');
                error_log($diff);
                $avg = $diff / $patientAllowed;
                error_log('avg time in hours');
                error_log($avg);
                //convert to mints
                $min = $avg * 60;
                error_log('convert in mints');
                error_log($min);
            }

            $endSlot1 = (new Carbon($startTime))->addMinute($min)->format('H:i:s');
            $indexItem = $endSlot1;

            error_log("end slot");
            error_log($endSlot1);

            error_log("index Item");
            error_log($indexItem);

            $range = $startTime . '-' . $endSlot1;

            error_log("range");
            error_log($range);

            array_push($timeSlots, $range);
        }
        while ($indexItem < $endTime) {

            $endSlot2 = (new Carbon($endSlot1))->addMinute($min)->format('H:i:s');

            error_log("end slot2 n endTime");

            error_log($endSlot2);
            error_log($endTime);

            if ($endSlot2 <= $endTime) {
                error_log("in while loop");

                $range = $endSlot1 . '-' . $endSlot2;

                $indexItem = $endSlot2;
                $endSlot1 = $endSlot2;

                error_log("end slot");
                error_log($endSlot1);

                error_log("index Item");
                error_log($indexItem);

                error_log("range");
                error_log($range);

                array_push($timeSlots, $range);
            } else {
                error_log("slot is exceed");
                error_log($endSlot2);
                $indexItem = $endSlot2;
            }
        }
        error_log("end now");
        return $timeSlots;
    }
}
