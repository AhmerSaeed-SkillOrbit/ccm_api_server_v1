<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/test', function (Request $request) {
    return response()->json(['data' => "Hello World", 'message' => 'Hello World'], 400);
});

//Role list with pagination
Route::get('/role/list/search', 'PageController@RoleListViaPagination');
//Role list without pagination
Route::get('/role/list', 'PageController@RoleList');
//Role list count
Route::get('/role/count', 'PageController@RoleCount');


//user list with pagination
Route::get('/user/list/search', 'UserController@UserListViaPagination');
//user list without pagination
Route::get('/user/list', 'UserController@UserList');
//user list count
Route::get('/user/count', 'UserController@UserCount');
//User update route
Route::post('/user/update', 'UserController@UserUpdate');
//Get single user via id
Route::get('/user/single', 'UserController@GetSingleUserViaId');
//User registration
Route::post('/user/add', 'UserController@UserRegistration');
//User delete route
Route::post('/user/delete', 'UserController@UserDelete');
//User invitation list with pagination and search
Route::get('/user/invitation', 'UserController@GetUserInvitationListWithPaginationAndSearch');
//User invitation list count with search
Route::get('/user/invitation/count', 'UserController@GetUserInvitationListCount');
//User block route
Route::post('/user/block', 'UserController@UserBlock');
//User unblock route
Route::post('/user/unblock', 'UserController@UserUnblock');

//get support staff list
Route::get('/user/via/role', 'UserController@GetUserViaRoleCode');

//Associate doctor to facilitator route
Route::post('/associate/doctor/facilitator', 'UserController@AssociateFacilitatorsWithDoctor');

//Dashboard API for super admin
Route::get('/dashboard/superadmin', 'UserController@SuperAdminDashboard');

//permission list with pagination
Route::get('/permission/list/search', 'PageController@PermissionListViaPagination');
//permission list without pagination
Route::get('/permission/list', 'PageController@PermissionList');
//permission list count
Route::get('/permission/count', 'PageController@PermissionCount');
//Role permission assign
Route::post('/role/permission/assign', 'PageController@RolePermissionAssign');
//Get permission via role Id
Route::get('/permission/via/role/id', 'UserController@PermissionViaRoleId');
//Get permission via user Id
Route::get('/permission/via/user/id', 'UserController@PermissionViaUserId');


//Adding schedule of doctor
Route::post('/doctor/schedule/save', 'DoctorScheduleController@AddDoctorScheduleUpdatedCode');
//Updating schedule of doctor
Route::post('/doctor/schedule/update', 'DoctorScheduleController@UpdateDoctorSchedule');

//Adding schedule of doctor
Route::get('/doctor/schedule/single', 'DoctorScheduleController@GetDoctorScheduleDetailAhmerUpdate');

Route::get('/doctor/schedule/single/ahsan', 'DoctorScheduleController@GetDoctorScheduleDetail');

//Adding schedule of doctor
Route::get('/doctor/schedule/shift/single', 'DoctorScheduleController@GetDoctorScheduleShiftSingleViaId');

//Get doctor facilitator list
Route::get('/doctor/facilitator', 'UserController@GetAssociateFacilitator');
//Doctor schedule list
Route::get('/doctor/schedule/list', 'DoctorScheduleController@GetDoctorScheduleListViaPagination');
//Doctor schedule list count
Route::get('/doctor/schedule/list/count', 'DoctorScheduleController@GetDoctorScheduleListCount');

//Adding doctor appointment
Route::post('/appointment/add', 'DoctorScheduleController@AddAppointment');

//get doctor appointment list
Route::get('/appointment/list', 'DoctorScheduleController@getDoctorAppointmentListViaPagination');

//get doctor appointment list
Route::get('/appointment/single', 'DoctorScheduleController@getDoctorAppointmentSingleViaId');

//get doctor schedule count
Route::get('/appointment/list/count', 'DoctorScheduleController@getDoctorAppointmentListCount');

Route::post('/appointment/request/status/update', 'DoctorScheduleController@updateAppointmentRequestStatus');

Route::post('/appointment/cancel/', 'DoctorScheduleController@MarkAppointmentCancel');

