<?php
/**
 * Created by PhpStorm.
 * User: Ahsan Vohra
 * Date: 3/14/2019
 * Time: 9:41 PM
 */

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HelperModel;


class ForumModel
{

    static public function getTagList()
    {
        error_log('in model');

        $query = DB::table('tag')
            ->where('IsActive', '=', true)
            ->orderBy('Id', 'DESC')
            ->get();

        return $query;
    }

    static public function getTagsViaTopicForumId($topicForumId)
    {
        error_log('in model, fetching tags via id');

        $query = DB::table('forum_topic_tag')
            ->select('tag.Id', 'tag.Name', 'tag.Code', 'tag.ToolTip', 'tag.Description')
            ->leftjoin('tag as tag', 'forum_topic_tag.TagId', 'tag.Id')
            ->where('forum_topic_tag.ForumTopicId', '=', $topicForumId)
            ->where('tag.IsActive', '=', true)
            ->groupBy('forum_topic_tag.TagId')
            ->get();

        return $query;
    }

    static public function getForumTopicViaId($forumTopicId)
    {
        error_log('in model , fetching forum topic');

        $query = DB::table('forum_topic')
            ->leftjoin('user as user', 'forum_topic.CreatedBy', 'user.Id')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->select('forum_topic.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
            ->where('forum_topic.Id', '=', $forumTopicId)
            ->where('forum_topic.IsActive', '=', true)
            ->first();

        return $query;
    }

    static public function getForumCommentsViaForumTopicId($topicForumId)
    {
        error_log('in model, fetching tags via id');

        $query = DB::table('forum_topic_comment')
            ->select('forum_topic_comment.Id', 'forum_topic_comment.Comment',
                'forum_topic_comment.CreatedOn', 'forum_topic_comment.UpdatedOn')
            ->where('forum_topic_comment.ForumTopicId', '=', $topicForumId)
            ->where('forum_topic_comment.IsActive', '=', true)
            ->get();

        return $query;
    }

    static public function getForumTopicListViaPagination($pageNo, $limit)
    {
        error_log('in model , fetching forum topic');

        $query = DB::table('forum_topic')
            ->leftjoin('forum_topic_tag as ftg', 'ftg.ForumTopicId', 'forum_topic.Id')
            ->leftjoin('user as user', 'forum_topic.CreatedBy', 'user.Id')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->select('forum_topic.*', 'user.Id as CreatedById', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
            ->where('forum_topic.IsActive', '=', true)
            ->orderBy('forum_topic.Id', 'DESC')
            ->groupBy('forum_topic.Id')
            ->offset($pageNo)->limit($limit)
            ->get();

        return $query;
    }

    //function to convert time as now, 1 min ago
    static public function getFormattedTime($uTCTime)
    {

        error_log($uTCTime);


    }

    static public function getForumTopicListCount()
    {
        error_log('in model , fetching forum topic');

        $query = DB::table('forum_topic')
            ->leftjoin('forum_topic_tag as ftg', 'ftg.ForumTopicId', 'forum_topic.Id')
            ->where('forum_topic.IsActive', '=', true)
            ->count();

        return $query;
    }

    static public function getSingleCommentViaCommentId($commentId)
    {
        error_log('in model, fetching comment via comment id');

        $query = DB::table('forum_topic_comment')
            ->leftjoin('user as user', 'forum_topic_comment.CreatedBy', 'user.Id')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->select('forum_topic_comment.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
            ->where('forum_topic_comment.IsActive', '=', true)
            ->where('forum_topic_comment.Id', '=', $commentId)
            ->first();

        return $query;
    }

    static public function getCommentsViaTopicForumId($pageNo, $limit, $topicForumId)
    {
        error_log('in model, fetching comments via forum topic id');

        $query = DB::table('forum_topic_comment')
            ->leftjoin('user as user', 'forum_topic_comment.CreatedBy', 'user.Id')
            ->join('user_access', 'user_access.UserId', 'user.Id')
            ->join('role', 'user_access.RoleId', 'role.Id')
            ->select('forum_topic_comment.*', 'user.FirstName', 'user.LastName', 'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
            ->where('forum_topic_comment.IsActive', '=', true)
            ->where('forum_topic_comment.ForumTopicId', '=', $topicForumId)
            ->orderBy('forum_topic_comment.Id', 'ASC')
            ->skip($pageNo * $limit)
            ->take($limit)
            ->get();

        return $query;
    }

    static public function getCommentsCountViaTopicForumId($topicForumId)
    {
        error_log('in model, fetching comments via forum topic id');

        $query = DB::table('forum_topic_comment')
            ->where('IsActive', '=', true)
            ->where('ForumTopicId', '=', $topicForumId)
            ->count();

        return $query;
    }

    static public function calculateTopicAnCommentTime($createdOn)
    {
        $formatMessage = null;
//
//        $timestamp = $request->get('t');
//        error_log($timestamp);

        $topicCreatedTime = Carbon::createFromTimestamp($createdOn);
        $currentTime = Carbon::now("UTC");

        $diffInYears = $currentTime->diffInYears($topicCreatedTime);
        $diffInMonths = $currentTime->diffInMonths($topicCreatedTime);
        $diffInWeeks = $currentTime->diffInWeeks($topicCreatedTime);
        $diffInDays = $currentTime->diffInDays($topicCreatedTime);
        $diffInHours = $currentTime->diffInHours($topicCreatedTime);
        $diffInMints = $currentTime->diffInMinutes($topicCreatedTime);
        $diffInSec = $currentTime->diffInSeconds($topicCreatedTime);

        error_log($topicCreatedTime);
        error_log($currentTime);
        error_log($diffInYears);
        error_log($diffInMonths);
        error_log($diffInWeeks);
        error_log($diffInDays);
        error_log($diffInHours);
        error_log($diffInMints);
        error_log($diffInSec);

        if ($diffInYears > 0) {
            $formatMessage = $diffInYears . ' y ago';
            return $formatMessage;
        } else if ($diffInMonths > 0) {
            $formatMessage = $diffInMonths . ' mon ago';
            return $formatMessage;
        } else if ($diffInWeeks > 0) {
            $formatMessage = $diffInWeeks . ' w ago';
            return $formatMessage;
        } else if ($diffInDays > 0) {
            $formatMessage = $diffInDays . ' d ago';
            return $formatMessage;
        } else if ($diffInHours > 0) {
            $formatMessage = $diffInHours . ' h ago';
            return $formatMessage;
        } else if ($diffInMints > 0) {
            $formatMessage = $diffInMints . ' min ago';
            return $formatMessage;
        } else if ($diffInSec >= 30) {
            $formatMessage = $diffInMints . ' sec ago';
            return $formatMessage;
        } else {
            //seconds
            $formatMessage = 'Now';
            return $formatMessage;
        }
    }
}
