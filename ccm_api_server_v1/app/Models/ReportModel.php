<?php
/**
 * Created by PhpStorm.
 * User: SO-LPT-031
 * Date: 5/18/2019
 * Time: 2:41 PM
 */

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HelperModel;

class ReportModel
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
}