//Add tag
Route::post('/tag/add', 'ForumController@AddTag');

//get tag list
Route::get('/tag/list', 'ForumController@getTagList');

//Add forum topic
Route::post('/forum/topic/add', 'ForumController@AddForumTopic');

//Update forum topic
Route::post('/forum/topic/update', 'ForumController@UpdateForumTopic');

//Delete forum topic
Route::post('/forum/topic/delete', 'ForumController@DeleteForumTopic');

//Get single forum topic
Route::get('/forum/topic/single', 'ForumController@GetSingleForumTopic');

//Get forum topic list
Route::get('/forum/topic/list', 'ForumController@GetForumTopicListViaPagination');

Route::get('/forum/topic/list/count', 'ForumController@GetForumTopicListCount');

//Add forum comment
Route::post('/forum/topic/comment/add', 'ForumController@AddForumTopicComment');

//Update forum comment
Route::post('/forum/topic/comment/update', 'ForumController@UpdateForumTopicComment');

//Delete forum comment
Route::post('/forum/topic/comment/delete', 'ForumController@DeleteForumTopicComment');

//get single forum comment
Route::get('/forum/topic/comment/single', 'ForumController@GetSingleForumTopicComment');

//get forum comment list
Route::get('/forum/topic/comment/list', 'ForumController@GetForumTopicCommentsViaPagination');

//get forum comment list count
Route::get('/forum/topic/comment/list/count', 'ForumController@GetForumTopicCommentsCount');

//Create ticket
Route::post('/ticket/create', 'TicketController@CreateTicket');
//Update ticket
Route::post('/ticket/update', 'TicketController@UpdateTicket');

//get ticket single
Route::get('/ticket/single', 'TicketController@TicketSingle');

//get ticket list via pagination
Route::get('/ticket/list', 'TicketController@TicketListViaPaginationAndSearch');

//get ticket list count
Route::get('/ticket/list/count', 'TicketController@TicketListCount');

//get ticket list count
Route::get('/ticket/priority/list', 'TicketController@GetTicketPriorities');
Route::get('/ticket/type/list', 'TicketController@GetTicketTypes');
Route::get('/ticket/track/status/list', 'TicketController@GetTicketTrackStauses');


//Create ticket reply
Route::post('/ticket/reply/add', 'TicketController@AddTicketReply');

//get ticket single
Route::get('/ticket/reply/single', 'TicketController@TicketReplySingle');

//Update ticket reply
Route::post('/ticket/reply/update', 'TicketController@UpdateTicketReply');

//ticket assign
Route::post('/ticket/assign', 'TicketController@AssignTicket');

//ticket status update
Route::post('/ticket/track/status/update', 'TicketController@TicketTrackStatusUpdate');

##
# CCM PLAN APIS
# ##
// get questions list API
Route::get('/question/list', 'CcmPlanController@GetQuestionsList');

Route::get('/answer/type/list', 'CcmPlanController@GetAnswerTypeList');

// give answer to questions
Route::post('/give/answer', 'CcmPlanController@GiveAnswerToQuestion');

// update answer
Route::post('/update/answer', 'CcmPlanController@UpdateAnswer');

//Get all question and answers
Route::get('/question/answer/all', 'CcmPlanController@GetAllQuestionAnswers');

//Get all question and answers
Route::get('/question/answer/single', 'CcmPlanController@GetQuestionAnswerSingle');

// add active medicine
Route::post('/add/active/medicine', 'CcmPlanController@AddActiveMedicine');
// update active medicine
Route::post('/update/active/medicine', 'CcmPlanController@UpdateActiveMedicine');
//Get all prescribed medicine
Route::get('/active/medicine/all', 'CcmPlanController@GetAllActiveMedicine');
//Get single active medicine
Route::get('/active/medicine/single', 'CcmPlanController@GetSingleActiveMedicine');

// add allergy medicine
Route::post('/add/allergy/medicine', 'CcmPlanController@AddAllergyMedicine');
// update allergy medicine
Route::post('/update/allergy/medicine', 'CcmPlanController@UpdateAllergyMedicine');
//Get all prescribed allergy medicine
Route::get('/allergy/medicine/all', 'CcmPlanController@GetAllAllergyMedicine');
//Get single allergy medicine
Route::get('/allergy/medicine/single', 'CcmPlanController@GetSingleAllergyMedicine');

