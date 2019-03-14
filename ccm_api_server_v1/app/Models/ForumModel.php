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
}
