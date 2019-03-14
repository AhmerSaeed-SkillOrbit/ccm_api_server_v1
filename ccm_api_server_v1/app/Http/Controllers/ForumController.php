<?php
/**
 * Created by PhpStorm.
 * User: Ahsan Vohra
 * Date: 3/14/2019
 * Time: 9:40 PM
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


class ForumController extends Controller
{
    function addTag(Request $request)
    {
        error_log('in controller');

        $tagName = $request->input('Name');
        $tagTooltip = $request->input('ToolTip');
        $tagDescription = $request->input('Description');

        $date = HelperModel::getDate();

        $tagData = array(
            "Name" => $tagName,
            "ToolTip" => $tagTooltip,
            "Description" => $tagDescription,
            "CreatedOn" => $date["timestamp"],
            "IsActive" => true
        );

        //First insert doctor schedule data and then get id of that record
        $insertedData = GenericModel::insertGenericAndReturnID('tag', $tagData);
        if ($insertedData == 0) {
            return response()->json(['data' => null, 'message' => 'Error in adding tag'], 400);
        } else {
            return response()->json(['data' => $insertedData, 'message' => 'Tag successfully inserted'], 200);
        }
    }

    function getTagList()
    {
        error_log('in controller');

        $getTagList = ForumModel::getTagList();

        error_log('$getTagList ' . $getTagList);

        if (count($getTagList) > 0) {
            return response()->json(['data' => $getTagList, 'message' => 'Tag list found'], 200);

        } else {
            return response()->json(['data' => null, 'message' => 'Tag list not found'], 200);
        }
    }
}
