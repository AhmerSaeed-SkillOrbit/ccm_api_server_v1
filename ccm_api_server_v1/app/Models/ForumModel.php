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
            ->where('Id', '=', $forumTopicId)
            ->where('IsActive', '=', true)
            ->first();

        return $query;
    }

}
