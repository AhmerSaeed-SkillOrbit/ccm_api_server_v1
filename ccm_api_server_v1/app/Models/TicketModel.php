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

    static public function GetTicketListViaPaginationAndSearch($pageNo, $limit, $searchKeyword, $ticketType, $trackStatus, $priority)
    {
        error_log('in model, fetching tickets generated');

        if ($ticketType == "null"
            && $trackStatus == "null"
            && $priority == "null"
            && $searchKeyword == "null"
        ) {
            error_log('All search parameters are null');

            $query = DB::table('ticket')
                ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                ->leftjoin('user_access', 'user_access.UserId', 'user.Id')
                ->leftjoin('role', 'user_access.RoleId', 'role.Id')
                ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                ->where('ticket.IsActive', '=', true)
                ->orderBy('ticket.Id', 'DESC')
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
        } else {
            error_log('some of the parameters are given');
            if ($searchKeyword != "null") {
                error_log('search keyword is not null');
                if ($ticketType != "null" && $trackStatus != "null" && $priority != "null") {
                    error_log('All parameters are given');

//                    DB::enableQueryLog();

                    $query = DB::table('ticket')
                        ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                        ->join('user_access', 'user_access.UserId', 'user.Id')
                        ->join('role', 'user_access.RoleId', 'role.Id')
                        ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.Priority', '=', $priority)
                        ->where('ticket.TrackStatus', '=', $trackStatus)
                        ->where('ticket.Type', '=', $ticketType)
                        ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                        ->orderBy('ticket.Id', 'DESC')
                        ->skip($pageNo * $limit)
                        ->take($limit)
                        ->get();

//                    dd(DB::getQueryLog());

                } else if ($ticketType != "null" || $trackStatus != "null" || $priority != "null") {
                    //Checks for only 1 drop down given
                    if ($ticketType != "null" && $priority == "null" && $trackStatus == "null") {
                        error_log('Search keyword and TICKET type is not null');

                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword and priority is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType == "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword and track status is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } //Now checks if any of the two parameters are given
                    else if ($ticketType != "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword and ticket type and priority is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus != "null") {
                        error_log('Search keyword and priority and track status is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType != "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword and ticket type and track status is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else {
                        error_log('Searching on the basis of all parameters');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    }
                } else {
                    error_log('Search only on the basis of search keyword');

                    $query = DB::table('ticket')
                        ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                        ->join('user_access', 'user_access.UserId', 'user.Id')
                        ->join('role', 'user_access.RoleId', 'role.Id')
                        ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                        ->where('ticket.IsActive', '=', true)
                        ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                        ->orderBy('ticket.Id', 'DESC')
                        ->skip($pageNo * $limit)
                        ->take($limit)
                        ->get();
                }
            } else {
                error_log('Search keyword is null, now checking drop down parameters');
                if ($ticketType != "null" && $trackStatus != "null" && $priority != "null") {
                    $query = DB::table('ticket')
                        ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                        ->join('user_access', 'user_access.UserId', 'user.Id')
                        ->join('role', 'user_access.RoleId', 'role.Id')
                        ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.Priority', '=', $priority)
                        ->where('ticket.TrackStatus', '=', $trackStatus)
                        ->where('ticket.Type', '=', $ticketType)
                        ->orderBy('ticket.Id', 'DESC')
                        ->skip($pageNo * $limit)
                        ->take($limit)
                        ->get();
                } else if ($ticketType != "null" || $trackStatus != "null" || $priority != "null") {
                    //Checks for only 1 drop down given
                    if ($ticketType != "null" && $priority == "null" && $trackStatus == "null") {
                        error_log('Search keyword IS NULL and TICKET type is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Type', '=', $ticketType)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword IS NULL and priority is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType == "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword IS NULL and track status is not null');

                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } //Now checks if any of the two parameters are given
                    else if ($ticketType != "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword IS NULL and ticket type and priority is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.Type', '=', $ticketType)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus != "null") {
                        error_log('Search keyword IS NULL and priority and track status is not null');

                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType != "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword IS NULL and ticket type and track status is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else {
                        error_log('give data on the basis of drop down values');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    }
                } else {
                    error_log('In search else');

                    $query = DB::table('ticket')
                        ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                        ->join('user_access', 'user_access.UserId', 'user.Id')
                        ->join('role', 'user_access.RoleId', 'role.Id')
                        ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.Priority', '=', $priority)
                        ->where('ticket.TrackStatus', '=', $trackStatus)
                        ->where('ticket.Type', '=', $ticketType)
                        ->orderBy('ticket.Id', 'DESC')
                        ->skip($pageNo * $limit)
                        ->take($limit)
                        ->get();
                }
            }
        }

        return $query;
    }

    static public function GetTicketListViaPaginationAndSearchForPatient($pageNo, $limit, $searchKeyword, $ticketType, $trackStatus, $priority, $userId)
    {
        error_log('in model, fetching tickets generated');

        if ($ticketType == "null"
            && $trackStatus == "null"
            && $priority == "null"
            && $searchKeyword == "null"
        ) {
            error_log('All search parameters are null');

            $query = DB::table('ticket')
                ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                ->join('user_access', 'user_access.UserId', 'user.Id')
                ->join('role', 'user_access.RoleId', 'role.Id')
                ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                ->where('ticket.IsActive', '=', true)
                ->where('ticket.RaiseById', '=', $userId)
                ->orderBy('ticket.Id', 'DESC')
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
        } else {
            error_log('some of the parameters are given');
            if ($searchKeyword != "null") {
                error_log('search keyword is not null');
                if ($ticketType != "null" && $trackStatus != "null" && $priority != "null") {
                    error_log('All parameters are given');

//                    DB::enableQueryLog();

                    $query = DB::table('ticket')
                        ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                        ->join('user_access', 'user_access.UserId', 'user.Id')
                        ->join('role', 'user_access.RoleId', 'role.Id')
                        ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.Priority', '=', $priority)
                        ->where('ticket.TrackStatus', '=', $trackStatus)
                        ->where('ticket.Type', '=', $ticketType)
                        ->where('ticket.RaiseById', '=', $userId)
                        ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                        ->orderBy('ticket.Id', 'DESC')
                        ->skip($pageNo * $limit)
                        ->take($limit)
                        ->get();

//                    dd(DB::getQueryLog());

                } else if ($ticketType != "null" || $trackStatus != "null" || $priority != "null") {
                    //Checks for only 1 drop down given
                    if ($ticketType != "null" && $priority == "null" && $trackStatus == "null") {
                        error_log('Search keyword and TICKET type is not null');

                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword and priority is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType == "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword and track status is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } //Now checks if any of the two parameters are given
                    else if ($ticketType != "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword and ticket type and priority is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus != "null") {
                        error_log('Search keyword and priority and track status is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType != "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword and ticket type and track status is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else {
                        error_log('Searching on the basis of all parameters');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    }
                } else {
                    error_log('Search only on the basis of search keyword');

                    $query = DB::table('ticket')
                        ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                        ->join('user_access', 'user_access.UserId', 'user.Id')
                        ->join('role', 'user_access.RoleId', 'role.Id')
                        ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.RaiseById', '=', $userId)
                        ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                        ->orderBy('ticket.Id', 'DESC')
                        ->skip($pageNo * $limit)
                        ->take($limit)
                        ->get();
                }
            } else {
                error_log('Search keyword is null, now checking drop down parameters');
                if ($ticketType != "null" && $trackStatus != "null" && $priority != "null") {
                    $query = DB::table('ticket')
                        ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                        ->join('user_access', 'user_access.UserId', 'user.Id')
                        ->join('role', 'user_access.RoleId', 'role.Id')
                        ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.Priority', '=', $priority)
                        ->where('ticket.TrackStatus', '=', $trackStatus)
                        ->where('ticket.Type', '=', $ticketType)
                        ->where('ticket.RaiseById', '=', $userId)
                        ->orderBy('ticket.Id', 'DESC')
                        ->skip($pageNo * $limit)
                        ->take($limit)
                        ->get();
                } else if ($ticketType != "null" || $trackStatus != "null" || $priority != "null") {
                    //Checks for only 1 drop down given
                    if ($ticketType != "null" && $priority == "null" && $trackStatus == "null") {
                        error_log('Search keyword IS NULL and TICKET type is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword IS NULL and priority is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType == "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword IS NULL and track status is not null');

                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } //Now checks if any of the two parameters are given
                    else if ($ticketType != "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword IS NULL and ticket type and priority is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus != "null") {
                        error_log('Search keyword IS NULL and priority and track status is not null');

                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else if ($ticketType != "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword IS NULL and ticket type and track status is not null');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    } else {
                        error_log('give data on the basis of drop down values');
                        $query = DB::table('ticket')
                            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                            ->join('user_access', 'user_access.UserId', 'user.Id')
                            ->join('role', 'user_access.RoleId', 'role.Id')
                            ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->orderBy('ticket.Id', 'DESC')
                            ->skip($pageNo * $limit)
                            ->take($limit)
                            ->get();
                    }
                } else {
                    error_log('In search else');

                    $query = DB::table('ticket')
                        ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
                        ->join('user_access', 'user_access.UserId', 'user.Id')
                        ->join('role', 'user_access.RoleId', 'role.Id')
                        ->select('ticket.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.Priority', '=', $priority)
                        ->where('ticket.TrackStatus', '=', $trackStatus)
                        ->where('ticket.Type', '=', $ticketType)
                        ->where('ticket.RaiseById', '=', $userId)
                        ->orderBy('ticket.Id', 'DESC')
                        ->skip($pageNo * $limit)
                        ->take($limit)
                        ->get();
                }
            }
        }

        return $query;
    }

    static public function GetTicketViaId($ticketId)
    {
        error_log('in model, fetching single ticket via id');

        $query = DB::table('ticket')
            ->leftjoin('user as user', 'ticket.CreatedBy', 'user.Id')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->select('ticket.*', 'user.FirstName', 'user.LastName',
                'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
            ->where('ticket.IsActive', '=', true)
            ->where('ticket.Id', '=', $ticketId)
            ->first();

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

    static public function GetTicketListCountViaSearch($searchKeyword, $ticketType, $trackStatus, $priority)
    {
        error_log('in model, fetching tickets generated');

        if ($ticketType == "null"
            && $trackStatus == "null"
            && $priority == "null"
            && $searchKeyword == "null"
        ) {
            error_log('All search parameters are null');

            $query = DB::table('ticket')
                ->where('ticket.IsActive', '=', true)
                ->count();
        } else {
            error_log('some of the parameters are given');
            if ($searchKeyword != "null") {
                error_log('search keyword is not null');
                if ($ticketType != "null" && $trackStatus != "null" && $priority != "null") {
                    error_log('All parameters are given');

//                    DB::enableQueryLog();

                    $query = DB::table('ticket')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.Priority', '=', $priority)
                        ->where('ticket.TrackStatus', '=', $trackStatus)
                        ->where('ticket.Type', '=', $ticketType)
                        ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                        ->count();

//                    dd(DB::getQueryLog());

                } else if ($ticketType != "null" || $trackStatus != "null" || $priority != "null") {
                    //Checks for only 1 drop down given
                    if ($ticketType != "null" && $priority == "null" && $trackStatus == "null") {
                        error_log('Search keyword and TICKET type is not null');

                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword and priority is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    } else if ($ticketType == "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword and track status is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    } //Now checks if any of the two parameters are given
                    else if ($ticketType != "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword and ticket type and priority is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus != "null") {
                        error_log('Search keyword and priority and track status is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    } else if ($ticketType != "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword and ticket type and track status is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    } else {
                        error_log('Searching on the basis of all parameters');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    }
                } else {
                    error_log('Search only on the basis of search keyword');

                    $query = DB::table('ticket')
                        ->where('ticket.IsActive', '=', true)
                        ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                        ->count();
                }
            } else {
                error_log('Search keyword is null, now checking drop down parameters');
                if ($ticketType != "null" && $trackStatus != "null" && $priority != "null") {
                    $query = DB::table('ticket')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.Priority', '=', $priority)
                        ->where('ticket.TrackStatus', '=', $trackStatus)
                        ->where('ticket.Type', '=', $ticketType)
                        ->orderBy('ticket.Id', 'DESC')
                        ->count();
                } else if ($ticketType != "null" || $trackStatus != "null" || $priority != "null") {
                    //Checks for only 1 drop down given
                    if ($ticketType != "null" && $priority == "null" && $trackStatus == "null") {
                        error_log('Search keyword IS NULL and TICKET type is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Type', '=', $ticketType)
                            ->count();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword IS NULL and priority is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->count();
                    } else if ($ticketType == "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword IS NULL and track status is not null');

                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->count();
                    } //Now checks if any of the two parameters are given
                    else if ($ticketType != "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword IS NULL and ticket type and priority is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.Type', '=', $ticketType)
                            ->count();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus != "null") {
                        error_log('Search keyword IS NULL and priority and track status is not null');

                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->count();
                    } else if ($ticketType != "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword IS NULL and ticket type and track status is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->count();
                    } else {
                        error_log('give data on the basis of drop down values');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->count();
                    }
                } else {
                    error_log('In search else');

                    $query = DB::table('ticket')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.Priority', '=', $priority)
                        ->where('ticket.TrackStatus', '=', $trackStatus)
                        ->where('ticket.Type', '=', $ticketType)
                        ->count();
                }
            }
        }

        return $query;
    }

    static public function GetTicketListCountViaSearchForPatient($searchKeyword, $ticketType, $trackStatus, $priority, $userId)
    {
        error_log('in model, fetching tickets generated');

        if ($ticketType == "null"
            && $trackStatus == "null"
            && $priority == "null"
            && $searchKeyword == "null"
        ) {
            error_log('All search parameters are null');

            $query = DB::table('ticket')
                ->where('ticket.IsActive', '=', true)
                ->where('ticket.RaiseById', '=', $userId)
                ->count();
        } else {
            error_log('some of the parameters are given');
            if ($searchKeyword != "null") {
                error_log('search keyword is not null');
                if ($ticketType != "null" && $trackStatus != "null" && $priority != "null") {
                    error_log('All parameters are given');

//                    DB::enableQueryLog();

                    $query = DB::table('ticket')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.Priority', '=', $priority)
                        ->where('ticket.TrackStatus', '=', $trackStatus)
                        ->where('ticket.Type', '=', $ticketType)
                        ->where('ticket.RaiseById', '=', $userId)
                        ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                        ->count();

//                    dd(DB::getQueryLog());

                } else if ($ticketType != "null" || $trackStatus != "null" || $priority != "null") {
                    //Checks for only 1 drop down given
                    if ($ticketType != "null" && $priority == "null" && $trackStatus == "null") {
                        error_log('Search keyword and TICKET type is not null');

                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword and priority is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    } else if ($ticketType == "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword and track status is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    } //Now checks if any of the two parameters are given
                    else if ($ticketType != "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword and ticket type and priority is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus != "null") {
                        error_log('Search keyword and priority and track status is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    } else if ($ticketType != "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword and ticket type and track status is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    } else {
                        error_log('Searching on the basis of all parameters');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                            ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                            ->count();
                    }
                } else {
                    error_log('Search only on the basis of search keyword');

                    $query = DB::table('ticket')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.RaiseById', '=', $userId)
                        ->where('.ticket.TicketNumber', 'like', '%' . $searchKeyword . '%')
                        ->orWhere('.ticket.Title', 'like', '%' . $searchKeyword . '%')
                        ->count();
                }
            } else {
                error_log('Search keyword is null, now checking drop down parameters');
                if ($ticketType != "null" && $trackStatus != "null" && $priority != "null") {
                    $query = DB::table('ticket')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.Priority', '=', $priority)
                        ->where('ticket.TrackStatus', '=', $trackStatus)
                        ->where('ticket.Type', '=', $ticketType)
                        ->where('ticket.RaiseById', '=', $userId)
                        ->orderBy('ticket.Id', 'DESC')
                        ->count();
                } else if ($ticketType != "null" || $trackStatus != "null" || $priority != "null") {
                    //Checks for only 1 drop down given
                    if ($ticketType != "null" && $priority == "null" && $trackStatus == "null") {
                        error_log('Search keyword IS NULL and TICKET type is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->count();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword IS NULL and priority is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->count();
                    } else if ($ticketType == "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword IS NULL and track status is not null');

                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->count();
                    } //Now checks if any of the two parameters are given
                    else if ($ticketType != "null" && $priority != "null" && $trackStatus == "null") {
                        error_log('Search keyword IS NULL and ticket type and priority is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->count();
                    } else if ($ticketType == "null" && $priority != "null" && $trackStatus != "null") {
                        error_log('Search keyword IS NULL and priority and track status is not null');

                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->count();
                    } else if ($ticketType != "null" && $priority == "null" && $trackStatus != "null") {
                        error_log('Search keyword IS NULL and ticket type and track status is not null');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->count();
                    } else {
                        error_log('give data on the basis of drop down values');
                        $query = DB::table('ticket')
                            ->where('ticket.IsActive', '=', true)
                            ->where('ticket.Priority', '=', $priority)
                            ->where('ticket.TrackStatus', '=', $trackStatus)
                            ->where('ticket.Type', '=', $ticketType)
                            ->where('ticket.RaiseById', '=', $userId)
                            ->count();
                    }
                } else {
                    error_log('In search else');

                    $query = DB::table('ticket')
                        ->where('ticket.IsActive', '=', true)
                        ->where('ticket.Priority', '=', $priority)
                        ->where('ticket.TrackStatus', '=', $trackStatus)
                        ->where('ticket.Type', '=', $ticketType)
                        ->where('ticket.RaiseById', '=', $userId)
                        ->count();
                }
            }
        }

        return $query;
    }

    public static function getPriorities()
    {
        $type = DB::select(DB::raw("SHOW COLUMNS FROM ticket WHERE Field = 'priority'"))[0]->Type;
        preg_match('/^enum\((.*)\)$/', $type, $matches);
        $enum = array();
        foreach (explode(',', $matches[1]) as $value) {
            $v = trim($value, "'");
            $enum = array_add($enum, $v, $v);
        }
        return $enum;
    }

    public static function getEnumValues($tableName, $columnName)
    {
        $type = DB::select(DB::raw("SHOW COLUMNS FROM " . $tableName . " WHERE Field = '" . $columnName . "'"))[0]->Type;
        preg_match('/^enum\((.*)\)$/', $type, $matches);
        $enum = array();
        foreach (explode(',', $matches[1]) as $value) {
            $v = trim($value, "'");
            $enum = array_add($enum, $v, $v);
        }
        return $enum;
    }

    public static function GetAssigneeViaTicketId($ticketId)
    {
        error_log('in model, fetching ticket assignee data');

        $query = DB::table('ticket_assignee')
            //Assign by data
            ->leftjoin('user as assignBy', 'ticket_assignee.AssignById', 'assignBy.Id')
            ->leftjoin('user_access as assignByUserAccess', 'assignByUserAccess.UserId', 'assignBy.Id')
            ->leftjoin('role as assignByRole', 'assignByUserAccess.RoleId', 'assignByRole.Id')
            //Assign to data
            ->leftjoin('user as assignTo', 'ticket_assignee.AssignToId', 'assignTo.Id')
            ->leftjoin('user_access as assignToUserAccess', 'assignToUserAccess.UserId', 'assignTo.Id')
            ->leftjoin('role as assignToRole', 'assignToUserAccess.RoleId', 'assignToRole.Id')
            ->select('ticket_assignee.*',

                'assignBy.FirstName as AssignByFirstName', 'assignBy.LastName as AssignByLastName',
                'assignByRole.Id as AssignByRoleId', 'assignByRole.Name as AssignByRoleName', 'assignByRole.CodeName as AssignByRoleCodeName',

                'assignTo.FirstName as AssignToFirstName', 'assignTo.LastName as AssignToLastName',
                'assignToRole.Id as AssignToRoleId', 'assignToRole.Name as AssignToRoleName', 'assignToRole.CodeName as AssignToRoleCodeName'
            )
            ->where('ticket_assignee.IsActive', '=', true)
            ->where('ticket_assignee.TicketId', '=', $ticketId)
            ->orderBy('ticket_assignee.Id', 'DESC')
            ->get();

        return $query;
    }

    public static function GetRepliesViaTicketId($ticketId)
    {
        error_log('in model, fetching ticket reply data');

        $query = DB::table('ticket_reply')
            ->leftjoin('user as replyBy', 'ticket_reply.ReplyById', 'replyBy.Id')
            ->leftjoin('user_access', 'user_access.UserId', 'replyBy.Id')
            ->leftjoin('role', 'user_access.RoleId', 'role.Id')
            ->select('ticket_reply.*',

                'replyBy.FirstName as ReplyByFirstName', 'replyBy.LastName as ReplyByLastName',
                'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName'
            )
            ->where('ticket_reply.IsActive', '=', true)
            ->where('ticket_reply.TicketId', '=', $ticketId)
            ->orderBy('ticket_reply.Id', 'DESC')
            ->get();

        return $query;
    }

    public static function GetRepliesCountViaTicketId($ticketId)
    {
        error_log('in model, fetching ticket reply count');

        $query = DB::table('ticket_reply')
            ->where('ticket_reply.IsActive', '=', true)
            ->where('ticket_reply.TicketId', '=', $ticketId)
            ->count();

        return $query;
    }

    public static function GetTicketReplySingle($ticketReplyId)
    {
        error_log('in model, fetching ticket reply single');

        $query = DB::table('ticket_reply')
            ->leftjoin('user as repliedBy', 'ticket_reply.ReplyById', 'repliedBy.Id')
            ->join('user_access', 'user_access.UserId', 'repliedBy.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->select('ticket_reply.*', 'repliedBy.FirstName', 'repliedBy.LastName',
                'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
            ->where('ticket_reply.IsActive', '=', true)
            ->where('ticket_reply.Id', '=', $ticketReplyId)
            ->first();

        return $query;
    }

}
