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
use Twilio\Twiml;


class TicketController extends Controller
{
    function CreateTicket(Request $request)
    {
//        following array received once ticket is generate from SMS
//        Array
//        (
//            [userId] => null
//            [requestType] => sms
//            [ToCountry] => US
//            [ToState] => GA
//            [SmsMessageSid] => SMa2f569007da647f8cef3df546c096552
//            [NumMedia] => 0
//            [ToCity] =>
//            [FromZip] =>
//            [SmsSid] => SMa2f569007da647f8cef3df546c096552
//            [FromState] =>
//            [SmsStatus] => received
//            [FromCity] =>
//            [Body] => abc
//            [FromCountry] => PK
//            [To] => +17622142131
//            [ToZip] =>
//            [NumSegments] => 1
//            [MessageSid] => SMa2f569007da647f8cef3df546c096552
//            [AccountSid] => AC33aa54ff3737112912bf80e398d3b742
//            [From] => +923122410823
//            [ApiVersion] => 2010 - 04 - 01
//        )
        $date = HelperModel::getDate();
        $userId = $request->get('userId');
        $requestType = $request->get('requestType');
        $fromNum = $request->get('From');
        $messageBody = $request->get('Body');
        $defaultTicketNumber = env('DEFAULT_TICKET_NUMBER');
        $smsRequestType = env('REQUEST_TYPE_SMS');
        $portalRequestType = env('REQUEST_TYPE_PORTAL');

        //check request status type
        if ($requestType == $smsRequestType) {
            error_log('Request type is of SMS');
            $response = new Twiml;
            $patientRoleCode = env('ROLE_PATIENT');

            //first check the fromNumber is
            //registered on our platform
            //if yes the proceed as follow
            //otherwise reply back with
            //Not Allowed

            //verifying the fromNumber
            //eliminating the country code
            //from the number

            //this extraction of fromNumb
            //will be remove once we
            //complete the country code
            //task on user add form

            $eliminatedNumOne = substr($fromNum, 0, 2);
            $eliminatedNumTwo = 0;

            error_log("eliminatedNumberOne-1");
            error_log($eliminatedNumOne);

            if ($eliminatedNumOne == '+1') {
                $eliminatedNumTwo = substr($fromNum, 2);
            } else {
                $eliminatedNumOne = substr($fromNum, 0, 3);
                error_log("eliminatedNumberOne-2");
                error_log($eliminatedNumOne);
                if ($eliminatedNumOne == "+92") {
                    error_log('yes +92');
                    $eliminatedNumTwo = substr($fromNum, 3);
                }
            }

            error_log("eliminatedNumberTwo");
            error_log($eliminatedNumTwo);

            //as fromNum is extracted
            //now verifying the fromNum
            //is registered or not
            $checkUserData = UserModel::GetPatientViaMobileNum($eliminatedNumTwo, $patientRoleCode);
//            (
//                [Id] => 3
//                [FirstName] => Vohra VV
//                [LastName] => Ahsan AV
//                [EmailAddress] => ahsan . yakoob@hotmail . com
//                [MobileNumber] => 3122410823
//                [TelephoneNumber] => 1234333
//                [OfficeAddress] => None
//                [ResidentialAddress] => None
//                [Password] => 1d564e43b739b7311c74c15236196b78
//                [Gender] => Female
//                [FunctionalTitle] => Nurse
//                [Age] => 90
//                [AgeGroup] => old
//                [ProfilePictureId] =>
//                [AccountVerified] => 1
//                [NotificationToken] =>
//                [CreatedBy] => 1
//                [UpdatedBy] =>
//                [CreatedOn] =>
//                [UpdatedOn] =>
//                [IsActive] => 1
//                [IsBlock] =>
//                [BlockReason] =>
//                [InActiveReason] =>
//                [CityId] =>
//                [RoleId] => 5
//                [RoleName] => Patient
//                [RoleCodeName] => patient
//            )

            if ($checkUserData == null) {
                error_log('Patient record not found');
                $response->message("Dear User, This facility is only available to Patient, please get registered with this Mobile Number as a Patient. Thanks !!!");
                return $response;
            } else {
                error_log('Patient record found with this Mobile number');

                //fetching last generated ticket number
                $getLastTicketNumber = TicketModel::getLastTicket();
                $ticketPriorityHigh = env('TICKET_PRIORITY_1');
                if ($getLastTicketNumber != null) {
                    error_log('ticket number found');
                    $getTicketNumber = 0000 . $getLastTicketNumber->TicketNumber + 1;
                } else {
                    error_log('ticket number not found');
                    $getTicketNumber = $defaultTicketNumber;
                }

                //Now we will make data and will insert it
                $ticketData = array(
                    "TicketNumber" => $getTicketNumber,
                    "RaiseById" => $checkUserData->Id,
                    "Title" => "Ticket via SMS",
                    "Description" => $messageBody,
                    "Priority" => $ticketPriorityHigh,
                    "TrackStatus" => "open",
                    "OtherType" => null,
                    "Type" => null,
                    "RaisedFrom" => $requestType,
                    "CreatedBy" => $checkUserData->Id,
                    "CreatedOn" => $date["timestamp"],
                    "IsActive" => true
                );

                $insertedDataId = GenericModel::insertGenericAndReturnID('ticket', $ticketData);

                if ($insertedDataId == 0) {
                    error_log('Dear Patient, Problem in creating Ticket. Sorry for inconvenience, Please can you try again Thanks CCM - Support Staff !!!');
                    $response->message('Dear Patient, Problem in creating Ticket. Sorry for inconvenience, Please can you try again Thanks CCM - Support Staff !!!');
                    return $response;
                } else {
                    error_log('Dear Patient, your Ticket is created. We are responding shortly, your Ticket Number is ' . $getTicketNumber . '. Regards CCM - Support Staff Thanks');
                    $response->message('Dear Patient, your Ticket is created. We are responding shortly, your Ticket Number is ' . $getTicketNumber . '. Regards CCM - Support Staff Thanks');
                    return $response;
                }
            }

        } else if ($requestType == $portalRequestType) {

            //fetching last generated ticket number
            $getLastTicketNumber = TicketModel::getLastTicket();
            if ($getLastTicketNumber != null) {
                error_log('ticket number found');
                $getTicketNumber = 0000 . $getLastTicketNumber->TicketNumber + 1;
            } else {
                error_log('ticket number not found');
                $getTicketNumber = $defaultTicketNumber;
            }

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
                    return response()->json(['data' => null, 'message' => 'Error in inserting ticket'], 400);
                } else {
                    return response()->json(['data' => $insertedDataId, 'message' => 'Ticket is successfully created'], 200);
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
                        'Id' => $item->Id,
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

    function TicketListViaPaginationAndSearch(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');
        $searchKeyword = $request->get('searchKeyword');
        $ticketType = $request->get('type');
        $trackStatus = $request->get('trackStatus');
        $priority = $request->get('priority');

        $patientRole = env('ROLE_PATIENT');

        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('user record found');
            //Now fetch all the tickets with respect to pagination
            $ticketData = array();

            $ticketListData = array();

            if ($checkUserData->RoleCodeName == $patientRole) {
                error_log('logged in user role is patient');
                $ticketListData = TicketModel::GetTicketListViaPaginationAndSearchForPatient($pageNo, $limit, $searchKeyword, $ticketType, $trackStatus, $priority, $userId);
            } else {
                error_log('logged in user is not from Patient');
                $ticketListData = TicketModel::GetTicketListViaPaginationAndSearch($pageNo, $limit, $searchKeyword, $ticketType, $trackStatus, $priority);
            }
            if (count($ticketListData) > 0) {
                error_log('ticket data found');

                foreach ($ticketListData as $item) {
                    //Now making data
                    $data = array(
                        'Id' => $item->Id,
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

    function TicketSingle(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $ticketId = $request->get('ticketId');

        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('user record found');
            //Now fetch all the tickets with respect to ticket id

            $ticketData = TicketModel::GetTicketViaId($ticketId);
            if ($ticketData == null) {
                error_log('ticket data not found');
                return response()->json(['data' => $ticketData, 'message' => 'ticket data not found'], 200);

            } else {
                error_log('ticket data found');

                //Now making data
                $data['Id'] = $ticketData->Id;
                $data['TicketNumber'] = $ticketData->TicketNumber;
                $data['Title'] = $ticketData->Title;
                $data['Description'] = $ticketData->Description;
                $data['Priority'] = $ticketData->Priority;
                $data['TrackStatus'] = $ticketData->TrackStatus;
                $data['OtherType'] = $ticketData->OtherType;
                $data['Type'] = $ticketData->Type;
                $data['RaisedFrom'] = $ticketData->RaisedFrom;
                $data['TicketReplyCount'] = TicketModel::GetRepliesCountViaTicketId($ticketId);
                $data['CreatedOn'] = ForumModel::calculateTopicAnCommentTime($ticketData->CreatedOn);
                $data['Role'] = array();
                $data['CreatedBy'] = array();
                $data['TicketReply'] = array();
                $data['TicketAssignee'] = array();

                $data['CreatedBy']['Id'] = $ticketData->CreatedBy;
                $data['CreatedBy']['FirstName'] = $ticketData->FirstName;
                $data['CreatedBy']['LastName'] = $ticketData->LastName;

                $data['Role']['Id'] = $ticketData->RoleId;
                $data['Role']['Name'] = $ticketData->RoleName;
                $data['Role']['CodeName'] = $ticketData->RoleCodeName;

                //Now fetching replies to ticket

                $ticketRepliedData = array();

                $getTicketReplies = TicketModel::GetRepliesViaTicketId($ticketId);
                if (count($getTicketReplies) > 0) {
                    error_log('ticket replies found');
                    foreach ($getTicketReplies as $item) {
                        $replyData = array(
                            'Id' => $item->Id,
                            'Reply' => $item->Reply,
                            'CreatedOn' => ForumModel::calculateTopicAnCommentTime($item->CreatedOn),
                            'ReplyBy' => array(
                                'Role' => array()
                            ),
                        );

                        $replyData['ReplyBy']['Id'] = $item->ReplyById;
                        $replyData['ReplyBy']['FirstName'] = $item->ReplyByFirstName;
                        $replyData['ReplyBy']['LastName'] = $item->ReplyByLastName;

                        $replyData['ReplyBy']['Role']['Id'] = $item->RoleId;
                        $replyData['ReplyBy']['Role']['Name'] = $item->RoleName;
                        $replyData['ReplyBy']['Role']['CodeName'] = $item->RoleCodeName;

                        array_push($ticketRepliedData, $replyData);
                    }

                    $data['TicketReply'] = $ticketRepliedData;
                }

                //Now fetching ticket assignee data

                $ticketAssignedData = array();

                $getTicketAssignee = TicketModel::GetAssigneeViaTicketId($ticketId);
                if (count($getTicketAssignee) > 0) {
                    error_log('ticket assignee found');
                    foreach ($getTicketAssignee as $item) {
                        $assignedData = array(
                            'Id' => $item->Id,
                            'AssignByDescription' => $item->AssignByDescription,
                            'CreatedOn' => ForumModel::calculateTopicAnCommentTime($item->CreatedOn),
                            'AssignBy' => array(
                                'Role' => array()
                            ),
                            'AssignTo' => array(
                                'Role' => array()
                            )
                        );

                        $assignedData['AssignBy']['Id'] = $item->AssignById;
                        $assignedData['AssignBy']['FirstName'] = $item->AssignByFirstName;
                        $assignedData['AssignBy']['LastName'] = $item->AssignByLastName;

                        $assignedData['AssignBy']['Role']['Id'] = $item->AssignByRoleId;
                        $assignedData['AssignBy']['Role']['Name'] = $item->AssignByRoleName;
                        $assignedData['AssignBy']['Role']['CodeName'] = $item->AssignByRoleCodeName;

                        $assignedData['AssignTo']['Id'] = $item->AssignToId;
                        $assignedData['AssignTo']['FirstName'] = $item->AssignToFirstName;
                        $assignedData['AssignTo']['LastName'] = $item->AssignToLastName;

                        $assignedData['AssignTo']['Role']['Id'] = $item->AssignToRoleId;
                        $assignedData['AssignTo']['Role']['Name'] = $item->AssignToRoleName;
                        $assignedData['AssignTo']['Role']['CodeName'] = $item->AssignToRoleCodeName;

                        array_push($ticketAssignedData, $assignedData);
                    }

                    $data['TicketAssignee'] = $ticketAssignedData;
                }


                return response()->json(['data' => $data, 'message' => 'ticket data found'], 200);
            }
        }
    }

    function TicketListCount(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $searchKeyword = $request->get('searchKeyword');
        $ticketType = $request->get('type');
        $trackStatus = $request->get('trackStatus');
        $priority = $request->get('priority');

        $patientRole = env('ROLE_PATIENT');

        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('user record found');
            //Now fetch all the tickets with respect to pagination
            $ticketListCount = array();

            //Old function
//            $ticketListCount = TicketModel::GetTicketListCount();

            if ($checkUserData->RoleCodeName == $patientRole) {
                error_log('logged in user role is patient');
                $ticketListCount = TicketModel::GetTicketListCountViaSearchForPatient($searchKeyword, $ticketType, $trackStatus, $priority, $userId);
            } else {
                error_log('logged in user is not from Patient');
                $ticketListCount = TicketModel::GetTicketListCountViaSearch($searchKeyword, $ticketType, $trackStatus, $priority);
            }

            return response()->json(['data' => $ticketListCount, 'message' => 'Total count'], 200);
        }
    }

    function GetTicketPriorities()
    {
        error_log('in controller');

        $ticketPriorities = TicketModel::getEnumValues('ticket', 'Priority');
        if ($ticketPriorities == null) {
            return response()->json(['data' => null, 'message' => 'Priorities not found'], 200);
        } else {
            return response()->json(['data' => $ticketPriorities, 'message' => 'Priorities found'], 200);
        }
    }

    function GetTicketTypes()
    {
        error_log('in controller');

        $ticketPriorities = TicketModel::getEnumValues('ticket', 'Type');
        if ($ticketPriorities == null) {
            return response()->json(['data' => null, 'message' => 'Types not found'], 200);
        } else {
            return response()->json(['data' => $ticketPriorities, 'message' => 'Types found'], 200);
        }
    }

    function GetTicketTrackStauses()
    {
        error_log('in controller');

        $ticketTrackStatus = TicketModel::getEnumValues('ticket', 'TrackStatus');
        if ($ticketTrackStatus == null) {
            return response()->json(['data' => null, 'message' => 'Track Status not found'], 200);
        } else {
            return response()->json(['data' => $ticketTrackStatus, 'message' => 'Track Status found'], 200);
        }
    }

    function UpdateTicket(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $ticketId = $request->get('ticketId');
        $openTrackStatus = env('TICKET_TRACK_STATUS_OPEN');

        $date = HelperModel::getDate();

        // First check if user data found or not via user ID
        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            //Now fetch the ticket and check if it exists
            $getSingleTicket = TicketModel::GetTicketViaId($ticketId);
            if ($getSingleTicket == null) {
                return response()->json(['data' => null, 'message' => 'Ticket not found'], 400);
            } else {
                error_log('Ticket found');
                error_log('Now checking if ticket is already open');
                //Only open tickets will be updated

                if ($getSingleTicket->TrackStatus != $openTrackStatus) {
                    return response()->json(['data' => null, 'message' => 'Only open tickets can be updated'], 400);
                } else {
                    error_log('User record found');
                    //Now we will make data and will insert it
                    $ticketData = array(
                        "Title" => $request->input('Title'),
                        "Description" => $request->input('Description'),
                        "Priority" => $request->input('Priority'),
                        "OtherType" => $request->input('OtherType'),
                        "Type" => $request->input('Type'),
                        "UpdatedBy" => $userId,
                        "UpdatedOn" => $date["timestamp"],
                        "IsActive" => true
                    );

                    $insertedDataId = GenericModel::updateGeneric('ticket', 'Id', $ticketId, $ticketData);
                    if ($insertedDataId == false) {
                        return response()->json(['data' => null, 'message' => 'Error in updating ticket'], 400);
                    } else {
                        return response()->json(['data' => $ticketId, 'message' => 'ticket successfully updated'], 200);
                    }
                }
            }
        }
    }

    function AddTicketReply(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $ticketId = $request->get('ticketId');
        $patientRole = env('ROLE_PATIENT');
        $supportStaffRole = env('ROLE_SUPPORT_STAFF');

        $ticketClose = env('TICKET_TRACK_STATUS_CLOSE');

        $date = HelperModel::getDate();
        DB::beginTransaction();

        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            //Now check if this ticket is already assigned to someone or not
            error_log('User record found');
            //Now checking if logged in user is patinet or from support staff

            if ($checkUserData->RoleCodeName == $patientRole || $checkUserData->RoleCodeName == $supportStaffRole) {
                //We will put check if logged in user is patient then
                //this ticket cannot be assigned to him
                //Now check if given ticket exists or not

                $ticketData = TicketModel::GetTicketViaId($ticketId);
                $ticketRaiseById = $ticketData->RaiseById;
                $ticketNumber = $ticketData->TicketNumber;

                if ($ticketData == null) {
                    error_log('ticket data not found');
                    return response()->json(['data' => $ticketData, 'message' => 'ticket data not found'], 200);

                } else {

                    error_log('ticket data found');

                    //Now checking if ticket is closed or not
                    if ($ticketData->TrackStatus == $ticketClose) {
                        return response()->json(['data' => null, 'message' => 'Ticket is already closed'], 400);
                    } else {
                        //else insert data in ticket reply and assignee table too

                        $getAssigneeData = TicketModel::GetAssigneeViaTicketId($ticketId);
                        if (count($getAssigneeData) > 0) {
                            error_log('ticket has assigned to someone');

                            $ticketReplyData = array(
                                "TicketId" => $ticketId,
                                "ReplyById" => $userId,
                                "Reply" => $request->input('Reply'),
                                "CreatedBy" => $userId,
                                "CreatedOn" => $date["timestamp"],
                                "IsActive" => true
                            );

                            $insertedDataId = GenericModel::insertGenericAndReturnID('ticket_reply', $ticketReplyData);
                            if ($insertedDataId == 0) {
                                DB::rollBack();
                                return response()->json(['data' => null, 'message' => 'Error in replying to ticket'], 400);
                            } else {
                                DB::commit();
                                if ($checkUserData->RoleCodeName == $supportStaffRole) {
                                    //emailing to patient who created ticket
                                    //Now fetch patient number and email so that email can be sent to that respective patient

                                    error_log('$patientUserData');
                                    $patientUserData = UserModel::GetSingleUserViaIdNewFunction($ticketData->RaiseById);
                                    if ($patientUserData == null) {
                                        return response()->json(['data' => null, 'message' => 'Patient data not found, email not sent'], 400);
                                    } else {
                                        error_log('Patient data found, now sending email');

                                        $emailMessage = "Dear Patient, please check the response from Support Staff on your Ticket. Remember Your Ticket Number is " . $ticketNumber . "";

                                        $toNumber = array();
                                        $phoneCode = getenv("PAK_NUM_CODE");//fetch from front-end

                                        //pushing mobile number
                                        //in array for use in sending sms
                                        array_push($toNumber, $phoneCode . $patientUserData->MobileNumber);

                                        UserModel::sendEmail($patientUserData->EmailAddress, $emailMessage, null);

                                        ## Preparing Data for SMS  - START ##
                                        if (count($toNumber) > 0) {
                                            HelperModel::sendSms($toNumber, $emailMessage, null);
                                        }
                                        ## Preparing Data for SMS  - END ##
                                    }
                                }

                                return response()->json(['data' => $insertedDataId, 'message' => 'ticket replied given'], 200);
                            }
                        } else {
                            error_log('ticket has not assigned to anyone yet');

                            $ticketReplyData = array(
                                "TicketId" => $ticketId,
                                "ReplyById" => $userId,
                                "Reply" => $request->input('Reply'),
                                "CreatedBy" => $userId,
                                "CreatedOn" => $date["timestamp"],
                                "IsActive" => true
                            );

                            //If assignee data will be fetched then it means this ticket has assigned to support staff
                            //then insert data only in ticket reply

                            $message = "Ticket replied given successfully";

                            error_log('$checkUserData->RoleCodeName : ' . $checkUserData->RoleCodeName);


                            if ($checkUserData->RoleCodeName != $patientRole) {
                                error_log('user role is not patient');
                                //Now we will make data and will insert it
                                $ticketData = array(
                                    "UpdatedBy" => $userId,
                                    "UpdatedOn" => $date["timestamp"],
                                    "IsAssigned" => true
                                );

                                $ticketDataUpdate = GenericModel::updateGeneric('ticket', 'Id', $ticketId, $ticketData);
                                if ($ticketDataUpdate == false) {
                                    DB::rollBack();
                                    return response()->json(['data' => null, 'message' => 'Error in assigning ticket'], 400);
                                }

                                $ticketAssigneeData = array(
                                    "TicketId" => $ticketId,
                                    "AssignToId" => $userId,
                                    "AssignById" => $userId,
                                    "CreatedBy" => $userId,
                                    "CreatedOn" => $date["timestamp"],
                                    "AssignByDescription" => $request->input('AssignByDescription'),
                                    "IsActive" => true
                                );

                                $insertedDataId = GenericModel::insertGeneric('ticket_assignee', $ticketAssigneeData);

                                $message = "Ticket replied given and assigned to you";
                            }

                            $ticketReplyInsertedId = GenericModel::insertGenericAndReturnID('ticket_reply', $ticketReplyData);

                            if ($ticketReplyInsertedId == 0) {
                                DB::rollBack();
                                return response()->json(['data' => null, 'message' => 'Error in replying to ticket'], 400);
                            } else {
                                DB::commit();

                                if ($checkUserData->RoleCodeName == $supportStaffRole) {
                                    //emailing to patient who created ticket
                                    //Now fetch patient number and email so that email can be sent to that respective patient

                                    $patientUserData = UserModel::GetSingleUserViaIdNewFunction($ticketRaiseById);
                                    if ($patientUserData == null) {
                                        return response()->json(['data' => null, 'message' => 'Patient data not found, email not sent'], 400);
                                    } else {
                                        error_log('Patient data found, now sending email');

                                        $emailMessage = "Dear Patient, please check the response from Support Staff on your Ticket. Remember Your Ticket Number is " . $ticketNumber . "";

                                        $toNumber = array();
                                        $phoneCode = getenv("PAK_NUM_CODE");//fetch from front-end

                                        //pushing mobile number
                                        //in array for use in sending sms
                                        array_push($toNumber, $phoneCode . $patientUserData->MobileNumber);

                                        UserModel::sendEmail($patientUserData->EmailAddress, $emailMessage, null);

                                        ## Preparing Data for SMS  - START ##
                                        if (count($toNumber) > 0) {
                                            HelperModel::sendSms($toNumber, $emailMessage, null);
                                        }
                                        ## Preparing Data for SMS  - END ##
                                    }
                                }

                                return response()->json(['data' => $ticketReplyInsertedId, 'message' => $message], 200);
                            }
                        }
                    }
                }
            } else {
                return response()->json(['data' => null, 'message' => 'Only patient and support staff can reply to this ticket'], 400);
            }
        }
    }

    function TicketReplySingle(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $ticketReplyId = $request->get('ticketReplyId');

        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('user record found');
            //Now fetch all the tickets with respect to ticket id

            $ticketReplyData = TicketModel::GetTicketReplySingle($ticketReplyId);
            if ($ticketReplyData == null) {
                error_log('ticket reply data not found');
                return response()->json(['data' => $ticketReplyData, 'message' => 'ticket data not found'], 200);

            } else {
                error_log('ticket data found');

                //Now making data
                $data['Id'] = $ticketReplyData->Id;
                $data['Reply'] = $ticketReplyData->Reply;
                $data['CreatedOn'] = ForumModel::calculateTopicAnCommentTime($ticketReplyData->CreatedOn);
                $data['Role'] = array();
                $data['ReplyBy'] = array();


                $data['ReplyBy']['Id'] = $ticketReplyData->CreatedBy;
                $data['ReplyBy']['FirstName'] = $ticketReplyData->FirstName;
                $data['ReplyBy']['LastName'] = $ticketReplyData->LastName;

                $data['Role']['Id'] = $ticketReplyData->RoleId;
                $data['Role']['Name'] = $ticketReplyData->RoleName;
                $data['Role']['CodeName'] = $ticketReplyData->RoleCodeName;


                return response()->json(['data' => $data, 'message' => 'ticket reply data found'], 200);
            }
        }
    }

    function UpdateTicketReply(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $ticketReplyId = $request->get('ticketReplyId');

        $date = HelperModel::getDate();

        // First check if user data found or not via user ID
        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            //Now fetch the ticket and check if it exists
            $ticketReplyData = TicketModel::GetTicketReplySingle($ticketReplyId);
            if ($ticketReplyData == null) {
                error_log('ticket reply data not found');
                return response()->json(['data' => $ticketReplyData, 'message' => 'ticket data not found'], 200);

            } else {
                error_log('ticket reply data found');

                //Now we will make data and will insert it

                //Checking if logged in person is that one who has given reply or someone else
                if ($ticketReplyData->ReplyById != $userId) {
                    return response()->json(['data' => null, 'message' => 'This reply has not given by you'], 400);
                } else {


                    $ticketReplyData = array(
                        "ReplyById" => $userId,
                        "Reply" => $request->input('Reply'),
                        "UpdatedBy" => $userId,
                        "UpdatedOn" => $date["timestamp"]
                    );

                    $insertedDataId = GenericModel::updateGeneric('ticket_reply', 'Id', $ticketReplyId, $ticketReplyData);
                    if ($insertedDataId == false) {
                        return response()->json(['data' => null, 'message' => 'Error in updating ticket reply'], 400);
                    } else {
                        return response()->json(['data' => $ticketReplyId, 'message' => 'Ticket reply successfully updated'], 200);
                    }
                }
            }
        }
    }

