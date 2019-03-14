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
            ->where('ForumTopicId', '=', $topicForumId)
            ->get();

        return $query;
    }

    static public function getForumTopicViaId($forumTopicId)
    {
        $query = DB::table('forum_topic')
            ->where('Id', '=', $forumTopicId)
            ->where('IsActive', '=', true)
            ->first();

        return $query;
    }

}