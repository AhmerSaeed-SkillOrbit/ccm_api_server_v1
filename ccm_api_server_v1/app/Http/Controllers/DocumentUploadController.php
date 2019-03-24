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


class DocumentUploadController extends Controller
{
    function UploadFiles(Request $request)
    {
//        $ticketClose = env('TICKET_TRACK_STATUS_CLOSE');

        error_log('in controller');
//        $input = Input::all();

//        $file = array_get($input, 'file');
        // SET UPLOAD PATH
        $destinationPath = 'E:\IMAGES';
        // GET THE FILE EXTENSION
//        $extension = $file->getClientOriginalExtension();
        // RENAME THE UPLOAD WITH RANDOM NUMBER
//        $fileName = rand(11111, 99999) . '.' . $extension;
        // MOVE THE UPLOADED FILES TO THE DESTINATION DIRECTORY
//        $upload_success = $file->move($destinationPath, $fileName);
        //Upload File to external server

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

        error_log(' $filenametostore ' . $filenametostore);

        $upload_success = Storage::disk('ftp')->put($filename, fopen($request->file('file'), 'r+'));

        error_log(' $upload_success ' . $upload_success);

        // IF UPLOAD IS SUCCESSFUL SEND SUCCESS MESSAGE OTHERWISE SEND ERROR MESSAGE
        if ($upload_success) {
            return response()->json(['data' => null, 'message' => 'File successfully uploaded'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Error in uploading file'], 400);
        }
    }
}
