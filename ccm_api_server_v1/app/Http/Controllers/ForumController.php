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

    function AddForumTopic(Request $request)
    {
        error_log('in controller');

        $userId = $request->input('UserId');
        $title = $request->input('Title');
        $description = $request->input('Description');
        $tags = $request->input('Tag');

        //First check if logged if user id is valid or not

        $checkUserData = UserModel::GetSingleUserViaId($userId);

        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('logged in user data found');
            //Now making data for forum_topic table

            $date = HelperModel::getDate();

            $forumTopicData = array(
                "UserId" => $userId,
                "Title" => $title,
                "Description" => $description,
                "CreatedBy" => $userId,
                "CreatedOn" => $date["timestamp"],
                "IsActive" => true
            );

            DB::beginTransaction();

            $insertedData = GenericModel::insertGenericAndReturnID('forum_topic', $forumTopicData);
            if ($insertedData == 0) {
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in inserting forum topic'], 400);
            } else {
                error_log('Forum topic inserted id is: ' . $insertedData);

                //Now we will make data for inserting forum tags

                if (count($tags) > 0) {

                    $forumTopicTagData = array();

                    foreach ($tags as $item) {

                        array_push($forumTopicTagData,
                            array(
                                "ForumTopicId" => $insertedData,
                                "TagId" => $item['Id']
                            )
                        );
                    }

                    $insertForumTopicTagData = GenericModel::insertGeneric('forum_topic_tag', $forumTopicTagData);
                    if ($insertForumTopicTagData == 0) {
                        DB::rollBack();
                        return response()->json(['data' => null, 'message' => 'Error in inserting forum topic'], 400);
                    } else {
                        error_log('Forum topic tag inserted id ');
                        DB::commit();
                        return response()->json(['data' => $insertedData, 'message' => 'Forum topic started successfully'], 200);
                    }
                } else {
                    DB::commit();
                    return response()->json(['data' => $insertedData, 'message' => 'Forum topic started successfully'], 200);
                }
            }
        }
    }
}
