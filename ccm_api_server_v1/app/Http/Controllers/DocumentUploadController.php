<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use App\Models\UserModel;
use App\Models\GenericModel;
use App\Models\HelperModel;
use Carbon\Carbon;
use mysql_xdevapi\Exception;


class DocumentUploadController extends Controller
{
    function UploadFiles(Request $request)
    {
        error_log('in controller');

        //get filename with extension
        $filenamewithextension = $request->file('file')->getClientOriginalName();

        error_log(' $filenamewithextension ' . $filenamewithextension);

        //get filename without extension
        $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);

        error_log(' $filename ' . $filename);

        //get file extension
        $extension = $request->file('file')->getClientOriginalExtension();

        //filename to store
        $filenametostore = $filename . '_' . uniqid() . '.' . $extension;

        $createDir = Storage::disk('ftp')->makeDirectory('1/forum');

        error_log("createDir");

        error_log($createDir);

        error_log(' $filenametostore ' . $filenametostore);

        try {
            $upload_success = Storage::disk('ftp')->put('/1/forum/' . $filenametostore, fopen($request->file('file'), 'r+'));
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
//        $filenamewithextension = $request->file('file')->getClientOriginalName();
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
//        $extension = $request->file('file')->getClientOriginalExtension();
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
//        $upload_success = Storage::disk('ftp')->put($filename, fopen($request->file('file'), 'r+'));
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

        $userId = $request->get('userId');
        $byUserId = $request->get('byUserId');

        $profileDirectory = env('PROFILE_PICTURE_DIR');

        error_log('Checking if user record exists or not');
        $checkUserData = UserModel::GetSingleUserViaIdNewFunction($userId);
        if ($checkUserData == null) {
            return response()->json(['data' => null, 'message' => 'User record not found'], 400);
        }

        error_log('user record found');

        //get filename with extension
        $filenamewithextension = $request->file('file')->getClientOriginalName();
        error_log(' File with extension ' . $filenamewithextension);

        //get filename without extension
        $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
        error_log(' Only file name is:  ' . $filename);

        //get file extension
        $extension = $request->file('file')->getClientOriginalExtension();
        error_log(' File extension is:  ' . $extension);

        $filenameWithoutExtension = $filename . '_' . uniqid();

        //filename to store
        $filenametostore = $filename . '_' . uniqid() . '.' . $extension;
        error_log(' File name unique id is : ' . $filenametostore);

        $fileSize = $request->file('file')->getSize();
        error_log(' File size is : ' . $fileSize);

        $dirPath = $byUserId . '/' . $profileDirectory;

        $createDir = Storage::disk('ftp')->makeDirectory($dirPath);

        error_log("createDir");

        error_log($createDir);

        try {
            $upload_success = Storage::disk('ftp')->put($dirPath . '/' . $filenametostore, fopen($request->file('file'), 'r+'));
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
                    return response()->json(['data' => $insertedData, 'message' => 'User profile picture uploaded successfully'], 200);
                }
            }

        } else {
            return response()->json(['data' => null, 'message' => 'Error in uploading profile picture'], 400);
        }
    }
}