    function AssignTicket(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $ticketId = $request->get('ticketId');

        $ticketClose = env('TICKET_TRACK_STATUS_CLOSE');

        $date = HelperModel::getDate();
        DB::beginTransaction();

        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            //Now check if this ticket is already assigned to someone or not
            error_log('User record found');

            //Now check if given ticket exists or not

            $ticketData = TicketModel::GetTicketViaId($ticketId);
            if ($ticketData == null) {
                error_log('ticket data not found');
                return response()->json(['data' => $ticketData, 'message' => 'ticket data not found'], 200);

            } else {

                error_log('ticket data found');
                //Now checking if logged in user is assigning ticket to himself or not
                //Now checking if ticket is closed or not
                if ($ticketData->TrackStatus == $ticketClose) {
                    return response()->json(['data' => null, 'message' => 'Ticket is already closed'], 400);
                } else {
                    if ($userId == $request->input('AssignToId')) {
                        return response()->json(['data' => null, 'message' => 'You cannot assign this ticket to yourself'], 400);
                    } else {
                        $ticketAssigneeData = array(
                            "TicketId" => $ticketId,
                            "AssignToId" => $request->input('AssignToId'),
                            "AssignById" => $userId,
                            "CreatedBy" => $userId,
                            "CreatedOn" => $date["timestamp"],
                            "IsActive" => true,
                            "AssignByDescription" => $request->input('AssignByDescription')
                        );

                        $insertedDataId = GenericModel::insertGenericAndReturnID('ticket_assignee', $ticketAssigneeData);
                        if ($insertedDataId == 0) {
                            DB::rollBack();
                            return response()->json(['data' => null, 'message' => 'Error in assigning ticket'], 400);
                        } else {
                            DB::commit();
                            $assignedToData = UserModel::GetSingleUserViaIdNewFunction($request->input('AssignToId'));
                            if ($assignedToData == null) {
                                return response()->json(['data' => null, 'message' => 'Ticket assigned to data not found, email not sent'], 400);
                            } else {
                                error_log('assigned to data found, now sending email');

                                $emailMessage = "Dear Support Staff, Ticket Number - " . $ticketData->TicketNumber . " is assigned to you. 
                            Please check the details in the following";

                                $toNumber = array();
                                $phoneCode = getenv("PAK_NUM_CODE");//fetch from front-end

                                //pushing mobile number
                                //in array for use in sending sms
                                array_push($toNumber, $phoneCode . $assignedToData->MobileNumber);

                                UserModel::sendEmail($assignedToData->EmailAddress, $emailMessage, null);

                                ## Preparing Data for SMS  - START ##
                                if (count($toNumber) > 0) {
                                    HelperModel::sendSms($toNumber, $emailMessage, null);
                                }
                                ## Preparing Data for SMS  - END ##
                            }
                            return response()->json(['data' => $insertedDataId, 'message' => 'Ticket is successfully Assigned'], 200);
                        }
                    }
                }
            }
        }
    }

    function TicketTrackStatusUpdate(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $ticketId = $request->get('ticketId');
        $ticketTrackStatus = $request->get('trackStatus');

        $ticketClose = env('TICKET_TRACK_STATUS_CLOSE');
        $ticketInProgress = env('TICKET_TRACK_STATUS_IN_PROGRESS');
        $patientRole = env('ROLE_PATIENT');

        $date = HelperModel::getDate();

        // First check if user data found or not via user ID
        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            //Now fetch the ticket and check if it exists
            $ticketData = TicketModel::GetTicketViaId($ticketId);
            if ($ticketData == null) {
                error_log('ticket data not found');
                return response()->json(['data' => $ticketData, 'message' => 'ticket not found'], 200);

            } else {
                error_log('ticket data found');

                //Now we will make data and will insert it

                //Checking if ticket status is same as got from front end or not

                if ($ticketData->TrackStatus == $ticketTrackStatus) {
                    return response()->json(['data' => null, 'message' => 'Ticket status is already ' . $ticketTrackStatus], 400);
                } else {
                    if ($ticketData->TrackStatus == $ticketClose) {
                        return response()->json(['data' => null, 'message' => 'Ticket is already closed'], 400);
                    } else {
                        error_log('ticket is not close');
                        //Now checking if logged in user is patient or not.
                        //Because only patient can close the ticket

                        if ($ticketTrackStatus == $ticketClose) {
                            if ($checkUserData->RoleCodeName != $patientRole) {
                                error_log('logged in user role is NOT patient');
                                return response()->json(['data' => null, 'message' => 'Only patient can close this ticket'], 400);
                            }
                            if ($ticketData->RaiseById != $userId) {
                                error_log('This ticket does not belong to you');
                                return response()->json(['data' => null, 'message' => 'This ticket does not belong to you'], 400);
                            }
                        }
                    }

                    $ticketDataUpdate = array(
                        "TrackStatus" => $ticketTrackStatus,
                        "UpdatedBy" => $userId,
                        "UpdatedOn" => $date["timestamp"]
                    );

                    $insertedDataId = GenericModel::updateGeneric('ticket', 'Id', $ticketId, $ticketDataUpdate);
                    if ($insertedDataId == false) {
                        return response()->json(['data' => null, 'message' => 'Error in closing the Ticket ticket'], 400);
                    } else {
                        return response()->json(['data' => $ticketId, 'message' => 'Ticket is successfully closed'], 200);
                    }
                }
            }
        }
    }
}
