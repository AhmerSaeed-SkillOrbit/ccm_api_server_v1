<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use App\Models\GenericModel;
use App\Models\HelperModel;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use PhpParser\Node\Stmt\Return_;
use PHPUnit\Util\RegularExpressionTest;

use Exception;
use Mail;

class ServicesModel
{

    static public function sendInviteTrans(Request $request)
    {
        $phoneCode = getenv("PAK_NUM_CODE");//fetch from front-end

        $email = Input::get('email');
        $mobileNumber = $phoneCode . Input::get('mobileNumber');

        $type = Input::get('type');
        $userId = Input::get('userId');

        error_log($mobileNumber);

        $data = $request->all();

        DB::beginTransaction();
        try {

            $invite = DB::table('account_invitation')
                ->select()
                ->where('ToEmailAddress', '=', $email)
                ->get();

            $checkInvite = json_decode(json_encode($invite), true);

            foreach ($invite as $i) {
                error_log('in loop');
                if ($i->Status_ == "accepted" || $i->Status_ == "pending") {
                    if ($i->IsActive == 1) {
                        error_log('is Active');
                        DB::rollBack();
                        return array("status" => "failed", "data" => null, "message" => "Invite is already sent to this Email address");
                    }
                }
            }

            error_log('loop passed');

            $token = md5(uniqid(rand(), true));

            if ($token != null) {

                $date = HelperModel::getDate();

                $insertData = array(
                    "ByUserId" => $userId,
                    "ToEmailAddress" => $email,
                    "ToMobileNumber" => "",
                    "Status_" => "pending",
                    "Token" => $token,
                    "BelongTo" => $type,
                    "CreatedOn" => $date["timestamp"],
                    "IsActive" => 1
                );

                $checkInsertTokenId = DB::table("account_invitation")->insertGetId($insertData);

                if ($checkInsertTokenId) {
                    ServicesModel::sendEmail($email, $type, $token);

                    error_log("email sent");

                    ## Preparing Data for SMS  - START ##
                    if ($mobileNumber != null) {
                        $toNumber = array();
                        $toNumber[0] = $mobileNumber;

                        $content = getenv("ACCOUNT_INVITATION_SMS");
                        $url = url(env('WEB_URL') . '/#/registration') . '?type=' . $type . '&token=' . $token;
                        try {
                            error_log('sms in try block');
                            HelperModel::sendSms($toNumber, $content, $url);
                        } catch (Exception $ex) {
                            error_log('sms exception catched');
                            return array("status" => "success", "data" => null);
                        }
                    }
                    ## Preparing Data for SMS  - END ##

                    DB::commit();
                    return array("status" => "success", "data" => null);

                } else {
                    DB::rollBack();
                    return array("status" => "failed", "data" => null);
                }
            } else {
                DB::rollBack();
                return array("status" => "failed", "data" => null);
            }

//            if (count($checkInvite) > 0) {
//
//                DB::rollBack();
//                return array("status" => "failed", "data" => null, "message" => "Invite is already sent to this Email address");
//
//            } else {
//
//
//            }

        } catch (Exception $e) {

            echo "error";
            DB::rollBack();
            return array("status" => "error", "data" => null);
            //   return $e;
        }
    }


    static public function inviteUpdate(Request $request)
    {

        $token = Input::get('Token');

        try {

            $invite = DB::table('account_invitation')
                ->select()
                ->where('Token', '=', $token)
                ->get();

            $checkInvite = json_decode(json_encode($invite), true);

            if (count($checkInvite) > 0) {

                if ($checkInvite[0]['Status_'] == "pending") {

                    $inviteUpdateData = array(
                        "Status_" => "ignored",
                        "IsActive" => 0
                    );

                    $inviteUpdate = DB::table('account_invitation')
                        ->where('Id', $checkInvite[0]['Id'])
                        ->update($inviteUpdateData);

                    if ($inviteUpdate > 0) {
                        // return array("status" => "success", "data" => true, "message" => "Your Invitation is pending");
                        return array("status" => "success", "data" => $checkInvite[0]['ToEmailAddress'], "message" => "Your Invitation is pending");
                    } else {
                        return array("status" => "failed", "data" => null, "message" => "Your Invitation is pending");
                    }

                }
                if ($checkInvite[0]['Status_'] == "ignored" && $checkInvite[0]['IsActive'] == 0) {
                    // return array("status" => "success", "data" => true, "message" => "Your Invitation is pending");
                    return array("status" => "success", "data" => $checkInvite[0]['ToEmailAddress'], "message" => "Your Invitation is pending");
                } else if ($checkInvite[0]['Status_'] == "accepted") {
                    return array("status" => "failed", "data" => null, "message" => "Your invitation is accepted");
                } else if ($checkInvite[0]['Status_'] == "rejected") {
                    return array("status" => "failed", "data" => null, "message" => "Your invitation is rejected");
                } else {
                    return array("status" => "failed", "data" => null, "message" => "Your invitation is ignored");
                }
            } else {

                return array("status" => "failed", "data" => null, "message" => "Invitation code not found");
            }

        } catch (Exception $e) {

            return array("status" => "error", "data" => null);
            //   return $e;
        }
    }

    private static function sendEmail($email, $type, $token)
    {
        $url = url(env('WEB_URL') . '/#/registration') . '?type=' . $type . '&token=' . $token;
        Mail::raw('Invitation URL ' . $url, function ($message) use ($email) {
            $message->to($email)->subject("Invitation");
        });
        return true;
    }
}
