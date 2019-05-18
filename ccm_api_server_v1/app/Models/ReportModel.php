<?php
/**
 * Created by PhpStorm.
 * User: SO-LPT-031
 * Date: 5/18/2019
 * Time: 2:41 PM
 */

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HelperModel;

class ReportModel
{

    static public function getMultipleUsersViaPagination($userIds, $pageNo, $limit, $searchStartDate, $searchEndDate)
    {
        if ($searchStartDate != "null" && $searchEndDate != "null") {

            error_log('search date is not null');

            $result = DB::table('user')
                ->select('user.Id', 'user.PatientUniqueId', 'user.FirstName', 'user.LastName', 'user.MiddleName', 'user.DateOfBirth',
                    'user.CreatedOn', 'user.RegisteredAs')
                ->whereIn('Id', $userIds)
                ->where('IsActive', '=', true)
                ->where('CreatedOn', '>=', $searchStartDate)
                ->where('CreatedOn', '<=', $searchEndDate)
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
            return $result;
        } else {
            error_log('search date is null');
            $result = DB::table('user')
                ->select('user.Id', 'user.PatientUniqueId', 'user.FirstName', 'user.LastName', 'user.MiddleName', 'user.DateOfBirth',
                    'user.CreatedOn', 'user.RegisteredAs')
                ->whereIn('Id', $userIds)
                ->where('IsActive', '=', true)
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
            return $result;
        }
    }

    static public function getMultipleUsersCount($userIds, $searchStartDate, $searchEndDate)
    {
        if ($searchStartDate != "null" && $searchEndDate != "null") {

            error_log('search date is not null');

            $result = DB::table('user')
                ->whereIn('Id', $userIds)
                ->where('IsActive', '=', true)
                ->where('CreatedOn', '>=', $searchStartDate)
                ->where('CreatedOn', '<=', $searchEndDate)
                ->count();
            return $result;
        } else {
            error_log('search date is null');
            $result = DB::table('user')
                ->whereIn('Id', $userIds)
                ->where('IsActive', '=', true)
                ->count();
            return $result;
        }
    }

    static public function getUsersViaRegisteredAs($userIds, $registeredAs, $searchStartDate, $searchEndDate)
    {
        if ($searchStartDate != "null" && $searchEndDate != "null") {

            $result = DB::table('user')
                ->whereIn('Id', $userIds)
                ->where('IsActive', '=', true)
                ->where('CreatedOn', '>=', $searchStartDate)
                ->where('CreatedOn', '<=', $searchEndDate)
                ->where('RegisteredAs', '=', $registeredAs)
                ->get();
            return $result;
        } else {

            $result = DB::table('user')
                ->whereIn('Id', $userIds)
                ->where('IsActive', '=', true)
                ->where('RegisteredAs', '=', $registeredAs)
                ->get();
            return $result;
        }
    }
}