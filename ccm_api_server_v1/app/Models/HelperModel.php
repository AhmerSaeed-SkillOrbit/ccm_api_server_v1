<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use App\Models\GenericModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserRolesModel;
use Carbon\Carbon;
use Session;
use Twilio\Rest\Client as TwilioClient;

class HelperModel
{

    static public function getUserSessionID()
    {
        if (Session::has('sessionLoginData')) {
            $sessionData = HelperModel::getSessionObject();
            return $sessionData['UserID'];
        } else {
            return -1;
        }
    }

    static public function getUserSessionRoleAuth()
    {
        $sessionData = HelperModel::getSessionObject();
        return $sessionData['RoleAndAuth'];
    }

    public static function getMenuIDsFromDatabase($routeName)
    {
        $menuid = DB::table('menu')->select('MenuID')->where('page', '=', $routeName)->get();
        $id = json_decode(json_encode($menuid), true);
        return $id;
    }

    static function getRoleNameFromSessionData()
    {
        $roleAuthOfSessionUser = HelperModel::getUserSessionRoleAuth();
        $roleID = $roleAuthOfSessionUser[0]['RoleID'];
        $roleName = UserRolesModel::getRoleNameByID($roleID);
        return $roleName;
    }

    public static function getUserRoleIDFromSession()
    {
        $sessionObject = HelperModel::getSessionObject();
        $roleIDs = HelperModel::getUniqueRoleIDs($sessionObject['RoleAndAuth']);
        return array_unique($roleIDs);
    }

    public static function getTaskApproverFromSession()
    {
        $sessionObject = HelperModel::getSessionObject();
        $roles = $sessionObject['RoleAndAuth'];
        $taskApprover = $roles[0]['TaskApprover'];
        return $taskApprover;
    }

    private static function getUniqueRoleIDs($roleAndAuth)
    {
        $arr = array();
        foreach ($roleAndAuth as $role) {
            array_push($arr, $role['RoleID']);
        }
        return $arr;
    }

    public static function getSessionObject()
    {
        return Session::get('sessionLoginData');
    }

    public static function getDate()
    {

        $date = Carbon::now();
        return $date->toArray();
    }

    public static function generateAccessToken()
    {
        // return Session::get('sessionLoginData');

        // $hash = md5(uniqid(rand(), true));
        $attemp = 0;
        do {
            $token = md5(uniqid(rand(), true));
            // $user_access_token = DB::table('access_token')->where('AccessToken', $token)->get();
            $user_access_token = GenericModel::simpleFetchGenericByWhere('access_token', "=", "AccessToken", $token);
            $attemp++;
        } while (!empty($user_access_token));
        // while(!empty($user_access_token) || $attemp > 5);

        if (!empty($user_access_token)) {
            return $token;
        } else {
            return null;
        }
    }

    /*
     * @toNumber = array
     * @content = string
     * @data = data to send it can be null
    */
    public static function sendSms($toNumbers, $content, $data = null)
    {
        $twilioAccountSid = getenv("TWILIO_SID");
        $twilioAuthToken = getenv("TWILIO_TOKEN");
        $myTwilioNumber = getenv("TWILIO_NUMBER");

        foreach ($toNumbers as $number) {
            $twilioClient = new TwilioClient($twilioAccountSid, $twilioAuthToken);
            $twilioClient->messages->create(
                $number,
                array(
                    "from" => $myTwilioNumber,
                    "body" => $content . " " . $data
                )
            );
        }

        error_log("sms sent");

        return true;
    }

}
