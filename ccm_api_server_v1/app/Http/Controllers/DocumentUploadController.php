<?php

namespace App\Http\Controllers;

use App\Models\LoginModel;
use App\User;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use App\Models\UserModel;
use App\Models\ForumModel;
use App\Models\GenericModel;
use App\Models\HelperModel;
use App\Models\DocumentUploadModel;
use Carbon\Carbon;
use mysql_xdevapi\Exception;
use PDF;
use Response;

class DocumentUploadController extends Controller
{
    function UploadFiles(Request $request)
    {
        error_log('in controller');

        //get filename with extension
        $filenamewithextension = $request->file('File')->getClientOriginalName();

        error_log(' $filenamewithextension ' . $filenamewithextension);

        //get filename without extension
        $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);

        error_log(' $filename ' . $filename);

        //get file extension
        $extension = $request->file('File')->getClientOriginalExtension();

        //filename to store
        $filenametostore = $filename . '_' . uniqid() . '.' . $extension;

        $createDir = Storage::disk('ftp')->makeDirectory('1/forum');

        error_log("createDir");

        error_log($createDir);

        error_log(' $filenametostore ' . $filenametostore);

        try {
            $upload_success = Storage::disk('ftp')->put('/1/forum/' . $filenametostore, fopen($request->file('File'), 'r+'));
            error_log('$upload_success');
            error_log($upload_success);
        } catch (Exception $ex) {
            error_log('exception');
            error_log($ex);
        }

