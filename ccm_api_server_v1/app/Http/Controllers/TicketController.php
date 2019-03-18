<?php
/**
 * Created by PhpStorm.
 * User: Ahsan Vohra
 * Date: 3/18/2019
 * Time: 8:03 PM
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
use App\Models\ForumModel;
use App\Models\TicketModel;


class TicketController extends Controller
{
    function CreateTicket(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $requestType = $request->get('requestType');

        $date = HelperModel::getDate();
        $defaultTicketNumber = env('DEFAULT_TICKET_NUMBER');

        $smsRequestType = env('REQUEST_TYPE_SMS');
        $portalRequestType = env('REQUEST_TYPE_PORTAL');

        $getTicketNumber = 0;

        //fetching last generated ticket number
        $getLastTicketNumber = TicketModel::getLastTicket();
        if ($getLastTicketNumber != null) {
            error_log('ticket number found');
            $getTicketNumber = 0000 . $getLastTicketNumber->TicketNumber + 1;
        } else {
            error_log('ticket number not found');
            $getTicketNumber = $defaultTicketNumber;
        }

        //check request status type
        if ($requestType == $smsRequestType) {
            error_log('Request type is of SMS');
            return response()->json(['data' => null, 'message' => 'WIP'], 400);
        } else if ($requestType == $portalRequestType) {
            error_log('Request type is of PORTAL');
            // First check if user data found or not via user ID
            $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
            if ($checkUserData == null) {
                return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
            } else {
                error_log('User record found');
                //Now we will make data and will insert it
                $ticketData = array(
                    "TicketNumber" => $getTicketNumber,
                    "RaiseById" => $userId,
                    "Title" => $request->input('Title'),
                    "Description" => $request->input('Description'),
                    "Priority" => $request->input('Priority'),
                    "TrackStatus" => "open",
                    "OtherType" => $request->input('OtherType'),
                    "Type" => $request->input('Type'),
                    "RaisedFrom" => $requestType,
                    "CreatedBy" => $userId,
                    "CreatedOn" => $date["timestamp"],
                    "IsActive" => true
                );

                $insertedDataId = GenericModel::insertGenericAndReturnID('ticket', $ticketData);
                if ($insertedDataId == 0) {
                    return response()->json(['data' => null, 'message' => 'Error in inserting forum topic'], 400);
                } else {
                    return response()->json(['data' => $insertedDataId, 'message' => 'ticket successfully created'], 200);
                }
            }
        } else {
            return response()->json(['data' => null, 'message' => 'Invalid request type'], 400);
        }
    }

    function TicketListViaPagination(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');

        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('user record found');
            //Now fetch all the tickets with respect to pagination
            $ticketData = array();

            $ticketListData = TicketModel::GetTicketListViaPagination($pageNo, $limit);
            if (count($ticketListData) > 0) {
                error_log('ticket data found');

                foreach ($ticketListData as $item) {
                    //Now making data
                    $data = array(
                        'TicketNumber' => $item->TicketNumber,
                        'Title' => $item->Title,
                        'Description' => $item->Description,
                        'Priority' => $item->Priority,
                        'TrackStatus' => $item->TrackStatus,
                        'OtherType' => $item->OtherType,
                        'Type' => $item->Type,
                        'RaisedFrom' => $item->RaisedFrom,
                        'CreatedOn' => ForumModel::calculateTopicAnCommentTime($item->CreatedOn),
                        'Role' => array(),
                        'CreatedBy' => array(),
                    );

                    $data['CreatedBy']['Id'] = $item->CreatedBy;
                    $data['CreatedBy']['FirstName'] = $item->FirstName;
                    $data['CreatedBy']['LastName'] = $item->LastName;

                    $data['Role']['Id'] = $item->RoleId;
                    $data['Role']['Name'] = $item->RoleName;
                    $data['Role']['CodeName'] = $item->RoleCodeName;

                    array_push($ticketData, $data);
                }

                return response()->json(['data' => $ticketData, 'message' => 'ticket data found'], 200);

            } else {
                error_log('ticket data not found');
                return response()->json(['data' => $ticketData, 'message' => 'ticket data not found'], 200);
            }
        }
    }

}
