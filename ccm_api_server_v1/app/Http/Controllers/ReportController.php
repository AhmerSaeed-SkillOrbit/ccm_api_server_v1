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

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');
        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');
        $patientRole = env('ROLE_PATIENT');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        //First check if logged in user belongs to facilitator
        //if it is facilitator then check it's doctor association
        //And then check if that patient is associated with dr or not

        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);

        if ($checkUserData->RoleCodeName == $doctorRole) {
            error_log('logged in user role is doctor');
            error_log('Now fetching its associated patients');

            $checkAssociatedPatient = UserModel::getAssociatedPatientViaDoctorId($userId, $doctorPatientAssociation, $patientId);
            if (count($checkAssociatedPatient) <= 0) {
                return response()->json(['data' => null, 'message' => 'This patient is not associated to this doctor'], 400);
            }

        } else if ($checkUserData->RoleCodeName == $facilitatorRole) {
            error_log('logged in user role is facilitator');
            error_log('Now first get facilitator association with doctor');

            $getAssociatedDoctors = UserModel::getSourceIdViaLoggedInUserIdAndAssociationType($userId, $doctorFacilitatorAssociation);
            if (count($getAssociatedDoctors) > 0) {
                error_log('this facilitator is associated to doctor');
                $doctorIds = array();
                foreach ($getAssociatedDoctors as $item) {
                    array_push($doctorIds, $item->SourceUserId);
                }

                //Now we will get associated patient with respect to these doctors.
                //If there will be no data then we will throw an error message that this patient is not associated to doctor

                $checkAssociatedPatient = UserModel::getAssociatedPatientWithRespectToMultipleDoctorIds($doctorIds, $doctorPatientAssociation, $patientId);
                if (count($checkAssociatedPatient) <= 0) {
                    return response()->json(['data' => null, 'message' => 'This patient is not associated to this doctor'], 400);
                }

            } else {
                error_log('associated doctor not found');
                return response()->json(['data' => null, 'message' => 'logged in facilitator is not yet associated to any doctor'], 400);
            }

        } else if ($checkUserData->RoleCodeName == $superAdminRole) {
            error_log('logged in user is super admin');
        } else {
            return response()->json(['data' => null, 'message' => 'logged in user must be from doctor, facilitator or super admin'], 400);
        }

        if ($startDate == "null" && $endDate != "null" || $startDate != "null" && $endDate == "null") {
            return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 400);
        }

        $finalData = array();

        //Now check if this ccm cpt option for this patient already exists
        //If exists then delete it

        $getData = CcmModel::getAllCcmCptOptionViaPatientId($patientId);
        if (count($getData) > 0) {
            foreach ($getData as $item) {
                $data = array(
                    'Id' => (int) $item->cptId,
                    'Name' => $item->Name,
                    'Code' => $item->Code,
                    'Description' => $item->Description
                );

                array_push($finalData, $data);
            }

            return response()->json(['data' => $finalData, 'message' => 'Ccm cpt option for this patient found'], 200);

        } else {
            error_log('ccm cpt option not assigned');
            return response()->json(['data' => null, 'message' => 'Ccm cpt option not assigned to this patient yet'], 200);
        }
    }
}