        // IF UPLOAD IS SUCCESSFUL SEND SUCCESS MESSAGE OTHERWISE SEND ERROR MESSAGE
        if ($upload_success) {
            return response()->json(['data' => null, 'message' => 'File successfully uploaded'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Error in uploading file'], 400);
        }
    }

    function DownloadFilesNew()
    {

//        $file = $request->get('file');
//        if (!$file) {
//            return Response::json('please provide valid path', 400);
//        }
//        $fileName = basename($file);

        $ftp = Storage::createFtpDriver([
            'host' => env('FTP_HOST'),
            'username' => env('FTP_USER'),
            'password' => env('FTP_PASSWORD'),
            'port' => env('FTP_PORT'),
            'timeout' => env('FTP_TIMEOUT')
        ]);

        $file = '/ccm_attachment/EXTLMS-small_5cade01a9cacd.txt';

        $filecontent = $ftp->get($file); // read file content

        // download file.

        return Response::make($filecontent, '200', array(
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'inline; filename=EXTLMS-small_5cade01a9cacd.txt'
        ));
    }


//    not final yet
    function DownloadFiles(Request $request)
    {
//        $ticketClose = env('TICKET_TRACK_STATUS_CLOSE');

//        error_log('in controller');
////        $input = Input::all();
//
////        $file = array_get($input, 'file');
//        // SET UPLOAD PATH
//        $destinationPath = 'E:\IMAGES';
//        // GET THE FILE EXTENSION
////        $extension = $file->getClientOriginalExtension();
//        // RENAME THE UPLOAD WITH RANDOM NUMBER
////        $fileName = rand(11111, 99999) . '.' . $extension;
//        // MOVE THE UPLOADED FILES TO THE DESTINATION DIRECTORY
////        $upload_success = $file->move($destinationPath, $fileName);
//        //Upload File to external server
//
//        error_log('in controller');
//
//
//        //get filename with extension
//        $filenamewithextension = $request->file('File')->getClientOriginalName();
//
//        error_log(' $filenamewithextension ' . $filenamewithextension);
//
//        //get filename without extension
//        $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
//
//        error_log(' $filename ' . $filename);
//
//
//        //get file extension
//        $extension = $request->file('File')->getClientOriginalExtension();
//
//        //filename to store
//        $filenametostore = $filename . '_' . uniqid() . '.' . $extension;
//
//        error_log(' $filenametostore ' . $filenametostore);

        try {
            //ols
//            $upload_success = Storage::disk('ftp')->get('EXTLMS-small_5caa359dbaa6d.txt');
//            error_log('$upload_success');
//            error_log($upload_success);
            error_log("checking");

//            $exists = Storage::disk('ftp')->get('EXTLMS-small_5cade01a9cacd.txt');
//            $exists = Storage::disk('ftp')->get('EXTLMS-small_5cade01a9cacd.txt');
//            $exists = Storage::disk('ftp')->get('/1/forum/EXTLMS-small_5cade1d338d70.txt');
//
//            error_log($exists);
//            Storage::get('file.jpg');
//            return response()->file('/ccm_attachment/EXTLMS-small_5cade01a9cacd.txt', array(
//                'Content-Type' => 'text/plain'
//            ));

//            return Storage::download('file.jpg');

            $headers = [
                'Content-Type' => 'application/pdf',
            ];

            return response()->json(['data' => null, 'message' => 'Error in uploading file'], 400);
//            return response()->download('/ccm_attachment/1/forum/ERD.pdf', 'ERD.pdf', $headers);
//            return response()->download( public_path().'/ERD.pdf', 'ERD.pdf', $headers);

//            return response()->file(Storage::disk('ftp')->download('/1/forum/EXTLMS-small_5cade1d338d70.txt'), array(
//                'Content-Type' => 'text/plain',
//                'Content-Disposition' => 'attachment; filename="EXTLMS-small_5cade1d338d70.txt"'
//            ));

        } catch (Exception $ex) {
            error_log('exception');
            error_log($ex);
        }
//
//        $upload_success = Storage::disk('ftp')->put($filename, fopen($request->file('File'), 'r+'));
//
//        error_log(' $upload_success ' . $upload_success);

        // IF UPLOAD IS SUCCESSFUL SEND SUCCESS MESSAGE OTHERWISE SEND ERROR MESSAGE
//        $exists = Storage::disk('ftp')->exists('/1/forum/EXTLMS-small_5cade1d338d70.txt');
//        return response()->file($exists, array(
//            'Content-Type' => 'text/plan'
//        ));

//        response()->make($upload_success, 200, array(
//            'Content-Type' => 'text/plain',
//            'Content-Disposition' => 'attachment; filename="' . $upload_success . '"'
//        ));
//        return Response::make($upload_success, '200', array(
//            'Content-Type' => 'application/octet-stream',
//            'Content-Disposition' => 'attachment; filename="'.$upload_success.'"'
//        ));

//        if ($upload_success) {
//            return response()->json(['data' => null, 'message' => 'File successfully uploaded'], 200);
//        } else {
//            return response()->json(['data' => null, 'message' => 'Error in uploading file'], 400);
//        }
    }


    function UploadProfilePicture(Request $request)
    {
        error_log('in controller');
        $fileSize = $request->file('File')->getSize();
        $extension = $request->file('File')->getClientOriginalExtension();
        $extension = strtolower($extension);

        if ($fileSize >= env('FILE_SIZE_ALLOWED')) {
            error_log("Image Size is huge");
            //Image size Not Allowed
            return response()->json(['data' => null, 'message' => 'Image size is Not Allowed'], 400);
        } else {
            error_log("Image Size is acceptable");
            error_log($extension);
            if ($extension != "jpg" && $extension != "jpeg" && $extension != "png" && $extension != "gif" && $extension != "svg") {
                error_log("Image type is not allowed");
                //Only Image is allowed
                return response()->json(['data' => null, 'message' => 'Only Image is allowed'], 400);
            } else {

                error_log("Image type is acceptable");

                $userId = $request->get('userId');
                $byUserId = $request->get('byUserId');

                $profileDirectory = env('PROFILE_PICTURE_DIR');
                $baseUrl = env('BASE_URL');
                $profilePicAPIPrefix = env('PROFILE_PIC_API_PREFIX');

                error_log('Checking if user record exists or not');
                $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
                if ($checkUserData == null) {
                    return response()->json(['data' => null, 'message' => 'User record not found'], 400);
                }

                error_log('user record found');
                $file = $request->file('File');

                if (!isset($file)) {
                    return response()->json(['data' => null, 'message' => 'File is missing'], 400);
                }

                //get filename with extension
                $filenamewithextension = $request->file('File')->getClientOriginalName();
                error_log(' File with extension ' . $filenamewithextension);

                //get filename without extension
                $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
                error_log(' Only file name is:  ' . $filename);

                //get file extension

                error_log(' File extension is:  ' . $extension);

                $filenameWithoutExtension = $filename . '_' . uniqid();

                //filename to store
                //        $filenametostore = $filename . '_' . uniqid() . '.' . $extension;
                error_log(' File name unique id is : ' . $filenameWithoutExtension . '.' . $extension);

                error_log(' File size is : ');
                error_log($fileSize);

                $dirPath = $byUserId . '/' . $profileDirectory . '/';

                $createDir = Storage::disk('ftp')->makeDirectory($dirPath);

                error_log("createDir");

                error_log($createDir);

                try {
                    $upload_success = Storage::disk('ftp')->put($dirPath . '/' . $filenameWithoutExtension . '.' . $extension, fopen($request->file('File'), 'r+'));
                    error_log('$upload_success');
                    error_log($upload_success);

                } catch (Exception $ex) {

                    error_log('exception');
                    error_log($ex);
                    return response()->json(['data' => null, 'message' => $ex->getMessage()], 400);
                }

                $date = HelperModel::getDate();

                DB::beginTransaction();

                // IF UPLOAD IS SUCCESSFUL SEND SUCCESS MESSAGE OTHERWISE SEND ERROR MESSAGE
                if ($upload_success == true) {

                    error_log('upload successfully done');
                    error_log('Now insert data in file upload table');

                    $fileUpload = array(
                        'ByUserId' => $byUserId,
                        'RelativePath' => $dirPath,
                        'FileOriginalName' => $filename . '.' . $extension,
                        'FileName' => $filenameWithoutExtension,
                        'FileExtension' => '.' . $extension,
                        'FileSizeByte' => $fileSize,
                        'BelongTo' => 'profile',
                        'CreatedOn' => $date["timestamp"],
                        'IsActive' => true
                    );
                    //Now inserting data in file_upload table

                    $insertedData = GenericModel::insertGenericAndReturnID('file_upload', $fileUpload);

                    if ($insertedData == 0) {
                        error_log('data not inserted');
                        DB::rollBack();
                        return response()->json(['data' => null, 'message' => 'Error in inserting file upload information'], 400);
                    } else {
                        error_log('data inserted in file upload');
                        error_log('File upload id is : ' . $insertedData);

                        $dataToUpdate = array(
                            'UpdatedBy' => $userId,
                            'UpdatedOn' => $date["timestamp"],
                            'ProfilePictureId' => $insertedData
                        );

                        $updatedData = GenericModel::updateGeneric('user', 'Id', $userId, $dataToUpdate);

                        if ($updatedData == false) {
                            error_log('user data not updated');
                            DB::rollBack();
                            return response()->json(['data' => null, 'message' => 'Error in updating user profile picture information'], 400);
                        } else {
                            error_log('user profile picture data updated ');
                            DB::commit();

                            error_log('Checking if user record exists or not');
                            $checkDocument = DocumentUploadModel::GetDocumentData($insertedData);
                            if ($checkDocument == null) {
                                return response()->json(['data' => null, 'message' => 'Document not found'], 400);
                            } else {
                                error_log($checkDocument->FileName . '' . $checkDocument->FileExtension);
                                //Now checking if document name is same as it is given in parameter
                                error_log('document name is valid');
                                $fileData['Id'] = $insertedData;
                                $fileData['Path'] = $baseUrl . '' . $profilePicAPIPrefix . '/' . $insertedData . '/' . $checkDocument->FileName . '' . $checkDocument->FileExtension;

                                return response()->json(['data' => $fileData, 'message' => 'User profile picture uploaded successfully'], 200);
                            }
                        }
                    }

                } else {
                    return response()->json(['data' => null, 'message' => 'Error in uploading profile picture'], 400);
                }
            }
        }
    }

    function UploadForumTopicFile(Request $request)
    {
        error_log('in controller');

        $byUserId = $request->get('byUserId');

        $forumTopicDir = env('FORUM_TOPIC_DIR');
        error_log('user record found');

        $file = $request->file('File');

        if (!isset($file)) {
            return response()->json(['data' => null, 'message' => 'File is missing'], 400);
        }

        //get filename with extension
        $filenamewithextension = $request->file('File')->getClientOriginalName();
        error_log(' File with extension ' . $filenamewithextension);

        //get filename without extension
        $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
        error_log(' Only file name is:  ' . $filename);

        //get file extension
        $extension = $request->file('File')->getClientOriginalExtension();
        error_log(' File extension is:  ' . $extension);

        $filenameWithoutExtension = $filename . '_' . uniqid();

        //filename to store
        $filenametostore = $filename . '_' . uniqid() . '.' . $extension;
        error_log(' File name unique id is : ' . $filenametostore);

        $fileSize = $request->file('File')->getSize();
        error_log(' File size is : ' . $fileSize);

        $dirPath = $byUserId . '/' . $forumTopicDir . '/';

        $createDir = Storage::disk('ftp')->makeDirectory($dirPath);

        error_log("createDir");

        error_log($createDir);

        try {
            $upload_success = Storage::disk('ftp')->put($dirPath . '/' . $filenametostore, fopen($request->file('File'), 'r+'));
            error_log('$upload_success');
            error_log($upload_success);

        } catch (Exception $ex) {

            error_log('exception');
            error_log($ex);
            return response()->json(['data' => null, 'message' => $ex->getMessage()], 400);
        }

        $date = HelperModel::getDate();

        DB::beginTransaction();

        // IF UPLOAD IS SUCCESSFUL SEND SUCCESS MESSAGE OTHERWISE SEND ERROR MESSAGE
        if ($upload_success == true) {

            error_log('upload successfully done');
            error_log('Now insert data in file upload table');

            $fileUpload = array(
                'ByUserId' => $byUserId,
                'RelativePath' => $dirPath,
                'FileOriginalName' => $filename . '.' . $extension,
                'FileName' => $filenameWithoutExtension,
                'FileExtension' => '.' . $extension,
                'FileSizeByte' => $fileSize,
                'BelongTo' => 'forum',
                'CreatedOn' => $date["timestamp"],
                'IsActive' => true
            );
            //Now inserting data in file_upload table

            $insertedData = GenericModel::insertGenericAndReturnID('file_upload', $fileUpload);

            if ($insertedData == 0) {
                error_log('data not inserted');
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in inserting file upload information'], 400);
            } else {
                error_log('data inserted in file upload');
                error_log('File upload id is : ' . $insertedData);

                DB::commit();
                return response()->json(['data' => $insertedData, 'message' => 'Forum topic file uploaded successfully'], 200);
            }
        } else {
            return response()->json(['data' => null, 'message' => 'Error in uploading file'], 400);
        }
    }

    function UploadForumCommentFile(Request $request)
    {
        error_log('in controller');

        $byUserId = $request->get('byUserId');

        $forumTopicDir = env('FORUM_TOPIC_DIR');
        $forumTopicCommentDir = env('FORUM_COMMENT_DIR');

        error_log('user record found');

        $file = $request->file('File');

        if (!isset($file)) {
            return response()->json(['data' => null, 'message' => 'File is missing'], 400);
        }

        //get filename with extension
        $filenamewithextension = $request->file('File')->getClientOriginalName();
        error_log(' File with extension ' . $filenamewithextension);

        //get filename without extension
        $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
        error_log(' Only file name is:  ' . $filename);

        //get file extension
        $extension = $request->file('File')->getClientOriginalExtension();
        error_log(' File extension is:  ' . $extension);

        $filenameWithoutExtension = $filename . '_' . uniqid();

        //filename to store
        $filenametostore = $filename . '_' . uniqid() . '.' . $extension;
        error_log(' File name unique id is : ' . $filenametostore);

        $fileSize = $request->file('File')->getSize();
        error_log(' File size is : ' . $fileSize);

        $dirPath = $byUserId . '/' . $forumTopicDir . '/' . $forumTopicCommentDir . '/';

        $createDir = Storage::disk('ftp')->makeDirectory($dirPath);

        error_log("createDir");

        error_log($createDir);

        try {
            $upload_success = Storage::disk('ftp')->put($dirPath . '/' . $filenametostore, fopen($request->file('File'), 'r+'));
            error_log('$upload_success');
            error_log($upload_success);

        } catch (Exception $ex) {

            error_log('exception');
            error_log($ex);
            return response()->json(['data' => null, 'message' => $ex->getMessage()], 400);
        }

        $date = HelperModel::getDate();

        DB::beginTransaction();

        // IF UPLOAD IS SUCCESSFUL SEND SUCCESS MESSAGE OTHERWISE SEND ERROR MESSAGE
        if ($upload_success == true) {

            error_log('upload successfully done');
            error_log('Now insert data in file upload table');

            $fileUpload = array(
                'ByUserId' => $byUserId,
                'RelativePath' => $dirPath,
                'FileOriginalName' => $filename . '.' . $extension,
                'FileName' => $filenameWithoutExtension,
                'FileExtension' => '.' . $extension,
                'FileSizeByte' => $fileSize,
                'BelongTo' => 'forum_comment',
                'CreatedOn' => $date["timestamp"],
                'IsActive' => true
            );
            //Now inserting data in file_upload table

            $insertedData = GenericModel::insertGenericAndReturnID('file_upload', $fileUpload);

            if ($insertedData == 0) {
                error_log('data not inserted');
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in inserting file upload information'], 400);
            } else {
                error_log('File and data uploaded ');
                DB::commit();
                return response()->json(['data' => $insertedData, 'message' => 'Forum topic comment file uploaded successfully'], 200);
            }

        } else {
            return response()->json(['data' => null, 'message' => 'Error in uploading file'], 400);
        }
    }

    function UploadPatientAssessmentFile(Request $request)
    {
        error_log('in controller');

        $byUserId = $request->get('byUserId');

        $patientAssessmentDir = env('PATIENT_RECORD_DIR');

        $file = $request->file('File');

        if (!isset($file)) {
            return response()->json(['data' => null, 'message' => 'File is missing'], 400);
        }

        error_log('record found');

        //get filename with extension
        $filenamewithextension = $request->file('File')->getClientOriginalName();
        error_log(' File with extension ' . $filenamewithextension);

        //get filename without extension
        $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
        error_log(' Only file name is:  ' . $filename);

        //get file extension
        $extension = $request->file('File')->getClientOriginalExtension();
        error_log(' File extension is:  ' . $extension);

        $filenameWithoutExtension = $filename . '_' . uniqid();

        //filename to store
        $filenametostore = $filename . '_' . uniqid() . '.' . $extension;
        error_log(' File name unique id is : ' . $filenametostore);

        $fileSize = $request->file('File')->getSize();
        error_log(' File size is : ' . $fileSize);

        $dirPath = $byUserId . '/' . $patientAssessmentDir . '/';

        $createDir = Storage::disk('ftp')->makeDirectory($dirPath);

        error_log("createDir");

        error_log($createDir);

        try {
            $upload_success = Storage::disk('ftp')->put($dirPath . '/' . $filenametostore, fopen($request->file('File'), 'r+'));
            error_log('$upload_success');
            error_log($upload_success);

        } catch (Exception $ex) {

            error_log('exception');
            error_log($ex);
            return response()->json(['data' => null, 'message' => $ex->getMessage()], 400);
        }

        $date = HelperModel::getDate();

        DB::beginTransaction();

        // IF UPLOAD IS SUCCESSFUL SEND SUCCESS MESSAGE OTHERWISE SEND ERROR MESSAGE
        if ($upload_success == true) {

            error_log('upload successfully done');
            error_log('Now insert data in file upload table');

            $fileUpload = array(
                'ByUserId' => $byUserId,
                'RelativePath' => $dirPath,
                'FileOriginalName' => $filename . '.' . $extension,
                'FileName' => $filenameWithoutExtension,
                'FileExtension' => '.' . $extension,
                'FileSizeByte' => $fileSize,
                'BelongTo' => 'forum',
                'CreatedOn' => $date["timestamp"],
                'IsActive' => true
            );
            //Now inserting data in file_upload table

            $insertedData = GenericModel::insertGenericAndReturnID('file_upload', $fileUpload);

            if ($insertedData == 0) {
                error_log('data not inserted');
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in inserting file upload information'], 400);
            } else {
                DB::commit();
                return response()->json(['data' => $insertedData, 'message' => 'Patient assessment file uploaded successfully'], 200);
            }

        } else {
            return response()->json(['data' => null, 'message' => 'Error in uploading file'], 400);
        }
    }

    function UploadTicketFile(Request $request)
    {
        error_log('in controller');

        $file = $request->file('File');

        if (!isset($file)) {
            return response()->json(['data' => null, 'message' => 'File is missing'], 400);
        }

        $byUserId = $request->get('byUserId');

        $ticketDir = env('TICKET_DIR');

        error_log('record found');

        //get filename with extension
        $filenamewithextension = $request->file('File')->getClientOriginalName();
        error_log(' File with extension ' . $filenamewithextension);

        //get filename without extension
        $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
        error_log(' Only file name is:  ' . $filename);

        //get file extension
        $extension = $request->file('File')->getClientOriginalExtension();
        error_log(' File extension is:  ' . $extension);

        $filenameWithoutExtension = $filename . '_' . uniqid();

        //filename to store
        $filenametostore = $filename . '_' . uniqid() . '.' . $extension;
        error_log(' File name unique id is : ' . $filenametostore);

        $fileSize = $request->file('File')->getSize();
        error_log(' File size is : ' . $fileSize);

        $dirPath = $byUserId . '/' . $ticketDir . '/';

        $createDir = Storage::disk('ftp')->makeDirectory($dirPath);

        error_log("createDir");

        error_log($createDir);

        try {
            $upload_success = Storage::disk('ftp')->put($dirPath . '/' . $filenametostore, fopen($request->file('File'), 'r+'));
            error_log('$upload_success');
            error_log($upload_success);

        } catch (Exception $ex) {

            error_log('exception');
            error_log($ex);
            return response()->json(['data' => null, 'message' => $ex->getMessage()], 400);
        }

        $date = HelperModel::getDate();

        DB::beginTransaction();

        // IF UPLOAD IS SUCCESSFUL SEND SUCCESS MESSAGE OTHERWISE SEND ERROR MESSAGE
        if ($upload_success == true) {

            error_log('upload successfully done');
            error_log('Now insert data in file upload table');

            $fileUpload = array(
                'ByUserId' => $byUserId,
                'RelativePath' => $dirPath,
                'FileOriginalName' => $filename . '.' . $extension,
                'FileName' => $filenameWithoutExtension,
                'FileExtension' => '.' . $extension,
                'FileSizeByte' => $fileSize,
                'BelongTo' => 'ticket',
                'CreatedOn' => $date["timestamp"],
                'IsActive' => true
            );
            //Now inserting data in file_upload table

            $insertedData = GenericModel::insertGenericAndReturnID('file_upload', $fileUpload);

            if ($insertedData == 0) {
                error_log('data not inserted');
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in inserting file upload information'], 400);
            } else {
                DB::commit();
                return response()->json(['data' => $insertedData, 'message' => 'Ticket file uploaded successfully'], 200);
            }

        } else {
            return response()->json(['data' => null, 'message' => 'Error in uploading file'], 400);
        }
    }

    function UploadTicketReplyFile(Request $request)
    {
        error_log('in controller');

        $file = $request->file('File');

        if (!isset($file)) {
            return response()->json(['data' => null, 'message' => 'File is missing'], 400);
        }

        $byUserId = $request->get('byUserId');

        $ticketDir = env('TICKET_DIR');
        $ticketReplyDir = env('TICKET_REPLY_DIR');

        error_log('record found');

        //get filename with extension
        $filenamewithextension = $request->file('File')->getClientOriginalName();
        error_log(' File with extension ' . $filenamewithextension);

        //get filename without extension
        $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
        error_log(' Only file name is:  ' . $filename);

        //get file extension
        $extension = $request->file('File')->getClientOriginalExtension();
        error_log(' File extension is:  ' . $extension);

        $filenameWithoutExtension = $filename . '_' . uniqid();

        //filename to store
        $filenametostore = $filename . '_' . uniqid() . '.' . $extension;
        error_log(' File name unique id is : ' . $filenametostore);

        $fileSize = $request->file('File')->getSize();
        error_log(' File size is : ' . $fileSize);

        $dirPath = $byUserId . '/' . $ticketDir . '/' . $ticketReplyDir . '/';

        $createDir = Storage::disk('ftp')->makeDirectory($dirPath);

        error_log("createDir");

        error_log($createDir);

        try {
            $upload_success = Storage::disk('ftp')->put($dirPath . '/' . $filenametostore, fopen($request->file('File'), 'r+'));
            error_log('$upload_success');
            error_log($upload_success);

        } catch (Exception $ex) {

            error_log('exception');
            error_log($ex);
            return response()->json(['data' => null, 'message' => $ex->getMessage()], 400);
        }

        $date = HelperModel::getDate();

        DB::beginTransaction();

        // IF UPLOAD IS SUCCESSFUL SEND SUCCESS MESSAGE OTHERWISE SEND ERROR MESSAGE
        if ($upload_success == true) {

            error_log('upload successfully done');
            error_log('Now insert data in file upload table');

            $fileUpload = array(
                'ByUserId' => $byUserId,
                'RelativePath' => $dirPath,
                'FileOriginalName' => $filename . '.' . $extension,
                'FileName' => $filenameWithoutExtension,
                'FileExtension' => '.' . $extension,
                'FileSizeByte' => $fileSize,
                'BelongTo' => 'ticket_reply',
                'CreatedOn' => $date["timestamp"],
                'IsActive' => true
            );
            //Now inserting data in file_upload table

            $insertedData = GenericModel::insertGenericAndReturnID('file_upload', $fileUpload);

            if ($insertedData == 0) {
                error_log('data not inserted');
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in inserting file upload information'], 400);
            } else {
                DB::commit();
                return response()->json(['data' => $insertedData, 'message' => 'Ticket reply file uploaded successfully'], 200);
            }

        } else {
            return response()->json(['data' => null, 'message' => 'Error in uploading file'], 400);
        }
    }

    function UploadCcmFile(Request $request)
    {
        error_log('in controller');

        $file = $request->file('File');

        if (!isset($file)) {
            return response()->json(['data' => null, 'message' => 'File is missing'], 400);
        }

        $byUserId = $request->get('byUserId');

        $ccmPlanDir = env('CCM_PLAN_DIR');

        error_log('record found');

        //get filename with extension
        $filenamewithextension = $request->file('File')->getClientOriginalName();
        error_log(' File with extension ' . $filenamewithextension);

        //get filename without extension
        $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
        error_log(' Only file name is:  ' . $filename);

        //get file extension
        $extension = $request->file('File')->getClientOriginalExtension();
        error_log(' File extension is:  ' . $extension);

        $filenameWithoutExtension = $filename . '_' . uniqid();

        //filename to store
        $filenametostore = $filename . '_' . uniqid() . '.' . $extension;
        error_log(' File name unique id is : ' . $filenametostore);

        $fileSize = $request->file('File')->getSize();
        error_log(' File size is : ' . $fileSize);

        $dirPath = $byUserId . '/' . $ccmPlanDir . '/';

        $createDir = Storage::disk('ftp')->makeDirectory($dirPath);

        error_log("createDir");

        error_log($createDir);

        try {
            $upload_success = Storage::disk('ftp')->put($dirPath . '/' . $filenametostore, fopen($request->file('File'), 'r+'));
            error_log('$upload_success');
            error_log($upload_success);

        } catch (Exception $ex) {

            error_log('exception');
            error_log($ex);
            return response()->json(['data' => null, 'message' => $ex->getMessage()], 400);
        }

        $date = HelperModel::getDate();

        DB::beginTransaction();

        // IF UPLOAD IS SUCCESSFUL SEND SUCCESS MESSAGE OTHERWISE SEND ERROR MESSAGE
        if ($upload_success == true) {

            error_log('upload successfully done');
            error_log('Now insert data in file upload table');

            $fileUpload = array(
                'ByUserId' => $byUserId,
                'RelativePath' => $dirPath,
                'FileOriginalName' => $filename . '.' . $extension,
                'FileName' => $filenameWithoutExtension,
                'FileExtension' => '.' . $extension,
                'FileSizeByte' => $fileSize,
                'BelongTo' => 'ccm_plan',
                'CreatedOn' => $date["timestamp"],
                'IsActive' => true
            );
            //Now inserting data in file_upload table

            $insertedData = GenericModel::insertGenericAndReturnID('file_upload', $fileUpload);

            if ($insertedData == 0) {
                error_log('data not inserted');
                DB::rollBack();
                return response()->json(['data' => null, 'message' => 'Error in inserting ccm plan file information'], 400);
            } else {
                DB::commit();
                return response()->json(['data' => $insertedData, 'message' => 'Ccm plan file uploaded successfully'], 200);
            }

        } else {
            return response()->json(['data' => null, 'message' => 'Error in uploading file'], 400);
        }
    }

    function DownloadProfilePicture($fileUploadId, $fileName)
    {
        error_log('in controller');

        $baseUrl = env('FTP_DIR');

        error_log('Checking if image record is exists in the database');
        $checkDocument = DocumentUploadModel::GetDocumentData($fileUploadId);
        if ($checkDocument == null) {
            return response()->json(['data' => null, 'message' => 'Document not found'], 400);
        } else {
            error_log($checkDocument->FileName . '' . $checkDocument->FileExtension);

            $fileType = "";
            //Now checking if document name is same as it is given in parameter
            if ($fileName == ($checkDocument->FileName . '' . $checkDocument->FileExtension)) {
                error_log('image name is valid');
                $filePath = '/' . $baseUrl . '/' . $checkDocument->RelativePath . $fileName;
                error_log($filePath);
                if (strtolower($checkDocument->FileExtension) == ".jpg" || strtolower($checkDocument->FileExtension) == ".jpeg") {
                    $fileType = env('JPG_IMAGE_TYPE');
                    error_log($fileType);
                } else if (strtolower($checkDocument->FileExtension) == ".png") {
                    $fileType = env('PNG_IMAGE_TYPE');
                    error_log($fileType);
                } else if (strtolower($checkDocument->FileExtension) == ".gif") {
                    $fileType = env('GIF_IMAGE_TYPE');
                    error_log($fileType);
                } else if (strtolower($checkDocument->FileExtension) == ".svg") {
                    $fileType = env('SVG_IMAGE_TYPE');
                    error_log($fileType);
                }
                $ftp = Storage::createFtpDriver([
                    'host' => env('FTP_HOST'),
                    'username' => env('FTP_USER'),
                    'password' => env('FTP_PASSWORD'),
                    'port' => env('FTP_PORT'),
                    'timeout' => env('FTP_TIMEOUT')
                ]);

                $fileContent = $ftp->get($filePath); // read file content

                // download file
                return Response::make($fileContent, '200', array(
                    'Content-Type' => $fileType,
                    'Content-Disposition' => 'inline; filename=' . $fileName . ''
                ));
            } else {
                return response()->json(['data' => null, 'message' => 'Invalid image name'], 400);
            }
        }
    }

    function DownloadDefaultProfilePicture($imageName)
    {
        error_log('## Downloading Default Profile Picture ##');

        $baseUrl = env('FTP_DIR');
        $defaultProfilePicDir = env('DEFAULT_PROFILE_PICTURE_DIR');

        $fileType = "";
        //Now checking if document name is same as it is given in parameter
        if ($imageName == "" || $imageName == null) {
            return response()->json(['data' => null, 'message' => 'Invalid image name'], 400);
        } else {
            error_log('image name is valid');

            $filePath = '/' . $defaultProfilePicDir . '/' . $imageName;
            $fileType = env('PNG_IMAGE_TYPE');

            error_log($filePath);
            error_log($fileType);

            $ftp = Storage::createFtpDriver([
                'host' => env('FTP_HOST'),
                'username' => env('FTP_USER'),
                'password' => env('FTP_PASSWORD'),
                'port' => env('FTP_PORT'),
                'timeout' => env('FTP_TIMEOUT')
            ]);

            $fileContent = $ftp->get($filePath); // read file content

            // download file
            return Response::make($fileContent, '200', array(
                'Content-Type' => $fileType,
                'Content-Disposition' => 'inline; filename=' . $imageName . ''
            ));
        }
    }

    function DownloadTopicFile($fileUploadId, $fileName)
    {
        error_log('in controller');

//        $fileId = $fileUploadId;
//        $fileUplaodName = $fileName;

        error_log('$fileUploadId ' . $fileUploadId);

        return response()->json(['data' => null, 'message' => 'Work in progress'], 200);

        $baseUrl = env('BASE_URL');
        $apiPrefix = env('TOPIC_FILE_API_PREFIX');

        error_log('Checking if user record exists or not');
        $checkDocument = DocumentUploadModel::GetDocumentData($fileUploadId);
        if ($checkDocument == null) {
            return response()->json(['data' => null, 'message' => 'Document not found'], 400);
        } else {
            error_log($checkDocument->FileName . '' . $checkDocument->FileExtension);
            error_log($fileName);
            //Now checking if document name is same as it is given in parameter
            if ($fileName == ($checkDocument->FileName . '' . $checkDocument->FileExtension)) {
                error_log('document name is valid');
                $fileData['Path'] = $baseUrl . '' . $apiPrefix . '' . $checkDocument->RelativePath . '/' . $checkDocument->FileName . '' . $checkDocument->FileExtension;

                return response()->json(['data' => $fileData, 'message' => 'Document found'], 200);

            } else {
                return response()->json(['data' => null, 'message' => 'Invalid document name'], 400);
            }
        }
    }

    function DownloadTopicCommentFile($fileUploadId, $fileName)
    {
        error_log('in controller');

//        $fileId = $fileUploadId;
//        $fileUplaodName = $fileName;

        error_log('$fileUploadId ' . $fileUploadId);

        return response()->json(['data' => null, 'message' => 'Work in progress'], 200);

        $baseUrl = env('BASE_URL');
        $apiPrefix = env('TOPIC_COMMENT_FILE_API_PREFIX');

        error_log('Checking if user record exists or not');
        $checkDocument = DocumentUploadModel::GetDocumentData($fileUploadId);
        if ($checkDocument == null) {
            return response()->json(['data' => null, 'message' => 'Document not found'], 400);
        } else {
            error_log($checkDocument->FileName . '' . $checkDocument->FileExtension);
            error_log($fileName);
            //Now checking if document name is same as it is given in parameter
            if ($fileName == ($checkDocument->FileName . '' . $checkDocument->FileExtension)) {
                error_log('document name is valid');
                $fileData['Path'] = $baseUrl . '' . $apiPrefix . '' . $checkDocument->RelativePath . '/' . $checkDocument->FileName . '' . $checkDocument->FileExtension;

                return response()->json(['data' => $fileData, 'message' => 'Document found'], 200);

            } else {
                return response()->json(['data' => null, 'message' => 'Invalid document name'], 400);
            }
        }
    }

    function DownloadPatientAssessmentFile($fileUploadId, $fileName)
    {
        error_log('in controller');

//        $fileId = $fileUploadId;
//        $fileUplaodName = $fileName;

        error_log('$fileUploadId ' . $fileUploadId);

        return response()->json(['data' => null, 'message' => 'Work in progress'], 200);

        $baseUrl = env('BASE_URL');
        $apiPrefix = env('PATIENT_ASSESSMENT_FILE_API_PREFIX');

        error_log('Checking if user record exists or not');
        $checkDocument = DocumentUploadModel::GetDocumentData($fileUploadId);
        if ($checkDocument == null) {
            return response()->json(['data' => null, 'message' => 'Document not found'], 400);
        } else {
            error_log($checkDocument->FileName . '' . $checkDocument->FileExtension);
            error_log($fileName);
            //Now checking if document name is same as it is given in parameter
            if ($fileName == ($checkDocument->FileName . '' . $checkDocument->FileExtension)) {
                error_log('document name is valid');
                $fileData['Path'] = $baseUrl . '' . $apiPrefix . '' . $checkDocument->RelativePath . '/' . $checkDocument->FileName . '' . $checkDocument->FileExtension;

                return response()->json(['data' => $fileData, 'message' => 'Document found'], 200);

            } else {
                return response()->json(['data' => null, 'message' => 'Invalid document name'], 400);
            }
        }
    }

    function DownloadTicketFile($fileUploadId, $fileName)
    {
        error_log('in controller');

//        $fileId = $fileUploadId;
//        $fileUplaodName = $fileName;

        error_log('$fileUploadId ' . $fileUploadId);

        return response()->json(['data' => null, 'message' => 'Work in progress'], 200);

        $baseUrl = env('BASE_URL');
        $apiPrefix = env('TICKET_FILE_API_PREFIX');

        error_log('Checking if user record exists or not');
        $checkDocument = DocumentUploadModel::GetDocumentData($fileUploadId);
        if ($checkDocument == null) {
            return response()->json(['data' => null, 'message' => 'Document not found'], 400);
        } else {
            error_log($checkDocument->FileName . '' . $checkDocument->FileExtension);
            error_log($fileName);
            //Now checking if document name is same as it is given in parameter
            if ($fileName == ($checkDocument->FileName . '' . $checkDocument->FileExtension)) {
                error_log('document name is valid');
                $fileData['Path'] = $baseUrl . '' . $apiPrefix . '' . $checkDocument->RelativePath . '/' . $checkDocument->FileName . '' . $checkDocument->FileExtension;

                return response()->json(['data' => $fileData, 'message' => 'Document found'], 200);

            } else {
                return response()->json(['data' => null, 'message' => 'Invalid document name'], 400);
            }
        }
    }

    function DownloadTicketReplyFile($fileUploadId, $fileName)
    {
        error_log('in controller');

//        $fileId = $fileUploadId;
//        $fileUplaodName = $fileName;

        error_log('$fileUploadId ' . $fileUploadId);

        return response()->json(['data' => null, 'message' => 'Work in progress'], 200);

        $baseUrl = env('BASE_URL');
        $apiPrefix = env('TICKET_REPLY_FILE_API_PREFIX');

        error_log('Checking if user record exists or not');
        $checkDocument = DocumentUploadModel::GetDocumentData($fileUploadId);
        if ($checkDocument == null) {
            return response()->json(['data' => null, 'message' => 'Document not found'], 400);
        } else {
            error_log($checkDocument->FileName . '' . $checkDocument->FileExtension);
            error_log($fileName);
            //Now checking if document name is same as it is given in parameter
            if ($fileName == ($checkDocument->FileName . '' . $checkDocument->FileExtension)) {
                error_log('document name is valid');
                $fileData['Path'] = $baseUrl . '' . $apiPrefix . '' . $checkDocument->RelativePath . '/' . $checkDocument->FileName . '' . $checkDocument->FileExtension;

                return response()->json(['data' => $fileData, 'message' => 'Document found'], 200);

            } else {
                return response()->json(['data' => null, 'message' => 'Invalid document name'], 400);
            }
        }
    }

    function DownloadCCMPlanFile($fileUploadId, $fileName)
    {
        error_log('in controller');

//        $fileId = $fileUploadId;
//        $fileUplaodName = $fileName;

        error_log('$fileUploadId ' . $fileUploadId);

        return response()->json(['data' => null, 'message' => 'Work in progress'], 200);

        $baseUrl = env('BASE_URL');
        $apiPrefix = env('CCM_PLAN_FILE_API_PREFIX');

        error_log('Checking if user record exists or not');
        $checkDocument = DocumentUploadModel::GetDocumentData($fileUploadId);
        if ($checkDocument == null) {
            return response()->json(['data' => null, 'message' => 'Document not found'], 400);
        } else {
            error_log($checkDocument->FileName . '' . $checkDocument->FileExtension);
            error_log($fileName);
            //Now checking if document name is same as it is given in parameter
            if ($fileName == ($checkDocument->FileName . '' . $checkDocument->FileExtension)) {
                error_log('document name is valid');
                $fileData['Path'] = $baseUrl . '' . $apiPrefix . '' . $checkDocument->RelativePath . '/' . $checkDocument->FileName . '' . $checkDocument->FileExtension;

                return response()->json(['data' => $fileData, 'message' => 'Document found'], 200);

            } else {
                return response()->json(['data' => null, 'message' => 'Invalid document name'], 400);
            }
        }
    }

    function UploadGeneralAttachment(Request $request)
    {
        error_log('in controller');
        $fileSize = $request->file('File')->getSize();
        $extension = $request->file('File')->getClientOriginalExtension();
        $extension = strtolower($extension);

        if ($fileSize >= env('FILE_SIZE_ALLOWED')) {
            error_log("File Size is huge");
            //Image size Not Allowed
            return response()->json(['data' => null, 'message' => 'File size is Not Allowed'], 400);
        } else {
            error_log("File Size is acceptable");
            error_log($extension);

            if ($extension != "jpg" && $extension != "jpeg" && $extension != "png" && $extension != "gif" && $extension != "svg"
                && $extension != "txt" && $extension != "pdf" && $extension != "doc" && $extension != "docx" && $extension != "docs" &&
                $extension != "xls" && $extension != "xlsx" && $extension != "csv" && $extension != "zip" && $extension != "rar") {
                error_log("File type is not allowed");
                //Only Image is allowed
                return response()->json(['data' => null, 'message' => 'This File Type is not allowed'], 400);
            } else {
                error_log('in controller');

                $file = $request->file('File');
                $purpose = $request->get('Purpose');

                $doctorRole = env('ROLE_DOCTOR');
                $facilitatorRole = env('ROLE_FACILITATOR');
                $patientRole = env('ROLE_PATIENT');

                //I have taken this variable because enum and dir name is same
                $dirAndEnumValue = 'none';

                if (!isset($file)) {
                    return response()->json(['data' => null, 'message' => 'File is missing'], 400);
                }

                $byUserId = $request->get('byUserId');

                $checkUserData = UserModel::GetSingleUserViaIdNewFunction($byUserId);
                if ($checkUserData == null) {
                    return response()->json(['data' => null, 'message' => 'User not found'], 400);
                } else {
                    if ($checkUserData->RoleCodeName != $doctorRole) {
                        $dirAndEnumValue = 'doctor_attachment';
                    } else if ($checkUserData->RoleCodeName == $facilitatorRole) {
                        $dirAndEnumValue = 'facilitator_attachment';
                    } else if ($checkUserData->RoleCodeName != $patientRole) {
                        $dirAndEnumValue = 'patient_attachment';
                    } else {
                        return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
                    }
                }

                error_log('record found');

                //get filename with extension
                $filenamewithextension = $request->file('File')->getClientOriginalName();
                error_log(' File with extension ' . $filenamewithextension);

                //get filename without extension
                $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
                error_log(' Only file name is:  ' . $filename);

                //get file extension
//                $extension = $request->file('File')->getClientOriginalExtension();
                error_log(' File extension is:  ' . $extension);

                $filenameWithoutExtension = $filename . '_' . uniqid();

                //filename to store
//        $filenametostore = $filename . '_' . uniqid() . '.' . $extension;
                error_log(' File name unique id is : ' . $filenameWithoutExtension . '.' . $extension);

//                $fileSize = $request->file('File')->getSize();
                error_log(' File size is : ' . $fileSize);

                $dirPath = $dirAndEnumValue . '/';

                $createDir = Storage::disk('ftp')->makeDirectory($dirPath);

                error_log("createDir");

                error_log($createDir);

                try {
                    $upload_success = Storage::disk('ftp')->put($dirPath . '/' . $filenameWithoutExtension . '.' . $extension, fopen($request->file('File'), 'r+'));
                    error_log('$upload_success');
                    error_log($upload_success);

                } catch (Exception $ex) {

                    error_log('exception');
                    error_log($ex);
                    return response()->json(['data' => null, 'message' => $ex->getMessage()], 400);
                }

                $date = HelperModel::getDate();

                error_log("## formatted date ##");
                error_log($date["formatted"]);

                DB::beginTransaction();

                // IF UPLOAD IS SUCCESSFUL SEND SUCCESS MESSAGE OTHERWISE SEND ERROR MESSAGE
                if ($upload_success == true) {

                    error_log('upload successfully done');
                    error_log('Now insert data in file upload table');

                    $fileUpload = array(
                        'ByUserId' => $byUserId,
                        'RelativePath' => $dirPath,
                        'FileOriginalName' => $filename . '.' . $extension,
                        'FileName' => $filenameWithoutExtension,
                        'FileExtension' => '.' . $extension,
                        'FileSizeByte' => $fileSize,
                        'Purpose' => $purpose,
                        'BelongTo' => $dirAndEnumValue,
                        'CreatedOn' => $date["timestamp"],
                        'FileUploadDate' => $date["formatted"],
                        'IsActive' => true
                    );
                    //Now inserting data in file_upload table

                    $insertedData = GenericModel::insertGenericAndReturnID('file_upload', $fileUpload);

                    if ($insertedData == 0) {
                        error_log('data not inserted');
                        DB::rollBack();
                        return response()->json(['data' => null, 'message' => 'Error in inserting file upload information'], 400);
                    } else {
                        DB::commit();
                        return response()->json(['data' => $insertedData, 'message' => 'General file uploaded successfully'], 200);
                    }

                } else {
                    return response()->json(['data' => null, 'message' => 'Error in uploading file'], 400);
                }
            }
        }
    }

    function DownloadGeneralFile($fileUploadId, $fileName)
    {
        error_log('in controller');

        error_log('$fileUploadId ' . $fileUploadId);

        $baseUrl = env('FTP_DIR');
//        $apiPrefix = env('GENERAL_FILE_API_PREFIX');

        error_log('Checking if file record exists or not');
        $checkDocument = DocumentUploadModel::GetDocumentData($fileUploadId);
        if ($checkDocument == null) {
            return response()->json(['data' => null, 'message' => 'File not found'], 400);
        } else {
            error_log($checkDocument->FileName . '' . $checkDocument->FileExtension);
            error_log($fileName);
            $fileType = "";
            //Now checking if document name is same as it is given in parameter
            if ($fileName == ($checkDocument->FileName . '' . $checkDocument->FileExtension)) {
                error_log('document name is valid');
                $filePath = '/' . $baseUrl . '/' . $checkDocument->RelativePath . $fileName;
                if (strtolower($checkDocument->FileExtension) == ".jpg" || strtolower($checkDocument->FileExtension) == ".jpeg") {
                    $fileType = env('JPG_IMAGE_TYPE');
                } else if (strtolower($checkDocument->FileExtension) == ".png") {
                    $fileType = env('PNG_IMAGE_TYPE');
                } else if (strtolower($checkDocument->FileExtension) == ".gif") {
                    $fileType = env('GIF_IMAGE_TYPE');
                } else if (strtolower($checkDocument->FileExtension) == ".svg") {
                    $fileType = env('SVG_IMAGE_TYPE');
                } else if (strtolower($checkDocument->FileExtension) == ".txt") {
                    $fileType = env('TXT_FILE_TYPE');
                } else if (strtolower($checkDocument->FileExtension) == ".pdf") {
                    $fileType = env('PDF_FILE_TYPE');
                } else if (strtolower($checkDocument->FileExtension) == ".doc" || strtolower($checkDocument->FileExtension) == ".docx") {
                    $fileType = env('DOC_FILE_TYPE');
                } else if (strtolower($checkDocument->FileExtension) == ".xls") {
                    $fileType = env('XLS_FILE_TYPE');
                } else if (strtolower($checkDocument->FileExtension) == ".xlsx") {
                    $fileType = env('XLSX_FILE_TYPE');
                } else if (strtolower($checkDocument->FileExtension) == ".csv") {
                    $fileType = env('CSV_FILE_TYPE');
                } else if (strtolower($checkDocument->FileExtension) == ".zip") {
                    $fileType = env('ZIP_FILE_TYPE');
                } else if (strtolower($checkDocument->FileExtension) == ".rar") {
                    $fileType = env('RAR_FILE_TYPE');
                }
                $ftp = Storage::createFtpDriver([
                    'host' => env('FTP_HOST'),
                    'username' => env('FTP_USER'),
                    'password' => env('FTP_PASSWORD'),
                    'port' => '21', // your ftp port
                    'timeout' => '30', // timeout setting
                ]);

                $fileContent = $ftp->get($filePath); // read file content

                // download file
                return Response::make($fileContent, '200', array(
                    'Content-Type' => $fileType,
                    'Content-Disposition' => 'inline; filename=' . $fileName . ''
                ));

            } else {
                return response()->json(['data' => null, 'message' => 'Invalid document name'], 400);
            }
        }
    }

    function GeneralFileListViaPagination(Request $request)
    {
        error_log('in controller');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $patientRole = env('ROLE_PATIENT');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        $byUserId = $request->get('userId');
        $searchDateFrom = $request->get('searchDateFrom');
        $searchDateTo = $request->get('searchDateTo');
        $searchKeyword = $request->get('searchKeyword');
        $byUserRoleId = $request->get('byUserRole');
        $pageNumber = $request->get('pageNo');
        $limit = $request->get('limit');

        $baseUrl = env('BASE_URL');
        $apiPrefix = env('GENERAL_FILE_API_PREFIX');

        $ids = array();

        if ($searchDateFrom == "null" && $searchDateTo != "null" || $searchDateFrom != "null" && $searchDateTo == "null") {
            return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 400);
        }

        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($byUserId);

        if ($checkUserData == null) {
            error_log('user record not found');
            return response()->json(['data' => null, 'message' => 'User not found'], 400);
        } else {

            array_push($ids, $byUserId);

            error_log('user record found');
            if ($checkUserData->RoleCodeName == $doctorRole) {
                error_log('logged in user is doctor');
                error_log('Now fetching its association with patients and facilitator');

                //First getting associated patients
                $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($byUserId, $doctorPatientAssociation);
                error_log('$getAssociatedPatients are ' . $getAssociatedPatients);
                if (count($getAssociatedPatients) > 0) {
                    error_log('associated patients are there');
                    //Means associated patients are there
                    foreach ($getAssociatedPatients as $item) {
                        array_push($ids, $item->DestinationUserId);
                    }
                }

                //Now getting associated

                $getAssociatedFacilitators = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($byUserId, $doctorFacilitatorAssociation);
                error_log('$getAssociatedFacilitators are ' . $getAssociatedFacilitators);
                if (count($getAssociatedFacilitators) > 0) {
                    error_log('associated facilitators are there');
                    //Means associated patients are there
                    foreach ($getAssociatedFacilitators as $item) {
                        array_push($ids, $item->DestinationUserId);
                    }
                }

                $getAllDocuments = DocumentUploadModel::GetAllGeneralDocumentsForDoctors($ids, $searchDateFrom, $searchDateTo, $searchKeyword, $pageNumber, $limit);

                $finalData = array();

                if (count($getAllDocuments) > 0) {
                    error_log('data found');
                    foreach ($getAllDocuments as $item) {

                        if ($byUserRoleId != "null") {
                            error_log('user role is given . ' . $byUserRoleId);
                            if ((int)$byUserRoleId == $item->RoleId) {

                                $data = array(
                                    'Id' => $item->FileUploadId,
                                    'FileOriginalName' => $item->FileOriginalName,
                                    'FileName' => $item->FileName,
                                    'FileExtension' => $item->FileExtension,
                                    'Purpose' => $item->Purpose,
                                    'CreatedOn' => date('d-M-Y h:m:s', $item->CreatedOn),
                                    'BelongTo' => $item->BelongTo,
                                    'Path' => $baseUrl . '' . $apiPrefix . '/' . $item->FileUploadId . '/' . $item->FileName . '' . $item->FileExtension,
                                    'Role' => array(),
                                    'CreatedBy' => array()
                                );

                                $data['Role']['Id'] = $item->RoleId;
                                $data['Role']['Name'] = $item->RoleName;
                                $data['Role']['CodeName'] = $item->RoleCodeName;

                                $data['CreatedBy']['Id'] = $item->UserId;
                                $data['CreatedBy']['FirstName'] = $item->FirstName;
                                $data['CreatedBy']['LastName'] = $item->LastName;

                                array_push($finalData, $data);
                            }

                        } else {
                            error_log('user role is not given');

                            $data = array(
                                'Id' => $item->FileUploadId,
                                'FileOriginalName' => $item->FileOriginalName,
                                'FileName' => $item->FileName,
                                'FileExtension' => $item->FileExtension,
                                'Purpose' => $item->Purpose,
                                'CreatedOn' => date('d-M-Y h:m:s', $item->CreatedOn),
                                'BelongTo' => $item->BelongTo,
                                'Path' => $baseUrl . '' . $apiPrefix . '/' . $item->FileUploadId . '/' . $item->FileName . '' . $item->FileExtension,
                                'Role' => array(),
                                'CreatedBy' => array()
                            );

                            $data['Role']['Id'] = $item->RoleId;
                            $data['Role']['Name'] = $item->RoleName;
                            $data['Role']['CodeName'] = $item->RoleCodeName;

                            $data['CreatedBy']['Id'] = $item->UserId;
                            $data['CreatedBy']['FirstName'] = $item->FirstName;
                            $data['CreatedBy']['LastName'] = $item->LastName;

                            array_push($finalData, $data);
                        }
                    }
                    if (count($finalData) > 0) {

                        return response()->json(['data' => $finalData, 'message' => 'Files found'], 200);
                    } else {

                        return response()->json(['data' => null, 'message' => 'Files not found'], 200);
                    }

                } else {
                    error_log('data not found');

                    return response()->json(['data' => null, 'message' => 'Files not found'], 200);
                }

            } else if ($checkUserData->RoleCodeName == $facilitatorRole) {
                error_log('logged in user is facilitator');

                //First get associated doctors id.
                $getAssociatedDoctorsId = UserModel::getSourceUserIdViaLoggedInUserId($byUserId);
                $doctorIds = array();
                if (count($getAssociatedDoctorsId) > 0) {
                    error_log('Associated doctor found');
                    foreach ($getAssociatedDoctorsId as $item) {
                        array_push($doctorIds, $item->SourceUserId);
                        //Pushing value in our variable
                        array_push($ids, $item->SourceUserId);
                    }
                }

                $getAssociatedPatientIds = UserModel::getAssociatedPatientsUserId($doctorIds, $doctorPatientAssociation);

                if (count($getAssociatedPatientIds) > 0) {
                    error_log('Associated patients found');
                    foreach ($getAssociatedPatientIds as $item) {
                        array_push($ids, $item->DestinationUserId);
                    }
                }

                $getAllDocuments = DocumentUploadModel::GetAllGeneralDocumentsForDoctors($ids, $searchDateFrom, $searchDateTo, $searchKeyword, $pageNumber, $limit);

                $finalData = array();

                if (count($getAllDocuments) > 0) {
                    error_log('data found');
                    foreach ($getAllDocuments as $item) {

                        if ($byUserRoleId != "null") {
                            error_log('user role is given . ' . $byUserRoleId);
                            if ((int)$byUserRoleId == $item->RoleId) {

                                $data = array(
                                    'Id' => $item->FileUploadId,
                                    'FileOriginalName' => $item->FileOriginalName,
                                    'FileName' => $item->FileName,
                                    'FileExtension' => $item->FileExtension,
                                    'Purpose' => $item->Purpose,
                                    'CreatedOn' => date('d-M-Y h:m:s', $item->CreatedOn),
                                    'BelongTo' => $item->BelongTo,
                                    'Path' => $baseUrl . '' . $apiPrefix . '/' . $item->FileUploadId . '/' . $item->FileName . '' . $item->FileExtension,
                                    'Role' => array(),
                                    'CreatedBy' => array()
                                );

                                $data['Role']['Id'] = $item->RoleId;
                                $data['Role']['Name'] = $item->RoleName;
                                $data['Role']['CodeName'] = $item->RoleCodeName;

                                $data['CreatedBy']['Id'] = $item->UserId;
                                $data['CreatedBy']['FirstName'] = $item->FirstName;
                                $data['CreatedBy']['LastName'] = $item->LastName;

                                array_push($finalData, $data);
                            }

                        } else {
                            error_log('user role is not given');

                            $data = array(
                                'Id' => $item->FileUploadId,
                                'FileOriginalName' => $item->FileOriginalName,
                                'FileName' => $item->FileName,
                                'FileExtension' => $item->FileExtension,
                                'Purpose' => $item->Purpose,
                                'CreatedOn' => date('d-M-Y h:m:s', $item->CreatedOn),
                                'BelongTo' => $item->BelongTo,
                                'Path' => $baseUrl . '' . $apiPrefix . '/' . $item->FileUploadId . '/' . $item->FileName . '' . $item->FileExtension,
                                'Role' => array(),
                                'CreatedBy' => array()
                            );

                            $data['Role']['Id'] = $item->RoleId;
                            $data['Role']['Name'] = $item->RoleName;
                            $data['Role']['CodeName'] = $item->RoleCodeName;

                            $data['CreatedBy']['Id'] = $item->UserId;
                            $data['CreatedBy']['FirstName'] = $item->FirstName;
                            $data['CreatedBy']['LastName'] = $item->LastName;

                            array_push($finalData, $data);
                        }
                    }
                    if (count($finalData) > 0) {

                        return response()->json(['data' => $finalData, 'message' => 'Files found'], 200);
                    } else {

                        return response()->json(['data' => null, 'message' => 'Files not found'], 200);
                    }

                } else {
                    error_log('data not found');

                    return response()->json(['data' => null, 'message' => 'Files not found'], 200);
                }

            } else if ($checkUserData->RoleCodeName == $patientRole) {
                error_log('logged in user is patient');
                error_log('documents uploaded by patient will be appeared');

                $getAllDocuments = DocumentUploadModel::GetAllGeneralDocumentsForPatient($ids, $searchDateFrom, $searchDateTo, $searchKeyword, $pageNumber, $limit);

                $finalData = array();

                if (count($getAllDocuments) > 0) {
                    error_log('data found');
                    foreach ($getAllDocuments as $item) {

                        $data = array(
                            'Id' => $item->FileUploadId,
                            'FileOriginalName' => $item->FileOriginalName,
                            'FileName' => $item->FileName,
                            'FileExtension' => $item->FileExtension,
                            'Purpose' => $item->Purpose,
                            'CreatedOn' => date('d-M-Y h:m:s', $item->CreatedOn),
                            'BelongTo' => $item->BelongTo,
                            'Path' => $baseUrl . '' . $apiPrefix . '/' . $item->FileUploadId . '/' . $item->FileName . '' . $item->FileExtension,
                            'Role' => array(),
                            'CreatedBy' => array()
                        );

                        $data['Role']['Id'] = $item->RoleId;
                        $data['Role']['Name'] = $item->RoleName;
                        $data['Role']['CodeName'] = $item->RoleCodeName;

                        $data['CreatedBy']['Id'] = $item->UserId;
                        $data['CreatedBy']['FirstName'] = $item->FirstName;
                        $data['CreatedBy']['LastName'] = $item->LastName;

                        array_push($finalData, $data);
                    }

                    if (count($finalData) > 0) {

                        return response()->json(['data' => $finalData, 'message' => 'Files found'], 200);
                    } else {

                        return response()->json(['data' => null, 'message' => 'Files not found'], 200);
                    }

                } else {
                    error_log('data not found');

                    return response()->json(['data' => null, 'message' => 'Files not found'], 200);
                }

            } else if ($checkUserData->RoleCodeName == $superAdminRole) {
                error_log('logged in user is super admin');
                error_log('all documents uploaded by everyone will be shown');

                $getAllDocuments = DocumentUploadModel::GetAllGeneralDocumentsForAdmin($searchDateFrom, $searchDateTo, $searchKeyword, $pageNumber, $limit);

                $finalData = array();

                if (count($getAllDocuments) > 0) {
                    error_log('data found');
                    foreach ($getAllDocuments as $item) {

                        if ($byUserRoleId != "null") {
                            error_log('user role is given . ' . $byUserRoleId);
                            if ((int)$byUserRoleId == $item->RoleId) {

                                $data = array(
                                    'Id' => $item->FileUploadId,
                                    'FileOriginalName' => $item->FileOriginalName,
                                    'FileName' => $item->FileName,
                                    'FileExtension' => $item->FileExtension,
                                    'Purpose' => $item->Purpose,
                                    'CreatedOn' => date('d-M-Y h:m:s', $item->CreatedOn),
                                    'BelongTo' => $item->BelongTo,
                                    'Path' => $baseUrl . '' . $apiPrefix . '/' . $item->FileUploadId . '/' . $item->FileName . '' . $item->FileExtension,
                                    'Role' => array(),
                                    'CreatedBy' => array()
                                );

                                $data['Role']['Id'] = $item->RoleId;
                                $data['Role']['Name'] = $item->RoleName;
                                $data['Role']['CodeName'] = $item->RoleCodeName;

                                $data['CreatedBy']['Id'] = $item->UserId;
                                $data['CreatedBy']['FirstName'] = $item->FirstName;
                                $data['CreatedBy']['LastName'] = $item->LastName;

                                array_push($finalData, $data);
                            }

                        } else {
                            error_log('user role is not given');

                            $data = array(
                                'Id' => $item->FileUploadId,
                                'FileOriginalName' => $item->FileOriginalName,
                                'FileName' => $item->FileName,
                                'FileExtension' => $item->FileExtension,
                                'Purpose' => $item->Purpose,
                                'CreatedOn' => date('d-M-Y h:m:s', $item->CreatedOn),
                                'BelongTo' => $item->BelongTo,
                                'Path' => $baseUrl . '' . $apiPrefix . '/' . $item->FileUploadId . '/' . $item->FileName . '' . $item->FileExtension,
                                'Role' => array(),
                                'CreatedBy' => array()
                            );

                            $data['Role']['Id'] = $item->RoleId;
                            $data['Role']['Name'] = $item->RoleName;
                            $data['Role']['CodeName'] = $item->RoleCodeName;

                            $data['CreatedBy']['Id'] = $item->UserId;
                            $data['CreatedBy']['FirstName'] = $item->FirstName;
                            $data['CreatedBy']['LastName'] = $item->LastName;

                            array_push($finalData, $data);
                        }
                    }
                    if (count($finalData) > 0) {

                        return response()->json(['data' => $finalData, 'message' => 'Files found'], 200);
                    } else {

                        return response()->json(['data' => null, 'message' => 'Files not found'], 200);
                    }

                } else {
                    error_log('data not found');

                    return response()->json(['data' => null, 'message' => 'Files not found'], 200);
                }

            } else {
                return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
            }
        }

    }

