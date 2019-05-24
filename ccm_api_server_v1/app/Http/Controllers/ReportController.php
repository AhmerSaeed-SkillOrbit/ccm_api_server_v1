<?php
/**
 * Created by PhpStorm.
 * User: SO-LPT-031
 * Date: 5/18/2019
 * Time: 3:08 PM
 */

namespace App\Http\Controllers;

use App\Models\LoginModel;
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
use App\Models\CcmModel;
use App\Models\ReportModel;
use Symfony\Component\Translation\Tests\Writer\BackupDumper;
use Twilio\Twiml;
use Carbon\Carbon;


class ReportController
{
    static public function GetPatientRegisteredReport(Request $request)
    {
        error_log('in controller');

        $doctorId = $request->get('doctorId');
        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $searchStartDate = "null";
        $searchEndDate = "null";

        $registeredDirectly = env('REGISTERED_DIRECT');
        $registeredViaInvitation = env('REGISTERED_VIA_INVITATION');

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        if ($startDate == "null" && $endDate != "null" || $startDate != "null" && $endDate == "null") {
            return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 400);
        }
        if ($startDate != "null" && $endDate != null) {

            $timestamp = Carbon::createFromFormat('Y-m-d', $startDate)->timestamp;
            $searchStartDate = $timestamp;

            $timeStampEndDate = Carbon::createFromFormat('Y-m-d', $endDate)->timestamp;
            $searchEndDate = $timeStampEndDate;

            error_log($timestamp);
        }

