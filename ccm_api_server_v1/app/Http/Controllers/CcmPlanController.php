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

        $data = array(
            'CcmQuestionId' => $request->get('CcmQuestionId'),
            'AskById' => $userId,
            'PatientId' => $patientId,
            'IsAnswered' => $request->get('IsAnswered'),
            'Answer' => $request->get('Answer'),
            'IsActive' => true,
            'CreatedBy' => $userId,
            'CreatedOn' => $date["timestamp"]
        );

        $insertedData = GenericModel::insertGeneric('ccm_answer', $data);
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
                    'Answer' => array()
                );

                //Now one by one we will fetch answers and will bind it in Answers array
                $answerList = CcmModel::getAnswersViaQuestionIdAndPatientId($item->Id, $patientId);
                if ($answerList != null) {
                    error_log('answer found for question id : ' . $item->Id);
                    error_log('in for each loop');

                    $questionData['Answer']['Id'] = $answerList->Id;
                    $questionData['Answer']['IsAnswered'] = $answerList->IsAnswered;
                    $questionData['Answer']['Answer'] = $answerList->Answer;

                } else {
                    $questionData['Answer'] = null;
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
            if ($item['Id'] == null || $item['Id'] == 0) {
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
        }

        error_log('data count is : ' . count($activeMedicineData));

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
                'IsActive' => (bool)$request->get('IsActive'),
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
                    'StartDate' => $item->StartDate,
                    'StartBy' => $item->StartBy,
                    'WhyComments' => $item->WhyComments
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Active medicine found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Active medicine not found'], 200);
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
            $data['StartDate'] = $medicineData->StartDate;
            $data['StartBy'] = $medicineData->StartBy;
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
            if ($item['Id'] == null || $item['Id'] == 0) {
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
                'IsActive' => (bool)$request->get('IsActive'),
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
                    'ReactionDate' => $item->ReactionDate,
                    'IsReactionSevere' => $item->IsReactionSevere
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Allergy medicine found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Allergy medicine not found'], 200);
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
            $data['ReactionDate'] = $medicineData->ReactionDate;
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
            if ($item['Id'] == null || $item['Id'] == 0) {
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
                'IsActive' => (bool)$request->get('IsActive'),
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
                    'ReactionDate' => $item->ReactionDate,
                    'IsReactionSevere' => $item->IsReactionSevere
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Non medicine found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Non medicine not found'], 200);
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
            $data['ReactionDate'] = $medicineData->ReactionDate;
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
            if ($item['Id'] == null || $item['Id'] == 0) {
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
                'IsActive' => (bool)$request->get('IsActive'),
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

    static public function GetAllImmunizationVaccine(Request $request)
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
                    'VaccineDate' => $item->VaccineDate
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Immunization vaccine found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Immunization vaccine not found'], 200);
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
            $data['VaccineDate'] = $medicineData->VaccineDate;

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
            if ($item['Id'] == null || $item['Id'] == 0) {
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
                'IsActive' => (bool)$request->get('IsActive'),
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
                    'LastVisitDate' => $item->LastVisitDate,
                    'VisitReason' => $item->VisitReason
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Health care history found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Health care history not found'], 200);
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
            $data['LastVisitDate'] = $medicineData->LastVisitDate;
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
        $assistanceOrganizationData = array();

        if (count($assistanceList) > 0) {
            error_log('Assistance list found ');

            foreach ($assistanceList as $item) {
                error_log('in for each loop');

                $data = array(
                    'Id' => $item->Id,
                    'Type' => $item->Type,
                    'Description' => $item->Description,
                    'AssistanceOrganization' => array()
                );

                $assistanceOrganizationList = CcmModel::getAllAssistanceOrganizationViaAssistanceType($item->Id);

                if (count($assistanceOrganizationList) > 0) {
                    error_log('Assistance organization list found ');

                    foreach ($assistanceOrganizationList as $item2) {
                        error_log('in for each loop of assistance organization');

                        $data1 = array(
                            'Id' => $item2->Id,
                            'Organization' => $item2->Organization,
                            'TelephoneNumber' => $item2->TelephoneNumber,
                            'OfficeAddress' => $item2->OfficeAddress,
                            'ContactPerson' => $item2->ContactPerson,
                            'Description' => $item2->Description
                        );

                        array_push($data['AssistanceOrganization'], $data1);
                    }
                }

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Assistance type found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Assistance type not found'], 200);
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
            if ($item['Id'] == null || $item['Id'] == 0) {
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
                'IsActive' => (bool)$request->get('IsActive'),
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
            return response()->json(['data' => $finalData, 'message' => 'Health care history found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Health care history not found'], 200);
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

    static public function AddHospitalizationHistory(Request $request)
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


        foreach ($request->input('HospitalizationHistory') as $item) {
            if ($item['Id'] == null || $item['Id'] == 0) {
                $data = array(
                    'PatientId' => $patientId,
                    'IsHospitalized' => $item['IsHospitalized'],
                    'HospitalizedDate' => $item['HospitalizedDate'],
                    'HospitalName' => $item['HospitalName'],
                    'PatientComments' => $item['PatientComments'],
                    'IsActive' => true,
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );

                array_push($dataToInsert, $data);
            }
        }

        $insertedData = GenericModel::insertGeneric('ccm_hospitalization_history', $dataToInsert);
        if ($insertedData == false) {
            error_log('data not inserted');
            return response()->json(['data' => null, 'message' => 'Error in inserting hospitalization history'], 400);
        } else {
            error_log('data inserted');
            return response()->json(['data' => (int)$userId, 'message' => 'Hospitalization history successfully added'], 200);
        }
    }

    static public function UpdateHospitalizationHistory(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $hospitalizationHistoryId = $request->get('id');

        //First checking if answer exists or not

        $date = HelperModel::getDate();

        $checkData = CcmModel::getSingleHospitalizationHistory($hospitalizationHistoryId);

        if ($checkData == null) {
            error_log(' medicine not found');
            return response()->json(['data' => null, 'message' => 'Hospitalization history not found'], 400);
        } else {
            error_log('Answer found');

            $dataToUpdate = array(
                'IsHospitalized' => $request->get('IsHospitalized'),
                'HospitalizedDate' => $request->get('HospitalizedDate'),
                'HospitalName' => $request->get('HospitalName'),
                'PatientComments' => $request->get('PatientComments'),
                'IsActive' => (bool)$request->get('IsActive'),
                'UpdatedBy' => $userId,
                'UpdatedOn' => $date["timestamp"]
            );

            $updatedData = GenericModel::updateGeneric('ccm_hospitalization_history', 'Id', $hospitalizationHistoryId, $dataToUpdate);

            if ($updatedData == false) {
                error_log('data not updated');
                return response()->json(['data' => null, 'message' => 'Error in updating hospitalization history'], 400);
            } else {
                error_log('data updated');
                return response()->json(['data' => (int)$userId, 'message' => 'Hospitalization history successfully updated'], 200);
            }
        }
    }

    static public function GetAllHospitalizationHistory(Request $request)
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
        $medicineList = CcmModel::getAllHospitalizationHistoryViaPatientId($patientId);
        if (count($medicineList) > 0) {
            error_log('medicine list found ');

            foreach ($medicineList as $item) {
                error_log('in for each loop');

                $data = array(
                    'Id' => $item->Id,
                    'IsHospitalized' => $item->IsHospitalized,
                    'HospitalizedDate' => Carbon::createFromTimestamp($item->HospitalizedDate),
                    'HospitalName' => $item->HospitalName,
                    'PatientComments' => $item->PatientComments
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Hospitalization history found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Hospitalization history not found'], 200);
        }
    }

    static public function GetSingleHospitalizationHistory(Request $request)
    {
        error_log('in controller');

        $hospitalizationHistoryId = $request->get('id');

        //Get single active medicine via medicine id
        $medicineData = CcmModel::getSingleHospitalizationHistory($hospitalizationHistoryId);
        if ($medicineData != null) {
            error_log('data found ');

            $data['Id'] = $medicineData->Id;
            $data['IsHospitalized'] = $medicineData->IsHospitalized;
            $data['HospitalName'] = $medicineData->HospitalName;
            $data['HospitalizedDate'] = Carbon::createFromTimestamp($medicineData->HospitalizedDate);
            $data['PatientComments'] = $medicineData->PatientComments;

            return response()->json(['data' => $data, 'message' => 'Hospitalization history found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Hospitalization history not found'], 400);
        }
    }

    static public function AddSurgeryHistory(Request $request)
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


        foreach ($request->input('SurgeryHistory') as $item) {
            if ($item['Id'] == null || $item['Id'] == 0) {
                $data = array(
                    'PatientId' => $patientId,
                    'DiagnoseDescription' => $item['DiagnoseDescription'],
                    'DiagnoseDate' => $item['DiagnoseDate'],
                    'CurrentProblem' => $item['CurrentProblem'],
                    'NeedAttention' => $item['NeedAttention'],
                    'IsActive' => true,
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );

                array_push($dataToInsert, $data);
            }
        }

        $insertedData = GenericModel::insertGeneric('ccm_surgery_history', $dataToInsert);
        if ($insertedData == false) {
            error_log('data not inserted');
            return response()->json(['data' => null, 'message' => 'Error in inserting surgery history'], 400);
        } else {
            error_log('data inserted');
            return response()->json(['data' => (int)$userId, 'message' => 'Hospitalization surgery successfully added'], 200);
        }
    }

    static public function UpdateSurgeryHistory(Request $request)
    {
        error_log('in controller');

        $userId = $request->get('userId');
        $surgeryHistoryId = $request->get('id');

        //First checking if answer exists or not

        $date = HelperModel::getDate();

        $checkData = CcmModel::getSingleSurgeryHistory($surgeryHistoryId);

        if ($checkData == null) {
            error_log(' surgery found');
            return response()->json(['data' => null, 'message' => 'Surgery not found'], 400);
        } else {
            error_log('surgery found');

            $dataToUpdate = array(
                'DiagnoseDescription' => $request->get('DiagnoseDescription'),
                'DiagnoseDate' => $request->get('DiagnoseDate'),
                'CurrentProblem' => $request->get('CurrentProblem'),
                'NeedAttention' => $request->get('NeedAttention'),
                'IsActive' => (bool)$request->get('IsActive'),
                'UpdatedBy' => $userId,
                'UpdatedOn' => $date["timestamp"]
            );

            $updatedData = GenericModel::updateGeneric('ccm_surgery_history', 'Id', $surgeryHistoryId, $dataToUpdate);

            if ($updatedData == false) {
                error_log('data not updated');
                return response()->json(['data' => null, 'message' => 'Error in updating surgery history'], 400);
            } else {
                error_log('data updated');
                return response()->json(['data' => (int)$userId, 'message' => 'Surgery history successfully updated'], 200);
            }
        }
    }

    static public function GetAllSurgeryHistory(Request $request)
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
        $medicineList = CcmModel::getAllSurgeryHistoryViaPatientId($patientId);
        if (count($medicineList) > 0) {
            error_log('surgery list found ');

            foreach ($medicineList as $item) {
                error_log('in for each loop');

                $data = array(
                    'Id' => $item->Id,
                    'DiagnoseDescription' => $item->DiagnoseDescription,
                    'DiagnoseDate' => Carbon::createFromTimestamp($item->DiagnoseDate),
                    'CurrentProblem' => $item->CurrentProblem,
                    'NeedAttention' => $item->NeedAttention
                );

                array_push($finalData, $data);
            }
        }

        if (count($finalData) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Surgery history found'], 200);
        } else {
            return response()->json(['data' => $finalData, 'message' => 'Surgery history not found'], 200);
        }
    }

    static public function GetSingleSurgeryHistory(Request $request)
    {
        error_log('in controller');

        $surgeryHistoryId = $request->get('id');

        //Get single active medicine via medicine id
        $medicineData = CcmModel::getSingleSurgeryHistory($surgeryHistoryId);
        if ($medicineData != null) {
            error_log('data found ');

            $data['Id'] = $medicineData->Id;
            $data['DiagnoseDescription'] = $medicineData->DiagnoseDescription;
            $data['DiagnoseDate'] = Carbon::createFromTimestamp($medicineData->DiagnoseDate);
            $data['CurrentProblem'] = $medicineData->CurrentProblem;
            $data['NeedAttention'] = $medicineData->NeedAttention;

            return response()->json(['data' => $data, 'message' => 'Surgery history found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Surgery history not found'], 400);
        }
    }

    static public function GetPatientGeneralInformation(Request $request)
    {
        error_log('in controller');

        $id = $request->get('patientId');

        //Get single active medicine via medicine id
        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($id);
        if ($checkUserData != null) {
            error_log('data found ');

            $data['Id'] = $checkUserData->Id;
            $data['FirstName'] = $checkUserData->FirstName;
            $data['LastName'] = $checkUserData->LastName;
            $data['MiddleName'] = $checkUserData->MiddleName;
            $data['PatientUniqueId'] = $checkUserData->PatientUniqueId;
            $data['EmailAddress'] = $checkUserData->EmailAddress;
            $data['MobileNumber'] = $checkUserData->MobileNumber;
            $data['TelephoneNumber'] = $checkUserData->TelephoneNumber;
            $data['OfficeAddress'] = $checkUserData->OfficeAddress;
            $data['ResidentialAddress'] = $checkUserData->ResidentialAddress;
            $data['Gender'] = $checkUserData->Gender;
            $data['FunctionalTitle'] = $checkUserData->FunctionalTitle;
            $data['Age'] = $checkUserData->Age;
            $data['AgeGroup'] = $checkUserData->AgeGroup;

            return response()->json(['data' => $data, 'message' => 'Patient general information found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Patient general information not found'], 400);
        }
    }

    function UpdatePatientGeneralInfo(Request $request)
    {
        $id = $request->get('patientId');

        //First get and check if record exists or not
        $data = UserModel::GetSingleUserViaIdNewFunction($id);

        if ($data == null) {
            return response()->json(['data' => null, 'message' => 'User not found'], 400);
        }

        //Binding data to variable.

        $firstName = $request->post('FirstName');
        $middleName = $request->post('MiddleName');
        $lastName = $request->post('LastName');
        $mobileNumber = $request->post('MobileNumber');
        $telephoneNumber = $request->post('TelephoneNumber');
        $gender = $request->post('Gender');
        $age = $request->post('Age');

        $dataToUpdate = array(
            "FirstName" => $firstName,
            "MiddleName" => $middleName,
            "LastName" => $lastName,
            "MobileNumber" => $mobileNumber,
            "TelephoneNumber" => $telephoneNumber,
            "Gender" => $gender,
            "Age" => $age
        );

        $update = GenericModel::updateGeneric('user', 'Id', $id, $dataToUpdate);

        if ($update == true) {
            DB::commit();
            return response()->json(['data' => $id, 'message' => 'User successfully updated'], 200);
        } else {
            DB::rollBack();
            return response()->json(['data' => null, 'message' => 'Error in updating user record'], 400);
        }
    }

    static public function GetAllPsychologicalReviewParam()
    {
        error_log('in controller');

        //Get all active medicine via patient id
        $dataList = GenericModel::simpleFetchGenericAll('psychological_review_param');

        $finalData = array();

        if (count($dataList) > 0) {
            foreach ($dataList as $item) {
                $data = array(
                    'Id' => $item->Id,
                    'Name' => $item->Name,
                    'Description' => $item->Description
                );

                array_push($finalData, $data);
            }
        }

        if (count($dataList) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Psychological reviews found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Psychological reviews not found'], 200);
        }
    }

    static public function GetAllFunctionalReviewParam()
    {
        error_log('in controller');

        //Get all active medicine via patient id
        $dataList = GenericModel::simpleFetchGenericAll('functional_review_param');

        $finalData = array();

        if (count($dataList) > 0) {
            foreach ($dataList as $item) {
                $data = array(
                    'Id' => $item->Id,
                    'Name' => $item->Name,
                    'Description' => $item->Description
                );

                array_push($finalData, $data);
            }
        }

        if (count($dataList) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Functional reviews found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Functional reviews not found'], 200);
        }
    }

    static public function GetAllSocialReviewParam()
    {
        error_log('in controller');

        //Get all active medicine via patient id
        $dataList = GenericModel::simpleFetchGenericAll('social_review_param');

        $finalData = array();

        if (count($dataList) > 0) {
            foreach ($dataList as $item) {
                $data = array(
                    'Id' => $item->Id,
                    'Name' => $item->Name,
                    'Description' => $item->Description
                );

                array_push($finalData, $data);
            }
        }

        if (count($dataList) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Social reviews found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Social reviews not found'], 200);
        }
    }

    static public function GetAllPreventativeScreenExamParam()
    {
        error_log('in controller');

        //Get all active medicine via patient id
        $dataList = GenericModel::simpleFetchGenericAll('prevent_screening_examination_param');

        $finalData = array();

        if (count($dataList) > 0) {
            foreach ($dataList as $item) {
                $data = array(
                    'Id' => $item->Id,
                    'Name' => $item->Name,
                    'Description' => $item->Description
                );

                array_push($finalData, $data);
            }
        }

        if (count($dataList) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Preventative screen examination found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Preventative screen examination not found'], 200);
        }
    }


    static public function GetAllDiabeticMeasureParam()
    {
        error_log('in controller');

        //Get all active medicine via patient id
        $dataList = GenericModel::simpleFetchGenericAll('diabetic_measure_param');

        $finalData = array();

        if (count($dataList) > 0) {
            foreach ($dataList as $item) {
                $data = array(
                    'Id' => $item->Id,
                    'Name' => $item->Name,
                    'Description' => $item->Description
                );

                array_push($finalData, $data);
            }
        }

        if (count($dataList) > 0) {
            return response()->json(['data' => $finalData, 'message' => 'Diabetic measures found'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Diabetic measures not found'], 200);
        }
    }

    function GetInsuranceType()
    {
        error_log('in controller');

        $ticketPriorities = TicketModel::getEnumValues('patient_assessment_insurance', 'InsuranceType');
        if ($ticketPriorities == null) {
            return response()->json(['data' => null, 'message' => 'Insurance type not found'], 200);
        } else {
            return response()->json(['data' => $ticketPriorities, 'message' => 'Insurance type found'], 200);
        }
    }

    function GetInsuranceCoverageType()
    {
        error_log('in controller');

        $ticketPriorities = TicketModel::getEnumValues('patient_assessment_insurance', 'CoverageType');
        if ($ticketPriorities == null) {
            return response()->json(['data' => null, 'message' => 'Insurance coverage type not found'], 200);
        } else {
            return response()->json(['data' => $ticketPriorities, 'message' => 'Insurance coverage type found'], 200);
        }
    }

    function GetPatientLiveType()
    {
        error_log('in controller');

        $ticketPriorities = TicketModel::getEnumValues('patient_assessment_self', 'LiveType');
        if ($ticketPriorities == null) {
            return response()->json(['data' => null, 'message' => 'Live type not found'], 200);
        } else {
            return response()->json(['data' => $ticketPriorities, 'message' => 'Live type found'], 200);
        }
    }

    function GetPatientChallengeType()
    {
        error_log('in controller');

        $ticketPriorities = TicketModel::getEnumValues('patient_assessment_self', 'ChallengeWith');
        if ($ticketPriorities == null) {
            return response()->json(['data' => null, 'message' => 'Challenge type not found'], 200);
        } else {
            return response()->json(['data' => $ticketPriorities, 'message' => 'Challenge type found'], 200);
        }
    }

    function GetPatientLearningType()
    {
        error_log('in controller');

        $ticketPriorities = TicketModel::getEnumValues('patient_assessment_self', 'LearnBestBy');
        if ($ticketPriorities == null) {
            return response()->json(['data' => null, 'message' => 'Learn type not found'], 200);
        } else {
            return response()->json(['data' => $ticketPriorities, 'message' => 'Learn type found'], 200);
        }
    }

    function GetPatientAssistanceAvailabilityType()
    {
        error_log('in controller');

        $ticketPriorities = TicketModel::getEnumValues('patient_assessment_self', 'AssistanceAvailable');
        if ($ticketPriorities == null) {
            return response()->json(['data' => null, 'message' => 'Assistance type not found'], 200);
        } else {
            return response()->json(['data' => $ticketPriorities, 'message' => 'Assistance type found'], 200);
        }
    }

    static public function SavePatientAssessment(Request $request)
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

        //First check if id is null or not
        //If id is null then insert
        //else check that record
        if ($request->get('Id') == "null" || $request->get('Id') == null) {
            error_log('Data id is null');
            error_log('Now checking if record is existing via patient id or not');

            $checkData = GenericModel::simpleFetchGenericById('patient_assessment', 'PatientId', $patientId);

            if ($checkData == null) {
                error_log('data not found, so INSERTING');

                $dataToAdd = array(
                    'PatientId' => $patientId,
                    'AbleToMessage' => (bool)$request->get('AbleToMessage'),
                    'AbleToCall' => (bool)$request->get('AbleToCall'),
                    'FeasibleMessageTime' => $request->get('FeasibleMessageTime'),
                    'FeasibleCallTime' => $request->get('FeasibleCallTime'),
                    'DayTimePhoneNumber' => $request->get('DayTimePhoneNumber'),
                    'NightTimePhoneNumber' => $request->get('NightTimePhoneNumber'),
                    'IsInternetAvailable' => (bool)$request->get('IsInternetAvailable'),
                    'IsInternetHelper' => (bool)$request->get('IsInternetHelper'),
                    'CanUseInternet' => (bool)$request->get('CanUseInternet'),
                    'WantToChange' => $request->get('WantToChange'),
                    'EffortToChange' => $request->get('EffortToChange'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'CanCallOnDayTimePhone' => (bool)$request->get('CanCallOnDayTimePhone'),
                    'CanMsgOnDayTimePhone' => (bool)$request->get('CanMsgOnDayTimePhone'),
                    'CanCallOnNightTimePhone' => (bool)$request->get('CanCallOnNightTimePhone'),
                    'CanMsgOnNightTimePhone' => (bool)$request->get('CanMsgOnNightTimePhone'),
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );
                $insertedData = GenericModel::insertGenericAndReturnID('patient_assessment', $dataToAdd);
                if ($insertedData == false) {
                    error_log('data not inserted');
                    return response()->json(['data' => null, 'message' => 'Error in inserting patient assessment'], 400);
                } else {
                    error_log('data inserted');
                    return response()->json(['data' => $insertedData, 'message' => 'Patient assessment successfully added'], 200);
                }
            } else {
                error_log('data found. But id is null so we cannot update');
                return response()->json(['data' => null, 'message' => 'Patient assessment cannot be updated because id is NULL'], 200);
            }
        } else {
            error_log('fetching single data');
            $checkData = GenericModel::simpleFetchGenericById('patient_assessment', 'Id', $request->get('Id'));
            if ($checkData == null) {
                error_log('data not found');
                return response()->json(['data' => null, 'message' => 'Patient assessment not found'], 400);
            } else {
                error_log('data found. Now update');

                $dataToUpdate = array(
                    'AbleToMessage' => (bool)$request->get('AbleToMessage'),
                    'AbleToCall' => (bool)$request->get('AbleToCall'),
                    'FeasibleMessageTime' => $request->get('FeasibleMessageTime'),
                    'FeasibleCallTime' => $request->get('FeasibleCallTime'),
                    'DayTimePhoneNumber' => $request->get('DayTimePhoneNumber'),
                    'NightTimePhoneNumber' => $request->get('NightTimePhoneNumber'),
                    'IsInternetAvailable' => (bool)$request->get('IsInternetAvailable'),
                    'IsInternetHelper' => (bool)$request->get('IsInternetHelper'),
                    'CanUseInternet' => (bool)$request->get('CanUseInternet'),
                    'WantToChange' => $request->get('WantToChange'),
                    'EffortToChange' => $request->get('EffortToChange'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'CanCallOnDayTimePhone' => (bool)$request->get('CanCallOnDayTimePhone'),
                    'CanMsgOnDayTimePhone' => (bool)$request->get('CanMsgOnDayTimePhone'),
                    'CanCallOnNightTimePhone' => (bool)$request->get('CanCallOnNightTimePhone'),
                    'CanMsgOnNightTimePhone' => (bool)$request->get('CanMsgOnNightTimePhone'),
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $date["timestamp"]
                );

                $updatedData = GenericModel::updateGeneric('patient_assessment', 'Id', (int)$request->get('Id'), $dataToUpdate);

                if ($updatedData == false) {
                    error_log('data not updated');
                    return response()->json(['data' => null, 'message' => 'Error in updating patient assessment'], 400);
                } else {
                    error_log('data updated');
                    return response()->json(['data' => (int)$request->get('Id'), 'message' => 'Patient assessment successfully updated'], 200);
                }
            }
        }
    }

    static public function GetPatientAssessment(Request $request)
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

        $checkData = GenericModel::simpleFetchGenericById('patient_assessment', 'PatientId', (int)$patientId);
        if ($checkData == null) {
            error_log('data not found');
            return response()->json(['data' => null, 'message' => 'Patient assessment not found'], 400);
        } else {
            error_log('data found. Now update');

            $data = array(
                'Id' => $checkData->Id,
                'AbleToMessage' => $checkData->AbleToMessage,
                'AbleToCall' => $checkData->AbleToCall,
                'FeasibleMessageTime' => $checkData->FeasibleMessageTime,
                'FeasibleCallTime' => $checkData->FeasibleCallTime,
                'DayTimePhoneNumber' => $checkData->DayTimePhoneNumber,
                'NightTimePhoneNumber' => $checkData->NightTimePhoneNumber,
                'IsInternetAvailable' => $checkData->IsInternetAvailable,
                'IsInternetHelper' => $checkData->IsInternetHelper,
                'CanUseInternet' => $checkData->CanUseInternet,
                'WantToChange' => $checkData->WantToChange,
                'EffortToChange' => $checkData->EffortToChange,
                'CanCallOnDayTimePhone' => $checkData->CanCallOnDayTimePhone,
                'CanMsgOnDayTimePhone' => $checkData->CanMsgOnDayTimePhone,
                'CanCallOnNightTimePhone' => $checkData->CanCallOnNightTimePhone,
                'CanMsgOnNightTimePhone' => $checkData->CanMsgOnNightTimePhone
            );

            return response()->json(['data' => $data, 'message' => 'Patient assessment found'], 200);
        }
    }

    static public function SavePatientAssessmentAbilityConcern(Request $request)
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

        //First check if id is null or not
        //If id is null then insert
        //else check that record
        if ($request->get('Id') == "null" || $request->get('Id') == null) {
            error_log('Data id is null');
            error_log('Now checking if record is existing via patient id or not');

            $checkData = GenericModel::simpleFetchGenericById('patient_assessment_ability_concern', 'PatientId', $patientId);

            if ($checkData == null) {
                error_log('data not found, so INSERTING');

                $dataToAdd = array(
                    'PatientId' => $patientId,
                    'ManageChronicCondition' => (bool)$request->get('ManageChronicCondition'),
                    'ManageChronicConditionComment' => $request->get('ManageChronicConditionComment'),
                    'DecreaseEnergyLevel' => (bool)$request->get('DecreaseEnergyLevel'),
                    'DecreaseEnergyLevelComment' => $request->get('DecreaseEnergyLevelComment'),
                    'CanCleanHome' => (bool)$request->get('CanCleanHome'),
                    'CanCleanHomeComment' => $request->get('CanCleanHomeComment'),
                    'EmotionalCurrentIssue' => (bool)$request->get('EmotionalCurrentIssue'),
                    'EmotionalCurrentIssueComment' => $request->get('EmotionalCurrentIssueComment'),
                    'ManageMedication' => (bool)$request->get('ManageMedication'),
                    'ManageMedicationComment' => $request->get('ManageMedicationComment'),
                    'ObtainHealthyFood' => (bool)$request->get('ObtainHealthyFood'),
                    'ObtainHealthyFoodComment' => $request->get('ObtainHealthyFoodComment'),
                    'CopeLifeIssue' => (bool)$request->get('CopeLifeIssue'),
                    'CopeLifeIssueComment' => $request->get('CopeLifeIssueComment'),
                    'IsCurrentlyDnr' => (bool)$request->get('IsCurrentlyDnr'),
                    'CurrentlyDnrComment' => $request->get('CurrentlyDnrComment'),
                    'IsCurrentlyPoa' => (bool)$request->get('IsCurrentlyPoa'),
                    'CurrentlyPoaComment' => $request->get('CurrentlyPoaComment'),
                    'IsCurrentlyDirective' => (bool)$request->get('IsCurrentlyDirective'),
                    'CurrentlyDirectiveComment' => $request->get('CurrentlyDirectiveComment'),
                    'IsAbleToMoveDaily' => (bool)$request->get('IsAbleToMoveDaily'),
                    'AbleToMoveDailyComment' => $request->get('AbleToMoveDailyComment'),
                    'ConcernDetailComment' => $request->get('ConcernDetailComment'),
                    'IsActive' => $request->get('IsActive'),
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );
                $insertedData = GenericModel::insertGenericAndReturnID('patient_assessment_ability_concern', $dataToAdd);
                if ($insertedData == false) {
                    error_log('data not inserted');
                    return response()->json(['data' => null, 'message' => 'Error in inserting patient assessment ability concern'], 400);
                } else {
                    error_log('data inserted');
                    return response()->json(['data' => $insertedData, 'message' => 'Patient assessment ability concern successfully added'], 200);
                }
            } else {
                error_log('data found. But id is null so we cannot update');
                return response()->json(['data' => null, 'message' => 'Patient assessment ability concern cannot be updated because id is NULL'], 200);
            }
        } else {
            error_log('fetching single data');
            $checkData = GenericModel::simpleFetchGenericById('patient_assessment_ability_concern', 'Id', $request->get('Id'));
            if ($checkData == null) {
                error_log('data not found');
                return response()->json(['data' => null, 'message' => 'Patient assessment ability not found'], 400);
            } else {
                error_log('data found. Now update');

                $dataToUpdate = array(
                    'ManageChronicCondition' => (bool)$request->get('ManageChronicCondition'),
                    'ManageChronicConditionComment' => $request->get('ManageChronicConditionComment'),
                    'DecreaseEnergyLevel' => (bool)$request->get('DecreaseEnergyLevel'),
                    'DecreaseEnergyLevelComment' => $request->get('DecreaseEnergyLevelComment'),
                    'CanCleanHome' => (bool)$request->get('CanCleanHome'),
                    'CanCleanHomeComment' => $request->get('CanCleanHomeComment'),
                    'EmotionalCurrentIssue' => (bool)$request->get('EmotionalCurrentIssue'),
                    'EmotionalCurrentIssueComment' => $request->get('EmotionalCurrentIssueComment'),
                    'ManageMedication' => (bool)$request->get('ManageMedication'),
                    'ManageMedicationComment' => $request->get('ManageMedicationComment'),
                    'ObtainHealthyFood' => (bool)$request->get('ObtainHealthyFood'),
                    'ObtainHealthyFoodComment' => $request->get('ObtainHealthyFoodComment'),
                    'CopeLifeIssue' => (bool)$request->get('CopeLifeIssue'),
                    'CopeLifeIssueComment' => $request->get('CopeLifeIssueComment'),
                    'IsCurrentlyDnr' => (bool)$request->get('IsCurrentlyDnr'),
                    'CurrentlyDnrComment' => $request->get('CurrentlyDnrComment'),
                    'IsCurrentlyPoa' => (bool)$request->get('IsCurrentlyPoa'),
                    'CurrentlyPoaComment' => $request->get('CurrentlyPoaComment'),
                    'IsCurrentlyDirective' => (bool)$request->get('IsCurrentlyDirective'),
                    'CurrentlyDirectiveComment' => $request->get('CurrentlyDirectiveComment'),
                    'IsAbleToMoveDaily' => (bool)$request->get('IsAbleToMoveDaily'),
                    'AbleToMoveDailyComment' => $request->get('AbleToMoveDailyComment'),
                    'ConcernDetailComment' => $request->get('ConcernDetailComment'),
                    'IsActive' => $request->get('IsActive'),
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $date["timestamp"]
                );

                $updatedData = GenericModel::updateGeneric('patient_assessment_ability_concern', 'Id', (int)$request->get('Id'), $dataToUpdate);

                if ($updatedData == false) {
                    error_log('data not updated');
                    return response()->json(['data' => null, 'message' => 'Error in updating patient assessment ability concern'], 400);
                } else {
                    error_log('data updated');
                    return response()->json(['data' => (int)$request->get('Id'), 'message' => 'Patient assessment ability concern successfully updated'], 200);
                }
            }
        }
    }

    static public function GetPatientAssessmentAbilityConcern(Request $request)
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

        $checkData = GenericModel::simpleFetchGenericById('patient_assessment_ability_concern', 'PatientId', (int)$patientId);

        if ($checkData == null) {
            error_log('data not found');
            return response()->json(['data' => null, 'message' => 'Patient assessment ability concern not found'], 400);
        } else {
            error_log('data found. Now update');

            $data = array(
                'Id' => $checkData->Id,
                'ManageChronicCondition' => $checkData->ManageChronicCondition,
                'ManageChronicConditionComment' => $checkData->ManageChronicConditionComment,
                'DecreaseEnergyLevel' => $checkData->DecreaseEnergyLevel,
                'DecreaseEnergyLevelComment' => $checkData->DecreaseEnergyLevelComment,
                'CanCleanHome' => $checkData->CanCleanHome,
                'CanCleanHomeComment' => $checkData->CanCleanHomeComment,
                'EmotionalCurrentIssue' => $checkData->EmotionalCurrentIssue,
                'EmotionalCurrentIssueComment' => $checkData->EmotionalCurrentIssueComment,
                'ManageMedication' => $checkData->ManageMedication,
                'ManageMedicationComment' => $checkData->ManageMedicationComment,
                'ObtainHealthyFood' => $checkData->ObtainHealthyFood,
                'ObtainHealthyFoodComment' => $checkData->ObtainHealthyFoodComment,
                'CopeLifeIssue' => $checkData->CopeLifeIssue,
                'CopeLifeIssueComment' => $checkData->CopeLifeIssueComment,
                'IsCurrentlyDnr' => $checkData->IsCurrentlyDnr,
                'CurrentlyDnrComment' => $checkData->CurrentlyDnrComment,
                'IsCurrentlyPoa' => $checkData->IsCurrentlyPoa,
                'CurrentlyPoaComment' => $checkData->CurrentlyPoaComment,
                'IsCurrentlyDirective' => $checkData->IsCurrentlyDirective,
                'CurrentlyDirectiveComment' => $checkData->CurrentlyDirectiveComment,
                'IsAbleToMoveDaily' => $checkData->IsAbleToMoveDaily,
                'AbleToMoveDailyComment' => $checkData->AbleToMoveDailyComment,
                'ConcernDetailComment' => $checkData->ConcernDetailComment
            );

            return response()->json(['data' => $data, 'message' => 'Patient assessment ability concern found'], 200);
        }
    }

    static public function SavePatientAssessmentAlternateContact(Request $request)
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

        //First check if id is null or not
        //If id is null then insert
        //else check that record
        if ($request->get('Id') == "null" || $request->get('Id') == null) {
            error_log('Data id is null');
            error_log('Now checking if record is existing via patient id or not');

            $checkData = GenericModel::simpleFetchGenericById('patient_assessment_alternate_contact', 'PatientId', $patientId);

            if ($checkData == null) {
                error_log('data not found, so INSERTING');

                $dataToAdd = array(
                    'PatientId' => $patientId,
                    'CareGiverName' => $request->get('CareGiverName'),
                    'CareGiverPhoneNumber' => $request->get('CareGiverPhoneNumber'),
                    'EmergencyContactName' => $request->get('EmergencyContactName'),
                    'EmergencyContactPhoneNumber' => $request->get('EmergencyContactPhoneNumber'),
                    'FinancerName' => $request->get('FinancerName'),
                    'FinancerPhoneNumber' => $request->get('FinancerPhoneNumber'),
                    'HealthCarerName' => $request->get('HealthCarerName'),
                    'HealthCarerPhoneNumber' => $request->get('HealthCarerPhoneNumber'),
                    'Comment' => $request->get('Comment'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );
                $insertedData = GenericModel::insertGenericAndReturnID('patient_assessment_alternate_contact', $dataToAdd);
                if ($insertedData == false) {
                    error_log('data not inserted');
                    return response()->json(['data' => null, 'message' => 'Error in inserting patient assessment alternate contact'], 400);
                } else {
                    error_log('data inserted');
                    return response()->json(['data' => $insertedData, 'message' => 'Patient assessment alternate contact successfully added'], 200);
                }
            } else {
                error_log('data found. But id is null so we cannot update');
                return response()->json(['data' => null, 'message' => 'Patient assessment alternate contact cannot be updated because id is NULL'], 200);
            }
        } else {
            error_log('fetching single data');
            $checkData = GenericModel::simpleFetchGenericById('patient_assessment_alternate_contact', 'Id', $request->get('Id'));
            if ($checkData == null) {
                error_log('data not found');
                return response()->json(['data' => null, 'message' => 'Patient assessment alternate contact not found'], 400);
            } else {
                error_log('data found. Now update');

                $dataToUpdate = array(
                    'CareGiverName' => $request->get('CareGiverName'),
                    'CareGiverPhoneNumber' => $request->get('CareGiverPhoneNumber'),
                    'EmergencyContactName' => $request->get('EmergencyContactName'),
                    'EmergencyContactPhoneNumber' => $request->get('EmergencyContactPhoneNumber'),
                    'FinancerName' => $request->get('FinancerName'),
                    'FinancerPhoneNumber' => $request->get('FinancerPhoneNumber'),
                    'HealthCarerName' => $request->get('HealthCarerName'),
                    'HealthCarerPhoneNumber' => $request->get('HealthCarerPhoneNumber'),
                    'Comment' => $request->get('Comment'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $date["timestamp"]
                );

                $updatedData = GenericModel::updateGeneric('patient_assessment_alternate_contact', 'Id', (int)$request->get('Id'), $dataToUpdate);

                if ($updatedData == false) {
                    error_log('data not updated');
                    return response()->json(['data' => null, 'message' => 'Error in updating patient assessment alternate contact'], 400);
                } else {
                    error_log('data updated');
                    return response()->json(['data' => (int)$request->get('Id'), 'message' => 'Patient assessment alternate contact successfully updated'], 200);
                }
            }
        }
    }

    static public function GetPatientAssessmentAlternateContact(Request $request)
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

        $checkData = GenericModel::simpleFetchGenericById('patient_assessment_alternate_contact', 'PatientId', $patientId);

        if ($checkData == null) {
            error_log('data not found');
            return response()->json(['data' => null, 'message' => 'Patient assessment alternate contact not found'], 400);
        } else {
            error_log('data found. Now update');

            $data = array(
                'Id' => $checkData->Id,
                'CareGiverName' => $checkData->CareGiverName,
                'CareGiverPhoneNumber' => $checkData->CareGiverPhoneNumber,
                'EmergencyContactName' => $checkData->EmergencyContactName,
                'EmergencyContactPhoneNumber' => $checkData->EmergencyContactPhoneNumber,
                'FinancerName' => $checkData->FinancerName,
                'FinancerPhoneNumber' => $checkData->FinancerPhoneNumber,
                'HealthCarerName' => $checkData->HealthCarerName,
                'HealthCarerPhoneNumber' => $checkData->HealthCarerPhoneNumber,
                'Comment' => $checkData->Comment
            );

            return response()->json(['data' => $data, 'message' => 'Patient assessment alternate contact found'], 200);
        }
    }

    static public function SavePatientAssessmentInsurance(Request $request)
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

        //First check if id is null or not
        //If id is null then insert
        //else check that record
        if ($request->get('Id') == "null" || $request->get('Id') == null) {
            error_log('Data id is null');
            error_log('Now checking if record is existing via patient id or not');

            $checkData = GenericModel::simpleFetchGenericById('patient_assessment_insurance', 'PatientId', $patientId);

            if ($checkData == null) {
                error_log('data not found, so INSERTING');

                $dataToAdd = array(
                    'PatientId' => $patientId,
                    'InsuranceType' => $request->get('InsuranceType'),
                    'InsurancePolicyNumber' => $request->get('InsurancePolicyNumber'),
                    'InsuranceOtherType' => $request->get('InsuranceOtherType'),
                    'CoverageType' => $request->get('CoverageType'),
                    'CoverageOtherType' => $request->get('CoverageOtherType'),
                    'CoveragePolicyNumber' => $request->get('CoveragePolicyNumber'),
                    'Comment' => $request->get('Comment'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );
                $insertedData = GenericModel::insertGenericAndReturnID('patient_assessment_insurance', $dataToAdd);
                if ($insertedData == false) {
                    error_log('data not inserted');
                    return response()->json(['data' => null, 'message' => 'Error in inserting patient assessment insurance'], 400);
                } else {
                    error_log('data inserted');
                    return response()->json(['data' => $insertedData, 'message' => 'Patient assessment insurance successfully added'], 200);
                }
            } else {
                error_log('data found. But id is null so we cannot update');
                return response()->json(['data' => null, 'message' => 'Patient assessment insurance cannot be updated because id is NULL'], 200);
            }
        } else {
            error_log('fetching single data');
            $checkData = GenericModel::simpleFetchGenericById('patient_assessment_insurance', 'Id', $request->get('Id'));
            if ($checkData == null) {
                error_log('data not found');
                return response()->json(['data' => null, 'message' => 'Patient assessment insurance not found'], 400);
            } else {
                error_log('data found. Now update');

                $dataToUpdate = array(
                    'InsuranceType' => $request->get('InsuranceType'),
                    'InsurancePolicyNumber' => $request->get('InsurancePolicyNumber'),
                    'InsuranceOtherType' => $request->get('InsuranceOtherType'),
                    'CoverageType' => $request->get('CoverageType'),
                    'CoverageOtherType' => $request->get('CoverageOtherType'),
                    'CoveragePolicyNumber' => $request->get('CoveragePolicyNumber'),
                    'Comment' => $request->get('Comment'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $date["timestamp"]
                );

                $updatedData = GenericModel::updateGeneric('patient_assessment_insurance', 'Id', (int)$request->get('Id'), $dataToUpdate);

                if ($updatedData == false) {
                    error_log('data not updated');
                    return response()->json(['data' => null, 'message' => 'Error in updating patient assessment insurance'], 400);
                } else {
                    error_log('data updated');
                    return response()->json(['data' => (int)$request->get('Id'), 'message' => 'Patient insurance successfully updated'], 200);
                }
            }
        }
    }

    static public function GetPatientAssessmentInsurance(Request $request)
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

        $checkData = GenericModel::simpleFetchGenericById('patient_assessment_insurance', 'PatientId', $patientId);

        if ($checkData == null) {
            error_log('data not found');
            return response()->json(['data' => null, 'message' => 'Patient assessment insurance not found'], 400);
        } else {
            error_log('data found. Now update');

            $data = array(
                'Id' => $checkData->Id,
                'InsuranceType' => $checkData->InsuranceType,
                'InsurancePolicyNumber' => $checkData->InsurancePolicyNumber,
                'InsuranceOtherType' => $checkData->InsuranceOtherType,
                'CoverageType' => $checkData->CoverageType,
                'CoverageOtherType' => $checkData->CoverageOtherType,
                'CoveragePolicyNumber' => $checkData->CoveragePolicyNumber,
                'Comment' => $checkData->Comment
            );

            return response()->json(['data' => $data, 'message' => 'Patient assessment insurance found'], 200);
        }
    }

    static public function SavePatientAssessmentResource(Request $request)
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

        //First check if id is null or not
        //If id is null then insert
        //else check that record
        if ($request->get('Id') == "null" || $request->get('Id') == null) {
            error_log('Data id is null');
            error_log('Now checking if record is existing via patient id or not');

            $checkData = GenericModel::simpleFetchGenericById('patient_assessment_resource', 'PatientId', $patientId);

            if ($checkData == null) {
                error_log('data not found, so INSERTING');

                $dataToAdd = array(
                    'PatientId' => $patientId,
                    'IsForgetMedicine' => (bool)$request->get('IsForgetMedicine'),
                    'IsForgetMedicineComment' => $request->get('IsForgetMedicineComment'),
                    'IsForgetAppointment' => (bool)$request->get('IsForgetAppointment'),
                    'IsForgetAppointmentComment' => $request->get('IsForgetAppointmentComment'),
                    'IsGoWhenSick' => (bool)$request->get('IsGoWhenSick'),
                    'IsGoWhenSickComment' => $request->get('IsGoWhenSickComment'),
                    'GoWithoutFood' => (bool)$request->get('GoWithoutFood'),
                    'GoWithoutFoodComment' => $request->get('GoWithoutFoodComment'),
                    'IsPowerShutOff' => (bool)$request->get('IsPowerShutOff'),
                    'IsPowerShutOffComment' => $request->get('IsPowerShutOffComment'),
                    'GetUnAbleToDress' => (bool)$request->get('GetUnAbleToDress'),
                    'GetUnAbleToDressComment' => $request->get('GetUnAbleToDressComment'),
                    'HardToPrepareFood' => (bool)$request->get('HardToPrepareFood'),
                    'HardToPrepareFoodComment' => $request->get('HardToPrepareFoodComment'),
                    'IsFrequentlySad' => (bool)$request->get('IsFrequentlySad'),
                    'IsFrequentlySadComment' => $request->get('IsFrequentlySadComment'),
                    'HardToTakeBath' => (bool)$request->get('HardToTakeBath'),
                    'HardToTakeBathComment' => $request->get('HardToTakeBathComment'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );
                $insertedData = GenericModel::insertGenericAndReturnID('patient_assessment_resource', $dataToAdd);
                if ($insertedData == false) {
                    error_log('data not inserted');
                    return response()->json(['data' => null, 'message' => 'Error in inserting patient assessment resource'], 400);
                } else {
                    error_log('data inserted');
                    return response()->json(['data' => $insertedData, 'message' => 'Patient assessment resource successfully added'], 200);
                }
            } else {
                error_log('data found. But id is null so we cannot update');
                return response()->json(['data' => null, 'message' => 'Patient assessment resource cannot be updated because id is NULL'], 200);
            }
        } else {
            error_log('fetching single data');
            $checkData = GenericModel::simpleFetchGenericById('patient_assessment_resource', 'Id', $request->get('Id'));
            if ($checkData == null) {
                error_log('data not found');
                return response()->json(['data' => null, 'message' => 'Patient assessment resource not found'], 400);
            } else {
                error_log('data found. Now update');

                $dataToUpdate = array(
                    'IsForgetMedicine' => (bool)$request->get('IsForgetMedicine'),
                    'IsForgetMedicineComment' => $request->get('IsForgetMedicineComment'),
                    'IsForgetAppointment' => (bool)$request->get('IsForgetAppointment'),
                    'IsForgetAppointmentComment' => $request->get('IsForgetAppointmentComment'),
                    'IsGoWhenSick' => (bool)$request->get('IsGoWhenSick'),
                    'IsGoWhenSickComment' => $request->get('IsGoWhenSickComment'),
                    'GoWithoutFood' => (bool)$request->get('GoWithoutFood'),
                    'GoWithoutFoodComment' => $request->get('GoWithoutFoodComment'),
                    'IsPowerShutOff' => (bool)$request->get('IsPowerShutOff'),
                    'IsPowerShutOffComment' => $request->get('IsPowerShutOffComment'),
                    'GetUnAbleToDress' => (bool)$request->get('GetUnAbleToDress'),
                    'GetUnAbleToDressComment' => $request->get('GetUnAbleToDressComment'),
                    'HardToPrepareFood' => (bool)$request->get('HardToPrepareFood'),
                    'HardToPrepareFoodComment' => $request->get('HardToPrepareFoodComment'),
                    'IsFrequentlySad' => (bool)$request->get('IsFrequentlySad'),
                    'IsFrequentlySadComment' => $request->get('IsFrequentlySadComment'),
                    'HardToTakeBath' => (bool)$request->get('HardToTakeBath'),
                    'HardToTakeBathComment' => $request->get('HardToTakeBathComment'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $date["timestamp"]
                );

                $updatedData = GenericModel::updateGeneric('patient_assessment_resource', 'Id', (int)$request->get('Id'), $dataToUpdate);

                if ($updatedData == false) {
                    error_log('data not updated');
                    return response()->json(['data' => null, 'message' => 'Error in updating patient assessment resource'], 400);
                } else {
                    error_log('data updated');
                    return response()->json(['data' => (int)$request->get('Id'), 'message' => 'Patient resource successfully updated'], 200);
                }
            }
        }
    }

    static public function GetPatientAssessmentResource(Request $request)
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

        $checkData = GenericModel::simpleFetchGenericById('patient_assessment_resource', 'PatientId', $patientId);

        if ($checkData == null) {
            error_log('data not found');
            return response()->json(['data' => null, 'message' => 'Patient assessment resource not found'], 400);
        } else {
            error_log('data found. Now update');

            $data = array(
                'Id' => $checkData->Id,
                'IsForgetMedicine' => $checkData->IsForgetMedicine,
                'IsForgetMedicineComment' => $checkData->IsForgetMedicineComment,
                'IsForgetAppointment' => $checkData->IsForgetAppointment,
                'IsForgetAppointmentComment' => $checkData->IsForgetAppointmentComment,
                'IsGoWhenSick' => $checkData->IsGoWhenSick,
                'IsGoWhenSickComment' => $checkData->IsGoWhenSickComment,
                'GoWithoutFood' => $checkData->GoWithoutFood,
                'GoWithoutFoodComment' => $checkData->GoWithoutFoodComment,
                'IsPowerShutOff' => $checkData->IsPowerShutOff,
                'IsPowerShutOffComment' => $checkData->IsPowerShutOffComment,
                'GetUnAbleToDress' => $checkData->GetUnAbleToDress,
                'GetUnAbleToDressComment' => $checkData->GetUnAbleToDressComment,
                'HardToPrepareFood' => $checkData->HardToPrepareFood,
                'HardToPrepareFoodComment' => $checkData->HardToPrepareFoodComment,
                'IsFrequentlySad' => $checkData->IsFrequentlySad,
                'IsFrequentlySadComment' => $checkData->IsFrequentlySadComment,
                'HardToTakeBath' => $checkData->HardToTakeBath,
                'HardToTakeBathComment' => $checkData->HardToTakeBathComment
            );

            return response()->json(['data' => $data, 'message' => 'Patient assessment resource found'], 200);
        }
    }

    static public function SavePatientAssessmentSelf(Request $request)
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

        //First check if id is null or not
        //If id is null then insert
        //else check that record
        if ($request->get('Id') == "null" || $request->get('Id') == null) {
            error_log('Data id is null');
            error_log('Now checking if record is existing via patient id or not');

            $checkData = GenericModel::simpleFetchGenericById('patient_assessment_self', 'PatientId', $patientId);

            if ($checkData == null) {
                error_log('data not found, so INSERTING');

                $dataToAdd = array(
                    'PatientId' => $patientId,
                    'LiveType' => $request->get('LiveType'),
                    'LiveOtherType' => $request->get('LiveOtherType'),
                    'LiveComment' => $request->get('LiveComment'),
                    'ChallengeWith' => $request->get('ChallengeWith'),
                    'ChallengeOtherType' => $request->get('ChallengeOtherType'),
                    'ChallengeComment' => $request->get('ChallengeComment'),
                    'PrimaryLanguage' => $request->get('PrimaryLanguage'),
                    'PrimaryLanguageOther' => $request->get('PrimaryLanguageOther'),
                    'PrimaryLanguageComment' => $request->get('PrimaryLanguageComment'),
                    'LearnBestBy' => $request->get('LearnBestBy'),
                    'LearnBestByOther' => $request->get('LearnBestByOther'),
                    'LearnBestByComment' => $request->get('LearnBestByComment'),
                    'ThingImpactHealth' => $request->get('ThingImpactHealth'),
                    'ThingImpactHealthOther' => $request->get('ThingImpactHealthOther'),
                    'IsDietaryRequire' => (bool)$request->get('IsDietaryRequire'),
                    'DietaryRequireDescription' => $request->get('DietaryRequireDescription'),
                    'AssistanceAvailable' => $request->get('AssistanceAvailable'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );
                $insertedData = GenericModel::insertGenericAndReturnID('patient_assessment_self', $dataToAdd);
                if ($insertedData == false) {
                    error_log('data not inserted');
                    return response()->json(['data' => null, 'message' => 'Error in inserting patient assessment self'], 400);
                } else {
                    error_log('data inserted');
                    return response()->json(['data' => $insertedData, 'message' => 'Patient assessment self successfully added'], 200);
                }
            } else {
                error_log('data found. But id is null so we cannot update');
                return response()->json(['data' => null, 'message' => 'Patient assessment self cannot be updated because id is NULL'], 200);
            }
        } else {
            error_log('fetching single data');
            $checkData = GenericModel::simpleFetchGenericById('patient_assessment_self', 'Id', $request->get('Id'));
            if ($checkData == null) {
                error_log('data not found');
                return response()->json(['data' => null, 'message' => 'Patient assessment self not found'], 400);
            } else {
                error_log('data found. Now update');

                $dataToUpdate = array(
                    'LiveType' => $request->get('LiveType'),
                    'LiveOtherType' => $request->get('LiveOtherType'),
                    'LiveComment' => $request->get('LiveComment'),
                    'ChallengeWith' => $request->get('ChallengeWith'),
                    'ChallengeOtherType' => $request->get('ChallengeOtherType'),
                    'ChallengeComment' => $request->get('ChallengeComment'),
                    'PrimaryLanguage' => $request->get('PrimaryLanguage'),
                    'PrimaryLanguageOther' => $request->get('PrimaryLanguageOther'),
                    'PrimaryLanguageComment' => $request->get('PrimaryLanguageComment'),
                    'LearnBestBy' => $request->get('LearnBestBy'),
                    'LearnBestByOther' => $request->get('LearnBestByOther'),
                    'LearnBestByComment' => $request->get('LearnBestByComment'),
                    'ThingImpactHealth' => $request->get('ThingImpactHealth'),
                    'ThingImpactHealthOther' => $request->get('ThingImpactHealthOther'),
                    'IsDietaryRequire' => (bool)$request->get('IsDietaryRequire'),
                    'DietaryRequireDescription' => $request->get('DietaryRequireDescription'),
                    'AssistanceAvailable' => $request->get('AssistanceAvailable'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $date["timestamp"]
                );

                $updatedData = GenericModel::updateGeneric('patient_assessment_self', 'Id', (int)$request->get('Id'), $dataToUpdate);

                if ($updatedData == false) {
                    error_log('data not updated');
                    return response()->json(['data' => null, 'message' => 'Error in updating patient assessment self'], 400);
                } else {
                    error_log('data updated');
                    return response()->json(['data' => (int)$request->get('Id'), 'message' => 'Patient assessment self successfully updated'], 200);
                }
            }
        }
    }

    static public function GetPatientAssessmentSelf(Request $request)
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

        $checkData = GenericModel::simpleFetchGenericById('patient_assessment_self', 'PatientId', $patientId);

        if ($checkData == null) {
            error_log('data not found');
            return response()->json(['data' => null, 'message' => 'Patient assessment self not found'], 400);
        } else {
            error_log('data found. Now update');

            $data = array(
                'Id' => $checkData->Id,
                'LiveType' => $checkData->LiveType,
                'LiveOtherType' => $checkData->LiveOtherType,
                'LiveComment' => $checkData->LiveComment,
                'ChallengeWith' => $checkData->ChallengeWith,
                'ChallengeOtherType' => $checkData->ChallengeOtherType,
                'ChallengeComment' => $checkData->ChallengeComment,
                'PrimaryLanguage' => $checkData->PrimaryLanguage,
                'PrimaryLanguageOther' => $checkData->PrimaryLanguageOther,
                'PrimaryLanguageComment' => $checkData->PrimaryLanguageComment,
                'LearnBestBy' => $checkData->LearnBestBy,
                'LearnBestByOther' => $checkData->LearnBestByOther,
                'LearnBestByComment' => $checkData->LearnBestByComment,
                'ThingImpactHealth' => $checkData->ThingImpactHealth,
                'ThingImpactHealthOther' => $checkData->ThingImpactHealthOther,
                'IsDietaryRequire' => $checkData->IsDietaryRequire,
                'DietaryRequireDescription' => $checkData->DietaryRequireDescription,
                'AssistanceAvailable' => $checkData->AssistanceAvailable
            );

            return response()->json(['data' => $data, 'message' => 'Patient assessment self found'], 200);
        }
    }

    static public function SavePatientDiabeticMeasure(Request $request)
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

        //First check if id is null or not
        //If id is null then insert
        //else check that record
        if ($request->get('Id') == "null" || $request->get('Id') == null) {
            error_log('Data id is null');
            error_log('Now checking if record is existing via patient id or not');

            //Check if diabetic measure is valid

            if ((int)$request->get('DiabeticMeasureParamId') != null || (int)$request->get('DiabeticMeasureParamId') != "null") {
                $checkDiabeticMeasure = GenericModel::simpleFetchGenericById('diabetic_measure_param', 'Id', (int)$request->get('DiabeticMeasureParamId'));
                if ($checkDiabeticMeasure == null) {
                    return response()->json(['data' => null, 'message' => 'Invalid diabetic measure param'], 400);
                }
            }

            $checkData = GenericModel::simpleFetchGenericById('patient_diabetic_measure', 'PatientId', $patientId);

            if ($checkData == null) {
                error_log('data not found, so INSERTING');

                $dataToAdd = array(
                    'PatientId' => $patientId,
                    'DiabeticMeasureParamId' => (int)$request->get('DiabeticMeasureParamId'),
                    'IsPatientMeasure' => (bool)$request->get('IsPatientMeasure'),
                    'Description' => $request->get('Description'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );
                $insertedData = GenericModel::insertGenericAndReturnID('patient_diabetic_measure', $dataToAdd);
                if ($insertedData == false) {
                    error_log('data not inserted');
                    return response()->json(['data' => null, 'message' => 'Error in inserting patient diabetic measure'], 400);
                } else {
                    error_log('data inserted');
                    return response()->json(['data' => $insertedData, 'message' => 'Patient diabetic measure successfully added'], 200);
                }
            } else {
                error_log('data found. But id is null so we cannot update');
                return response()->json(['data' => null, 'message' => 'Patient diabetic measure cannot be updated because id is NULL'], 200);
            }
        } else {
            error_log('fetching single data');
            $checkData = GenericModel::simpleFetchGenericById('patient_diabetic_measure', 'Id', $request->get('Id'));
            if ($checkData == null) {
                error_log('data not found');
                return response()->json(['data' => null, 'message' => 'Patient diabetic measure not found'], 400);
            } else {
                error_log('data found. Now update');

                $dataToUpdate = array(
                    'DiabeticMeasureParamId' => (int)$request->get('DiabeticMeasureParamId'),
                    'IsPatientMeasure' => (bool)$request->get('IsPatientMeasure'),
                    'Description' => $request->get('Description'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $date["timestamp"]
                );

                $updatedData = GenericModel::updateGeneric('patient_diabetic_measure', 'Id', (int)$request->get('Id'), $dataToUpdate);

                if ($updatedData == false) {
                    error_log('data not updated');
                    return response()->json(['data' => null, 'message' => 'Error in updating patient diabetic measure'], 400);
                } else {
                    error_log('data updated');
                    return response()->json(['data' => (int)$request->get('Id'), 'message' => 'Patient diabetic measure successfully updated'], 200);
                }
            }
        }
    }

    static public function GetPatientPatientDiabeticMeasure(Request $request)
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

        $checkData = CcmModel::GetSinglePatientDiabeticMeasure($patientId);

        if ($checkData == null) {
            error_log('data not found');
            return response()->json(['data' => null, 'message' => 'Patient diabetic measure not found'], 400);
        } else {
            error_log('data found. Now update');

            $data = array(
                'Id' => $checkData->pdmId,
                'IsPatientMeasure' => $checkData->IsPatientMeasure,
                'Description' => $checkData->pdmDescription,
                'DiabeticMeasureParam' => array()
            );

            $data['DiabeticMeasureParam']['Id'] = $checkData->dmpId;
            $data['DiabeticMeasureParam']['Name'] = $checkData->Name;
            $data['DiabeticMeasureParam']['Description'] = $checkData->dmpDescription;

            return response()->json(['data' => $data, 'message' => 'Patient diabetic measure found'], 200);
        }
    }

    static public function SavePatientFunctionalReview(Request $request)
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

        //First check if id is null or not
        //If id is null then insert
        //else check that record
        if ($request->get('Id') == "null" || $request->get('Id') == null) {
            error_log('Data id is null');
            error_log('Now checking if record is existing via patient id or not');

            //Check if diabetic measure is valid

            if ((int)$request->get('FunctionalReviewParamId') != null || (int)$request->get('FunctionalReviewParamId') != "null") {
                $checkDiabeticMeasure = GenericModel::simpleFetchGenericById('functional_review_param', 'Id', (int)$request->get('FunctionalReviewParamId'));
                if ($checkDiabeticMeasure == null) {
                    return response()->json(['data' => null, 'message' => 'Invalid functional review param'], 400);
                }
            }

            $checkData = GenericModel::simpleFetchGenericById('patient_functional_review', 'PatientId', $patientId);

            if ($checkData == null) {
                error_log('data not found, so INSERTING');

                $dataToAdd = array(
                    'PatientId' => $patientId,
                    'FunctionalReviewParamId' => (int)$request->get('FunctionalReviewParamId'),
                    'IsOkay' => (bool)$request->get('IsOkay'),
                    'Description' => $request->get('Description'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );
                $insertedData = GenericModel::insertGenericAndReturnID('patient_functional_review', $dataToAdd);
                if ($insertedData == false) {
                    error_log('data not inserted');
                    return response()->json(['data' => null, 'message' => 'Error in inserting patient functional review'], 400);
                } else {
                    error_log('data inserted');
                    return response()->json(['data' => $insertedData, 'message' => 'Patient functional review successfully added'], 200);
                }
            } else {
                error_log('data found. But id is null so we cannot update');
                return response()->json(['data' => null, 'message' => 'Patient functional review cannot be updated because id is NULL'], 200);
            }
        } else {
            error_log('fetching single data');
            $checkData = GenericModel::simpleFetchGenericById('patient_functional_review', 'Id', $request->get('Id'));
            if ($checkData == null) {
                error_log('data not found');
                return response()->json(['data' => null, 'message' => 'Patient functional review not found'], 400);
            } else {
                error_log('data found. Now update');

                $dataToUpdate = array(
                    'FunctionalReviewParamId' => (int)$request->get('FunctionalReviewParamId'),
                    'IsOkay' => (bool)$request->get('IsOkay'),
                    'Description' => $request->get('Description'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $date["timestamp"]
                );

                $updatedData = GenericModel::updateGeneric('patient_functional_review', 'Id', (int)$request->get('Id'), $dataToUpdate);

                if ($updatedData == false) {
                    error_log('data not updated');
                    return response()->json(['data' => null, 'message' => 'Error in updating patient functional review'], 400);
                } else {
                    error_log('data updated');
                    return response()->json(['data' => (int)$request->get('Id'), 'message' => 'Patient functional review successfully updated'], 200);
                }
            }
        }
    }

    static public function GetPatientPatientFunctionalReview(Request $request)
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

        $checkData = CcmModel::GetSinglePatientFunctionalReview($patientId);

        if ($checkData == null) {
            error_log('data not found');
            return response()->json(['data' => null, 'message' => 'Patient functional review not found'], 400);
        } else {
            error_log('data found. Now update');

            $data = array(
                'Id' => $checkData->ptrId,
                'IsOkay' => $checkData->IsOkay,
                'Description' => $checkData->ptrDescription,
                'FunctionalReviewParam' => array()
            );

            $data['FunctionalReviewParam']['Id'] = $checkData->frpId;
            $data['FunctionalReviewParam']['Name'] = $checkData->Name;
            $data['FunctionalReviewParam']['Description'] = $checkData->frpDescription;

            return response()->json(['data' => $data, 'message' => 'Patient functional review found'], 200);
        }
    }

    static public function SavePatientOrganizationAssistance(Request $request)
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

        //First check if id is null or not
        //If id is null then insert
        //else check that record
        if ($request->get('Id') == "null" || $request->get('Id') == null) {
            error_log('Data id is null');
            error_log('Now checking if record is existing via patient id or not');

            //Check if diabetic measure is valid

            if ((int)$request->get('AssistanceOrganizationId') != null || (int)$request->get('AssistanceOrganizationId') != "null") {
                $checkDiabeticMeasure = GenericModel::simpleFetchGenericById('assistance_organization', 'Id', (int)$request->get('AssistanceOrganizationId'));
                if ($checkDiabeticMeasure == null) {
                    return response()->json(['data' => null, 'message' => 'Invalid assistance organization'], 400);
                }
            }

            $checkData = GenericModel::simpleFetchGenericById('patient_organization_assistance', 'PatientId', $patientId);

            if ($checkData == null) {
                error_log('data not found, so INSERTING');

                $dataToAdd = array(
                    'PatientId' => $patientId,
                    'AssistanceOrganizationId' => (int)$request->get('AssistanceOrganizationId'),
                    'Organization' => $request->get('Organization'),
                    'TelephoneNumber' => (int)$request->get('TelephoneNumber'),
                    'OfficeAddress' => $request->get('OfficeAddress'),
                    'ContactPerson' => $request->get('ContactPerson'),
                    'Description' => $request->get('Description'),
                    'IsPatientRefused' => (bool)$request->get('IsPatientRefused'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );
                $insertedData = GenericModel::insertGenericAndReturnID('patient_organization_assistance', $dataToAdd);
                if ($insertedData == false) {
                    error_log('data not inserted');
                    return response()->json(['data' => null, 'message' => 'Error in inserting patient organization assistance'], 400);
                } else {
                    error_log('data inserted');
                    return response()->json(['data' => $insertedData, 'message' => 'Patient organization assistance successfully added'], 200);
                }
            } else {
                error_log('data found. But id is null so we cannot update');
                return response()->json(['data' => null, 'message' => 'Patient organization assistance cannot be updated because id is NULL'], 200);
            }
        } else {
            error_log('fetching single data');
            $checkData = GenericModel::simpleFetchGenericById('patient_organization_assistance', 'Id', $request->get('Id'));
            if ($checkData == null) {
                error_log('data not found');
                return response()->json(['data' => null, 'message' => 'Patient organization assistance not found'], 400);
            } else {
                error_log('data found. Now update');

                $dataToUpdate = array(
                    'AssistanceOrganizationId' => (int)$request->get('AssistanceOrganizationId'),
                    'Organization' => $request->get('Organization'),
                    'TelephoneNumber' => (int)$request->get('TelephoneNumber'),
                    'OfficeAddress' => $request->get('OfficeAddress'),
                    'ContactPerson' => $request->get('ContactPerson'),
                    'Description' => $request->get('Description'),
                    'IsPatientRefused' => (bool)$request->get('IsPatientRefused'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $date["timestamp"]
                );

                $updatedData = GenericModel::updateGeneric('patient_organization_assistance', 'Id', (int)$request->get('Id'), $dataToUpdate);

                if ($updatedData == false) {
                    error_log('data not updated');
                    return response()->json(['data' => null, 'message' => 'Error in updating patient organization assistance'], 400);
                } else {
                    error_log('data updated');
                    return response()->json(['data' => (int)$request->get('Id'), 'message' => 'Patient organization assistance successfully updated'], 200);
                }
            }
        }
    }

    static public function GetPatientOrganizationAssistanceViaPatientId(Request $request)
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

        $patientOrganizationData = CcmModel::GetSinglePatientOrgnizationAssistanceViaPatientId($patientId);

        if ($patientOrganizationData == null) {
            error_log('data not found');
            return response()->json(['data' => null, 'message' => 'Patient organization assistance not found'], 400);
        } else {
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
        }
    }


    static public function SavePatientScreenExamination(Request $request)
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

        //First check if id is null or not
        //If id is null then insert
        //else check that record
        if ($request->get('Id') == "null" || $request->get('Id') == null) {
            error_log('Data id is null');
            error_log('Now checking if record is existing via patient id or not');

            //Check if diabetic measure is valid

            if ((int)$request->get('PreventScreeningParamId') != null || (int)$request->get('PreventScreeningParamId') != "null") {
                $checkDiabeticMeasure = GenericModel::simpleFetchGenericById('prevent_screening_examination_param', 'Id', (int)$request->get('PreventScreeningParamId'));
                if ($checkDiabeticMeasure == null) {
                    return response()->json(['data' => null, 'message' => 'Invalid screen examination'], 400);
                }
            }

            $checkData = GenericModel::simpleFetchGenericById('patient_prevent_screening_examination', 'PatientId', $patientId);

            if ($checkData == null) {
                error_log('data not found, so INSERTING');

                $dataToAdd = array(
                    'PatientId' => $patientId,
                    'PreventScreeningParamId' => (int)$request->get('PreventScreeningParamId'),
                    'Description' => $request->get('Description'),
                    'IsPatientExamined' => (bool)$request->get('IsPatientExamined'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );
                $insertedData = GenericModel::insertGenericAndReturnID('patient_prevent_screening_examination', $dataToAdd);
                if ($insertedData == false) {
                    error_log('data not inserted');
                    return response()->json(['data' => null, 'message' => 'Error in inserting patient screen examination'], 400);
                } else {
                    error_log('data inserted');
                    return response()->json(['data' => $insertedData, 'message' => 'Patient screen examination successfully added'], 200);
                }
            } else {
                error_log('data found. But id is null so we cannot update');
                return response()->json(['data' => null, 'message' => 'Patient screen examination cannot be updated because id is NULL'], 200);
            }
        } else {
            error_log('fetching single data');
            $checkData = GenericModel::simpleFetchGenericById('patient_prevent_screening_examination', 'Id', $request->get('Id'));
            if ($checkData == null) {
                error_log('data not found');
                return response()->json(['data' => null, 'message' => 'Patient screen examination not found'], 400);
            } else {
                error_log('data found. Now update');

                $dataToUpdate = array(
                    'PreventScreeningParamId' => (int)$request->get('PreventScreeningParamId'),
                    'Description' => $request->get('Description'),
                    'IsPatientExamined' => (bool)$request->get('IsPatientExamined'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $date["timestamp"]
                );

                $updatedData = GenericModel::updateGeneric('patient_prevent_screening_examination', 'Id', (int)$request->get('Id'), $dataToUpdate);

                if ($updatedData == false) {
                    error_log('data not updated');
                    return response()->json(['data' => null, 'message' => 'Error in updating patient screen examination'], 400);
                } else {
                    error_log('data updated');
                    return response()->json(['data' => (int)$request->get('Id'), 'message' => 'Patient screen examination successfully updated'], 200);
                }
            }
        }
    }

    static public function GetPatientScreenExamination(Request $request)
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

        $checkData = CcmModel::GetSinglePatientScreenExamination($patientId);

        if ($checkData == null) {
            error_log('data not found');
            return response()->json(['data' => null, 'message' => 'Patient screen examination not found'], 400);
        } else {
            error_log('data found ');

            $data['Id'] = $checkData->ppseId;
            $data['IsPatientExamined'] = $checkData->IsPatientExamined;
            $data['Description'] = $checkData->ppseDescription;
            $data['PreventScreeningParam'] = array();

            //Assistance organization data
            $data['PreventScreeningParam']['Id'] = $checkData->psepId;
            $data['PreventScreeningParam']['Name'] = $checkData->Name;
            $data['PreventScreeningParam']['Description'] = $checkData->psepDescription;

            return response()->json(['data' => $data, 'message' => 'Patient screen examination found'], 200);
        }
    }


    static public function SavePatientPsychologicalReview(Request $request)
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

        //First check if id is null or not
        //If id is null then insert
        //else check that record
        if ($request->get('Id') == "null" || $request->get('Id') == null) {
            error_log('Data id is null');
            error_log('Now checking if record is existing via patient id or not');

            //Check if diabetic measure is valid

            if ((int)$request->get('PsychologicalReviewParamId') != null || (int)$request->get('PsychologicalReviewParamId') != "null") {
                $checkDiabeticMeasure = GenericModel::simpleFetchGenericById('psychological_review_param', 'Id', (int)$request->get('PsychologicalReviewParamId'));
                if ($checkDiabeticMeasure == null) {
                    return response()->json(['data' => null, 'message' => 'Invalid psychological review'], 400);
                }
            }

            $checkData = GenericModel::simpleFetchGenericById('patient_psychological_review', 'PatientId', $patientId);

            if ($checkData == null) {
                error_log('data not found, so INSERTING');

                $dataToAdd = array(
                    'PatientId' => $patientId,
                    'PsychologicalReviewParamId' => (int)$request->get('PsychologicalReviewParamId'),
                    'Description' => $request->get('Description'),
                    'IsOkay' => (bool)$request->get('IsOkay'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );
                $insertedData = GenericModel::insertGenericAndReturnID('patient_psychological_review', $dataToAdd);
                if ($insertedData == false) {
                    error_log('data not inserted');
                    return response()->json(['data' => null, 'message' => 'Error in inserting patient psychological review'], 400);
                } else {
                    error_log('data inserted');
                    return response()->json(['data' => $insertedData, 'message' => 'Patient psychological review successfully added'], 200);
                }
            } else {
                error_log('data found. But id is null so we cannot update');
                return response()->json(['data' => null, 'message' => 'Patient psychological review cannot be updated because id is NULL'], 200);
            }
        } else {
            error_log('fetching single data');
            $checkData = GenericModel::simpleFetchGenericById('patient_psychological_review', 'Id', $request->get('Id'));
            if ($checkData == null) {
                error_log('data not found');
                return response()->json(['data' => null, 'message' => 'Patient psychological review not found'], 400);
            } else {
                error_log('data found. Now update');

                $dataToUpdate = array(
                    'PsychologicalReviewParamId' => (int)$request->get('PsychologicalReviewParamId'),
                    'Description' => $request->get('Description'),
                    'IsOkay' => (bool)$request->get('IsOkay'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $date["timestamp"]
                );

                $updatedData = GenericModel::updateGeneric('patient_psychological_review', 'Id', (int)$request->get('Id'), $dataToUpdate);

                if ($updatedData == false) {
                    error_log('data not updated');
                    return response()->json(['data' => null, 'message' => 'Error in updating patient psychological review'], 400);
                } else {
                    error_log('data updated');
                    return response()->json(['data' => (int)$request->get('Id'), 'message' => 'Patient psychological review successfully updated'], 200);
                }
            }
        }
    }

    static public function GetPatientPsychologicalReview(Request $request)
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

        $checkData = CcmModel::GetSinglePatientPsychologicalReview($patientId);

        if ($checkData == null) {
            error_log('data not found');
            return response()->json(['data' => null, 'message' => 'Patient psychological review not found'], 400);
        } else {
            error_log('data found ');

            $data['Id'] = $checkData->ppsId;
            $data['IsPatientExamined'] = $checkData->IsOkay;
            $data['Description'] = $checkData->ppsDescription;
            $data['PsychologicalReviewParam'] = array();

            //Assistance organization data
            $data['PsychologicalReviewParam']['Id'] = $checkData->prpId;
            $data['PsychologicalReviewParam']['Name'] = $checkData->Name;
            $data['PsychologicalReviewParam']['Description'] = $checkData->prpDescription;

            return response()->json(['data' => $data, 'message' => 'Patient psychological review found'], 200);
        }
    }

    static public function SavePatientSocialReview(Request $request)
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

        //First check if id is null or not
        //If id is null then insert
        //else check that record
        if ($request->get('Id') == "null" || $request->get('Id') == null) {
            error_log('Data id is null');
            error_log('Now checking if record is existing via patient id or not');

            //Check if diabetic measure is valid

            if ((int)$request->get('SocialReviewParamId') != null || (int)$request->get('SocialReviewParamId') != "null") {
                $checkDiabeticMeasure = GenericModel::simpleFetchGenericById('social_review_param', 'Id', (int)$request->get('SocialReviewParamId'));
                if ($checkDiabeticMeasure == null) {
                    return response()->json(['data' => null, 'message' => 'Invalid social review'], 400);
                }
            }

            $checkData = GenericModel::simpleFetchGenericById('patient_social_review', 'PatientId', $patientId);

            if ($checkData == null) {
                error_log('data not found, so INSERTING');

                $dataToAdd = array(
                    'PatientId' => $patientId,
                    'SocialReviewParamId' => (int)$request->get('SocialReviewParamId'),
                    'Description' => $request->get('Description'),
                    'IsOkay' => (bool)$request->get('IsOkay'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'CreatedBy' => $userId,
                    'CreatedOn' => $date["timestamp"]
                );
                $insertedData = GenericModel::insertGenericAndReturnID('patient_social_review', $dataToAdd);
                if ($insertedData == false) {
                    error_log('data not inserted');
                    return response()->json(['data' => null, 'message' => 'Error in inserting patient social review'], 400);
                } else {
                    error_log('data inserted');
                    return response()->json(['data' => $insertedData, 'message' => 'Patient social review successfully added'], 200);
                }
            } else {
                error_log('data found. But id is null so we cannot update');
                return response()->json(['data' => null, 'message' => 'Patient social review cannot be updated because id is NULL'], 200);
            }
        } else {
            error_log('fetching single data');
            $checkData = GenericModel::simpleFetchGenericById('patient_social_review', 'Id', $request->get('Id'));
            if ($checkData == null) {
                error_log('data not found');
                return response()->json(['data' => null, 'message' => 'Patient social review not found'], 400);
            } else {
                error_log('data found. Now update');

                $dataToUpdate = array(
                    'SocialReviewParamId' => (int)$request->get('SocialReviewParamId'),
                    'Description' => $request->get('Description'),
                    'IsOkay' => (bool)$request->get('IsOkay'),
                    'IsActive' => (bool)$request->get('IsActive'),
                    'UpdatedBy' => $userId,
                    'UpdatedOn' => $date["timestamp"]
                );

                $updatedData = GenericModel::updateGeneric('patient_social_review', 'Id', (int)$request->get('Id'), $dataToUpdate);

                if ($updatedData == false) {
                    error_log('data not updated');
                    return response()->json(['data' => null, 'message' => 'Error in updating patient social review'], 400);
                } else {
                    error_log('data updated');
                    return response()->json(['data' => (int)$request->get('Id'), 'message' => 'Patient social review successfully updated'], 200);
                }
            }
        }
    }

    static public function GetPatientSocialReview(Request $request)
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

        $checkData = CcmModel::GetSinglePatientSocialReview($patientId);

        if ($checkData == null) {
            error_log('data not found');
            return response()->json(['data' => null, 'message' => 'Patient social review not found'], 400);
        } else {
            error_log('data found ');

            $data['Id'] = $checkData->psrId;
            $data['IsPatientExamined'] = $checkData->IsOkay;
            $data['Description'] = $checkData->psrDescription;
            $data['SocialReviewParam'] = array();

            //Assistance organization data
            $data['SocialReviewParam']['Id'] = $checkData->srpId;
            $data['SocialReviewParam']['Name'] = $checkData->Name;
            $data['SocialReviewParam']['Description'] = $checkData->srpDescription;

            return response()->json(['data' => $data, 'message' => 'Patient social review found'], 200);
        }
    }
}
