<?php
/**
 * Created by PhpStorm.
 * User: Ahsan Vohra
 * Date: 3/18/2019
 * Time: 8:04 PM
 */

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HelperModel;


class TicketModel
{
    static public function getLastTicket()
    {
        error_log('in model, fetching last ticket number');

        $query = DB::table("ticket")
            ->select('TicketNumber')
            ->where("IsActive", "=", true)
            ->orderBy('Id', 'desc')
            ->first();

        return $query;
    }

    static public function GetTicketListViaPagination($pageNo, $limit)
    {
        error_log('in model, fetching tickets generated');

        $query = DB::table('ticket')
            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
            ->where('ticket.IsActive', '=', true)
            ->orderBy('ticket.Id', 'DESC')
            ->skip($pageNo * $limit)
            ->take($limit)
            ->get();

        return $query;
    }

    static public function GetTicketListCount()
    {
        error_log('in model, fetching tickets count');

        $query = DB::table('ticket')
            ->where('ticket.IsActive', '=', true)
            ->count();

        return $query;
    }

}