// add non medicine
Route::post('/add/non/medicine', 'CcmPlanController@AddNonMedicine');
// update non medicine
Route::post('/update/non/medicine', 'CcmPlanController@UpdateNonMedicine');
//Get all prescribed non medicine
Route::get('/non/medicine/all', 'CcmPlanController@GetAllNonMedicine');
//Get single non medicine
Route::get('/non/medicine/single', 'CcmPlanController@GetSingleNonMedicine');

// add immunization vaccine
Route::post('/add/immunization/vaccine', 'CcmPlanController@AddImmunizationVaccine');
// update immunization vaccine
Route::post('/update/immunization/vaccine', 'CcmPlanController@UpdateImmunizationVaccine');
//Get all prescribed immunization vaccine
Route::get('/immunization/vaccine/all', 'CcmPlanController@GetAllImmunizationVaccine');
//Get single immunization vaccine
Route::get('/immunization/vaccine/single', 'CcmPlanController@GetSingleImmunizationVaccine');

// add health care history
Route::post('/add/health/care/history', 'CcmPlanController@AddHealthCareHistory');
// update health care history
Route::post('/update/health/care/history', 'CcmPlanController@UpdateHealthCareHistory');
//Get all health care history
Route::get('/health/care/history/all', 'CcmPlanController@GetAllHealthCareHistory');
//Get single health care history
Route::get('/health/care/history/single', 'CcmPlanController@GetSingleHealthCareHistory');

//Assistance APIS
//Get asistance organization
Route::get('/assistance/organization/via/assistance/type', 'CcmPlanController@GetAllAssistanceOrganization');
//Get asistance type
Route::get('/assistance/type/all', 'CcmPlanController@GetAllAssistanceType');

// add patient organization assistance
Route::post('/add/patient/organization/assistance', 'CcmPlanController@AddPatientOrganizationAssistance');
// update patient organization assistance
Route::post('/update/patient/organization/assistance', 'CcmPlanController@UpdatePatientOrganizationAssistance');
//Get all patient organization assistance
Route::get('/patient/organization/assistance/all', 'CcmPlanController@GetAllPatientOrganizationAssistance');
//Get single patient organization assistance
Route::get('/patient/organization/assistance/single', 'CcmPlanController@GetSinglePatientOrganizationAssistance');

// add hospitalization history
Route::post('/add/hospitalization/history', 'CcmPlanController@AddHospitalizationHistory');
// update hospitalization history
Route::post('/update/hospitalization/history', 'CcmPlanController@UpdateHospitalizationHistory');
//Get all hospitalization history
Route::get('/hospitalization/history/all', 'CcmPlanController@GetAllHospitalizationHistory');
//Get single hospitalization history
Route::get('/hospitalization/history/single', 'CcmPlanController@GetSingleHospitalizationHistory');


// add srugery history
Route::post('/add/surgery/history', 'CcmPlanController@AddSurgeryHistory');
// update hospitalization history
Route::post('/update/surgery/history', 'CcmPlanController@UpdateSurgeryHistory');
//Get all hospitalization history
Route::get('/surgery/history/all', 'CcmPlanController@GetAllSurgeryHistory');
//Get single hospitalization history
Route::get('/surgery/history/single', 'CcmPlanController@GetSingleSurgeryHistory');

//Get patient general information
Route::get('/patient/general/information', 'CcmPlanController@GetPatientGeneralInformation');

//Update patient general information
Route::post('/patient/general/info/update', 'CcmPlanController@UpdatePatientGeneralInfo');

//Get phsychological review param
Route::get('/psychological/review/all', 'CcmPlanController@GetAllPsychologicalReviewParam');
//Get functional review param
Route::get('/functional/review/all', 'CcmPlanController@GetAllFunctionalReviewParam');
//Get social review param
Route::get('/social/review/all', 'CcmPlanController@GetAllSocialReviewParam');
//Get preventative screening param
Route::get('/preventative/screen/exam/all', 'CcmPlanController@GetAllPreventativeScreenExamParam');
//Get diabetic measure param
Route::get('/diabetic/measure/all', 'CcmPlanController@GetAllDiabeticMeasureParam');

