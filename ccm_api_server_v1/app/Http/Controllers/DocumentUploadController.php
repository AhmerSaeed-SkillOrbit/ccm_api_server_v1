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
}
