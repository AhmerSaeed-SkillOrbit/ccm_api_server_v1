<?php
/**
 * Created by PhpStorm.
 * User: Ahsan Vohra
 * Date: 3/25/2019
 * Time: 7:51 PM
 */

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HelperModel;


class CcmModel
{
    static public function getQuestionList()
    {
        error_log('in model, fetching question list');

        $query = DB::table('ccm_question')
            ->select('Id', 'Question', 'Type')
            ->where('IsActive', '=', true)
            ->orderBy('Id', 'desc')
            ->get();

        return $query;
    }

    static public function getAnswersViaQuestionIdAndPatientId($questionId, $patientId)
    {


        error_log('in model, fetching all question and answers of patient');

        $query = DB::table('ccm_answer')
            ->where('ccm_answer.IsActive', '=', true)
            ->where('ccm_answer.PatientId', '=', $patientId)
            ->where('ccm_answer.CcmQuestionId', '=', $questionId)
            ->get();

        return $query;
    }

    static public function getSingleAnswer($id)
    {
        error_log('in model, fetching single answer');

        $query = DB::table('ccm_answer')
            ->where('IsActive', '=', true)
            ->where('Id', '=', $id)
            ->first();

        return $query;
    }

    static public function getSingleQuestionAnswers($id)
    {
        error_log('in model, fetching single answer');

        $query = DB::table('ccm_question')
            ->leftjoin('ccm_answer as answer', 'ccm_question.CcmQuestionId', 'answer.Id')
            ->where('IsActive', '=', true)
            ->where('Id', '=', $id)
            ->get();

        return $query;
    }

}
