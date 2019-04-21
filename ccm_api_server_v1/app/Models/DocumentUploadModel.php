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

    static public function GetAllGeneralDocumentsForDoctors($createdByIds, $searchDateFrom, $searchDateTo, $searchKeyword, $pageNo, $limit)
    {
        $belongsToArray = array();

        array_push($belongsToArray, 'patient_attachment');
        array_push($belongsToArray, 'facilitator_attachment');
        array_push($belongsToArray, 'doctor_attachment');

        if ($searchDateFrom == "null" && $searchDateTo == "null" && $searchKeyword == "null") {
            error_log('search date and search keyword both are NULL');
            $query = DB::table('file_upload')
                ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                    'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                    'user.Id as UserId', 'user.FirstName', 'user.LastName',
                    'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                ->where('file_upload.IsActive', '=', true)
                ->whereIn('file_upload.ByUserId', $createdByIds)
                ->whereIn('file_upload.BelongTo', $belongsToArray)
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
        } else {
            error_log('Some search parameter is given');
            if ($searchDateFrom == "null" && $searchDateTo == "null" && $searchKeyword != "null") {
                error_log('Only search word is given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.Purpose', 'like', '%' . $searchKeyword . '%')
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->skip($pageNo * $limit)
                    ->take($limit)
                    ->get();

            } else if ($searchDateFrom != "null" && $searchDateTo != "null" && $searchKeyword == "null") {

                error_log('Only search date is given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.CreatedOn', '>=', $searchDateFrom)
                    ->Where('.file_upload.CreatedOn', '<=', $searchDateTo)
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->skip($pageNo * $limit)
                    ->take($limit)
                    ->get();

            } else if ($searchDateFrom != "null" && $searchDateTo != "null" && $searchKeyword != "null") {
                error_log('Both search are given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.Purpose', 'like', '%' . $searchKeyword . '%')
                    ->Where('.file_upload.CreatedOn', '>=', $searchDateFrom)
                    ->Where('.file_upload.CreatedOn', '<=', $searchDateTo)
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->skip($pageNo * $limit)
                    ->take($limit)
                    ->get();

            } else {
                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->skip($pageNo * $limit)
                    ->take($limit)
                    ->get();
            }
        }

        return $query;
    }

    static public function GetAllGeneralDocumentsForPatient($createdByIds, $searchDateFrom, $searchDateTo, $searchKeyword, $pageNo, $limit)
    {
        $belongsToArray = array();

        array_push($belongsToArray, 'patient_attachment');

        if ($searchDateFrom == "null" && $searchDateTo == "null" && $searchKeyword == "null") {
            error_log('search date and search keyword both are NULL');
            $query = DB::table('file_upload')
                ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                    'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                    'user.Id as UserId', 'user.FirstName', 'user.LastName',
                    'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                ->where('file_upload.IsActive', '=', true)
                ->whereIn('file_upload.ByUserId', $createdByIds)
                ->whereIn('file_upload.BelongTo', $belongsToArray)
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
        } else {
            error_log('Some search parameter is given');
            if ($searchDateFrom == "null" && $searchDateTo == "null" && $searchKeyword != "null") {
                error_log('Only search word is given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.Purpose', 'like', '%' . $searchKeyword . '%')
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->skip($pageNo * $limit)
                    ->take($limit)
                    ->get();

            } else if ($searchDateFrom != "null" && $searchDateTo != "null" && $searchKeyword == "null") {

                error_log('Only search date is given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.CreatedOn', '>=', $searchDateFrom)
                    ->Where('.file_upload.CreatedOn', '<=', $searchDateTo)
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->skip($pageNo * $limit)
                    ->take($limit)
                    ->get();

            } else if ($searchDateFrom != "null" && $searchDateTo != "null" && $searchKeyword != "null") {
                error_log('Both search are given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.Purpose', 'like', '%' . $searchKeyword . '%')
                    ->Where('.file_upload.CreatedOn', '>=', $searchDateFrom)
                    ->Where('.file_upload.CreatedOn', '<=', $searchDateTo)
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->skip($pageNo * $limit)
                    ->take($limit)
                    ->get();

            } else {
                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->skip($pageNo * $limit)
                    ->take($limit)
                    ->get();
            }
        }

        return $query;
    }

    static public function GetAllGeneralDocumentsForAdmin($searchDateFrom, $searchDateTo, $searchKeyword, $pageNo, $limit)
    {
        $belongsToArray = array();

        array_push($belongsToArray, 'patient_attachment');
        array_push($belongsToArray, 'facilitator_attachment');
        array_push($belongsToArray, 'doctor_attachment');

        if ($searchDateFrom == "null" && $searchDateTo == "null" && $searchKeyword == "null") {
            error_log('search date and search keyword both are NULL');
            $query = DB::table('file_upload')
                ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                    'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                    'user.Id as UserId', 'user.FirstName', 'user.LastName',
                    'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                ->where('file_upload.IsActive', '=', true)
                ->whereIn('file_upload.BelongTo', $belongsToArray)
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
        } else {
            error_log('Some search parameter is given');
            if ($searchDateFrom == "null" && $searchDateTo == "null" && $searchKeyword != "null") {
                error_log('Only search word is given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.Purpose', 'like', '%' . $searchKeyword . '%')
                    ->skip($pageNo * $limit)
                    ->take($limit)
                    ->get();

            } else if ($searchDateFrom != "null" && $searchDateTo != "null" && $searchKeyword == "null") {

                error_log('Only search date is given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.CreatedOn', '>=', $searchDateFrom)
                    ->Where('.file_upload.CreatedOn', '<=', $searchDateTo)
                    ->skip($pageNo * $limit)
                    ->take($limit)
                    ->get();

            } else if ($searchDateFrom != "null" && $searchDateTo != "null" && $searchKeyword != "null") {
                error_log('Both search are given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.Purpose', 'like', '%' . $searchKeyword . '%')
                    ->Where('.file_upload.CreatedOn', '>=', $searchDateFrom)
                    ->Where('.file_upload.CreatedOn', '<=', $searchDateTo)
                    ->skip($pageNo * $limit)
                    ->take($limit)
                    ->get();

            } else {
                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->skip($pageNo * $limit)
                    ->take($limit)
                    ->get();
            }
        }

        return $query;
    }

    static public function GetAllGeneralDocumentsForDoctorsCount($createdByIds, $searchDateFrom, $searchDateTo, $searchKeyword)
    {
        $belongsToArray = array();

        array_push($belongsToArray, 'patient_attachment');
        array_push($belongsToArray, 'facilitator_attachment');
        array_push($belongsToArray, 'doctor_attachment');

        if ($searchDateFrom == "null" && $searchDateTo == "null" && $searchKeyword == "null") {
            error_log('search date and search keyword both are NULL');
            $query = DB::table('file_upload')->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                    'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                    'user.Id as UserId', 'user.FirstName', 'user.LastName',
                    'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                ->where('file_upload.IsActive', '=', true)
                ->whereIn('file_upload.ByUserId', $createdByIds)
                ->whereIn('file_upload.BelongTo', $belongsToArray)
                ->get();
        } else {
            error_log('Some search parameter is given');
            if ($searchDateFrom == "null" && $searchDateTo == "null" && $searchKeyword != "null") {
                error_log('Only search word is given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.Purpose', 'like', '%' . $searchKeyword . '%')
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->get();

            } else if ($searchDateFrom != "null" && $searchDateTo != "null" && $searchKeyword == "null") {

                error_log('Only search date is given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.CreatedOn', '>=', $searchDateFrom)
                    ->Where('.file_upload.CreatedOn', '<=', $searchDateTo)
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->get();

            } else if ($searchDateFrom != "null" && $searchDateTo != "null" && $searchKeyword != "null") {
                error_log('Both search are given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.Purpose', 'like', '%' . $searchKeyword . '%')
                    ->Where('.file_upload.CreatedOn', '>=', $searchDateFrom)
                    ->Where('.file_upload.CreatedOn', '<=', $searchDateTo)
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->get();

            } else {
                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->get();
            }
        }

        return $query;
    }

    static public function GetAllGeneralDocumentsForPatientCount($createdByIds, $searchDateFrom, $searchDateTo, $searchKeyword)
    {
        $belongsToArray = array();

        array_push($belongsToArray, 'patient_attachment');

        if ($searchDateFrom == "null" && $searchDateTo == "null" && $searchKeyword == "null") {
            error_log('search date and search keyword both are NULL');
            $query = DB::table('file_upload')
                ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                    'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                    'user.Id as UserId', 'user.FirstName', 'user.LastName',
                    'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                ->where('file_upload.IsActive', '=', true)
                ->whereIn('file_upload.ByUserId', $createdByIds)
                ->whereIn('file_upload.BelongTo', $belongsToArray)
                ->get();
        } else {
            error_log('Some search parameter is given');
            if ($searchDateFrom == "null" && $searchDateTo == "null" && $searchKeyword != "null") {
                error_log('Only search word is given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.Purpose', 'like', '%' . $searchKeyword . '%')
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->get();

            } else if ($searchDateFrom != "null" && $searchDateTo != "null" && $searchKeyword == "null") {

                error_log('Only search date is given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.CreatedOn', '>=', $searchDateFrom)
                    ->Where('.file_upload.CreatedOn', '<=', $searchDateTo)
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->get();

            } else if ($searchDateFrom != "null" && $searchDateTo != "null" && $searchKeyword != "null") {
                error_log('Both search are given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.Purpose', 'like', '%' . $searchKeyword . '%')
                    ->Where('.file_upload.CreatedOn', '>=', $searchDateFrom)
                    ->Where('.file_upload.CreatedOn', '<=', $searchDateTo)
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->get();

            } else {
                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->whereIn('file_upload.ByUserId', $createdByIds)
                    ->get();
            }
        }

        return $query;
    }

    static public function GetAllGeneralDocumentsForAdminCount($searchDateFrom, $searchDateTo, $searchKeyword)
    {
        $belongsToArray = array();

        array_push($belongsToArray, 'patient_attachment');
        array_push($belongsToArray, 'facilitator_attachment');
        array_push($belongsToArray, 'doctor_attachment');

        if ($searchDateFrom == "null" && $searchDateTo == "null" && $searchKeyword == "null") {
            error_log('search date and search keyword both are NULL');
            $query = DB::table('file_upload')
                ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                    'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                    'user.Id as UserId', 'user.FirstName', 'user.LastName',
                    'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                ->where('file_upload.IsActive', '=', true)
                ->whereIn('file_upload.BelongTo', $belongsToArray)
                ->get();
        } else {
            error_log('Some search parameter is given');
            if ($searchDateFrom == "null" && $searchDateTo == "null" && $searchKeyword != "null") {
                error_log('Only search word is given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.Purpose', 'like', '%' . $searchKeyword . '%')
                    ->get();

            } else if ($searchDateFrom != "null" && $searchDateTo != "null" && $searchKeyword == "null") {

                error_log('Only search date is given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.CreatedOn', '>=', $searchDateFrom)
                    ->Where('.file_upload.CreatedOn', '<=', $searchDateTo)
                    ->get();

            } else if ($searchDateFrom != "null" && $searchDateTo != "null" && $searchKeyword != "null") {
                error_log('Both search are given');

                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->Where('.file_upload.Purpose', 'like', '%' . $searchKeyword . '%')
                    ->Where('.file_upload.CreatedOn', '>=', $searchDateFrom)
                    ->Where('.file_upload.CreatedOn', '<=', $searchDateTo)
                    ->get();

            } else {
                $query = DB::table('file_upload')
                    ->leftjoin('user as user', 'user.Id', 'file_upload.ByUserId')
                    ->leftjoin('user_access as user_access', 'user.Id', 'user_access.UserId')
                    ->leftjoin('role as role', 'role.Id', 'user_access.RoleId')
                    ->select('file_upload.Id as FileUploadId', 'file_upload.RelativePath', 'file_upload.FileName', 'file_upload.FileOriginalName',
                        'file_upload.FileExtension', 'file_upload.Purpose', 'file_upload.BelongTo', 'file_upload.CreatedOn',
                        'user.Id as UserId', 'user.FirstName', 'user.LastName',
                        'role.Id as RoleId', 'role.Name as RoleName', 'role.CodeName as RoleCodeName')
                    ->where('file_upload.IsActive', '=', true)
                    ->whereIn('file_upload.BelongTo', $belongsToArray)
                    ->get();
            }
        }

        return $query;
    }
}