        $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($doctorId, $doctorPatientAssociation);
        error_log('$getAssociatedPatients are ' . $getAssociatedPatients);
        if (count($getAssociatedPatients) > 0) {
            //Means associated patients are there
            $getAssociatedPatientsIds = array();
            foreach ($getAssociatedPatients as $item) {
                array_push($getAssociatedPatientsIds, $item->DestinationUserId);
            };

            //Getting all registered user
            $getRegisteredPatientsCount = ReportModel::getMultipleUsersCount($getAssociatedPatientsIds, $searchStartDate, $searchEndDate);
            $userDetails['TotalRegisteredPatients'] = $getRegisteredPatientsCount;
            //Now fetching patients count who are registered directly
            $getDirectlyRegisteredPatientsData = ReportModel::getUsersViaRegisteredAs($getAssociatedPatientsIds, $registeredDirectly, $searchStartDate, $searchEndDate);
            $userDetails['DirectlyRegisteredPatients'] = count($getDirectlyRegisteredPatientsData);

            //Now getting patients count who got registered via invitation
            $getInvitedPatientsData = ReportModel::getUsersViaRegisteredAs($getAssociatedPatientsIds, $registeredViaInvitation, $searchStartDate, $searchEndDate);
            $userDetails['InvitedPatients'] = count($getInvitedPatientsData);

            $userDetails['PatientData'] = array();

            //Now fetching patients data
            $getAssociatedPatientsData = ReportModel::getMultipleUsersViaPagination($getAssociatedPatientsIds, $pageNo, $limit, $searchStartDate, $searchEndDate);

            if (count($getAssociatedPatientsData) > 0) {

                $userData = array();

                foreach ($getAssociatedPatientsData as $item) {
                    $data = array(
                        'Id' => (int)$item->Id,
                        'PatientUniqueId' => $item->PatientUniqueId,
                        'FirstName' => $item->FirstName,
                        'LastName' => $item->LastName,
                        'MiddleName' => $item->MiddleName,
                        'DateOfBirth' => $item->DateOfBirth,
                        'RegisteredOn' => (string) Carbon::createFromTimestampUTC($item->CreatedOn),
                        'RegisteredAs' => $item->RegisteredAs,
                    );
                    array_push($userData, $data);
                }
                $userDetails['PatientData'] = $userData;
            } else {
                $userDetails['PatientData'] = null;
            }

            return response()->json(['data' => $userDetails, 'message' => 'Patient registered data found'], 200);
        } else {
            error_log('No patient associated');
            return response()->json(['data' => null, 'message' => 'No patient(s) has been associated with this doctor'], 200);
        }
    }

    static public function GetPatientRegisteredReportCount(Request $request)
    {
        error_log('in controller');

        $doctorId = $request->get('doctorId');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $searchStartDate = "null";
        $searchEndDate = "null";

        $registeredDirectly = env('REGISTERED_DIRECT');
        $registeredViaInvitation = env('REGISTERED_VIA_INVITATION');

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        if ($startDate == "null" && $endDate != "null" || $startDate != "null" && $endDate == "null") {
            return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 400);
        }
        if ($startDate != "null" && $endDate != null) {

//            $timestamp = Carbon::createFromFormat('d-m-Y', $startDate)->timestamp;
//            $searchStartDate = $timestamp;
//
//            $timestampForEndDate = Carbon::createFromFormat('d-m-Y', $endDate)->timestamp;
//            $searchEndDate = $timestampForEndDate;

            $timestamp = Carbon::createFromFormat('Y-m-d', $startDate)->timestamp;
            $searchStartDate = $timestamp;

            $timestampForEndDate = Carbon::createFromFormat('Y-m-d', $endDate)->timestamp;
            $searchEndDate = $timestampForEndDate;

            error_log($searchEndDate);
        }

        $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($doctorId, $doctorPatientAssociation);
        error_log('$getAssociatedPatients are ' . $getAssociatedPatients);
        if (count($getAssociatedPatients) > 0) {
            //Means associated patients are there
            $getAssociatedPatientsIds = array();
            foreach ($getAssociatedPatients as $item) {
                array_push($getAssociatedPatientsIds, $item->DestinationUserId);
            };

            //Now fetching patients data
            $getAssociatedPatientsData = ReportModel::getMultipleUsersCount($getAssociatedPatientsIds, $searchStartDate, $searchEndDate);

            return response()->json(['data' => $getAssociatedPatientsData, 'message' => 'Patient registered report count'], 200);
        } else {
            error_log('No patient associated');
            return response()->json(['data' => null, 'message' => 'No patient(s) has been associated with this doctor'], 200);
        }
    }

    static public function GetPatientInvitationReport(Request $request)
    {
        error_log('in controller');

        error_log('in controller');

        $doctorId = $request->get('doctorId');
        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $searchStartDate = "null";
        $searchEndDate = "null";

        $invitationAccepted = env('INVITATION_ACCEPTED');
        $invitationPending = env('INVITATION_PENDING');
        $invitationRejected = env('INVITATION_REJECTED');
        $invitationIgnored = env('INVITATION_IGNORED');


        if ($startDate == "null" && $endDate != "null" || $startDate != "null" && $endDate == "null") {
            return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 400);
        }
        if ($startDate != "null" && $endDate != null) {

            $timestamp = Carbon::createFromFormat('Y-m-d', $startDate)->timestamp;
            $searchStartDate = $timestamp;

            $timeStampEndDate = Carbon::createFromFormat('Y-m-d', $endDate)->timestamp;
            $searchEndDate = $timeStampEndDate;

            error_log($timestamp);
        }

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        //First get all users which are invited
        $allInvitedPatientsData = ReportModel::getAllUsersInvitation($doctorId, $searchStartDate, $searchEndDate, $doctorPatientAssociation);

        error_log('count of $getAssociatedPatients is ' . count($allInvitedPatientsData));

        if (count($allInvitedPatientsData) > 0) {
            //Means associated patients are there
            $getEmailAddresses = array();
            foreach ($allInvitedPatientsData as $item) {
                array_push($getEmailAddresses, $item->ToEmailAddress);
            };
            $userDetails['TotalPatientsInvitation'] = count($allInvitedPatientsData);
            //Now fetching patients count who are accepted
            $getAcceptedInvitationData = ReportModel::getUsersInvitationViaInvitedType($doctorId, $invitationAccepted, $searchStartDate, $searchEndDate);
            $userDetails['AcceptedPatientsInvitation'] = count($getAcceptedInvitationData);

            //Now getting patients count who are in pending
            $getPendingInvitationData = ReportModel::getUsersInvitationViaInvitedType($doctorId, $invitationPending, $searchStartDate, $searchEndDate);
            $userDetails['PendingPatientsInvitation'] = count($getPendingInvitationData);

            //Now getting patients count who are in pending
            $getPendingInvitationData = ReportModel::getUsersInvitationViaInvitedType($doctorId, $invitationRejected, $searchStartDate, $searchEndDate);
            $userDetails['RejectedPatientsInvitation'] = count($getPendingInvitationData);

            //Now getting patients count who are ignored
            $getIgnoredInvitationData = ReportModel::getUsersInvitationViaInvitedType($doctorId, $invitationIgnored, $searchStartDate, $searchEndDate);
            $userDetails['IgnoredPatientsInvitation'] = count($getIgnoredInvitationData);

            $userDetails['PatientData'] = array();

            //Now fetching patients data
            $getInvitedPatientsData = ReportModel::getMultipleUsersViaEmailAddressesAndPagination($getEmailAddresses, $pageNo, $limit);

            if (count($getInvitedPatientsData) > 0) {

                $userData = array();

                foreach ($getInvitedPatientsData as $item) {

                    error_log("Timestamp");
                    error_log(Carbon::createFromDate($item->CreatedOn));

                    $data = array(
                        'Id' => (int)$item->Id,
                        'PatientUniqueId' => $item->PatientUniqueId,
                        'FirstName' => $item->FirstName,
                        'LastName' => $item->LastName,
                        'MiddleName' => $item->MiddleName,
                        'DateOfBirth' => $item->DateOfBirth,
                        'RegisteredOn' => (string) Carbon::createFromTimestampUTC($item->CreatedOn)
                    );
                    array_push($userData, $data);
                }
                $userDetails['PatientData'] = $userData;
            } else {
                $userDetails['PatientData'] = null;
            }

            return response()->json(['data' => $userDetails, 'message' => 'Patient invited data found'], 200);
        } else {
            error_log('No patient associated');
            return response()->json(['data' => null, 'message' => 'No patient(s) has been invited by this doctor'], 200);
        }
    }

    static public function GetPatientInvitationReportCount(Request $request)
    {
        error_log('in controller');

        error_log('in controller');

        $doctorId = $request->get('doctorId');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $searchStartDate = "null";
        $searchEndDate = "null";


        if ($startDate == "null" && $endDate != "null" || $startDate != "null" && $endDate == "null") {
            return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 400);
        }
        if ($startDate != "null" && $endDate != null) {

            $timestamp = Carbon::createFromFormat('Y-m-d', $startDate)->timestamp;
            $searchStartDate = $timestamp;

            $timeStampEndDate = Carbon::createFromFormat('Y-m-d', $endDate)->timestamp;
            $searchEndDate = $timeStampEndDate;

            error_log($timestamp);
        }

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        //First get all users which are invited
        $allInvitedPatientsData = ReportModel::getAllUsersInvitation($doctorId, $searchStartDate, $searchEndDate, $doctorPatientAssociation);

        error_log('count of $getAssociatedPatients is ' . count($allInvitedPatientsData));

        if (count($allInvitedPatientsData) > 0) {
            //Means associated patients are there
            $getEmailAddresses = array();
            foreach ($allInvitedPatientsData as $item) {
                array_push($getEmailAddresses, $item->ToEmailAddress);
            };

            //Now fetching patients data count
            $getInvitedPatientsDataCount = ReportModel::getMultipleUsersCountViaEmailAddreses($getEmailAddresses);

            return response()->json(['data' => $getInvitedPatientsDataCount, 'message' => 'Patient invited data count'], 200);
        } else {
            error_log('No patient associated');
            return response()->json(['data' => null, 'message' => 'No patient(s) has been invited by this doctor'], 200);
        }
    }

    static public function GetPatientCcmCptReport(Request $request)
    {
        error_log('in controller');

        $doctorId = $request->get('doctorId');
        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $ccmCptOptionCode = $request->post('CcmCptOptionCode');

        $ccmCptOptionCodeIds = array();

        if (count($ccmCptOptionCode) > 0) {
            error_log('ccm cpt option code id is given');
            foreach ($ccmCptOptionCode as $item) {
                array_push($ccmCptOptionCodeIds, (int)$item['Id']);
            }
        }

        $searchStartDate = "null";
        $searchEndDate = "null";

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        if ($startDate == "null" && $endDate != "null" || $startDate != "null" && $endDate == "null") {
            return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 400);
        }
        if ($startDate != "null" && $endDate != null) {

            $timestamp = Carbon::createFromFormat('Y-m-d', $startDate)->timestamp;
            $searchStartDate = $timestamp;

            $timeStampEndDate = Carbon::createFromFormat('Y-m-d', $endDate)->timestamp;
            $searchEndDate = $timeStampEndDate;

            error_log($timestamp);
        }

        $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($doctorId, $doctorPatientAssociation);
        error_log('$getAssociatedPatients are ' . $getAssociatedPatients);
        if (count($getAssociatedPatients) > 0) {
            //Means associated patients are there
            $getAssociatedPatientsIds = array();
            foreach ($getAssociatedPatients as $item) {
                array_push($getAssociatedPatientsIds, $item->DestinationUserId);
            };

            $userDetails['PatientData'] = array();

            $getAssociatedPatientsData = null;

            //Now fetching patients data
            if (count($ccmCptOptionCodeIds) > 0) {
                $getAssociatedPatientsData = ReportModel::getMultipleUsersViaPaginationAndCcmCptCode($getAssociatedPatientsIds, $pageNo, $limit, $searchStartDate, $searchEndDate, $ccmCptOptionCodeIds);
            } else {
                error_log('ccm cpt option code is not given');
                $getAssociatedPatientsData = ReportModel::getMultipleUsersViaPagination($getAssociatedPatientsIds, $pageNo, $limit, $searchStartDate, $searchEndDate);
            }

            if (count($getAssociatedPatientsData) > 0) {

                $userData = array();

                foreach ($getAssociatedPatientsData as $item) {
                    $data = array(
                        'Id' => (int)$item->Id,
                        'PatientUniqueId' => $item->PatientUniqueId,
                        'FirstName' => $item->FirstName,
                        'LastName' => $item->LastName,
                        'MiddleName' => $item->MiddleName,
                        'DateOfBirth' => $item->DateOfBirth,
                        'RegisteredOn' =>(string) Carbon::createFromTimestampUTC($item->CreatedOn),
                    );
                    array_push($userData, $data);
                }
                $userDetails['PatientData'] = $userData;
            } else {
                $userDetails['PatientData'] = null;
            }

            return response()->json(['data' => $userDetails, 'message' => 'Patient ccm cpt report data found'], 200);
        } else {
            error_log('No patient associated');
            return response()->json(['data' => null, 'message' => 'No patient(s) has been associated with this doctor'], 200);
        }
    }

    static public function GetPatientCcmCptReportCount(Request $request)
    {
        error_log('in controller');

        $doctorId = $request->get('doctorId');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $ccmCptOptionCode = $request->post('CcmCptOptionCode');

        $ccmCptOptionCodeIds = array();

        if (count($ccmCptOptionCode) > 0) {
            error_log('ccm cpt option code id is given');
            foreach ($ccmCptOptionCode as $item) {
                array_push($ccmCptOptionCodeIds, (int)$item['Id']);
            }
        }

        $searchStartDate = "null";
        $searchEndDate = "null";

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        if ($startDate == "null" && $endDate != "null" || $startDate != "null" && $endDate == "null") {
            return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 400);
        }
        if ($startDate != "null" && $endDate != null) {

            $timestamp = Carbon::createFromFormat('Y-m-d', $startDate)->timestamp;
            $searchStartDate = $timestamp;

            $timeStampEndDate = Carbon::createFromFormat('Y-m-d', $endDate)->timestamp;
            $searchEndDate = $timeStampEndDate;

            error_log($timestamp);
        }

        $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($doctorId, $doctorPatientAssociation);
        error_log('$getAssociatedPatients are ' . $getAssociatedPatients);
        if (count($getAssociatedPatients) > 0) {
            //Means associated patients are there
            $getAssociatedPatientsIds = array();
            foreach ($getAssociatedPatients as $item) {
                array_push($getAssociatedPatientsIds, $item->DestinationUserId);
            };

            $userDetails['PatientData'] = array();

            //Now fetching patients data
            if (count($ccmCptOptionCodeIds) > 0) {
                $getAssociatedPatientsData = ReportModel::getMultipleUsersViaCcmCptCodeCount($getAssociatedPatientsIds, $searchStartDate, $searchEndDate, $ccmCptOptionCodeIds);

                return response()->json(['data' => count($getAssociatedPatientsData), 'message' => 'Patient ccm cpt report count'], 200);
            } else {
                error_log('ccm cpt option code is not given');
                $getAssociatedPatientsData = ReportModel::getMultipleUsersCount($getAssociatedPatientsIds, $searchStartDate, $searchEndDate);
                return response()->json(['data' => $getAssociatedPatientsData, 'message' => 'Patient ccm cpt report count'], 200);
            }

        } else {
            error_log('No patient associated');
            return response()->json(['data' => null, 'message' => 'No patient(s) has been associated with this doctor'], 200);
        }
    }

    static public function GetPatientTypeReport(Request $request)
    {
        error_log('in controller');

        $doctorId = $request->get('doctorId');
        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $patientType = $request->post('PatientType');

        $patientTypeIds = array();

        if (count($patientType) > 0) {
            error_log('patient type id is given');
            foreach ($patientType as $item) {
                array_push($patientTypeIds, (int)$item['Id']);
            }
        }

        $searchStartDate = "null";
        $searchEndDate = "null";

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        if ($startDate == "null" && $endDate != "null" || $startDate != "null" && $endDate == "null") {
            return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 400);
        }
        if ($startDate != "null" && $endDate != null) {

            $timestamp = Carbon::createFromFormat('Y-m-d', $startDate)->timestamp;
            $searchStartDate = $timestamp;

            $timeStampEndDate = Carbon::createFromFormat('Y-m-d', $endDate)->timestamp;
            $searchEndDate = $timeStampEndDate;

            error_log($timestamp);
        }

        $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($doctorId, $doctorPatientAssociation);
        error_log('$getAssociatedPatients are ' . $getAssociatedPatients);
        if (count($getAssociatedPatients) > 0) {
            //Means associated patients are there
            $getAssociatedPatientsIds = array();
            foreach ($getAssociatedPatients as $item) {
                array_push($getAssociatedPatientsIds, $item->DestinationUserId);
            };

            $userDetails['PatientData'] = array();

            $getAssociatedPatientsData = null;

            //Now fetching patients data
            if (count($patientTypeIds) > 0) {
                $getAssociatedPatientsData = ReportModel::getMultipleUsersViaPaginationAndPatientType($getAssociatedPatientsIds, $pageNo, $limit, $searchStartDate, $searchEndDate, $patientTypeIds);
            } else {
                error_log('ccm cpt option code is not given');
                $getAssociatedPatientsData = ReportModel::getMultipleUsersViaPagination($getAssociatedPatientsIds, $pageNo, $limit, $searchStartDate, $searchEndDate);
            }

            if (count($getAssociatedPatientsData) > 0) {

                $userData = array();

                foreach ($getAssociatedPatientsData as $item) {
                    $data = array(
                        'Id' => (int)$item->Id,
                        'PatientUniqueId' => $item->PatientUniqueId,
                        'FirstName' => $item->FirstName,
                        'LastName' => $item->LastName,
                        'MiddleName' => $item->MiddleName,
                        'DateOfBirth' => $item->DateOfBirth,
                        'RegisteredOn' => (string) Carbon::createFromTimestampUTC($item->CreatedOn),
                        'PatientType' => array()
                    );

                    $data['PatientType']['Id'] = $item->PatientTypeId;
                    $data['PatientType']['Name'] = $item->Name;
                    $data['PatientType']['Code'] = $item->Code;

                    array_push($userData, $data);
                }
                $userDetails['PatientData'] = $userData;
            } else {
                $userDetails['PatientData'] = null;
            }

            return response()->json(['data' => $userDetails, 'message' => 'Patient type report data found'], 200);
        } else {
            error_log('No patient associated');
            return response()->json(['data' => null, 'message' => 'No patient(s) has been associated with this doctor'], 200);
        }
    }

    static public function GetPatientTypeReportCount(Request $request)
    {
        error_log('in controller');

        $doctorId = $request->get('doctorId');
        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $patientType = $request->post('PatientType');

        $patientTypeIds = array();

        if (count($patientType) > 0) {
            error_log('patient type id is given');
            foreach ($patientType as $item) {
                array_push($patientTypeIds, (int)$item['Id']);
            }
        }

        $searchStartDate = "null";
        $searchEndDate = "null";

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        if ($startDate == "null" && $endDate != "null" || $startDate != "null" && $endDate == "null") {
            return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 400);
        }
        if ($startDate != "null" && $endDate != null) {

            $timestamp = Carbon::createFromFormat('Y-m-d', $startDate)->timestamp;
            $searchStartDate = $timestamp;

            $timeStampEndDate = Carbon::createFromFormat('Y-m-d', $endDate)->timestamp;
            $searchEndDate = $timeStampEndDate;

            error_log($timestamp);
        }

        $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($doctorId, $doctorPatientAssociation);
        error_log('$getAssociatedPatients are ' . $getAssociatedPatients);
        if (count($getAssociatedPatients) > 0) {
            //Means associated patients are there
            $getAssociatedPatientsIds = array();
            foreach ($getAssociatedPatients as $item) {
                array_push($getAssociatedPatientsIds, $item->DestinationUserId);
            };

            $userDetails['PatientData'] = array();

            $getAssociatedPatientsData = 0;

            //Now fetching patients data
            if (count($patientTypeIds) > 0) {
                $getAssociatedPatientsData = ReportModel::getMultipleUsersCountViaPatientType($getAssociatedPatientsIds, $searchStartDate, $searchEndDate, $patientTypeIds);
            } else {
                error_log('ccm cpt option code is not given');
                $getAssociatedPatientsData = ReportModel::getMultipleUsersCount($getAssociatedPatientsIds, $searchStartDate, $searchEndDate);
            }

            return response()->json(['data' => $getAssociatedPatientsData, 'message' => 'Patient type report data count'], 200);
        } else {
            error_log('No patient associated');
            return response()->json(['data' => null, 'message' => 'No patient(s) has been associated with this doctor'], 200);
        }
    }
}