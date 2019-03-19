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

        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);

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
        $comment = $request->get('comment');

        //First check logged in user data if it is valid or not
        $checkUserData = UserModel::GetSingleUserViaId($userId);

        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('logged in user data found');

            //Now check if forum exists.
            //If exists fetched the record

            $getForumTopicData = ForumModel::getForumTopicViaId($forumTopicId);
            if ($getForumTopicData == null) {
                error_log('forum topic not found');
                return response()->json(['data' => null, 'message' => 'Forum topic not found'], 400);
            } else {
                error_log('forum topic found');

                $getCommentCount = ForumModel::getCommentsCountViaTopicForumId($forumTopicId);

                $forumTopicData['Id'] = $getForumTopicData->Id;
                $forumTopicData['Title'] = $getForumTopicData->Title;
                $forumTopicData['Description'] = $getForumTopicData->Description;
                $forumTopicData['CommentCount'] = $getCommentCount;

                $forumTopicData['CreatedBy'] = array();

                $forumTopicData['CreatedBy']['Id'] = $getForumTopicData->CreatedBy;
                $forumTopicData['CreatedBy']['FirstName'] = $getForumTopicData->FirstName;
                $forumTopicData['CreatedBy']['LastName'] = $getForumTopicData->LastName;

                $forumTopicData['Role'] = array();
                $forumTopicData['Role']['Id'] = $getForumTopicData->RoleId;
                $forumTopicData['Role']['Name'] = $getForumTopicData->RoleName;
                $forumTopicData['Role']['CodeName'] = $getForumTopicData->RoleCodeName;

                //After fetching this data
                //Now we will fetch tags data

                $getForumTagViaId = ForumModel::getTagsViaTopicForumId($forumTopicId);
                if (count($getForumTagViaId) > 0) {
                    error_log('tags found');
                    $forumTopicData['Tags'] = $getForumTagViaId;
                } else {
                    $forumTopicData['Tags'] = array();
                }

                //Now weill fetch comments of the forum
                if ($comment == "yes") {
                    error_log('YES comments');
                    $getForumComments = ForumModel::getForumCommentsViaForumTopicId($forumTopicId);
                    if (count($getForumComments) > 0) {
                        error_log('Comments found found');
                        $forumTopicData['Comments'] = $getForumComments;
                    } else {
                        $forumTopicData['Comments'] = array();
                    }
                } else {
                    error_log('NO comments');
                    $forumTopicData['Comments'] = array();
                }

                return response()->json(['data' => $forumTopicData, 'message' => 'Forum topic data found'], 200);
            }

        }
    }

    function GetForumTopicListViaPagination(Request $request)
    {
        $userId = $request->get('userId');
        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');

        $forumListData = array();

        //First check logged in user data if it is valid or not
        $checkUserData = UserModel::GetSingleUserViaId($userId);

        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('logged in user data found');

            //Now check if forum exists.
            //If exists fetched the record

            $getForumTopicData = ForumModel::getForumTopicListViaPagination($pageNo, $limit);

            if (count($getForumTopicData) > 0) {
                $counter = 0;
                foreach ($getForumTopicData as $item) {

                    error_log('loop iterating for : ' . $counter += 1);

                    $getCommentCount = ForumModel::getCommentsCountViaTopicForumId($item->Id);

                    $data = array(
                        'Id' => $item->Id,
                        'Title' => $item->Title,
                        'Description' => $item->Description,
                        'CreatedOn' => ForumModel::calculateTopicAnCommentTime($item->CreatedOn),
                        'UpdatedOn' => $item->UpdatedOn,
                        'CreatedBy' => array(),
                        'CommentCount' => $getCommentCount,
                        'Role' => array(),
                        'Tags' => array()
                    );

                    $data['CreatedBy']['Id'] = $item->CreatedById;
                    $data['CreatedBy']['FirstName'] = $item->FirstName;
                    $data['CreatedBy']['LastName'] = $item->LastName;

                    $data['Role']['Id'] = $item->RoleId;
                    $data['Role']['Name'] = $item->RoleName;
                    $data['Role']['CodeName'] = $item->RoleCodeName;

                    //Now get doc tor schedule shift detail with respect to loops id

                    error_log('forum topic id is : ' . $item->Id);

                    $tagData = ForumModel::getTagsViaTopicForumId($item->Id);
                    if (count($tagData) > 0) {
                        error_log('tag list found');
                        $data['Tags'] = $tagData;
                    } else {
                        error_log('tag list not found');
                        $data['Tags'] = array();
                    }

                    array_push($forumListData, $data);
                }

                return response()->json(['data' => $forumListData, 'message' => 'Forum topic list found'], 200);

            } else {
                return response()->json(['data' => $forumListData, 'message' => 'Forum topic list not found'], 200);
            }
        }
    }

    function GetForumTopicListCount(Request $request)
    {
        $userId = $request->get('userId');

        $forumListData = array();

        //First check logged in user data if it is valid or not
        $checkUserData = UserModel::GetSingleUserViaId($userId);

        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('logged in user data found');

            //Now check if forum exists.
            //If exists fetched the record

            $getForumTopicData = ForumModel::getForumTopicListCount();

            return response()->json(['data' => $getForumTopicData, 'message' => 'Total count'], 200);
        }
    }

    function DeleteForumTopic(Request $request)
    {
        error_log('in controller');

        $forumTopicId = $request->get('forumTopicId');
        $userId = $request->get('UserId');

        //First check if logged if user id is valid or not

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

                //Now making data to update forum_topic table

                $date = HelperModel::getDate();

                $forumTopicDataToUpdate = array(
                    "IsActive" => false,
                    "UpdatedBy" => $userId,
                    "UpdatedOn" => $date["timestamp"]
                );

                $update = GenericModel::updateGeneric('forum_topic', 'Id', $forumTopicId, $forumTopicDataToUpdate);
                if ($update == false) {
                    return response()->json(['data' => null, 'message' => 'Error in deleting forum topic'], 400);
                } else {
                    return response()->json(['data' => $forumTopicId, 'message' => 'Forum topic successfully deleted'], 200);
                }
            }
        }
    }

    function AddForumTopicComment(Request $request)
    {
        error_log('in controller');

        $forumCommentId = $request->input('ForumTopicCommentId');
        $forumTopicId = $request->input('ForumTopicId');
        $userId = $request->input('UserId');

        $comment = $request->input('Comment');

        //First check if logged if user id is valid or not

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
                //Now making data to insert forum_topic_comment table

                $date = HelperModel::getDate();

                $forumTopicCommentData = array(
                    "ForumTopicId" => $forumTopicId,
                    "Comment" => $comment,
                    "UserId" => $userId,
                    "IsActive" => true,
                    "CreatedBy" => $userId,
                    "CreatedOn" => $date["timestamp"]
                );

                $insertedData = GenericModel::insertGenericAndReturnID('forum_topic_comment', $forumTopicCommentData);
                if ($insertedData == false) {
                    return response()->json(['data' => null, 'message' => 'Error in adding comment'], 400);
                } else {
                    return response()->json(['data' => $insertedData, 'message' => 'Comment given successfully'], 200);
                }
            }
        }
    }

    function UpdateForumTopicComment(Request $request)
    {
        error_log('in controller');

        $forumCommentId = $request->input('ForumTopicCommentId');
        $userId = $request->input('UserId');

        $comment = $request->input('Comment');

        //First check if logged if user id is valid or not

        $checkUserData = UserModel::GetSingleUserViaId($userId);

        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('logged in user data found');
//            fetch the single comment to check if this comment exists or not

            $getComment = ForumModel::getSingleCommentViaCommentId($forumCommentId);

            if ($getComment == null) {
                error_log('comment not found');
                return response()->json(['data' => null, 'message' => 'Comment not found'], 400);
            } else {
                error_log('comment found');
                //Now making data to update forum_topic_comment table

                if ($getComment->UserId != $userId) {
                    return response()->json(['data' => null, 'message' => 'This comment is not given by you'], 400);
                }

                $date = HelperModel::getDate();

                $dataToUpdate = array(
                    "Comment" => $comment,
                    "UserId" => $userId,
                    "UpdatedBy" => $userId,
                    "UpdatedOn" => $date["timestamp"]
                );

                $update = GenericModel::updateGeneric('forum_topic_comment', 'Id', $forumCommentId, $dataToUpdate);

                if ($update == false) {
                    return response()->json(['data' => null, 'message' => 'Error in updating comment'], 400);
                } else {
                    return response()->json(['data' => $forumCommentId, 'message' => 'Comment updated successfully'], 200);
                }
            }
        }
    }

    function DeleteForumTopicComment(Request $request)
    {
        error_log('in controller');

        $forumCommentId = $request->get('forumTopicCommentId');
        $userId = $request->get('userId');

        //First check if logged if user id is valid or not

        $checkUserData = UserModel::GetSingleUserViaId($userId);

        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('logged in user data found');

            //Now fetch the single comment to check if this comment exists or not

            $getComment = ForumModel::getSingleCommentViaCommentId($forumCommentId);

            if ($getComment == null) {
                return response()->json(['data' => null, 'message' => 'Comment not found'], 400);
            } else {
                //Now making data to update forum_topic_comment table

                if ($getComment->UserId != $userId) {
                    return response()->json(['data' => null, 'message' => 'This comment is not given by you'], 400);
                }

                $date = HelperModel::getDate();

                $dataToUpdate = array(
                    "IsActive" => false,
                    "UpdatedBy" => $userId,
                    "UpdatedOn" => $date["timestamp"]
                );

                $update = GenericModel::updateGeneric('forum_topic_comment', 'Id', $forumCommentId, $dataToUpdate);

                if ($update == false) {
                    return response()->json(['data' => null, 'message' => 'Error in deleting comment'], 400);
                } else {
                    return response()->json(['data' => $forumCommentId, 'message' => 'Comment deleted successfully'], 200);
                }
            }
        }
    }

    function GetSingleForumTopicComment(Request $request)
    {
        error_log('in controller');

        $forumCommentId = $request->get('forumTopicCommentId');
        $userId = $request->get('userId');

        //First check if logged if user id is valid or not

        $checkUserData = UserModel::GetSingleUserViaId($userId);

        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'logged in user not found'], 400);
        } else {
            error_log('logged in user data found');

            //Now fetch the single comment to check if this comment exists or not

            $getComment = ForumModel::getSingleCommentViaCommentId($forumCommentId);

            if ($getComment == null) {
                return response()->json(['data' => null, 'message' => 'Comment not found'], 200);
            } else {

                $data['Id'] = $getComment->Id;
                $data['Comment'] = $getComment->Comment;
                $data['Vote'] = $getComment->Vote;
                $data['CreatedOn'] = ForumModel::calculateTopicAnCommentTime($getComment->CreatedOn);

                $data['CreatedBy'] = array();
                $data['Role'] = array();

                $data['CreatedBy']['Id'] = $getComment->CreatedBy;
                $data['CreatedBy']['FirstName'] = $getComment->FirstName;
                $data['CreatedBy']['LastName'] = $getComment->LastName;

                $data['Role']['Id'] = $getComment->RoleId;
                $data['Role']['Name'] = $getComment->RoleName;
                $data['Role']['CodeName'] = $getComment->RoleCodeName;

                return response()->json(['data' => $data, 'message' => 'Comment found'], 200);
            }
        }
    }

    function GetForumTopicCommentsViaPagination(Request $request)
    {
        error_log('in controller');

        $forumTopicId = $request->get('forumTopicId');
        $userId = $request->get('userId');
        $pageNo = $request->get('pageNo');
        $limit = $request->get('limit');

        //First check if logged if user id is valid or not

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

                //Now fetch the list of comment to check if this comment exists or not

                $getComment = ForumModel::getCommentsViaTopicForumId($pageNo, $limit, $forumTopicId);

                $commentsData = array();

                if (count($getComment) > 0) {
                    error_log('comments found');
                    foreach ($getComment as $item) {

                        $data = array(
                            "Id" => $item->Id,
                            "Comment" => $item->Comment,
                            "CreatedOn" => ForumModel::calculateTopicAnCommentTime($item->CreatedOn),
                            "Vote" => $item->Vote,
                            "CreatedBy" => array(),
                            "Role" => array()
                        );

                        $data['CreatedBy']['Id'] = $item->CreatedBy;
                        $data['CreatedBy']['FirstName'] = $item->FirstName;
                        $data['CreatedBy']['LastName'] = $item->LastName;

                        $data['Role']['Id'] = $item->RoleId;
                        $data['Role']['Name'] = $item->RoleName;
                        $data['Role']['CodeName'] = $item->RoleCodeName;

                        array_push($commentsData, $data);

                    }

                    return response()->json(['data' => $commentsData, 'message' => 'Comment found'], 200);
                } else {
                    return response()->json(['data' => null, 'message' => 'Comment not found'], 200);
                }
            }
        }
    }

    function GetForumTopicCommentsCount(Request $request)
    {
        error_log('in controller');

        $forumTopicId = $request->get('forumTopicId');
        $userId = $request->get('userId');

        //First check if logged if user id is valid or not

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

                //Now fetch the list of comment to check if this comment exists or not

                $getCommentCount = ForumModel::getCommentsCountViaTopicForumId($forumTopicId);

                return response()->json(['data' => $getCommentCount, 'message' => 'Total count'], 200);
            }
        }
    }
}
