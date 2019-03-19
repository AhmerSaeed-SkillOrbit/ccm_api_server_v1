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

    public static function getEnumValues($columnName)
    {
        $type = DB::select(DB::raw("SHOW COLUMNS FROM ticket WHERE Field = '" . $columnName . "'"))[0]->Type;
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