//
//ENUM apis
//

Route::get('/insurance/type/list', 'CcmPlanController@GetInsuranceType');
Route::get('/insurance/coverage/type/list', 'CcmPlanController@GetInsuranceCoverageType');
Route::get('/patient/live/type/list', 'CcmPlanController@GetPatientLiveType');
Route::get('/patient/challenge/type/list', 'CcmPlanController@GetPatientChallengeType');
Route::get('/patient/primary/language/list', 'CcmPlanController@GetPatientPrimaryLanguage');
Route::get('/patient/learn/by/type/list', 'CcmPlanController@GetPatientLearningType');
Route::get('/patient/things/impact/list', 'CcmPlanController@GetThingsImpactOnHealth');
Route::get('/patient/assistance/available/type/list', 'CcmPlanController@GetPatientAssistanceAvailabilityType');


//Save patient assessment
Route::post('/save/patient/assessment', 'CcmPlanController@SavePatientAssessment');
Route::get('/patient/assessment/single', 'CcmPlanController@GetPatientAssessment');

//Save patient assessment ability concern APIS
Route::post('/save/patient/assessment/ability/concern', 'CcmPlanController@SavePatientAssessmentAbilityConcern');
Route::get('/patient/assessment/ability/concern/single', 'CcmPlanController@GetPatientAssessmentAbilityConcern');

//Save patient assessment alternate contact APIS
Route::post('/save/patient/assessment/alternate/contact', 'CcmPlanController@SavePatientAssessmentAlternateContact');
Route::get('/patient/assessment/alternate/contact/single', 'CcmPlanController@GetPatientAssessmentAlternateContact');

//Save patient assessment insurance APIS
Route::post('/save/patient/assessment/insurance', 'CcmPlanController@SavePatientAssessmentInsurance');
Route::get('/patient/assessment/insurance/single', 'CcmPlanController@GetPatientAssessmentInsurance');

//Save patient assessment resource APIS
Route::post('/save/patient/assessment/resource', 'CcmPlanController@SavePatientAssessmentResource');
Route::get('/patient/assessment/resource/single', 'CcmPlanController@GetPatientAssessmentResource');

//Save patient assessment self APIS
Route::post('/save/patient/assessment/self', 'CcmPlanController@SavePatientAssessmentSelf');
Route::get('/patient/assessment/self/single', 'CcmPlanController@GetPatientAssessmentSelf');

//Save patient diabetic measure APIS
Route::post('/save/patient/diabetic/measure', 'CcmPlanController@SavePatientDiabeticMeasure');
Route::get('/patient/diabetic/measure/single', 'CcmPlanController@GetSinglePatientDiabeticMeasure');
Route::get('/patient/diabetic/measure/all', 'CcmPlanController@GetPatientDiabeticMeasureAll');

//Save patient functional review APIS
Route::post('/save/patient/functional/review', 'CcmPlanController@SavePatientFunctionalReview');
Route::get('/patient/functional/review/single', 'CcmPlanController@GetPatientFunctionalReview');
Route::get('/patient/functional/review/all', 'CcmPlanController@GetPatientFunctionalReviewAll');

//Save patient organization assistance APIS
Route::post('/save/patient/organization/assistance', 'CcmPlanController@SavePatientOrganizationAssistance');
Route::get('/patient/organization/assistance/single', 'CcmPlanController@GetPatientOrganizationAssistanceViaPatientId');

//Save patient screen examination APIS
Route::post('/save/patient/screen/examination', 'CcmPlanController@SavePatientScreenExamination');
Route::get('/patient/screen/examination/single', 'CcmPlanController@GetPatientScreenExamination');
Route::get('/patient/screen/examination/all', 'CcmPlanController@GetPatientScreenExaminationAll');

//Save patient psychological review APIS
Route::post('/save/patient/psychological/review', 'CcmPlanController@SavePatientPsychologicalReview');
Route::get('/patient/psychological/review/single', 'CcmPlanController@GetPatientPsychologicalReview');
Route::get('/patient/psychological/review/all', 'CcmPlanController@GetPatientPsychologicalReviewAll');

