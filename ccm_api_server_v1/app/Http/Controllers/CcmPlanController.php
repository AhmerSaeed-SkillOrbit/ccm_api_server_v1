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

        $checkAnswerData = CcmModel::getSingleAnswer($answerId);

        if ($checkAnswerData == null) {
            error_log('invalid answer');
            return response()->json(['data' => null, 'message' => 'Answer is not valid'], 400);
        } else {
            error_log('Answer found');

            $date = HelperModel::getDate();

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

                error_log('1st question id is : ' . $item->Id);

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

}
