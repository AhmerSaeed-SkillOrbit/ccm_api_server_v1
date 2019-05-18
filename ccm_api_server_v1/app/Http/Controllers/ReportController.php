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
    static public function GetInvitedPatientReport(Request $request)
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

        $registeredDirectly = env('REGISTERED_DIRECT');
        $registeredViaInvitation = env('REGISTERED_VIA_INVITATION');

        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        if ($startDate == "null" && $endDate != "null" || $startDate != "null" && $endDate == "null") {
            return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 400);
        }
        if ($startDate != "null" && $endDate != null) {

            $timestamp = Carbon::createFromFormat('d-m-Y', $startDate)->timestamp;
            $searchStartDate = $timestamp;

            $timestamp = Carbon::createFromFormat('d-m-Y', $endDate)->timestamp;
            $searchEndDate = $timestamp;
        }

        $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($doctorId, $doctorPatientAssociation);
        error_log('$getAssociatedPatients are ' . $getAssociatedPatients);
        if (count($getAssociatedPatients) > 0) {
            //Means associated patients are there
            $getAssociatedPatientsIds = array();
            foreach ($getAssociatedPatients as $item) {
                array_push($getAssociatedPatientsIds, $item->DestinationUserId);
            };
            $userDetails['TotalRegisteredPatients'] = null;
            //Now fetching patients count who are registered directly
            $getDirectlyRegisteredPatientsData = ReportModel::getUsersViaRegisteredAs($getAssociatedPatientsIds, $registeredDirectly, $searchStartDate, $searchEndDate);
            $userDetails['DirectlyRegisteredPatients'] = count($getDirectlyRegisteredPatientsData);

            //Now getting patients count who got registered via invitation
            $getInvitedPatientsData = ReportModel::getUsersViaRegisteredAs($getAssociatedPatientsIds, $registeredViaInvitation, $searchStartDate, $searchEndDate);
            $userDetails['InvitedPatientsPatients'] = count($getInvitedPatientsData);

            $userDetails['PatientData'] = array();

            //Now fetching patients data
            $getAssociatedPatientsData = ReportModel::getMultipleUsersViaPagination($getAssociatedPatientsIds, $pageNo, $limit, $searchStartDate, $searchEndDate);

            if (count($getAssociatedPatientsData) > 0) {

                $userDetails['TotalRegisteredPatients'] = count($getAssociatedPatientsData);

                $userData = array();

                foreach ($getAssociatedPatientsData as $item) {
                    $data = array(
                        'Id' => (int)$item->Id,
                        'PatientUniqueId' => $item->PatientUniqueId,
                        'FirstName' => $item->FirstName,
                        'LastName' => $item->LastName,
                        'MiddleName' => $item->MiddleName,
                        'DateOfBirth' => $item->DateOfBirth,
                        'RegisteredOn' => date("d-M-Y h:m a", strtotime($item->CreatedOn)),
                        'Registered' => $item->RegisteredAs,
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
}