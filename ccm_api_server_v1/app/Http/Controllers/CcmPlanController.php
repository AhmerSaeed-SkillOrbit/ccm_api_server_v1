<?php
/**
 * Created by PhpStorm.
 * User: Ahsan Vohra
 * Date: 3/25/2019
 * Time: 7:50 PM
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
use App\Models\CcmModel;
use Twilio\Twiml;
use Carbon\Carbon;


class CcmPlanController extends Controller
{
    static public function GetQuestionsList()
    {
        error_log('in controller');
        $questionsList = CcmModel::getQuestionList();

        if (count($questionsList) > 0) {
            return response()->json(['data' => $questionsList, 'message' => 'Questions found'], 400);
        } else {
            return response()->json(['data' => null, 'message' => 'Questions not found'], 200);
        }
    }

    static public function GiveAnswerToQuestion(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

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


        $date = HelperModel::getDate();

        $answerData = array();


        foreach ($request->input('Answer') as $item) {

            $data = array(
                'CcmQuestionId' => $item['CcmQuestionId'],
                'AskById' => $userId,
                'PatientId' => $patientId,
                'IsAnswered' => $item['IsAnswered'],
                'Answer' => $item['Answer'],
                'IsActive' => true,
                'CreatedBy' => $userId,
                'CreatedOn' => $date["timestamp"]
            );

            array_push($answerData, $data);
        }

        $insertedData = GenericModel::insertGeneric('ccm_answer', $answerData);
        if ($insertedData == false) {
            error_log('data not inserted');
            return response()->json(['data' => null, 'message' => 'Error in inserting answers'], 400);
        } else {
            error_log('data inserted');
            return response()->json(['data' => (int)$userId, 'message' => 'Answer successfully added'], 200);
        }
    }

    function GetAnswerTypeList()
    {
        error_log('in controller');

        $isAnsweredData = TicketModel::getEnumValues('ccm_answer', 'IsAnswered');
        if ($isAnsweredData == null) {
            return response()->json(['data' => null, 'message' => 'Answer type found'], 200);
        } else {
            return response()->json(['data' => $isAnsweredData, 'message' => 'Answer type not found'], 200);
        }
    }

    static public function UpdateAnswer(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');
        $answerId = $request->get('Id');

        //First checking if answer exists or not

        $date = HelperModel::getDate();

        $checkAnswerData = CcmModel::getSingleAnswer($answerId);

        if ($checkAnswerData == null) {
            error_log('Answer not found');
            return response()->json(['data' => null, 'message' => 'Answer is not valid'], 400);

            error_log('now we will add that questions answer');

            $dataToAdd = array(
                'AskById' => $userId,
                'PatientId' => $patientId,
                'IsAnswered' => $request->get('IsAnswered'),
                'Answer' => $request->get('Answer'),
                'CreatedBy' => $userId,
                'CreatedOn' => $date["timestamp"]
            );

            $insertedDataId = GenericModel::insertGenericAndReturnID('ccm_answer', $dataToAdd);

            if ($insertedDataId == 0) {
                error_log('data not inserted');
                return response()->json(['data' => null, 'message' => 'Error in inserting answer'], 400);
            } else {
                error_log('data inserted');
                return response()->json(['data' => $insertedDataId, 'message' => 'Answer successfully given'], 200);
            }

        } else {
            error_log('Answer found');

            $dataToUpdate = array(
                'AskById' => $userId,
                'PatientId' => $patientId,
                'IsAnswered' => $request->get('IsAnswered'),
                'Answer' => $request->get('Answer'),
                'UpdatedBy' => $userId,
                'UpdatedOn' => $date["timestamp"]
            );

            $updatedData = GenericModel::updateGeneric('ccm_answer', 'Id', $answerId, $dataToUpdate);

            if ($updatedData == false) {
                error_log('data not updated');
                return response()->json(['data' => null, 'message' => 'Error in updating answer'], 400);
            } else {
                error_log('data updated');
                return response()->json(['data' => (int)$userId, 'message' => 'Answer successfully updated'], 200);
            }
        }
    }

    static public function GetAllQuestionAnswers(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

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


        //First get all question list
        $questionLIst = CcmModel::getQuestionList();
        if (count($questionLIst) > 0) {
            error_log('questions found');

            $finalData = array();
            $answerData = array();

            foreach ($questionLIst as $item) {
                $questionData = array(
                    'Id' => $item->Id,
                    'Question' => $item->Question,
                    'Type' => $item->Type,
                    'Answers' => array()
                );

                //Now one by one we will fetch answers and will bind it in Answers array
                $answerList = CcmModel::getAnswersViaQuestionIdAndPatientId($item->Id, $patientId);
                if (count($answerList) > 0) {
                    error_log('answer found for question id : ' . $item->Id);

                    foreach ($answerList as $item2) {
                        error_log('in for each loop');

                        $data = array(
                            'Id' => $item2->Id,
                            'IsAnswered' => $item2->IsAnswered,
                            'Answer' => $item2->Answer,
                        );

                        array_push($questionData['Answers'], $data);
                    }
                }

                array_push($finalData, $questionData);
            }

            if (count($finalData) > 0) {

                return response()->json(['data' => $finalData, 'message' => 'Question and Answer found'], 200);
            } else {

                return response()->json(['data' => $finalData, 'message' => 'Question and Answer not found'], 400);
            }

        } else {
            return response()->json(['data' => null, 'message' => 'Question not found'], 400);
        }
    }

    static public function GetQuestionAnswerSingle(Request $request)
    {
        error_log('in controller');

        $questionId = $request->get('questionId');
        $patientId = $request->get('patientId');

        $questionData = array();

        //First get single question

        $question = CcmModel::getQuestionViaId($questionId);
        if ($question != null) {
            error_log('questions found');

            $questionData['Id'] = $question->Id;
            $questionData['Question'] = $question->Question;
            $questionData['Type'] = $question->Type;
            $questionData['Answers'] = array();

            //Now one by one we will fetch answers and will bind it in Answers array
            $answerList = CcmModel::getAnswersViaQuestionIdAndPatientId($question->Id, $patientId);
            if (count($answerList) > 0) {
                error_log('answer found for question id : ' . $question->Id);

                foreach ($answerList as $item2) {
                    error_log('in for each loop');

                    $data = array(
                        'Id' => $item2->Id,
                        'IsAnswered' => $item2->IsAnswered,
                        'Answer' => $item2->Answer,
                    );

                    array_push($questionData['Answers'], $data);
                }
            }

            if (count($questionData) > 0) {

                return response()->json(['data' => $questionData, 'message' => 'Question and Answer found'], 200);
            } else {

                return response()->json(['data' => $questionData, 'message' => 'Question and Answer not found'], 400);
            }

        } else {
            return response()->json(['data' => null, 'message' => 'Question not found'], 400);
        }
    }

    static public function AddActiveMedicine(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

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


        $date = HelperModel::getDate();

        $activeMedicineData = array();


        foreach ($request->input('ActiveMedicine') as $item) {

            $data = array(
                'PatientId' => $patientId,
                'MedicineName' => $item['MedicineName'],
                'DoseNumber' => $item['DoseNumber'],
                'Direction' => $item['Direction'],
                'StartDate' => $item['StartDate'],
                'StartBy' => $item['StartBy'],
                'WhyComments' => $item['WhyComments'],
                'IsActive' => true,
                'CreatedBy' => $userId,
                'CreatedOn' => $date["timestamp"]
            );

            array_push($activeMedicineData, $data);
        }

        $insertedData = GenericModel::insertGeneric('ccm_active_medicine', $activeMedicineData);
        if ($insertedData == false) {
            error_log('data not inserted');
            return response()->json(['data' => null, 'message' => 'Error in inserting active medicine'], 400);
        } else {
            error_log('data inserted');
            return response()->json(['data' => (int)$userId, 'message' => 'Active medicine successfully added'], 200);
        }
    }

    static public function UpdateActiveMedicine(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $activeMedicineId = $request->get('id');

        //First checking if answer exists or not

        $date = HelperModel::getDate();

        $checkData = CcmModel::getSingleActiveMedicine($activeMedicineId);

        if ($checkData == null) {
            error_log('active medicine not found');
            return response()->json(['data' => null, 'message' => 'Active medicine not found'], 400);
        } else {
            error_log('Answer found');

            $dataToUpdate = array(
                'MedicineName' => $request->get('MedicineName'),
                'DoseNumber' => $request->get('DoseNumber'),
                'Direction' => $request->get('Direction'),
                'StartDate' => $request->get('StartDate'),
                'StartBy' => $request->get('StartBy'),
                'WhyComments' => $request->get('WhyComments'),
                'UpdatedBy' => $userId,
                'UpdatedOn' => $date["timestamp"]
            );

            $updatedData = GenericModel::updateGeneric('ccm_active_medicine', 'Id', $activeMedicineId, $dataToUpdate);

            if ($updatedData == false) {
                error_log('data not updated');
                return response()->json(['data' => null, 'message' => 'Error in updating active medicine'], 400);
            } else {
                error_log('data updated');
                return response()->json(['data' => (int)$userId, 'message' => 'Active medicine successfully updated'], 200);
            }
        }
    }

    static public function GetAllActiveMedicine(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        //First check if logged in user belongs to facilitator
        //if it is facilitator then check it's doctor association
        //And then check if that patient is associated with dr or not

        $finalData = array();

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

        //Get all active medicine via patient id
        $medicineList = CcmModel::getAllActiveMedicineViaPatientId($patientId);
        if (count($medicineList) > 0) {
            error_log('medicine list found ');

            foreach ($medicineList as $item) {
                error_log('in for each loop');

                $data = array(
                    'Id' => $item->Id,
                    'MedicineName' => $item->MedicineName,
                    'DoseNumber' => $item->DoseNumber,
                    'Direction' => $item->Direction,
                    'StartDate' => Carbon::createFromTimestamp($item->StartDate),
                    'StartBy' => Carbon::createFromTimestamp($item->StartBy),
                    'WhyComments' => $item->WhyComments
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Active medicine not found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Active medicine found'], 400);
        }
    }

    static public function GetSingleActiveMedicine(Request $request)
    {
        error_log('in controller');

        $activeMedicineId = $request->get('id');

        //Get single active medicine via medicine id
        $medicineData = CcmModel::getSingleActiveMedicine($activeMedicineId);
        if ($medicineData != null) {
            error_log('medicine found ');

            $data['Id'] = $medicineData->Id;
            $data['MedicineName'] = $medicineData->MedicineName;
            $data['DoseNumber'] = $medicineData->DoseNumber;
            $data['Direction'] = $medicineData->Direction;
            $data['StartDate'] = Carbon::createFromTimestamp($medicineData->StartDate);
            $data['StartBy'] = Carbon::createFromTimestamp($medicineData->StartBy);
            $data['WhyComments'] = $medicineData->WhyComments;

            return response()->json(['data' => $data, 'message' => 'Active medicine found'], 200);

        } else {
            return response()->json(['data' => null, 'message' => 'Active medicine not found'], 400);
        }
    }

    static public function AddAllergyMedicine(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

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


        $date = HelperModel::getDate();

        $medicineData = array();


        foreach ($request->input('AllergyMedicine') as $item) {

            $data = array(
                'PatientId' => $patientId,
                'MedicineName' => $item['MedicineName'],
                'MedicineReaction' => $item['MedicineReaction'],
                'ReactionDate' => $item['ReactionDate'],
                'IsReactionSevere' => $item['IsReactionSevere'],
                'IsActive' => true,
                'CreatedBy' => $userId,
                'CreatedOn' => $date["timestamp"]
            );

            array_push($medicineData, $data);
        }

        $insertedData = GenericModel::insertGeneric('ccm_medicine_allergy', $medicineData);
        if ($insertedData == false) {
            error_log('data not inserted');
            return response()->json(['data' => null, 'message' => 'Error in inserting allergy medicine'], 400);
        } else {
            error_log('data inserted');
            return response()->json(['data' => (int)$userId, 'message' => 'Allergy medicine successfully added'], 200);
        }
    }

    static public function UpdateAllergyMedicine(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $allergyMedicineId = $request->get('id');

        //First checking if answer exists or not

        $date = HelperModel::getDate();

        $checkData = CcmModel::getSingleAllergy($allergyMedicineId);

        if ($checkData == null) {
            error_log(' medicine not found');
            return response()->json(['data' => null, 'message' => 'Allergy medicine not found'], 400);
        } else {
            error_log('Answer found');

            $dataToUpdate = array(
                'MedicineName' => $request->get('MedicineName'),
                'MedicineReaction' => $request->get('MedicineReaction'),
                'ReactionDate' => $request->get('ReactionDate'),
                'IsReactionSevere' => $request->get('IsReactionSevere'),
                'UpdatedBy' => $userId,
                'UpdatedOn' => $date["timestamp"]
            );

            $updatedData = GenericModel::updateGeneric('ccm_medicine_allergy', 'Id', $allergyMedicineId, $dataToUpdate);

            if ($updatedData == false) {
                error_log('data not updated');
                return response()->json(['data' => null, 'message' => 'Error in updating allergy medicine'], 400);
            } else {
                error_log('data updated');
                return response()->json(['data' => (int)$userId, 'message' => 'Allergy medicine successfully updated'], 200);
            }
        }
    }

    static public function GetAllAllergyMedicine(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        //First check if logged in user belongs to facilitator
        //if it is facilitator then check it's doctor association
        //And then check if that patient is associated with dr or not

        $finalData = array();

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

        //Get all active medicine via patient id
        $medicineList = CcmModel::getAllAllergiesViaPatientId($patientId);
        if (count($medicineList) > 0) {
            error_log('medicine list found ');

            foreach ($medicineList as $item) {
                error_log('in for each loop');

                $data = array(
                    'Id' => $item->Id,
                    'MedicineName' => $item->MedicineName,
                    'MedicineReaction' => $item->MedicineReaction,
                    'ReactionDate' => Carbon::createFromTimestamp($item->ReactionDate),
                    'IsReactionSevere' => $item->IsReactionSevere
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Allergy medicine not found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Allergy medicine found'], 400);
        }
    }

    static public function GetSingleAllergyMedicine(Request $request)
    {
        error_log('in controller');

        $allergyMedicineId = $request->get('id');

        //Get single active medicine via medicine id
        $medicineData = CcmModel::getSingleAllergy($allergyMedicineId);
        if ($medicineData != null) {
            error_log('medicine found ');

            $data['Id'] = $medicineData->Id;
            $data['MedicineName'] = $medicineData->MedicineName;
            $data['MedicineReaction'] = $medicineData->MedicineReaction;
            $data['ReactionDate'] = Carbon::createFromTimestamp($medicineData->ReactionDate);
            $data['IsReactionSevere'] = $medicineData->IsReactionSevere;

            return response()->json(['data' => $data, 'message' => 'Allergy medicine found'], 200);

        } else {
            return response()->json(['data' => null, 'message' => 'Allergy medicine not found'], 400);
        }
    }

    static public function AddNonMedicine(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

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


        $date = HelperModel::getDate();

        $medicineData = array();


        foreach ($request->input('NonMedicine') as $item) {

            $data = array(
                'PatientId' => $patientId,
                'SubstanceName' => $item['SubstanceName'],
                'SubstanceReaction' => $item['SubstanceReaction'],
                'ReactionDate' => $item['ReactionDate'],
                'IsReactionSevere' => $item['IsReactionSevere'],
                'IsActive' => true,
                'CreatedBy' => $userId,
                'CreatedOn' => $date["timestamp"]
            );

            array_push($medicineData, $data);
        }

        $insertedData = GenericModel::insertGeneric('ccm_non_medicine', $medicineData);
        if ($insertedData == false) {
            error_log('data not inserted');
            return response()->json(['data' => null, 'message' => 'Error in inserting non medicine'], 400);
        } else {
            error_log('data inserted');
            return response()->json(['data' => (int)$userId, 'message' => 'Non medicine successfully added'], 200);
        }
    }

    static public function UpdateNonMedicine(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $nonMedicineId = $request->get('id');

        //First checking if answer exists or not

        $date = HelperModel::getDate();

        $checkData = CcmModel::getSingleNonMedicine($nonMedicineId);

        if ($checkData == null) {
            error_log(' medicine not found');
            return response()->json(['data' => null, 'message' => 'Non medicine not found'], 400);
        } else {
            error_log('Answer found');

            $dataToUpdate = array(
                'SubstanceName' => $request->get('SubstanceName'),
                'SubstanceReaction' => $request->get('SubstanceReaction'),
                'ReactionDate' => $request->get('ReactionDate'),
                'IsReactionSevere' => $request->get('IsReactionSevere'),
                'UpdatedBy' => $userId,
                'UpdatedOn' => $date["timestamp"]
            );

            $updatedData = GenericModel::updateGeneric('ccm_non_medicine', 'Id', $nonMedicineId, $dataToUpdate);

            if ($updatedData == false) {
                error_log('data not updated');
                return response()->json(['data' => null, 'message' => 'Error in updating non active medicine'], 400);
            } else {
                error_log('data updated');
                return response()->json(['data' => (int)$userId, 'message' => 'Non active medicine successfully updated'], 200);
            }
        }
    }

    static public function GetAllNonMedicine(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        //First check if logged in user belongs to facilitator
        //if it is facilitator then check it's doctor association
        //And then check if that patient is associated with dr or not

        $finalData = array();

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

        //Get all active medicine via patient id
        $medicineList = CcmModel::getAllNonMedicinesViaPatientId($patientId);
        if (count($medicineList) > 0) {
            error_log('medicine list found ');

            foreach ($medicineList as $item) {
                error_log('in for each loop');

                $data = array(
                    'Id' => $item->Id,
                    'SubstanceName' => $item->SubstanceName,
                    'SubstanceReaction' => $item->SubstanceReaction,
                    'ReactionDate' => Carbon::createFromTimestamp($item->ReactionDate),
                    'IsReactionSevere' => $item->IsReactionSevere
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Non medicine not found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Non medicine found'], 400);
        }
    }

    static public function GetSingleNonMedicine(Request $request)
    {
        error_log('in controller');

        $nonMedicineId = $request->get('id');

        //Get single active medicine via medicine id
        $medicineData = CcmModel::getSingleNonMedicine($nonMedicineId);
        if ($medicineData != null) {
            error_log('medicine found ');

            $data['Id'] = $medicineData->Id;
            $data['SubstanceName'] = $medicineData->SubstanceName;
            $data['SubstanceReaction'] = $medicineData->SubstanceReaction;
            $data['ReactionDate'] = Carbon::createFromTimestamp($medicineData->ReactionDate);
            $data['IsReactionSevere'] = $medicineData->IsReactionSevere;

            return response()->json(['data' => $data, 'message' => 'Non medicine found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Non medicine not found'], 400);
        }
    }

    static public function AddImmunizationVaccine(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

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


        $date = HelperModel::getDate();

        $medicineData = array();


        foreach ($request->input('ImmunizationVaccine') as $item) {

            $data = array(
                'PatientId' => $patientId,
                'Vaccine' => $item['Vaccine'],
                'VaccineDate' => $item['VaccineDate'],
                'IsActive' => true,
                'CreatedBy' => $userId,
                'CreatedOn' => $date["timestamp"]
            );

            array_push($medicineData, $data);
        }

        $insertedData = GenericModel::insertGeneric('ccm_immunization_vaccine', $medicineData);
        if ($insertedData == false) {
            error_log('data not inserted');
            return response()->json(['data' => null, 'message' => 'Error in inserting immunization vaccine'], 400);
        } else {
            error_log('data inserted');
            return response()->json(['data' => (int)$userId, 'message' => 'Immunization vaccine successfully added'], 200);
        }
    }

    static public function UpdateImmunizationVaccine(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $immunizationVaccineId = $request->get('id');

        //First checking if answer exists or not

        $date = HelperModel::getDate();

        $checkData = CcmModel::getSingleImmunizationVaccine($immunizationVaccineId);

        if ($checkData == null) {
            error_log(' medicine not found');
            return response()->json(['data' => null, 'message' => 'Non medicine not found'], 400);
        } else {
            error_log('Answer found');

            $dataToUpdate = array(
                'Vaccine' => $request->get('Vaccine'),
                'VaccineDate' => $request->get('VaccineDate'),
                'UpdatedBy' => $userId,
                'UpdatedOn' => $date["timestamp"]
            );

            $updatedData = GenericModel::updateGeneric('ccm_immunization_vaccine', 'Id', $immunizationVaccineId, $dataToUpdate);

            if ($updatedData == false) {
                error_log('data not updated');
                return response()->json(['data' => null, 'message' => 'Error in updating immunization vaccine'], 400);
            } else {
                error_log('data updated');
                return response()->json(['data' => (int)$userId, 'message' => 'Immunization successfully updated'], 200);
            }
        }
    }

    static public function GetAllImmunizationVaccince(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        //First check if logged in user belongs to facilitator
        //if it is facilitator then check it's doctor association
        //And then check if that patient is associated with dr or not

        $finalData = array();

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

        //Get all active medicine via patient id
        $medicineList = CcmModel::getAllImmunizationVaccineViaPatientId($patientId);
        if (count($medicineList) > 0) {
            error_log('medicine list found ');

            foreach ($medicineList as $item) {
                error_log('in for each loop');

                $data = array(
                    'Id' => $item->Id,
                    'Vaccine' => $item->Vaccine,
                    'VaccineDate' => Carbon::createFromTimestamp($item->VaccineDate)
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Immunization vaccine not found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Immunization vaccine found'], 400);
        }
    }

    static public function GetSingleImmunization(Request $request)
    {
        error_log('in controller');

        $immunizationVaccineId = $request->get('id');

        //Get single active medicine via medicine id
        $medicineData = CcmModel::getSingleImmunizationVaccine($immunizationVaccineId);
        if ($medicineData != null) {
            error_log('medicine found ');

            $data['Id'] = $medicineData->Id;
            $data['Vaccine'] = $medicineData->Vaccine;
            $data['VaccineDate'] = Carbon::createFromTimestamp($medicineData->VaccineDate);

            return response()->json(['data' => $data, 'message' => 'Immunization vaccine found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Immunization vaccine not found'], 400);
        }
    }

    static public function AddHealthCareHistory(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

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


        $date = HelperModel::getDate();

        $dataToInsert = array();


        foreach ($request->input('HealthCareHistory') as $item) {

            $data = array(
                'PatientId' => $patientId,
                'Provider' => $item['Provider'],
                'LastVisitDate' => $item['LastVisitDate'],
                'VisitReason' => $item['VisitReason'],
                'IsActive' => true,
                'CreatedBy' => $userId,
                'CreatedOn' => $date["timestamp"]
            );

            array_push($dataToInsert, $data);
        }

        $insertedData = GenericModel::insertGeneric('ccm_healthcare_history', $dataToInsert);
        if ($insertedData == false) {
            error_log('data not inserted');
            return response()->json(['data' => null, 'message' => 'Error in inserting health care history'], 400);
        } else {
            error_log('data inserted');
            return response()->json(['data' => (int)$userId, 'message' => 'Health care history successfully added'], 200);
        }
    }

    static public function UpdateHealthCareHistory(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $healthCareHistoryId = $request->get('id');

        //First checking if answer exists or not

        $date = HelperModel::getDate();

        $checkData = CcmModel::getSingleHealthCareHistory($healthCareHistoryId);

        if ($checkData == null) {
            error_log(' medicine not found');
            return response()->json(['data' => null, 'message' => 'Health care history not found'], 400);
        } else {
            error_log('Answer found');

            $dataToUpdate = array(
                'Provider' => $request->get('Provider'),
                'LastVisitDate' => $request->get('LastVisitDate'),
                'VisitReason' => $request->get('VisitReason'),
                'UpdatedBy' => $userId,
                'UpdatedOn' => $date["timestamp"]
            );

            $updatedData = GenericModel::updateGeneric('ccm_healthcare_history', 'Id', $healthCareHistoryId, $dataToUpdate);

            if ($updatedData == false) {
                error_log('data not updated');
                return response()->json(['data' => null, 'message' => 'Error in updating health care history'], 400);
            } else {
                error_log('data updated');
                return response()->json(['data' => (int)$userId, 'message' => 'Health care history successfully updated'], 200);
            }
        }
    }

    static public function GetAllHealthCareHistory(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        //First check if logged in user belongs to facilitator
        //if it is facilitator then check it's doctor association
        //And then check if that patient is associated with dr or not

        $finalData = array();

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

        //Get all active medicine via patient id
        $medicineList = CcmModel::getAllHealthCareHistoryViaPatientId($patientId);
        if (count($medicineList) > 0) {
            error_log('health care history list found ');

            foreach ($medicineList as $item) {
                error_log('in for each loop');

                $data = array(
                    'Id' => $item->Id,
                    'Provider' => $item->Provider,
                    'LastVisitDate' => Carbon::createFromTimestamp($item->LastVisitDate),
                    'VisitReason' => $item->VisitReason
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Health care history not found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Health care history found'], 400);
        }
    }

    static public function GetSingleHealthCareHistory(Request $request)
    {
        error_log('in controller');

        $healthCareHistoryId = $request->get('id');

        //Get single active medicine via medicine id
        $medicineData = CcmModel::getSingleHealthCareHistory($healthCareHistoryId);
        if ($medicineData != null) {
            error_log('health care history found ');

            $data['Id'] = $medicineData->Id;
            $data['Provider'] = $medicineData->Provider;
            $data['LastVisitDate'] = Carbon::createFromTimestamp($medicineData->LastVisitDate);
            $data['VisitReason'] = $medicineData->VisitReason;

            return response()->json(['data' => $data, 'message' => 'Health care history found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Health care history not found'], 400);
        }
    }

    static public function GetAllAssistanceOrganization(Request $request)
    {
        error_log('in controller');
        $assistanceTypeId = $request->get('assistanceTypeId');

        $finalData = array();

        $assistanceList = CcmModel::getAllAssistanceOrganizationViaAssistanceType($assistanceTypeId);

        if (count($assistanceList) > 0) {
            error_log('Assistance organization list found ');

            foreach ($assistanceList as $item) {
                error_log('in for each loop');

                $data = array(
                    'Id' => $item->Id,
                    'Organization' => $item->Organization,
                    'TelephoneNumber' => $item->TelephoneNumber,
                    'OfficeAddress' => $item->OfficeAddress,
                    'ContactPerson' => $item->ContactPerson,
                    'Description' => $item->Description
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Assistance organization found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Assistance organization not found'], 400);
        }
    }

    static public function GetAllAssistanceType()
    {
        error_log('in controller');

        $assistanceList = CcmModel::getAllAssistanceType();
        $finalData = array();

        if (count($assistanceList) > 0) {
            error_log('Assistance list found ');

            foreach ($assistanceList as $item) {
                error_log('in for each loop');

                $data = array(
                    'Id' => $item->Id,
                    'Type' => $item->Type,
                    'Description' => $item->Description
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Assistance type not found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Assistance type found'], 400);
        }
    }

    static public function AddPatientOrganizationAssistance(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

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

        $date = HelperModel::getDate();
        $dataToInsert = array();

        foreach ($request->input('PatientOrganization') as $item) {

            $data = array(
                'PatientId' => $patientId,
                'AssistanceOrganizationId' => (int)$item['AssistanceOrganizationId'],
                'Organization' => $item['Organization'],
                'TelephoneNumber' => $item['TelephoneNumber'],
                'OfficeAddress' => $item['OfficeAddress'],
                'ContactPerson' => $item['ContactPerson'],
                'Description' => $item['Description'],
                'IsPatientRefused' => $item['IsPatientRefused'],
                'IsActive' => true,
                'CreatedBy' => $userId,
                'CreatedOn' => $date["timestamp"]
            );

            array_push($dataToInsert, $data);
        }

        $insertedData = GenericModel::insertGeneric('patient_organization_assistance', $dataToInsert);
        if ($insertedData == false) {
            error_log('data not inserted');
            return response()->json(['data' => null, 'message' => 'Error in inserting patient organization assistance'], 400);
        } else {
            error_log('data inserted');
            return response()->json(['data' => (int)$userId, 'message' => 'Patient organization assistance successfully added'], 200);
        }
    }

    static public function UpdatePatientOrganizationAssistance(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientOrganizationAssistanceId = $request->get('id');

        //First checking if answer exists or not

        $date = HelperModel::getDate();

        $checkData = CcmModel::getSinglePatientOrganizationAssistance($patientOrganizationAssistanceId);

        if ($checkData == null) {
            error_log(' data not found');
            return response()->json(['data' => null, 'message' => 'Patient organization not found'], 400);
        } else {
            error_log('data found');

            $dataToUpdate = array(
                'Organization' => $request->get('Organization'),
                'TelephoneNumber' => $request->get('TelephoneNumber'),
                'OfficeAddress' => $request->get('OfficeAddress'),
                'ContactPerson' => $request->get('ContactPerson'),
                'Description' => $request->get('Description'),
                'IsPatientRefused' => $request->get('IsPatientRefused'),
                'UpdatedBy' => $userId,
                'UpdatedOn' => $date["timestamp"]
            );

            $updatedData = GenericModel::updateGeneric('patient_organization_assistance', 'Id', $patientOrganizationAssistanceId, $dataToUpdate);

            if ($updatedData == false) {
                error_log('data not updated');
                return response()->json(['data' => null, 'message' => 'Error in updating patient organization assistance'], 400);
            } else {
                error_log('data updated');
                return response()->json(['data' => (int)$userId, 'message' => 'Patient organization assistance successfully updated'], 200);
            }
        }
    }

    static public function GetAllPatientOrganizationAssistance(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $patientId = $request->get('patientId');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        //First check if logged in user belongs to facilitator
        //if it is facilitator then check it's doctor association
        //And then check if that patient is associated with dr or not

        $finalData = array();

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

        //Get all active medicine via patient id
        $medicineList = CcmModel::getAllPatientOrganizationAssistanceViaPatientId($patientId);
        if (count($medicineList) > 0) {
            error_log('Patient organization assistance list found ');

            foreach ($medicineList as $item) {
                error_log('in for each loop');

                $data = array(
                    'Id' => $item->poaID,
                    'Organization' => $item->poaOrganization,
                    'TelephoneNumber' => $item->poaTelephoneNumber,
                    'OfficeAddress' => $item->poaOfficeAddress,
                    'ContactPerson' => $item->poaContactPerson,
                    'Description' => $item->poaDescription,
                    'IsPatientRefused' => $item->poaIsPatientRefused,
                    'AssistanceOrganization' => array(
                        'AssistanceType' => array()
                    )
                );

//                Assistance organization data
                $data['AssistanceOrganization']['Id'] = $item->aoId;
                $data['AssistanceOrganization']['Organization'] = $item->aoOrganization;
                $data['AssistanceOrganization']['OfficeAddress'] = $item->aoOfficeAddress;
                $data['AssistanceOrganization']['ContactPerson'] = $item->aoContactPerson;
                $data['AssistanceOrganization']['Description'] = $item->aoDescription;

                //Assistance organization type data

                $data['AssistanceOrganization']['AssistanceType']['Id'] = $item->atId;
                $data['AssistanceOrganization']['AssistanceType']['Type'] = $item->atType;
                $data['AssistanceOrganization']['AssistanceType']['Organization'] = $item->atOrganization;


                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Health care history not found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Health care history found'], 400);
        }
    }

    static public function GetSinglePatientOrganizationAssistance(Request $request)
    {
        error_log('in controller');

        $patientOrganizationAssistanceId = $request->get('id');

        //Get single active medicine via medicine id
        $patientOrganizationData = CcmModel::getSinglePatientOrganizationAssistance($patientOrganizationAssistanceId);

        if ($patientOrganizationData != null) {
            error_log('patient organization assistance found ');

            $data['Id'] = $patientOrganizationData->poaID;
            $data['Organization'] = $patientOrganizationData->poaOrganization;
            $data['TelephoneNumber'] = $patientOrganizationData->poaTelephoneNumber;
            $data['OfficeAddress'] = $patientOrganizationData->poaOfficeAddress;
            $data['ContactPerson'] = $patientOrganizationData->poaContactPerson;
            $data['Description'] = $patientOrganizationData->poaDescription;
            $data['IsPatientRefused'] = $patientOrganizationData->poaIsPatientRefused;
            $data['AssistanceOrganization'] = array();

            //Assistance organization data
            $data['AssistanceOrganization']['Id'] = $patientOrganizationData->aoId;
            $data['AssistanceOrganization']['Organization'] = $patientOrganizationData->aoOrganization;
            $data['AssistanceOrganization']['OfficeAddress'] = $patientOrganizationData->aoOfficeAddress;
            $data['AssistanceOrganization']['ContactPerson'] = $patientOrganizationData->aoContactPerson;
            $data['AssistanceOrganization']['Description'] = $patientOrganizationData->aoDescription;
            $data['AssistanceOrganization']['AssistanceType'] = array();

            //Assistance organization type data

            $data['AssistanceOrganization']['AssistanceType']['Id'] = $patientOrganizationData->atId;
            $data['AssistanceOrganization']['AssistanceType']['Type'] = $patientOrganizationData->atType;
            $data['AssistanceOrganization']['AssistanceType']['Organization'] = $patientOrganizationData->atOrganization;

            return response()->json(['data' => $data, 'message' => 'Patient organization assistance found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Patient organization assistance not found'], 400);
        }
    }
}
