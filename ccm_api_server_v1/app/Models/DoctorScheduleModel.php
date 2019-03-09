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
                DB::raw('TIME_FORMAT(EndTime, "%H:%i %p") as EndTime'))
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
                DB::raw('TIME_FORMAT(EndTime, "%H:%i %p") as EndTime'))
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

}