    function GeneralFileListCount(Request $request)
    {
        error_log('in controller');

        $doctorRole = env('ROLE_DOCTOR');
        $facilitatorRole = env('ROLE_FACILITATOR');
        $patientRole = env('ROLE_PATIENT');
        $superAdminRole = env('ROLE_SUPER_ADMIN');

        $doctorFacilitatorAssociation = env('ASSOCIATION_DOCTOR_FACILITATOR');
        $doctorPatientAssociation = env('ASSOCIATION_DOCTOR_PATIENT');

        $byUserId = $request->get('userId');
        $searchDateFrom = $request->get('searchDateFrom');
        $searchDateTo = $request->get('searchDateTo');
        $searchKeyword = $request->get('searchKeyword');
        $byUserRoleId = $request->get('byUserRole');

        $totalCount = 0;

        $ids = array();

        if ($searchDateFrom == "null" && $searchDateTo != "null" || $searchDateFrom != "null" && $searchDateTo == "null") {
            return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 400);
        }

        if ($searchDateFrom != "null" && $searchDateTo != "null") {
            //Do conversion here
        }


        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($byUserId);

        if ($checkUserData == null) {
            error_log('user record not found');
            return response()->json(['data' => null, 'message' => 'User not found'], 400);
        } else {

            array_push($ids, $byUserId);

            error_log('user record found');
            if ($checkUserData->RoleCodeName == $doctorRole) {
                error_log('logged in user is doctor');
                error_log('Now fetching its association with patients and facilitator');

                //First getting associated patients
                $getAssociatedPatients = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($byUserId, $doctorPatientAssociation);
                error_log('$getAssociatedPatients are ' . $getAssociatedPatients);
                if (count($getAssociatedPatients) > 0) {
                    error_log('associated patients are there');
                    //Means associated patients are there
                    foreach ($getAssociatedPatients as $item) {
                        array_push($ids, $item->DestinationUserId);
                    }
                }

                //Now getting associated

                $getAssociatedFacilitators = UserModel::getDestinationUserIdViaLoggedInUserIdAndAssociationType($byUserId, $doctorFacilitatorAssociation);
                error_log('$getAssociatedFacilitators are ' . $getAssociatedFacilitators);
                if (count($getAssociatedFacilitators) > 0) {
                    error_log('associated facilitators are there');
                    //Means associated patients are there
                    foreach ($getAssociatedFacilitators as $item) {
                        array_push($ids, $item->DestinationUserId);
                    }
                }

                $getAllDocuments = DocumentUploadModel::GetAllGeneralDocumentsForDoctorsCount($ids, $searchDateFrom, $searchDateTo, $searchKeyword);

                if (count($getAllDocuments) > 0) {
                    error_log('data found');
                    foreach ($getAllDocuments as $item) {

                        if ($byUserRoleId != "null") {
                            error_log('user role is given . ' . $byUserRoleId);
                            if ((int)$byUserRoleId == $item->RoleId) {

                                $totalCount = $totalCount + 1;
                            }

                        } else {
                            $totalCount = $totalCount + 1;
                        }
                    }

                    return response()->json(['data' => $totalCount, 'message' => 'Total count'], 200);

                } else {
                    return response()->json(['data' => $totalCount, 'message' => 'Total count'], 200);
                }

            } else if ($checkUserData->RoleCodeName == $facilitatorRole) {
                error_log('logged in user is facilitator');

                //First get associated doctors id.
                $getAssociatedDoctorsId = UserModel::getSourceUserIdViaLoggedInUserId($byUserId);
                $doctorIds = array();
                if (count($getAssociatedDoctorsId) > 0) {
                    error_log('Associated doctor found');
                    foreach ($getAssociatedDoctorsId as $item) {
                        array_push($doctorIds, $item->SourceUserId);
                        //Pushing value in our variable
                        array_push($ids, $item->SourceUserId);
                    }
                }

                $getAssociatedPatientIds = UserModel::getAssociatedPatientsUserId($doctorIds, $doctorPatientAssociation);

                if (count($getAssociatedPatientIds) > 0) {
                    error_log('Associated patients found');
                    foreach ($getAssociatedPatientIds as $item) {
                        array_push($ids, $item->DestinationUserId);
                    }
                }

                $getAllDocuments = DocumentUploadModel::GetAllGeneralDocumentsForDoctorsCount($ids, $searchDateFrom, $searchDateTo, $searchKeyword);

                if (count($getAllDocuments) > 0) {
                    error_log('data found');
                    foreach ($getAllDocuments as $item) {

                        if ($byUserRoleId != "null") {
                            error_log('user role is given . ' . $byUserRoleId);
                            if ((int)$byUserRoleId == $item->RoleId) {

                                $totalCount = $totalCount + 1;
                            }

                        } else {
                            $totalCount = $totalCount + 1;
                        }
                    }

                    return response()->json(['data' => $totalCount, 'message' => 'Total count'], 200);

                } else {
                    return response()->json(['data' => $totalCount, 'message' => 'Total count'], 200);
                }

            } else if ($checkUserData->RoleCodeName == $patientRole) {
                error_log('logged in user is patient');
                error_log('documents uploaded by patient will be appeared');

                $getAllDocuments = DocumentUploadModel::GetAllGeneralDocumentsForPatientCount($ids, $searchDateFrom, $searchDateTo, $searchKeyword);

                $totalCount = count($getAllDocuments);

                return response()->json(['data' => $totalCount, 'message' => 'Total Count'], 200);

            } else if ($checkUserData->RoleCodeName == $superAdminRole) {
                error_log('logged in user is super admin');
                error_log('all documents uploaded by everyone will be shown');

                $getAllDocuments = DocumentUploadModel::GetAllGeneralDocumentsForAdminCount($searchDateFrom, $searchDateTo, $searchKeyword);

                if (count($getAllDocuments) > 0) {
                    error_log('data found');
                    foreach ($getAllDocuments as $item) {

                        if ($byUserRoleId != "null") {
                            error_log('user role is given . ' . $byUserRoleId);
                            if ((int)$byUserRoleId == $item->RoleId) {

                                $totalCount = $totalCount + 1;
                            }

                        } else {
                            $totalCount = $totalCount + 1;
                        }
                    }

                    return response()->json(['data' => $totalCount, 'message' => 'Total count'], 200);

                } else {
                    return response()->json(['data' => $totalCount, 'message' => 'Total count'], 200);
                }
            } else {
                return response()->json(['data' => null, 'message' => 'Not allowed'], 400);
            }
        }

    }

    function TestPdf()
    {
        LoginModel::sendEmailAttach("ahmer.saeed.office@gmail.com", "Email Attachment", "This is a sample email", "", "");
        return response()->json(['data' => null, 'message' => 'One of the search date is empty'], 200);
//        return true;
//        error_log("Generate Test PDF");
//
//        $pdf = PDF::loadView('list_notes');
//        return $pdf->download('tuts_notes.pdf');
    }
}
