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

        $email = Input::get('email');
        $type = Input::get('type');
        $userId = Input::get('userId');

        $data = $request->all();

        DB::beginTransaction();
        try {

            // ('ID', 'FirstName', 'LastName','EmailAddress','MobileNumber','TelephoneNumber','Gender','FunctionalTitle','FunctionalTitle')
            $invite = DB::table('account_invitation')
                ->select()
                ->where('ToEmailAddress', '=', $email)
                ->get();

            $checkInvite = json_decode(json_encode($invite), true);

            if (count($checkInvite) > 0) {

                DB::rollBack();
                return array("status" => "failed", "data" => null, "message" => "invite is already send to this email address");

            } else {

                $token = md5(uniqid(rand(), true));
                // $token = LoginModel::generateAccessToken();

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
            }

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

            // ('ID', 'FirstName', 'LastName','EmailAddress','MobileNumber','TelephoneNumber','Gender','FunctionalTitle','FunctionalTitle')
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
                        ->where('id', $checkInvite[0]['Id'])
                        ->update($inviteUpdateData);

                    if ($inviteUpdate > 0) {
                        return array("status" => "success", "data" => true, "message" => "your invitation is in pending");
                    } else {
                        return array("status" => "failed", "data" => null, "message" => "your invitation is in pending");
                    }

                }
                if ($checkInvite[0]['Status_'] == "ignored" && $checkInvite[0]['IsActive'] == 0) {
                    return array("status" => "success", "data" => true, "message" => "your invitation is in pending");
                } else if ($checkInvite[0]['Status_'] == "accepted") {
                    return array("status" => "failed", "data" => null, "message" => "invitation is accepted by this email user");
                } else if ($checkInvite[0]['Status_'] == "rejected") {
                    return array("status" => "failed", "data" => null, "message" => "invitation is rejected by this email user");
                } else {
                    return array("status" => "failed", "data" => null, "message" => "invitation is ignored by this email user");
                }
            } else {

                return array("status" => "failed", "data" => null, "message" => "invitation code not found");
            }

        } catch (Exception $e) {

            return array("status" => "error", "data" => null);
            //   return $e;
        }
    }

    private static function sendEmail($email, $type, $token)
    {
        $url = url('registration') . '?type=' . $type . '&token=' . $token;
        Mail::raw('Invitation URL ' . $url, function ($message) use ($email) {
            $message->to($email)->subject("Invitation");
        });

        return true;
    }

}
