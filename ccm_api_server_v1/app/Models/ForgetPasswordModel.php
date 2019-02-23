<?php

namespace App\Models;

use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use DB;
use Mail;

class ForgetPasswordModel {
    
    static function forgetPassword() {
        return ForgetPasswordModel::sendEmail( "ahmer.saeed.office@gmail.com", 1 );
    }
    
    static function resetPassword() {
        $password = Input::get('password');
        $confirmPassword = Input::get('confirmPassword');
        $token = Input::get('token');
        
        if($password != $confirmPassword)
            return 'incorrect';
        
        $hashedPassword = md5($password);
        if( ForgetPasswordModel::checkForExpirationDate($token) && ForgetPasswordModel::updateUserForgetPasswordData($token, $hashedPassword)) {
            return 'success';
        }
        else {
            return 'failed';
        }
        
    }
        
    private static function generateToken($id) {
        $hashed = Hash::make($id);
        return $hashed;
    }
    
    private static function storeTokenAndUserID($data){
        $genericModel = new GenericModel;
        return $genericModel -> insertGeneric('user_forget_password', $data);
    }
    
    private static function checkForExpirationDate($token) {
        $row = DB::table('user_forget_password') 
                -> select() 
                -> where('token', '=', $token)
                -> where('Status', '=', 'ACTIVE')
                -> where(DB::raw('FROM_UNIXTIME(ExpireTime)') , '>' , Carbon::now() )
                -> get();
        if( count($row) > 0)
            return true;
        else
            return false;
    }
    
    private static function updateUserForgetPasswordData($token,$password) {
        $userID = DB::table('user_forget_password') 
                -> select('UserID') 
                -> where('token', '=', $token)
                -> get();
        $ids = json_decode( json_encode($userID) , true);
        $uid = $ids[0]['UserID'];
        
        $genericModel = new GenericModel;
        $isUpdated = $genericModel -> updateGeneric('user_forget_password', 'UserID', $uid, ['Status' => 'INACTIVE'] );
        
        if($isUpdated)
            return ForgetPasswordModel::replaceUserPassword($uid,$password);
        return false;
    }
    
    private static function replaceUserPassword($userid, $password) {
        $genericModel = new GenericModel;
        return $genericModel -> updateGeneric('user', 'UserID', $userid, ['Password' => $password] );
    }
    
    private static function sendEmail($email, $token) {
        $url = url('reset_password') . '?token=' . $token;
        Mail::raw('Forget Password URL ' . $url, function ($message) use ($email) {
            $message ->to($email) ->subject("Forget Password");
        });
        
        return true;
    }
}