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

    static public function GetSinglePatientDiabeticMeasure($id)
    {
        $query = DB::table('patient_diabetic_measure')
            ->leftjoin('diabetic_measure_param as diabetic_measure_param', 'patient_diabetic_measure.DiabeticMeasureParamId', 'diabetic_measure_param.Id')
            ->select('patient_diabetic_measure.Id as pdmId', 'patient_diabetic_measure.IsPatientMeasure', 'patient_diabetic_measure.Description as pdmDescription',
                'patient_diabetic_measure.IsActive as pdmIsActive',
                'diabetic_measure_param.Id as dmpId', 'diabetic_measure_param.Name', 'diabetic_measure_param.Description as dmpDescription'
            )
            ->where('patient_diabetic_measure.IsActive', '=', true)
            ->where('patient_diabetic_measure.Id', '=', $id)
            ->first();

        return $query;
    }

    static public function GetPatientDiabeticMeasureAll($paramId, $patientId)
    {
        $query = DB::table('patient_diabetic_measure')
            ->leftjoin('diabetic_measure_param as diabetic_measure_param', 'patient_diabetic_measure.DiabeticMeasureParamId', 'diabetic_measure_param.Id')
            ->select('patient_diabetic_measure.Id as pdmId', 'patient_diabetic_measure.IsPatientMeasure', 'patient_diabetic_measure.Description as pdmDescription',
                'patient_diabetic_measure.IsActive as pdmIsActive',
                'diabetic_measure_param.Id as dmpId', 'diabetic_measure_param.Name', 'diabetic_measure_param.Description as dmpDescription'
            )
            ->where('patient_diabetic_measure.IsActive', '=', true)
            ->where('patient_diabetic_measure.PatientId', '=', $patientId)
            ->where('patient_diabetic_measure.DiabeticMeasureParamId', '=', $paramId)
            ->first();

        return $query;
    }

    static public function GetSinglePatientFunctionalReview($id, $patientId)
    {
        $query = DB::table('patient_functional_review')
            ->leftjoin('functional_review_param as functional_review_param', 'patient_functional_review.FunctionalReviewParamId', 'functional_review_param.Id')
            ->select('patient_functional_review.Id as ptrId', 'patient_functional_review.IsOkay', 'patient_functional_review.IsActive as ptrIsActive',
                'patient_functional_review.Description as ptrDescription',
                'functional_review_param.Id as frpId', 'functional_review_param.Name', 'functional_review_param.Description as frpDescription'
            )
            ->where('patient_functional_review.IsActive', '=', true)
            ->where('patient_functional_review.PatientId', '=', $patientId)
            ->where('patient_functional_review.Id', '=', $id)
            ->first();

        return $query;
    }

    static public function GetPatientFunctionalReviewAll($paramId, $patientId)
    {
        $query = DB::table('patient_functional_review')
            ->leftjoin('functional_review_param as functional_review_param', 'patient_functional_review.FunctionalReviewParamId', 'functional_review_param.Id')
            ->select('patient_functional_review.Id as ptrId', 'patient_functional_review.IsOkay', 'patient_functional_review.IsActive as ptrIsActive',
                'patient_functional_review.Description as ptrDescription',
                'functional_review_param.Id as frpId', 'functional_review_param.Name', 'functional_review_param.Description as frpDescription'
            )
            ->where('patient_functional_review.IsActive', '=', true)
            ->where('patient_functional_review.PatientId', '=', $patientId)
            ->where('patient_functional_review.FunctionalReviewParamId', '=', $paramId)
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

    static public function GetSinglePatientScreenExamination($id, $patientId)
    {
        $query = DB::table('patient_prevent_screening_examination')
            ->leftjoin('prevent_screening_examination_param as prevent_screening_examination_param', 'patient_prevent_screening_examination.PreventScreeningParamId', 'prevent_screening_examination_param.Id')
            ->select('patient_prevent_screening_examination.Id as ppseId', 'patient_prevent_screening_examination.IsPatientExamined',
                'patient_prevent_screening_examination.Description as ppseDescription', 'patient_prevent_screening_examination.IsActive as ppseIsActive',
                'prevent_screening_examination_param.Id as psepId', 'prevent_screening_examination_param.Name',
                'prevent_screening_examination_param.Description as psepDescription'
            )
            ->where('patient_prevent_screening_examination.IsActive', '=', true)
            ->where('patient_prevent_screening_examination.Id', '=', $id)
            ->where('patient_prevent_screening_examination.PatientId', '=', $patientId)
            ->first();

        return $query;
    }

    static public function GetSinglePatientScreenExaminationViaParamId($paramId, $patientId)
    {
        $query = DB::table('patient_prevent_screening_examination')
            ->leftjoin('prevent_screening_examination_param as prevent_screening_examination_param', 'patient_prevent_screening_examination.PreventScreeningParamId', 'prevent_screening_examination_param.Id')
            ->select('patient_prevent_screening_examination.Id as ppseId', 'patient_prevent_screening_examination.IsPatientExamined',
                'patient_prevent_screening_examination.Description as ppseDescription', 'patient_prevent_screening_examination.IsActive as ppseIsActive',
                'prevent_screening_examination_param.Id as psepId', 'prevent_screening_examination_param.Name',
                'prevent_screening_examination_param.Description as psepDescription'
            )
            ->where('patient_prevent_screening_examination.IsActive', '=', true)
            ->where('patient_prevent_screening_examination.PreventScreeningParamId', '=', $paramId)
            ->where('patient_prevent_screening_examination.PatientId', '=', $patientId)
            ->first();

        return $query;
    }

    static public function GetSinglePatientPsychologicalReview($id, $patientId)
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
            ->where('patient_psychological_review.Id', '=', $id)
            ->first();

        return $query;
    }

    static public function GetPatientPsychologicalReviewAll($paramId, $patientId)
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
            ->where('patient_psychological_review.PsychologicalReviewParamId', '=', $paramId)
            ->first();

        return $query;
    }

    static public function GetSinglePatientSocialReview($id, $patientId)
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
            ->where('patient_social_review.Id', '=', $id)
            ->first();

        return $query;
    }

    static public function GetSinglePatientSocialReviewAll($paramId, $patientId)
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
            ->where('patient_social_review.SocialReviewParamId', '=', $paramId)
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

    static public function GetSinglePatientCcmPlanViaPatientId($patientId, $pageNo, $limit, $startDate, $endDate)
    {
        if ($startDate == "null" && $endDate == "null") {
            error_log('search key is NULL');
            $queryResult = DB::table('ccm_plan')
                ->select('ccm_plan.*')
                ->where('ccm_plan.IsActive', '=', true)
                ->where('ccm_plan.PatientId', '=', $patientId)
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
        } else {
            error_log('search key is NOT NULL');


            $queryResult = DB::table('ccm_plan')
                ->select('ccm_plan.*')
                ->where('ccm_plan.IsActive', '=', true)
                ->where('ccm_plan.PatientId', '=', $patientId)
                ->where('.ccm_plan.StartDate', '>=', $startDate)
                ->where('.ccm_plan.EndDate', '<=', $endDate)
                ->skip($pageNo * $limit)
                ->take($limit)
                ->get();
        }

        return $queryResult;
    }

    static public function GetSinglePatientCcmPlanViaPatientIdCount($patientId, $startDate, $endDate)
    {
        if ($startDate == "null" && $endDate == "null") {
            error_log('search key is NULL');
            $queryResult = DB::table('ccm_plan')
                ->select('ccm_plan.*')
                ->where('ccm_plan.IsActive', '=', true)
                ->where('ccm_plan.PatientId', '=', $patientId)
                ->count();
        } else {
            error_log('search key is NOT NULL');


            $queryResult = DB::table('ccm_plan')
                ->select('ccm_plan.*')
                ->where('ccm_plan.IsActive', '=', true)
                ->where('ccm_plan.PatientId', '=', $patientId)
                ->where('.ccm_plan.StartDate', '>=', $startDate)
                ->where('.ccm_plan.EndDate', '<=', $endDate)
                ->count();
        }

        return $queryResult;
    }

    static public function GetCcmPlanGoalsViaCcmPLanId($ccmPlanId)
    {
        $query = DB::table('ccm_plan_goal')
            ->where('IsActive', '=', true)
            ->where('CcmPlanId', '=', $ccmPlanId)
            ->get();

        return $query;
    }

    static public function GetCcmPlanGoalsViaId($ccmPlanGoalId)
    {
        $query = DB::table('ccm_plan_goal')
            ->where('IsActive', '=', true)
            ->where('Id', '=', $ccmPlanGoalId)
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


    static public function getFilesViaPatientAssessmentId($patientAssessmnetId)
    {
        error_log('in model, fetching files via id');

        $query = DB::table('patient_assessment_file')
            ->where('patient_assessment_file.PatientAssessmentId', '=', $patientAssessmnetId)
            ->where('patient_assessment_file.IsActive', '=', true)
            ->get();

        return $query;
    }

    static public function GetPatientAssessmentFile($patientAssessmentFile)
    {
        error_log('in model, fetching files');

        $query = DB::table('patient_assessment_file')
            ->leftjoin('file_upload as file_upload', 'file_upload.Id', 'patient_assessment_file.FileUploadId')
            ->select('file_upload.*')
            ->where('patient_assessment_file.IsActive', '=', true)
            ->where('file_upload.IsActive', '=', true)
            ->where('patient_assessment_file.PatientAssessmentId', '=', $patientAssessmentFile)
            ->get();

        return $query;
    }

    static public function GetCCMPlanFile($ccmPlanId)
    {
        error_log('in model, fetching files');

        $query = DB::table('ccm_plan_file')
            ->leftjoin('file_upload as file_upload', 'file_upload.Id', 'ccm_plan_file.FileUploadId')
            ->select('file_upload.*')
            ->where('ccm_plan_file.IsActive', '=', true)
            ->where('file_upload.IsActive', '=', true)
            ->where('ccm_plan_file.CcmPlanId', '=', $ccmPlanId)
            ->get();

        return $query;
    }

    static public function IsHealthParamDuplicate($ccmHealthParamName)
    {
        error_log('in model, checking if parametere exists');

        $query = DB::table('ccm_health_param')
            ->where('ccm_health_param.IsActive', '=', true)
            ->where('ccm_health_param.Name', '=', $ccmHealthParamName)
            ->first();

        return $query;
    }

    static public function GetCCMReviewViaPlanIdGoalIdAndDate($ccmPlanId, $ccmPlanGoalId, $reviewDate)
    {
        $query = DB::table('ccm_plan_review')
            ->select('ccm_plan_review.*')
            ->where('ccm_plan_review.IsActive', '=', true)
            ->where('ccm_plan_review.CcmPlanId', '=', $ccmPlanId)
            ->where('ccm_plan_review.CcmPlanGoalId', '=', $ccmPlanGoalId)
            ->where('ccm_plan_review.ReviewDate', '=', $reviewDate)
            ->first();

        return $query;
    }


    static public function GetCCMReviewViaPlanAndGoalId($ccmPlanId, $ccmPlanGoalId)
    {
        $query = DB::table('ccm_plan_review')
            ->select('ccm_plan_review.*')
            ->where('ccm_plan_review.IsActive', '=', true)
            ->where('ccm_plan_review.CcmPlanId', '=', $ccmPlanId)
            ->where('ccm_plan_review.CcmPlanGoalId', '=', $ccmPlanGoalId)
            ->get();

        return $query;
    }

    static public function GetCCMPlanReviewViewId($id)
    {
        $query = DB::table('ccm_plan_review')
            ->leftjoin('ccm_plan as ccm_plan', 'ccm_plan_review.CcmPlanId', 'ccm_plan.Id')
            ->leftjoin('ccm_plan_goal as ccm_plan_goal', 'ccm_plan_review.CcmPlanGoalId', 'ccm_plan_goal.Id')
            ->select('ccm_plan_review.Id as ccmPlanReviewId', 'ccm_plan_review.IsGoalAchieve', 'ccm_plan_review.ReviewerComment',
                'ccm_plan_review.Barrier', 'ccm_plan_review.ReviewDate', 'ccm_plan_review.IsActive',

                'ccm_plan.Id as CcmPlanId', 'ccm_plan.PlanNumber', 'ccm_plan.StartDate', 'ccm_plan.EndDate', 'ccm_plan.IsInitialHealthReading',

                'ccm_plan_goal.Id as CcmPlanGoalId', 'ccm_plan_goal.ItemName', 'ccm_plan_goal.GoalNumber', 'ccm_plan_goal.Goal',
                'ccm_plan_goal.Intervention'
            )
            ->where('ccm_plan_review.IsActive', '=', true)
            ->where('ccm_plan_review.Id', '=', $id)
            ->first();

        return $query;
    }

    static public function GetAllCCMPlanReviewViaPagination($ccmPlanId, $pageNo, $limit, $searchDateFrom, $searchDateTo)
    {
        if ($searchDateFrom == "null" && $searchDateTo == "null") {
            error_log('search dates are null');

            $query = DB::table('ccm_plan_review')
                ->leftjoin('ccm_plan as ccm_plan', 'ccm_plan_review.CcmPlanId', 'ccm_plan.Id')
                ->leftjoin('ccm_plan_goal as ccm_plan_goal', 'ccm_plan_review.CcmPlanGoalId', 'ccm_plan_goal.Id')
                ->leftjoin('user as user', 'ccm_plan.PatientId', 'user.Id')
                ->select('ccm_plan_review.Id as ccmPlanReviewId', 'ccm_plan_review.IsGoalAchieve', 'ccm_plan_review.ReviewerComment',
                    'ccm_plan_review.Barrier', 'ccm_plan_review.ReviewDate', 'ccm_plan_review.IsActive',

                    'ccm_plan.Id as CcmPlanId', 'ccm_plan.PlanNumber', 'ccm_plan.StartDate', 'ccm_plan.EndDate', 'ccm_plan.IsInitialHealthReading',

                    'user.Id as UserId', 'user.FirstName as FirstName', 'user.LastName as LastName', 'user.PatientUniqueId as PatientUniqueId',

                    'ccm_plan_goal.Id as CcmPlanGoalId', 'ccm_plan_goal.ItemName', 'ccm_plan_goal.GoalNumber', 'ccm_plan_goal.Goal',
                    'ccm_plan_goal.Intervention'
                )
                ->where('ccm_plan_review.IsActive', '=', true)
                ->where('ccm_plan_review.CcmPlanId', '=', $ccmPlanId)
                ->skip($pageNo * $limit)
                ->take($limit)
                ->orderBy('ccm_plan_review.Id', 'desc')
                ->get();
        } else {

            error_log('search date is given');

            $query = DB::table('ccm_plan_review')
                ->leftjoin('ccm_plan as ccm_plan', 'ccm_plan_review.CcmPlanId', 'ccm_plan.Id')
                ->leftjoin('ccm_plan_goal as ccm_plan_goal', 'ccm_plan_review.CcmPlanGoalId', 'ccm_plan_goal.Id')
                ->leftjoin('user as user', 'ccm_plan.PatientId', 'user.Id')
                ->select('ccm_plan_review.Id as ccmPlanReviewId', 'ccm_plan_review.IsGoalAchieve', 'ccm_plan_review.ReviewerComment',
                    'ccm_plan_review.Barrier', 'ccm_plan_review.ReviewDate', 'ccm_plan_review.IsActive',

                    'ccm_plan.Id as CcmPlanId', 'ccm_plan.PlanNumber', 'ccm_plan.StartDate', 'ccm_plan.EndDate', 'ccm_plan.IsInitialHealthReading',

                    'user.Id as UserId', 'user.FirstName as FirstName', 'user.LastName as LastName', 'user.PatientUniqueId as PatientUniqueId',

                    'ccm_plan_goal.Id as CcmPlanGoalId', 'ccm_plan_goal.ItemName', 'ccm_plan_goal.GoalNumber', 'ccm_plan_goal.Goal',
                    'ccm_plan_goal.Intervention'
                )
                ->where('ccm_plan_review.IsActive', '=', true)
                ->where('ccm_plan_review.CcmPlanId', '=', $ccmPlanId)
                ->Where('.ccm_plan_review.ReviewDate', '>=', $searchDateFrom)
                ->Where('.ccm_plan_review.ReviewDate', '<=', $searchDateTo)
                ->skip($pageNo * $limit)
                ->take($limit)
                ->orderBy('ccm_plan_review.Id', 'desc')
                ->get();
        }

        return $query;
    }

    static public function GetAllCCMPlanReviewCount($ccmPlanId, $searchDateFrom, $searchDateTo)
    {
        if ($searchDateFrom == "null" && $searchDateTo == "null") {
            error_log('search dates are null');

            $query = DB::table('ccm_plan_review')
                ->where('ccm_plan_review.IsActive', '=', true)
                ->where('ccm_plan_review.CcmPlanId', '=', $ccmPlanId)
                ->count();
        } else {

            error_log('search date is given');

            $query = DB::table('ccm_plan_review')
                ->leftjoin('ccm_plan as ccm_plan', 'ccm_plan_review.CcmPlanId', 'ccm_plan.Id')
                ->leftjoin('ccm_plan_goal as ccm_plan_goal', 'ccm_plan_review.CcmPlanGoalId', 'ccm_plan_goal.Id')
                ->select('ccm_plan_review.Id as ccmPlanReviewId', 'ccm_plan_review.IsGoalAchieve', 'ccm_plan_review.ReviewerComment',
                    'ccm_plan_review.Barrier', 'ccm_plan_review.ReviewDate', 'ccm_plan_review.IsActive',

                    'ccm_plan.Id as CcmPlanId', 'ccm_plan.PlanNumber', 'ccm_plan.StartDate', 'ccm_plan.EndDate', 'ccm_plan.IsInitialHealthReading',

                    'ccm_plan_goal.Id as CcmPlanGoalId', 'ccm_plan_goal.ItemName', 'ccm_plan_goal.GoalNumber', 'ccm_plan_goal.Goal',
                    'ccm_plan_goal.Intervention'
                )
                ->where('ccm_plan_review.IsActive', '=', true)
                ->where('ccm_plan_review.CcmPlanId', '=', $ccmPlanId)
                ->Where('.ccm_plan_review.ReviewDate', '>=', $searchDateFrom)
                ->Where('.ccm_plan_review.ReviewDate', '<=', $searchDateTo)
                ->count();
        }


        return $query;
    }

    static public function CheckIfPatientTabExists($patientId, $patientRecordTabId)
    {
        $query = DB::table('patient_record_tab_publish')
            ->where('patient_record_tab_publish.IsActive', '=', true)
            ->where('patient_record_tab_publish.PatientRecordTabId', '=', $patientRecordTabId)
            ->Where('.patient_record_tab_publish.PatientId', '=', $patientId)
            ->first();

        return $query;
    }

    static public function CheckIfPhoneNumberExist($countryCode, $phoneNumber, $type)
    {
        error_log($countryCode);
        error_log($phoneNumber);

        if ($type == "daytimenum") {
            error_log("daytimenum");

            $query = DB::table('patient_assessment')
                ->where('DayTimeCountryCode', '=', $countryCode)
                ->where('DayTimePhoneNumber', '=', $phoneNumber)
                ->first();

            return $query;
        } else if ($type == "nighttimenum") {
            error_log("nighttimenum");
            $query = DB::table('patient_assessment')
                ->where('NightTimeCountryCode', '=', $countryCode)
                ->where('NightTimePhoneNumber', '=', $phoneNumber)
                ->first();

            return $query;
        } else if ($type == "generalnum") {
            error_log("generalnum");
            $query = DB::table('user')
                ->where('CountryPhoneCode', '=', $countryCode)
                ->where('MobileNumber', '=', $phoneNumber)
                ->first();
            return $query;
        } else {
            return 0;
        }
    }

    static public function GetPatientRecordTabPublished($patientId, $tabId)
    {
        error_log($patientId);
        error_log($tabId);

        $query = DB::table('patient_record_tab_publish as prtp')
            ->select('prtp.IsPublish')
            ->where('prtp.PatientRecordTabId', '=', $tabId)
            ->where('prtp.PatientId', '=', $patientId)
            ->where('prtp.IsActive', '=', true)
            ->get();
        return $query;
    }

    static public function getAllCcmCptOptionViaPatientId($id)
    {
        error_log('in model');

        $query = DB::table('patient_ccm_cpt_option')
            ->leftjoin('ccm_cpt_option as ccm_cpt_option', 'patient_ccm_cpt_option.CcmCptOptionId', 'ccm_cpt_option.Id')
            ->select('ccm_cpt_option.Id as cptId', 'ccm_cpt_option.Name', 'ccm_cpt_option.Code', 'ccm_cpt_option.Description')
            ->where('patient_ccm_cpt_option.IsActive', '=', true)
            ->where('patient_ccm_cpt_option.PatientId', '=', $id)
            ->get();

        return $query;
    }

    static public function getActiveCcmPlansViaPatientIds($patientIds, $currentData)
    {
        $queryResult = DB::table('ccm_plan')
            ->select('ccm_plan.*')
            ->where('ccm_plan.IsActive', '=', true)
            ->whereIn('ccm_plan.PatientId', $patientIds)
            ->where('.ccm_plan.EndDate', '>=', $currentData)
            ->orWhere('.ccm_plan.EndDate', '=', null)
            ->count();

        return $queryResult;
    }
}
