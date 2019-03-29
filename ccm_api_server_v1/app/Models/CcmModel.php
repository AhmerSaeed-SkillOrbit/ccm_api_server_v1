<?php
/**
 * Created by PhpStorm.
 * User: Ahsan Vohra
 * Date: 3/25/2019
 * Time: 7:51 PM
 */

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HelperModel;


class CcmModel
{
    static public function getQuestionList()
    {
        error_log('in model, fetching question list');

        $query = DB::table('ccm_question')
            ->select('Id', 'Question', 'Type')
            ->where('IsActive', '=', true)
            ->orderBy('Id', 'desc')
            ->get();

        return $query;
    }

    static public function getQuestionViaId($questionId)
    {
        error_log('in model, fetching question list');

        $query = DB::table('ccm_question')
            ->select('Id', 'Question', 'Type')
            ->where('IsActive', '=', true)
            ->where('Id', '=', $questionId)
            ->first();

        return $query;
    }

    static public function getAnswersViaQuestionIdAndPatientId($questionId, $patientId)
    {
        error_log('in model, fetching all question and answers of patient');

        $query = DB::table('ccm_answer')
            ->where('ccm_answer.IsActive', '=', true)
            ->where('ccm_answer.PatientId', '=', $patientId)
            ->where('ccm_answer.CcmQuestionId', '=', $questionId)
            ->get();

        return $query;
    }

    static public function getSingleAnswer($id)
    {
        error_log('in model, fetching single answer');

        $query = DB::table('ccm_answer')
            ->where('IsActive', '=', true)
            ->where('Id', '=', $id)
            ->first();

        return $query;
    }

    static public function getSingleActiveMedicine($id)
    {
        error_log('in model, fetching single active medicine');

        $query = DB::table('ccm_active_medicine')
            ->where('IsActive', '=', true)
            ->where('Id', '=', $id)
            ->first();

        return $query;
    }

    static public function getAllActiveMedicineViaPatientId($id)
    {
        error_log('in model, fetching all active medicine');

        $query = DB::table('ccm_active_medicine')
            ->where('IsActive', '=', true)
            ->where('PatientId', '=', $id)
            ->get();

        return $query;
    }

    static public function getSingleAllergy($id)
    {
        error_log('in model, fetching single allergy');

        $query = DB::table('ccm_medicine_allergy')
            ->where('IsActive', '=', true)
            ->where('Id', '=', $id)
            ->first();

        return $query;
    }

    static public function getAllAllergiesViaPatientId($id)
    {
        error_log('in model, fetching all allergies');

        $query = DB::table('ccm_medicine_allergy')
            ->where('IsActive', '=', true)
            ->where('PatientId', '=', $id)
            ->get();

        return $query;
    }

    static public function getSingleNonMedicine($id)
    {
        error_log('in model, fetching single non active medicine');

        $query = DB::table('ccm_non_medicine')
            ->where('IsActive', '=', true)
            ->where('Id', '=', $id)
            ->first();

        return $query;
    }

    static public function getAllNonMedicinesViaPatientId($id)
    {
        error_log('in model, fetching all non active medicine');

        $query = DB::table('ccm_non_medicine')
            ->where('IsActive', '=', true)
            ->where('PatientId', '=', $id)
            ->get();

        return $query;
    }

    static public function getSingleImmunizationVaccine($id)
    {
        error_log('in model, fetching single immunization vaccine');

        $query = DB::table('ccm_immunization_vaccine')
            ->where('IsActive', '=', true)
            ->where('Id', '=', $id)
            ->first();

        return $query;
    }

    static public function getAllImmunizationVaccineViaPatientId($id)
    {
        error_log('in model, fetching all immunization vaccine');

        $query = DB::table('ccm_immunization_vaccine')
            ->where('IsActive', '=', true)
            ->where('PatientId', '=', $id)
            ->get();

        return $query;
    }

    static public function getSingleHealthCareHistory($id)
    {
        error_log('in model, fetching single health care history');

        $query = DB::table('ccm_healthcare_history')
            ->where('IsActive', '=', true)
            ->where('Id', '=', $id)
            ->first();

        return $query;
    }

