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
            ->first();

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

    static public function getSingleHospitalizationHistory($id)
    {
        error_log('in model, fetching single hospitalization history');

        $query = DB::table('ccm_hospitalization_history')
            ->where('IsActive', '=', true)
            ->where('Id', '=', $id)
            ->first();

        return $query;
    }

    static public function getAllHospitalizationHistoryViaPatientId($id)
    {
        error_log('in model, fetching all hospitalization history');

        $query = DB::table('ccm_hospitalization_history')
            ->where('IsActive', '=', true)
            ->where('PatientId', '=', $id)
            ->get();

        return $query;
    }


    static public function getSingleSurgeryHistory($id)
    {
        error_log('in model, fetching single surgery history');

        $query = DB::table('ccm_surgery_history')
            ->where('IsActive', '=', true)
            ->where('Id', '=', $id)
            ->first();

        return $query;
    }

    static public function getAllSurgeryHistoryViaPatientId($id)
    {
        error_log('in model, fetching all surgery history');

        $query = DB::table('ccm_surgery_history')
            ->where('IsActive', '=', true)
            ->where('PatientId', '=', $id)
            ->get();

        return $query;
    }

    static public function GetSinglePatientDiabeticMeasure($patientId)
    {
        $query = DB::table('patient_diabetic_measure')
            ->leftjoin('diabetic_measure_param as diabetic_measure_param', 'patient_diabetic_measure.DiabeticMeasureParamId', 'diabetic_measure_param.Id')
            ->select('patient_diabetic_measure.Id as pdmId', 'patient_diabetic_measure.IsPatientMeasure', 'patient_diabetic_measure.Description as pdmDescription',
                'patient_diabetic_measure.IsActive as pdmIsActive',
                'diabetic_measure_param.Id as dmpId', 'diabetic_measure_param.Name', 'diabetic_measure_param.Description as dmpDescription'
            )
            ->where('patient_diabetic_measure.IsActive', '=', true)
            ->where('patient_diabetic_measure.PatientId', '=', $patientId)
            ->first();

        return $query;
    }

    static public function GetSinglePatientFunctionalReview($patientId)
    {
        $query = DB::table('patient_functional_review')
            ->leftjoin('functional_review_param as functional_review_param', 'patient_functional_review.FunctionalReviewParamId', 'functional_review_param.Id')
            ->select('patient_functional_review.Id as ptrId', 'patient_functional_review.IsOkay', 'patient_functional_review.IsActive as ptrIsActive',
                'patient_functional_review.Description as ptrDescription',
                'functional_review_param.Id as frpId', 'functional_review_param.Name', 'functional_review_param.Description as frpDescription'
            )
            ->where('patient_functional_review.IsActive', '=', true)
            ->where('patient_functional_review.PatientId', '=', $patientId)
            ->first();

        return $query;
    }


    static public function GetSinglePatientOrgnizationAssistanceViaPatientId($patientId)
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
            ->where('patient_organization_assistance.PatientId', '=', $patientId)
            ->first();

        return $query;
    }

    static public function GetSinglePatientScreenExamination($patientId)
    {
        $query = DB::table('patient_prevent_screening_examination')
            ->leftjoin('prevent_screening_examination_param as prevent_screening_examination_param', 'patient_prevent_screening_examination.PreventScreeningParamId', 'prevent_screening_examination_param.Id')
            ->select('patient_prevent_screening_examination.Id as ppseId', 'patient_prevent_screening_examination.IsPatientExamined',
                'patient_prevent_screening_examination.Description as ppseDescription', 'patient_prevent_screening_examination.IsActive as ppseIsActive',
                'prevent_screening_examination_param.Id as psepId', 'prevent_screening_examination_param.Name',
                'prevent_screening_examination_param.Description as psepDescription'
            )
            ->where('patient_prevent_screening_examination.IsActive', '=', true)
            ->where('patient_prevent_screening_examination.PatientId', '=', $patientId)
            ->first();

        return $query;
    }

    static public function GetSinglePatientPsychologicalReview($patientId)
    {
        $query = DB::table('patient_psychological_review')
            ->leftjoin('psychological_review_param as psychological_review_param', 'patient_psychological_review.PsychologicalReviewParamId', 'psychological_review_param.Id')
            ->select('patient_psychological_review.Id as ppsId', 'patient_psychological_review.IsOkay',
                'patient_psychological_review.Description as ppsDescription', 'patient_psychological_review.IsActive as ppsIsActive',
                'psychological_review_param.Id as prpId', 'psychological_review_param.Name',
                'psychological_review_param.Description as prpDescription'
            )
            ->where('patient_psychological_review.IsActive', '=', true)
            ->where('patient_psychological_review.PatientId', '=', $patientId)
            ->first();

        return $query;
    }

    static public function GetSinglePatientSocialReview($patientId)
    {
        $query = DB::table('patient_social_review')
            ->leftjoin('social_review_param as social_review_param', 'patient_social_review.SocialReviewParamId', 'social_review_param.Id')
            ->select('patient_social_review.Id as psrId', 'patient_social_review.IsOkay',
                'patient_social_review.Description as psrDescription', 'patient_social_review.IsActive as psrIsActive',
                'social_review_param.Id as srpId', 'social_review_param.Name',
                'social_review_param.Description as srpDescription'
            )
            ->where('patient_social_review.IsActive', '=', true)
            ->where('patient_social_review.PatientId', '=', $patientId)
            ->first();

        return $query;
    }


    static public function GetTotalCcmPlans()
    {
        $query = DB::table('ccm_plan')
            ->count();

        return $query;
    }

    static public function GetSinglePatientCcmPlanViaId($id)
    {
        $query = DB::table('ccm_plan')
            ->select('ccm_plan.*')
            ->where('ccm_plan.IsActive', '=', true)
            ->where('ccm_plan.Id', '=', $id)
            ->first();

        return $query;
    }

    static public function GetSinglePatientCcmPlanViaPatientId($patientId)
    {
        $query = DB::table('ccm_plan')
            ->select('ccm_plan.*')
            ->where('ccm_plan.IsActive', '=', true)
            ->where('ccm_plan.PatientId', '=', $patientId)
            ->get();

        return $query;
    }

    static public function GetCcmPlanGoalsViaCcmPLanId($ccmPlanId)
    {
        $query = DB::table('ccm_plan_goal')
            ->where('IsActive', '=', true)
            ->where('CcmPlanId', '=', $ccmPlanId)
            ->get();

        return $query;
    }


    static public function CheckIfCcmPlanAlreadyExists($patientId, $startDate)
    {
        $query = DB::table('ccm_plan')
            ->where('IsActive', '=', true)
            ->where('PatientId', '=', $patientId)
            ->where('StartDate', '=', $startDate)
            ->first();

        return $query;
    }

    static public function GetPatientCcmPlanHealthParamViaCcmPlanId($ccmPlanId)
    {
        $query = DB::table('ccm_plan_initial_health')
            ->leftjoin('ccm_health_param as ccm_health_param', 'ccm_plan_initial_health.CcmHealthParamId', 'ccm_health_param.Id')
            ->select('ccm_plan_initial_health.Id as cpihId', 'ccm_plan_initial_health.ReadingValue', 'ccm_plan_initial_health.ReadingDate',
                'ccm_health_param.Id as chpId', 'ccm_health_param.Name', 'ccm_health_param.Description')
            ->where('ccm_plan_initial_health.IsActive', '=', true)
            ->where('ccm_plan_initial_health.CcmPlanId', '=', $ccmPlanId)
            ->get();

        return $query;
    }
}
