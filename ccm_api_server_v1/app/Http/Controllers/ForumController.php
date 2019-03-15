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

    function UpdateForumTopic(Request $request)
    {
        error_log('in controller');

        $forumTopicId = $request->input('Id');
        $userId = $request->input('UserId');
        $title = $request->input('Title');
        $description = $request->input('Description');
        $tags = $request->input('Tag');

        //First check if logged if user id is valid or not

        DB::beginTransaction();

        $checkUserData = UserModel::GetSingleUserViaId($userId);

        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('logged in user data found');

            //Now check if this forum exists or not
            $getForumTopicData = ForumModel::getForumTopicViaId($forumTopicId);
            if ($getForumTopicData == null) {
                return response()->json(['data' => null, 'message' => 'Forum topic not found'], 400);
            } else {
                error_log('forum found');
                //Now get forum tags
                //Delete them and insert new ones

                $getForumTopicTagsData = ForumModel::getTagsViaTopicForumId($forumTopicId);
                if (count($getForumTopicTagsData) > 0) {

                    error_log('forum topic tags already exists');
                    error_log('deleting them');

                    $deleteTags = GenericModel::deleteGeneric('forum_topic_tag', 'ForumTopicId', $forumTopicId);
                    if ($deleteTags == false) {
                        DB::rollBack();
                        return response()->json(['data' => null, 'message' => 'Error in deleting forum topic tags'], 400);
                    }
                }

                //Now making data to update forum_topic table

                $date = HelperModel::getDate();

                $forumTopicDataToUpdate = array(
                    "UserId" => $userId,
                    "Title" => $title,
                    "Description" => $description,
                    "UpdatedBy" => $userId,
                    "UpdatedOn" => $date["timestamp"]
                );

                $update = GenericModel::updateGeneric('forum_topic', 'Id', $forumTopicId, $forumTopicDataToUpdate);
                if ($update == false) {
                    DB::rollBack();
                    return response()->json(['data' => null, 'message' => 'Error in updating forum topic'], 400);
                } else {

                    //Now we will make data for inserting forum tags

                    if (count($tags) > 0) {

                        $forumTopicTagData = array();

                        foreach ($tags as $item) {

                            array_push($forumTopicTagData,
                                array(
                                    "ForumTopicId" => $forumTopicId,
                                    "TagId" => $item['Id']
                                )
                            );
                        }

                        $insertForumTopicTagData = GenericModel::insertGeneric('forum_topic_tag', $forumTopicTagData);
                        if ($insertForumTopicTagData == 0) {
                            DB::rollBack();
                            return response()->json(['data' => null, 'message' => 'Error in updating forum topic'], 400);
                        } else {
                            error_log('Forum topic tag inserted id ');
                            DB::commit();
                            return response()->json(['data' => $forumTopicId, 'message' => 'Forum topic updated successfully'], 200);
                        }
                    } else {
                        DB::commit();
                        return response()->json(['data' => $forumTopicId, 'message' => 'Forum topic updated successfully'], 200);
                    }
                }
            }
        }
    }

    function GetSingleForumTopic(Request $request)
    {
        $userId = $request->get('userId');
        $forumTopicId = $request->get('forumTopicId');

        //Now check if forum exists.
        //If exists fetched the record

        $getForumTopicData = ForumModel::getForumTopicViaId($forumTopicId);
        if ($getForumTopicData == null) {
            error_log('forum topic not found');
            return response()->json(['data' => null, 'message' => 'Forum topic not found'], 400);
        } else {
            error_log('forum topic found');

            $forumTopicData['Id'] = $getForumTopicData->Id;
            $forumTopicData['Title'] = $getForumTopicData->Title;
            $forumTopicData['Description'] = $getForumTopicData->Description;

            //After fetching this data
            //Now we will fetch tags data

            $getForumTagViaId = ForumModel::getTagsViaTopicForumId($forumTopicId);
            if (count($getForumTagViaId) > 0) {
                error_log('tags found');
                $forumTopicData['Tags'] = $getForumTagViaId;
            } else {
                $forumTopicData['Tags'] = array();
            }

            return response()->json(['data' => $forumTopicData, 'message' => 'Forum topic data found'], 200);
        }
    }
}