//Save patient social review APIS
Route::post('/save/patient/social/review', 'CcmPlanController@SavePatientSocialReview');
Route::get('/patient/social/review/single', 'CcmPlanController@GetPatientSocialReview');
Route::get('/patient/social/review/all', 'CcmPlanController@GetPatientSocialReviewAll');

//Get diabetic measure param
Route::get('/ccm/plan/health/param/all', 'CcmPlanController@GetAllHealthParam');
Route::post('/ccm/plan/health/param/add', 'CcmPlanController@SaveCCMHealthParam');

//Save CCM plan APIS
Route::post('/patient/ccm/plan/add', 'CcmPlanController@SavePatientCCMPlan');
//get single CCM plan
Route::get('/patient/ccm/plan/single', 'CcmPlanController@GetCCMPlanViaId');

//get all CCM plan
Route::get('/patient/ccm/plan/all', 'CcmPlanController@GetCCMPlanViaPatientId');
//get all CCM plan count
Route::get('/patient/ccm/plan/all/count', 'CcmPlanController@GetCCMPlanViaPatientIdCount');

//get update CCM plan
Route::post('/patient/ccm/plan/update', 'CcmPlanController@UpdateCcmPlan');

//Ccm plan reviews api
//get update CCM plan
Route::post('/ccm/plan/review/add', 'CcmPlanController@AddCCmPlanReview');
Route::post('/ccm/plan/review/update', 'CcmPlanController@UpdateCCmPlanReview');
Route::get('/ccm/plan/review/single', 'CcmPlanController@GetSingleCCMPlanReview');
Route::get('/ccm/plan/review/all', 'CcmPlanController@GetAllCCMPlanReviewViaPagination');
Route::get('/ccm/plan/review/all/count', 'CcmPlanController@GetAllCCMPlanReviewCount');

Route::post('/publish/tab', 'CcmPlanController@PublishTab');
Route::post('/unpublish/tab', 'CcmPlanController@UnPublishTab');

///patient/record/tab/published?patientId=logged in patient id
Route::get('/patient/record/tab/published', 'CcmPlanController@GetPatientRecordTabPublished');

Route::get('/patient/type/all', 'CcmPlanController@GetAllPatientType');
Route::get('/ccm/cpt/option/all', 'CcmPlanController@GetAllCCMCptOption');

Route::post('/save/patient/ccm/cpt/option', 'CcmPlanController@AddPatientCCMCptOption');
Route::get('/patient/ccm/cpt/option/all', 'CcmPlanController@GetPatientCcmCptOption');
Route::get('/doctor/list', 'CcmPlanController@GetDoctorList');

Route::get('/patient/registered/report', 'ReportController@GetPatientRegisteredReport');
Route::get('/patient/registered/report/count', 'ReportController@GetPatientRegisteredReportCount');

Route::get('/patient/invitation/report', 'ReportController@GetPatientInvitationReport');
Route::get('/patient/invitation/report/count', 'ReportController@GetPatientInvitationReportCount');

Route::post('/patient/ccm/cpt/report', 'ReportController@GetPatientCcmCptReport');
Route::post('/patient/ccm/cpt/report/count', 'ReportController@GetPatientCcmCptReportCount');

Route::post('/patient/type/report', 'ReportController@GetPatientTypeReport');
Route::post('/patient/type/report/count', 'ReportController@GetPatientTypeReportCount');


Route::get('/', function () {
    return 'Hello';
});

Route::get('/test/list', 'PageController@testFunction');

Route::get('/test/email', 'PageController@TestEmail');

Route::get('/test/sms', 'PageController@TestSms');

Route::get('/test/pdf', 'DocumentUploadController@TestPdf');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', 'LoginController@login');
Route::post('/register', 'LoginController@register');
Route::post('/forgetPass', 'LoginController@forgetPass');
Route::post('/resetPass', 'LoginController@resetPass');
Route::post('/change/password', 'LoginController@changePassword');
Route::post('/invite', 'ServicesController@invite');
Route::post('/invite/update', 'ServicesController@inviteUpdate');
Route::get('/logout', 'LoginController@logout');

//byUserId - means who want to view the history
//toUserId - means who's history is required