    static public function getAllHealthCareHistoryViaPatientId($id)
    {
        error_log('in model, fetching all health care history');

        $query = DB::table('ccm_healthcare_history')
            ->where('IsActive', '=', true)
            ->where('PatientId', '=', $id)
            ->get();

        return $query;
    }

    static public function getAllAssistanceOrganizationViaAssistanceType($assistanceTypeId)
    {
        error_log('in model, fetching all assistance organization');

        $query = DB::table('assistance_organization')
            ->where('IsActive', '=', true)
            ->where('AssistanceTypeId', '=', $assistanceTypeId)
            ->get();

        return $query;
    }


    static public function getAllAssistanceType()
    {
        error_log('in model, fetching all assistance type');

        $query = DB::table('assistance_type')
            ->where('IsActive', '=', true)
            ->get();

        return $query;
    }

    static public function getSinglePatientOrganizationAssistance($id)
    {
        error_log('in model, fetching single patient organization assistance');

        $query = DB::table('patient_organization_assistance')
            ->leftjoin('assistance_organization as assistance_organization', 'patient_organization_assistance.AssistanceOrganizationId', 'assistance_organization.Id')
            ->leftjoin('assistance_type as assistance_type', 'assistance_organization.AssistanceTypeId', 'assistance_type.Id')
            ->select('patient_organization_assistance.Id as poaID', 'patient_organization_assistance.Organization as poaOrganization',
                'patient_organization_assistance.TelephoneNumber as poaTelephoneNumber', 'patient_organization_assistance.OfficeAddress as poaOfficeAddress',
                'patient_organization_assistance.ContactPerson as poaContactPerson', 'patient_organization_assistance.Description as poaDescription',
                'patient_organization_assistance.IsPatientRefused as poaIsPatientRefused',
                //assistance organization data
                'assistance_organization.Id as aoId',
                'assistance_organization.Organization as aoOrganization', 'assistance_organization.OfficeAddress as aoOfficeAddress',
                'assistance_organization.ContactPerson as aoContactPerson', 'assistance_organization.Description as aoDescription',
                //Assistance organization type data
                'assistance_type.Id as atId', 'assistance_type.Type as atType', 'assistance_type.Description as atOrganization'
            )
            ->where('patient_organization_assistance.IsActive', '=', true)
            ->where('patient_organization_assistance.Id', '=', $id)
            ->first();

        return $query;
    }

    static public function getAllPatientOrganizationAssistanceViaPatientId($id)
    {
        error_log('in model, fetching all patient organization assistance');

        $query = DB::table('patient_organization_assistance')
            ->leftjoin('assistance_organization as assistance_organization', 'patient_organization_assistance.AssistanceOrganizationId', 'assistance_organization.Id')
            ->leftjoin('assistance_type as assistance_type', 'assistance_organization.AssistanceTypeId', 'assistance_type.Id')
            ->select('patient_organization_assistance.Id as poaID', 'patient_organization_assistance.Organization as poaOrganization',
                'patient_organization_assistance.TelephoneNumber as poaTelephoneNumber', 'patient_organization_assistance.OfficeAddress as poaOfficeAddress',
                'patient_organization_assistance.ContactPerson as poaContactPerson', 'patient_organization_assistance.Description as poaDescription',
                'patient_organization_assistance.IsPatientRefused as poaIsPatientRefused',
                //assistance organization data
                'assistance_organization.Id as aoId',
                'assistance_organization.Organization as aoOrganization', 'assistance_organization.OfficeAddress as aoOfficeAddress',
                'assistance_organization.ContactPerson as aoContactPerson', 'assistance_organization.Description as aoDescription',
                //Assistance organization type data
                'assistance_type.Id as atId', 'assistance_type.Type as atType', 'assistance_type.Description as atOrganization'
            )
            ->where('patient_organization_assistance.IsActive', '=', true)
            ->where('patient_organization_assistance.PatientId', '=', $id)
            ->get();

        return $query;
    }

}
