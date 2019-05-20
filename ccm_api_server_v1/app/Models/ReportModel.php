<?php
/**
 * Created by PhpStorm.
 * User: SO-LPT-031
 * Date: 5/18/2019
 * Time: 2:41 PM
 */

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HelperModel;

class ReportModel
{

    static public function getMultipleUsersViaPagination($userIds, $pageNo, $limit, $searchStartDate, $searchEndDate)
    {
        if ($searchStartDate != "null" && $searchEndDate != "null") {

            error_log('search date is not null');

            $result = DB::table('user')
                ->leftjoin('patient_type', 'patient_type.Id', '=', 'user.PatientTypeId')
                ->select('user.Id', 'user.PatientUniqueId', 'user.FirstName', 'user.LastName', 'user.MiddleName', 'user.DateOfBirth',
                    'user.CreatedOn', 'user.RegisteredAs', 'patient_type.Id as PatientTypeId', 'patient_type.Code', 'patient_type.Name')
                ->whereIn('Id', $userIds)
                ->where('IsActive', '=', true)
                ->where('CreatedOn', '>=', $searchStartDate)
                ->where('CreatedOn', '<=', $searchEndDate)
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
            return $result;
        } else {
            error_log('search date is null');
            $result = DB::table('user')
                ->leftjoin('patient_type', 'patient_type.Id', '=', 'user.PatientTypeId')
                ->select('user.Id', 'user.PatientUniqueId', 'user.FirstName', 'user.LastName', 'user.MiddleName', 'user.DateOfBirth',
                    'user.CreatedOn', 'user.RegisteredAs', 'patient_type.Id as PatientTypeId', 'patient_type.Code', 'patient_type.Name')
                ->whereIn('Id', $userIds)
                ->where('IsActive', '=', true)
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
            return $result;
        }
    }

    static public function getMultipleUsersViaPaginationAndCcmCptCode($userIds, $pageNo, $limit, $searchStartDate, $searchEndDate, $ccmCptIds)
    {
        if ($searchStartDate != "null" && $searchEndDate != "null") {

            error_log('search date is not null');

            $result = DB::table('user')
                ->leftjoin('patient_ccm_cpt_option', 'patient_ccm_cpt_option.PatientId', '=', 'user.Id')
                ->select('user.Id', 'user.PatientUniqueId', 'user.FirstName', 'user.LastName', 'user.MiddleName', 'user.DateOfBirth',
                    'user.CreatedOn', 'user.RegisteredAs')
                ->whereIn('user.Id', $userIds)
                ->whereIn('patient_ccm_cpt_option.CcmCptOptionId', $ccmCptIds)
                ->where('user.IsActive', '=', true)
                ->where('user.CreatedOn', '>=', $searchStartDate)
                ->where('user.CreatedOn', '<=', $searchEndDate)
                ->groupBy('user.Id')
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
            return $result;
        } else {
            error_log('search date is null');
            $result = DB::table('user')
                ->leftjoin('patient_ccm_cpt_option', 'patient_ccm_cpt_option.PatientId', '=', 'user.Id')
                ->select('user.Id', 'user.PatientUniqueId', 'user.FirstName', 'user.LastName', 'user.MiddleName', 'user.DateOfBirth',
                    'user.CreatedOn', 'user.RegisteredAs')
                ->whereIn('user.Id', $userIds)
                ->whereIn('patient_ccm_cpt_option.CcmCptOptionId', $ccmCptIds)
                ->where('user.IsActive', '=', true)
                ->groupBy('user.Id')
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
            return $result;
        }
    }

    static public function getMultipleUsersViaPaginationAndPatientType($userIds, $pageNo, $limit, $searchStartDate, $searchEndDate, $patientTypeIds)
    {
        if ($searchStartDate != "null" && $searchEndDate != "null") {

            error_log('search date is not null');

            $result = DB::table('user')
                ->leftjoin('patient_type', 'patient_type.Id', '=', 'user.PatientTypeId')
                ->select('user.Id', 'user.PatientUniqueId', 'user.FirstName', 'user.LastName', 'user.MiddleName', 'user.DateOfBirth',
                    'user.CreatedOn', 'user.RegisteredAs', 'patient_type.Id as PatientTypeId', 'patient_type.Code', 'patient_type.Name')
                ->whereIn('user.Id', $userIds)
                ->whereIn('user.PatientTypeId', $patientTypeIds)
                ->where('user.IsActive', '=', true)
                ->where('user.CreatedOn', '>=', $searchStartDate)
                ->where('user.CreatedOn', '<=', $searchEndDate)
                ->groupBy('user.Id')
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
            return $result;
        } else {
            error_log('search date is null');
            $result = DB::table('user')
                ->leftjoin('patient_type', 'patient_type.Id', '=', 'user.PatientTypeId')
                ->select('user.Id', 'user.PatientUniqueId', 'user.FirstName', 'user.LastName', 'user.MiddleName', 'user.DateOfBirth',
                    'user.CreatedOn', 'user.RegisteredAs', 'patient_type.Id as PatientTypeId', 'patient_type.Code', 'patient_type.Name')
                ->whereIn('user.Id', $userIds)
                ->whereIn('user.PatientTypeId', $patientTypeIds)
                ->where('user.IsActive', '=', true)
                ->groupBy('user.Id')
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
            return $result;
        }
    }

