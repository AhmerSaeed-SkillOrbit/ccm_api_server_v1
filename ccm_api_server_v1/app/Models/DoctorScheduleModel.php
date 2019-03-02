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
    static public function getDoctorSchedule($doctorId)
    {
        error_log('in model');

        $query = DB::table('doctor_schedule')
            ->select('Id', 'StartDate', 'EndDate')
            ->where('DoctorId', '=', $doctorId)
            ->where('IsActive', '=', true)
            ->get();

        return $query;
    }

    static public function getDoctorScheduleDetail($doctorScheduleId)
    {
        error_log('in model');

        $query = DB::table('doctor_schedule_detail')
            ->select('Id', 'ScheduleDate', 'StartTime', 'EndTime', 'ShiftType', 'IsOffDay')
            ->where('DoctorScheduleId', '=', $doctorScheduleId)
            ->where('IsActive', '=', true)
            ->get();

        return $query;
    }

}
