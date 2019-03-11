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
            ->select("Id", DB::raw('TIME_FORMAT(StartTime, "%H:%i %p") as StartTime'),
                DB::raw('TIME_FORMAT(EndTime, "%H:%i %p") as EndTime', 'NoOfPatientAllowed'))
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
                DB::raw('TIME_FORMAT(EndTime, "%H:%i %p") as EndTime', 'NoOfPatientAllowed'))
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
            ->leftjoin('shift_time_slot as ScheduleShiftTime', 'ScheduleShiftTime.DoctorScheduleShiftId', 'ScheduleShift.Id')
            ->leftjoin('doctor_schedule_detail_copy1 as ScheduleDetail', 'ScheduleShift.DoctorScheduleDetailId', 'ScheduleDetail.Id')
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
            ->select("dcdc.Id", 'DoctorScheduleDetailId', DB::raw('TIME_FORMAT(dcdc.ScheduleDate, "%H:%i %p") as ScheduleDate'))
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
            ->select("dcdc.Id", 'DoctorScheduleDetailId', DB::raw('TIME_FORMAT(dcdc.ScheduleDate, "%H:%i %p") as ScheduleDate'))
            ->where("app.PatientId", "=", $patientId)
            ->where("app.RequestStatus", "!=", "rejected")
            ->where("app.IsActive", "=", true)
            ->get();

        return $query;
    }
}