    static public function getMultipleUsersCountViaPatientType($userIds, $searchStartDate, $searchEndDate, $patientTypeIds)
    {
        if ($searchStartDate != "null" && $searchEndDate != "null") {

            error_log('search date is not null');

            $result = DB::table('user')
                ->whereIn('user.Id', $userIds)
                ->whereIn('user.PatientTypeId', $patientTypeIds)
                ->where('user.IsActive', '=', true)
                ->where('user.CreatedOn', '>=', $searchStartDate)
                ->where('user.CreatedOn', '<=', $searchEndDate)
                ->count();
            return $result;
        } else {
            error_log('search date is null');
            $result = DB::table('user')
                ->whereIn('user.Id', $userIds)
                ->whereIn('user.PatientTypeId', $patientTypeIds)
                ->where('user.IsActive', '=', true)
                ->count();
            return $result;
        }
    }

    static public function getMultipleUsersViaCcmCptCodeCount($userIds, $searchStartDate, $searchEndDate, $ccmCptIds)
    {
        error_log('here in ccm cpt code count model');
        if ($searchStartDate != "null" && $searchEndDate != "null") {

            error_log('search date is not null');

            $result = DB::table('user')
                ->leftjoin('patient_ccm_cpt_option', 'patient_ccm_cpt_option.PatientId', '=', 'user.Id')
                ->whereIn('user.Id', $userIds)
                ->whereIn('patient_ccm_cpt_option.CcmCptOptionId', $ccmCptIds)
                ->where('user.IsActive', '=', true)
                ->where('user.CreatedOn', '>=', $searchStartDate)
                ->where('user.CreatedOn', '<=', $searchEndDate)
                ->groupBy('user.Id')
                ->get();
            return $result;
        } else {
            error_log('search date is null');
            $result = DB::table('user')
                ->leftjoin('patient_ccm_cpt_option', 'patient_ccm_cpt_option.PatientId', '=', 'user.Id')
                ->whereIn('user.Id', $userIds)
                ->whereIn('patient_ccm_cpt_option.CcmCptOptionId', $ccmCptIds)
                ->where('user.IsActive', '=', true)
                ->groupBy('user.Id')
                ->get();
            return $result;
        }
    }

    static public function getMultipleUsersCount($userIds, $searchStartDate, $searchEndDate)
    {
        if ($searchStartDate != "null" && $searchEndDate != "null") {

            error_log('search date is not null');

            $result = DB::table('user')
                ->whereIn('Id', $userIds)
                ->where('IsActive', '=', true)
                ->where('CreatedOn', '>=', $searchStartDate)
                ->where('CreatedOn', '<=', $searchEndDate)
                ->count();
            return $result;
        } else {
            error_log('search date is null');
            $result = DB::table('user')
                ->whereIn('Id', $userIds)
                ->where('IsActive', '=', true)
                ->count();
            return $result;
        }
    }

    static public function getUsersViaRegisteredAs($userIds, $registeredAs, $searchStartDate, $searchEndDate)
    {
        if ($searchStartDate != "null" && $searchEndDate != "null") {

            $result = DB::table('user')
                ->whereIn('Id', $userIds)
                ->where('IsActive', '=', true)
                ->where('CreatedOn', '>=', $searchStartDate)
                ->where('CreatedOn', '<=', $searchEndDate)
                ->where('RegisteredAs', '=', $registeredAs)
                ->get();
            return $result;
        } else {

            $result = DB::table('user')
                ->whereIn('Id', $userIds)
                ->where('IsActive', '=', true)
                ->where('RegisteredAs', '=', $registeredAs)
                ->get();
            return $result;
        }
    }

    static public function getUsersInvitationViaInvitedType($userId, $invitedVia, $searchStartDate, $searchEndDate)
    {
        error_log('in model ' . $invitedVia);

        if ($searchStartDate != "null" && $searchEndDate != "null") {

            $result = DB::table('account_invitation')
                ->where('ByUserId', '=', $userId)
                ->where('IsActive', '=', true)
                ->where('CreatedOn', '>=', $searchStartDate)
                ->where('CreatedOn', '<=', $searchEndDate)
                ->where('Status_', '=', $invitedVia)
                ->get();
            return $result;
        } else {

            $result = DB::table('account_invitation')
                ->where('ByUserId', '=', $userId)
                ->where('IsActive', '=', true)
                ->where('Status_', '=', $invitedVia)
                ->get();
            return $result;
        }
    }

    static public function getAllUsersInvitation($userId, $searchStartDate, $searchEndDate, $doctorPatientAssociation)
    {
        if ($searchStartDate != "null" && $searchEndDate != "null") {

            $result = DB::table('account_invitation')
                ->where('ByUserId', '=', $userId)
                ->where('IsActive', '=', true)
                ->where('CreatedOn', '>=', $searchStartDate)
                ->where('CreatedOn', '<=', $searchEndDate)
                ->where('BelongTo', '=', $doctorPatientAssociation)
                ->get();
            return $result;
        } else {

            $result = DB::table('account_invitation')
                ->where('ByUserId', '=', $userId)
                ->where('IsActive', '=', true)
                ->where('BelongTo', '=', $doctorPatientAssociation)
                ->get();
            return $result;
        }
    }

    static public function getMultipleUsersViaEmailAddressesAndPagination($emailAddresses, $pageNo, $limit)
    {

        error_log('in model');

        $result = DB::table('user')
            ->select('user.Id', 'user.PatientUniqueId', 'user.FirstName', 'user.LastName', 'user.MiddleName', 'user.DateOfBirth',
                'user.CreatedOn', 'user.RegisteredAs')
            ->whereIn('EmailAddress', $emailAddresses)
            ->where('IsActive', '=', true)
            ->skip($pageNo * $limit)
            ->take($limit)
            ->get();
        return $result;
    }

    static public function getMultipleUsersCountViaEmailAddreses($emailAddresses)
    {

        error_log('in model');

        $result = DB::table('user')
            ->whereIn('EmailAddress', $emailAddresses)
            ->where('IsActive', '=', true)
            ->count();
        return $result;
    }
}