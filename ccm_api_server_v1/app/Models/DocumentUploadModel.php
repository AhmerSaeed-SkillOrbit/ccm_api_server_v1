<?php
/**
 * Created by PhpStorm.
 * User: SO-LPT-031
 * Date: 4/16/2019
 * Time: 11:32 PM
 */

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HelperModel;


class DocumentUploadModel
{
    static public function GetDocumentData($documentUploadId)
    {
        $query = DB::table('file_upload')
            ->where('Id', '=', $documentUploadId)
            ->where('IsActive', '=', true)
            ->first();

        return $query;
    }
}