Route::get('/login/history/count', 'LoginController@LoginHistoryCount'); //api/login/history/count?byUserId=1&ofUserId=2
Route::get('/login/history/all', 'LoginController@LoginHistoryList'); //api/login/history/list?byUserId=1&ofUserId=2&p=0&c=10


//?doctorScheduleDetailId=1
Route::post('/doctor/schedule/detail/single/update', 'DoctorScheduleController@UpdateDoctorScheduleDetailSingle');

//temp api
Route::get('/patient/associated/doctor', 'DoctorScheduleController@GetPatientAssociatedDoctor');

Route::get('/add/time/slot', 'DoctorScheduleController@AddTimeSlotDynamically');

Route::get('/format/time/', 'DoctorScheduleController@FormatTime');

//$time = strtotime($dateInUTC.' UTC');
//$dateInLocal = date("Y-m-d H:i:s", $time);


//Test file upload
Route::post('/upload/file', 'DocumentUploadController@UploadFiles');

Route::get('/download/file', 'DocumentUploadController@DownloadFilesNew');

Route::post('/upload/profile/picture', 'DocumentUploadController@UploadProfilePicture');
Route::post('/upload/forum/topic/file', 'DocumentUploadController@UploadForumTopicFile');
Route::post('/upload/forum/topic/comment/file', 'DocumentUploadController@UploadForumCommentFile');
Route::post('/upload/patient/assessment/file', 'DocumentUploadController@UploadPatientAssessmentFile');
Route::post('/upload/ticket/file', 'DocumentUploadController@UploadTicketFile');
Route::post('/upload/ticket/reply/file', 'DocumentUploadController@UploadTicketReplyFile');
Route::post('/upload/ccm/plan/file', 'DocumentUploadController@UploadCcmFile');
Route::post('/upload/general/file', 'DocumentUploadController@UploadGeneralAttachment');

Route::get('/general/file/list', 'DocumentUploadController@GeneralFileListViaPagination');
Route::get('/general/file/list/count', 'DocumentUploadController@GeneralFileListCount');
Route::post('/general/file/remove', 'DocumentUploadController@GeneralFileRemove');

//Download file routes

Route::get('/download/profile/picture/{fileUploadId}/{fileName}', 'DocumentUploadController@DownloadProfilePicture');
Route::get('/download/forum/topic/file/{fileUploadId}/{fileName}', 'DocumentUploadController@DownloadTopicFile');
Route::get('/download/forum/topic/comment/file/{fileUploadId}/{fileName}', 'DocumentUploadController@DownloadTopicCommentFile');
Route::get('/download/patient/assessment/file/{fileUploadId}/{fileName}', 'DocumentUploadController@DownloadPatientAssessmentFile');
Route::get('/download/ticket/file/{fileUploadId}/{fileName}', 'DocumentUploadController@DownloadTicketFile');
Route::get('/download/ticket/reply/file/{fileUploadId}/{fileName}', 'DocumentUploadController@DownloadTicketReplyFile');
Route::get('/download/ccm/plan/file/{fileUploadId}/{fileName}', 'DocumentUploadController@DownloadCCMPlanFile');
Route::get('/download/general/file/{fileUploadId}/{fileName}', 'DocumentUploadController@DownloadGeneralFile');
Route::get('/download/default/profile/picture/{imageName}', 'DocumentUploadController@DownloadDefaultProfilePicture');

//Background job API

//Close ticket
Route::post('/background/ticket/close', 'TicketController@CloseTicket');

Route::get('/ccm/plan/summary/email/pdf/', 'CcmPlanController@SendEmailPdfCcmPlanSummary');

//verify phone numbers
//post
//body params
//countryCode
//phoneNumber
//type=daytimenum || nighttimenum || generalnum

Route::post('/send/code/on/sms', 'CcmPlanController@SendCodeOnSms');

Route::post('/verify/sms/code', 'CcmPlanController@VerifySmsCode');

Route::post('/bulk/user/register', 'UserController@BulkUserRegister');

Route::get('/background/bulk/user/register', 'UserController@BackgroundBulkUserRegister');

Route::post('/patient/add', 'LoginController@AddPatientDirect');


