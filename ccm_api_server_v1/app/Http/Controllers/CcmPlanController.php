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

    static public function GiveAnswerToQuestion()
    {
        error_log('in controller');
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

}
