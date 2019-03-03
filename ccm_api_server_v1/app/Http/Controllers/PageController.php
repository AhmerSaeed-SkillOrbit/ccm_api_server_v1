<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use View;
use Illuminate\Http\Request;
use App\Models\SystemSettingsModel;
use App\Models\MenuBarModel;
use App\Models\UserRolesModel;
use App\Models\UserModel;
use App\Models\PortModel;
use App\Models\HelperModel;
use App\Models\ServicesModel;
use App\Models\PaymentTermsModel;
use App\Models\GenericModel;
use App\Models\ForgetPasswordModel;
use Config;
use Twilio\Rest\Client as TwilioClient;

class PageController extends BaseController
{

//    General Pages //

    function adminHome()
    {
        return view('admin_home');
    }

    function adminLogin()
    {
        return view('admin_login');
    }

    function home()
    {
        return view('home');
    }

    function login()
    {
        return view('login');
    }

//    List Pages //

    function systemSettingsList()
    {

        if (HelperModel::getRoleNameFromSessionData() == 'Super User') {
            $val = SystemSettingsModel::getsystemsettingslist();
            $resultArray = json_decode(json_encode($val), true);
            $data['systemsettings'] = $resultArray;
            return View::make('lists.systemsettings_list', $data);
        } else {
            $companyID = Config::get('settings.company_id');
            $result = SystemSettingsModel::find($companyID);
            $data['fetchresult'] = json_decode(json_encode($result[0]), true);
            $data['id'] = $data['fetchresult']['id'];
            if ($data['id'] != NULL) {
                $data['formType'] = 'update';
                return View::make('forms.systemsettings_form')->with($data);
            } else {
                return redirect(url('/'))->withError(['No Company is exist']);
            }
        }
    }

    function menuBarList()
    {
        $data['menubars'] = MenuBarModel::getMenuBarslist();
        return View::make('lists.menubar_list')->with($data);
    }

    function userRolesList()
    {
        $data['Roles'] = UserRolesModel::loadRolesDetails();
        return View::make('lists.userroles_list')->with($data);
    }

    function userList()
    {
        $val = UserModel::getUsersList();
        $resultArray = json_decode(json_encode($val), true);
        $data['users'] = $resultArray;
        return View::make('lists.user_list')->with($data);
    }

    function portList()
    {
        $val = PortModel::getPortlist();
        $resultArray = json_decode(json_encode($val), true);
        $data['ports'] = $resultArray;
        return View::make('lists.port_list')->with($data);
    }

    function servicesList()
    {
        $data['services'] = ServicesModel::getServicelist();
        return View::make('lists.services_list')->with($data);
    }

    function portTypeList()
    {

        $val = DB::table('port_type')->select()
            ->get();
        $resultArray = json_decode(json_encode($val), true);
        $data['portType'] = $resultArray;

        return View::make('lists.porttype_list')->with($data);
    }

    function serviceTypeList()
    {
        $val = DB::table('service_type')->select()
            ->get();
        $resultArray = json_decode(json_encode($val), true);
        $data['serviceType'] = $resultArray;

        return View::make('lists.servicetype_list')->with($data);
    }

    function paymentTermsList()
    {
//        $val = PaymentTermsModel::getPaymentTermList();
//        $resultArray = json_decode(json_encode($val), true);
//        $data['paymentTerms'] = $resultArray;
        $data['paymentTerms'] = PaymentTermsModel::getPaymentTermsList();
        return View::make('lists.paymentterms_list')->with($data);
//        return View::make('lists.paymentterms_list');
    }

    function taskApproverList()
    {
        $taskApprover = HelperModel::getTaskApproverFromSession();
        if ($taskApprover == 1) {
            $customerQuery = DB::table('customer_actions')
                ->join('user', 'user.UserID', '=', 'customer_actions.CreatedBy')
                ->select('ActionID', DB::raw('"Customer" as TaskType'), 'ActionType', 'State', 'customer_actions.Created', 'CompletedDate', 'FirstName', 'ContactPerson');

            $billingFileQuery = DB::table('bookingfile_action')
                ->join('user', 'user.UserID', '=', 'bookingfile_action.CreatedBy')
                ->select('ActionID', DB::raw('"File" as TaskType'), 'ActionType', 'State', 'bookingfile_action.Created', 'CompletedDate', 'FirstName', 'FileNumber as ContactPerson');

            $vendorQuery = DB::table('vendor_actions')
                ->join('user', 'user.UserID', '=', 'vendor_actions.CreatedBy')
                ->select('ActionID', DB::raw('"Vendor" as TaskType'), 'ActionType', 'State', 'vendor_actions.Created', 'CompletedDate', 'FirstName', 'ContactPerson')
                ->union($customerQuery)
                ->union($billingFileQuery)
                ->orderBy('ActionID', 'desc')
                ->get();
            $resultArray = json_decode(json_encode($vendorQuery), true);
            $data['taskApprover'] = $resultArray;
            return View::make('lists.taskapprover_list')->with($data);
        }
        return redirect(url('/'))->withErrors(['Access Denied']);
    }

    function customerList()
    {
        $val = DB::table('customer')
            ->select('CustomerID', 'CustomerCode', 'CustomerName', 'BillingAddress', 'ContactPerson', 'Phone', 'CellPhone', 'Email')
            ->get();
        $resultArray = json_decode(json_encode($val), true);
        $data['customers'] = $resultArray;
        return View::make('lists.customer_list')->with($data);
    }

    function vendorList()
    {
        $val = DB::table('vendor')
            ->select('VendorID', 'VendorCode', 'VendorName', 'BillingAddress', 'ContactPerson', 'Phone', 'CellPhone', 'Email')
            ->get();

        $resultArray = json_decode(json_encode($val), true);
        $data['vendors'] = $resultArray;
        return View::make('lists.vendor_list')->with($data);
    }

    function rateSearchList()
    {
        //$data['fetchedResult'] = PageController::rateQuery();
        return View::make('lists.ratesearch_list')->with(PageController::rateQuery());
    }

    function rateSetList()
    {
        //$data['fetchedResult'] = PageController::rateQuery();
        return View::make('lists.rateset_list')->with(PageController::rateQuery());
    }

    private static function rateQuery()
    {
        $val = DB::table('rate')
            ->leftJoin('vendor', 'rate.VendorID', '=', 'vendor.VendorID')
            ->leftJoin('port as p1', 'p1.PortID', '=', 'rate.Origin')
            ->leftJoin('port as p2', 'p2.PortID', '=', 'rate.Destination')
            ->select("rate.RateID", "vendor.VendorID", "vendor.VendorName", "rate.ValidUntil", "rate.Rate", "rate.RateType", "p1.PortID as OriginPortID", "rate.Origin as Origin",
                "p1.PortName as OriginPortName", "p2.PortID as DestinationPortID", "rate.Destination as Destination", "p2.PortName as DestinationPortName"
            )->get();
        $ports = DB::table('port')
            ->select()
            ->get();
        $data['fetchedResult'] = json_decode(json_encode($val), true);
        $data['ports'] = json_decode(json_encode($ports), true);
        return $data;
    }

    function quotationList()
    {
        $val = DB::table('quote')
            ->leftJoin('customer', 'customer.CustomerID', '=', 'quote.CustomerID')
            ->leftJoin('port as p1', 'p1.PortID', '=', 'quote.OriginPort')
            ->leftJoin('port as p2', 'p2.PortID', '=', 'quote.DestinationPort')
            ->select('quote.*', 'customer.*', 'p1.PortName as OriginName', 'p2.PortName as DestinationName')
            ->get();
        $ports = DB::table('port')
            ->select()
            ->get();
        $portList = json_decode(json_encode($ports), true);
        $data['ports'] = $portList;
        $data['fetchedResult'] = json_decode(json_encode($val), true);
        return View::make('lists.quotation_list')->with($data);
    }

    function customerFileList()
    {
        $val = DB::table('bookingfile')
            ->leftJoin('customer', 'customer.CustomerID', '=', 'bookingfile.CustomerID')
            ->leftJoin('port as p1', 'p1.PortID', '=', 'bookingfile.PortOfLoading')
            ->leftJoin('port as p2', 'p2.PortID', '=', 'bookingfile.PortOfDischarge')
            ->select('customer.*', 'bookingfile.*', 'p1.PortName as PortOfLoading', 'p2.PortName as FinalDestination')
            ->get();
        $data['fetchedResult'] = json_decode(json_encode($val), true);
        return View::make('lists.customerfile_list')->with($data);
    }

//    Form Pages //

    function systemSettingsForm($formtype, $id)
    {
        if (HelperModel::getRoleNameFromSessionData() == 'Super User') {
            if ($formtype == "add") {
                $data['formType'] = $formtype;
                return View::make('forms.systemsettings_form', $data);
            } else {
                $result = SystemSettingsModel::find($id);
                if (isset($result)) {
                    $data['fetchresult'] = json_decode(json_encode($result[0]), true);
                    $data['id'] = $id;
                    $data['formType'] = $formtype;
                    return View::make('forms.systemsettings_form')->with($data);
                } else {
                    return redirect(url('system_settings'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
                }
            }
        } else {
            return redirect(url('/'));
        }
    }

    function menuSettingsForm($formtype, $id)
    {

        if ($formtype == "add") {
            $resultArray = MenuBarModel::getRootMenuBar();
            if (isset($resultArray)) {
                $data = [];
                foreach ($resultArray as $result) {
                    array_push($data, $result['name']);
                }
                return View::make('forms.menubar_form')->with(['menuBarLevels' => $data, 'formType' => $formtype]);
            } else {
                return redirect(url('menu_settings'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
            }
        } else {
            $result = MenuBarModel::find($id);
            $fetchresult = json_decode(json_encode($result[0]), true);
            $resultArray = MenuBarModel::getRootMenuBar();
            if (isset($resultArray)) {
                $data = [];
                foreach ($resultArray as $result) {
                    array_push($data, $result['name']);
                }
                return View::make('forms.menubar_form')->with(['menuBarLevels' => $data, 'formType' => $formtype, 'id' => $id, 'fetchresult' => $fetchresult]);
            } else {
                return redirect(url('menu_settings'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
            }
        }
    }

    function userRolesForm($formtype, $id)
    {
        $helperController = new HelperController;
        $menuBar = $helperController->allMenuBarList();
        $users = DB::table('user')->select('FirstName', 'LastName', 'user.UserID')
            ->join('userrole', 'userrole.UserID', '=', 'user.UserID')
            ->where('userrole.RoleID', '=', $id)
            ->get();
        $userList = json_decode(json_encode($users), true);

        if ($formtype == "add") {
            return View::make('forms.userroles_form')->with(['data' => $menuBar, 'users' => $userList, 'formType' => $formtype, 'id' => $id]);
        } else {
            if (UserRolesModel::getRoleNameByID($id) != 'Super User') {
                $result = UserRolesModel::find($id);
                if (isset($result)) {
                    $fetchresult = json_decode(json_encode($result[0]), true);
                    return View::make('forms.userroles_form')->with(['data' => $menuBar, 'users' => $userList, 'formType' => $formtype, 'id' => $id, 'fetchResult' => $fetchresult]);
                } else {
                    return "Problem In fetching data in view";
                }
            } else {
                return redirect(url('user_roles'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
            }
        }
    }

    function userForm($formtype, $id = null)
    {
        //$roles = UserRolesModel::loadRoles();
        $roles = NULL;

        if ($formtype == "add") {
            view('user_signup');
            return View::make('user_signup')->with(['data' => $roles, 'formType' => $formtype]);
        } else {
            $result = UserModel::find($id);
            if (isset($result)) {
                $fetchresult = json_decode(json_encode($result[0]), true);
                return View::make('forms.user_form')->with(['data' => $roles, 'formType' => $formtype, 'id' => $id, 'fetchresult' => $fetchresult]);
            } else {
                return redirect(url('user'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
            }
        }


//        if (isset($roles)) {
//            if ($formtype == "add") {
//                return View::make('forms.user_form')->with(['data' => $roles, 'formType' => $formtype]);
//            } else {
//                $result = UserModel::find($id);
//                if (isset($result)) {
//                    $fetchresult = json_decode(json_encode($result[0]), true);
//                    return View::make('forms.user_form')->with(['data' => $roles, 'formType' => $formtype, 'id' => $id, 'fetchresult' => $fetchresult]);
//                } else {
//                    return redirect(url('user'))->withErrors([ Config::get('settings.form_edit_data_not_found')]);
//                }
//            }
//        } else {
//            return redirect(url('user'))->withErrors([ Config::get('settings.form_edit_data_not_found')]);
//        }
    }

    function patientForm()
    {
        return view('add_patient');
    }

    function portForm($formtype, $id)
    {
        $portDB = DB::table('port_type')->select('id', 'port_type')->get();
        $country = DB::table('country')->select('CountryID', 'CountryName')->get();
        $port = json_decode(json_encode($portDB), true);
        $data = array("portType" => $port);
        $countryData = json_decode(json_encode($country), true);
        $data2 = array("countries" => $countryData);
        if ($formtype == "add") {
            return View::make('forms.port_form')->with($data)->with($data2)->with(["formType" => $formtype, "id" => $id]);
        } else {
            $result = PortModel::find($id);
            if (isset($result)) {
                $fetchResult = json_decode(json_encode($result[0]), true);
                return View::make('forms.port_form')->with($data)->with($data2)->with(["formType" => $formtype, "id" => $id, "fetchResult" => $fetchResult]);
            } else {
                return redirect(url('port'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
            }
        }
    }

    function servicesProductForm($formtype, $id)
    {
        $serviceTypes = DB::table('service_type')->select('id', 'service_type')->get();
        $services = json_decode(json_encode($serviceTypes), true);
        if ($formtype == "add") {
            return View::make('forms.services_form')->with(["services" => $services])->with(["formType" => $formtype, "id" => $id]);
        } else {
            $result = ServicesModel::find($id);
            if (isset($result)) {
                $fetchResult = json_decode(json_encode($result[0]), true);
                return View::make('forms.services_form')->with(["services" => $services])->with(["formType" => $formtype, "id" => $id, "fetchResult" => $fetchResult]);
            } else {
                return redirect(url('services_product'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
            }
        }
    }

    function portTypeForm($formtype, $id)
    {
        if ($formtype == "add") {
            $data['formType'] = $formtype;
            return View::make('forms.porttype_form')->with($data);
        } else {
            $result = DB::table('port_type')->select()
                ->where('id', '=', $id)
                ->get();
            if (isset($result)) {
                $data['fetchresult'] = json_decode(json_encode($result[0]), true);
                $data['id'] = $id;
                $data['formType'] = $formtype;
                return View::make('forms.porttype_form')->with($data);
            } else {
                return redirect(url('service_type'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
            }
        }
    }

    function serviceTypeForm($formtype, $id)
    {
        if ($formtype == "add") {
            $data['formType'] = $formtype;
            return View::make('forms.servicetype_form')->with($data);
        } else {
            $result = DB::table('service_type')->select()
                ->where('id', '=', $id)
                ->get();
            if (isset($result)) {
                $data['fetchresult'] = json_decode(json_encode($result[0]), true);
                $data['id'] = $id;
                $data['formType'] = $formtype;
                return View::make('forms.servicetype_form')->with($data);
            } else {
                return redirect(url('port_type'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
            }
        }
    }

    function customerForm($formType, $id)
    {
        $paymentTerms = DB::table('payment_terms')->select()->where('status', '=', 1)->get();
        $data['paymentTerms'] = json_decode(json_encode($paymentTerms), true);
        $data['formType'] = $formType;

        if ($formType == 'add') {
            return View::make('forms.customer_form')->with($data);
        } else {
            $result = DB::table('customer')->where('CustomerID', '=', $id)->get();
            if (isset($result)) {
                $data['fetchresult'] = json_decode(json_encode($result[0]), true);
                return View::make('forms.customer_form')->with($data);
            } else {
                return redirect(url('customer'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
            }
        }
    }

    function vendorForm($formType, $id)
    {
        $data['formType'] = $formType;
        if ($formType == 'add') {
            $data['formType'] = $formType;
            return View::make('forms.vendor_form')->with($data);
        } else {
            $vendor = DB::table('vendor')
                ->select()
                ->where('VendorID', '=', $id)
                ->get();
            if (isset($vendor)) {
                $data['fetchresult'] = json_decode(json_encode($vendor[0]), true);
                return View::make('forms.vendor_form')->with($data);
            } else {
                return redirect(url('vendor'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
            }
        }
    }

    function paymentTermsForm($formType, $id)
    {
        if ($formType == 'add') {
            $data['formType'] = $formType;
            return View::make('forms.paymentterms_form')->with($data);
        } else {
            $result = PaymentTermsModel::find($id);
            if (isset($result)) {
                $data['fetchresult'] = json_decode(json_encode($result[0]), true);
                $data['id'] = $id;
                $data['formType'] = $formType;
                return View::make('forms.paymentterms_form')->with($data);
            } else {
                return redirect(url('payment_terms'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
            }
        }
    }

    function rateSetForm($formType, $id)
    {

        $port = DB::table('port')
            ->leftJoin('port_type', 'port.PortType_ID', '=', 'port_type.id')
            ->select()
            ->get();

        $vendor = DB::table('vendor')
            ->select()
            ->get();
        $portList = json_decode(json_encode($port), true);
        $vendorList = json_decode(json_encode($vendor), true);

        if ($formType == 'add') {
            $fetchedResult = array("vendor" => $vendorList, "ports" => $portList, "formType" => $formType, "id" => $id);
            return View::make('forms.rateset_form')->with($fetchedResult);
        } else {
            $result = DB::table('rate')
                ->leftJoin('vendor', 'rate.VendorID', '=', 'vendor.VendorID')
                ->leftJoin('port as p1', 'p1.PortID', '=', 'rate.Origin')
                ->leftJoin('port as p2', 'p2.PortID', '=', 'rate.Destination')
                ->where('RateID', '=', $id)
                ->select("vendor.VendorID", "vendor.VendorName", "rate.ValidUntil", "rate.Rate", "rate.RateType", "p1.PortID as OriginPortID", "rate.Origin as Origin", "p2.PortName as OriginPortName", "p2.PortID as DestinationPortID", "rate.Destination as Destination", "p2.PortName as DestinationPortName"
                )->get();

            if (isset($result)) {
                $fetchedResult = json_decode(json_encode($result), true);
                $data['fetchedResult'] = $fetchedResult[0];
                $data['ports'] = $portList;
                $data['vendor'] = $vendorList;
                $data['id'] = $id;
                $data['formType'] = $formType;
                return View::make('forms.rateset_form')->with($data);
            } else {
                return redirect(url('rate_set'))->withErrors([Config::get('settings.form_edit_data_not_found')]);
            }
        }
    }

    public function quotationForm($formType, $id)
    {
        $customer = DB::table('customer')->select("CustomerID", "CustomerName")->get();
        $customerList = json_decode(json_encode($customer), true);

        $ports = DB::table('port')
            ->select("PortID", "PortName")
            ->leftJoin('port_type', 'PortID', '=', 'id')
            ->get();
        $portList = json_decode(json_encode($ports), true);

        $services = DB::table('service')
            ->select("ServiceID", "ServiceName")
            ->leftJoin('service_type', 'ServiceID', '=', 'id')
            ->get();
        $serviceList = json_decode(json_encode($services), true);

        $data['ports'] = $portList;
        $data['customers'] = $customerList;
        $data['services'] = $serviceList;
        $data['formType'] = $formType;
        $data['id'] = $id;

        if ($formType == 'update') {
            $quote = DB::table('quote')
                ->select()
                ->where('QuoteID', '=', $id)
                ->get();
            $quoteDetail = DB::table('quotedetails')
                ->select()
                ->where('QuoteID', '=', $id)
                ->get();
            $data['quote'] = json_decode(json_encode($quote[0]), true);
            $data['quoteDetails'] = json_decode(json_encode($quoteDetail), true);
        }

        return View::make('forms.quotation_form')->with($data);
    }

    function customerFileForm($formType, $id)
    {
        $quote = DB::table('quote')->select()->get();
        $services = DB::table('service')->select()->get();
        $data['quoteList'] = $quoteList = json_decode(json_encode($quote), true);
        $data['serviceList'] = $quoteList = json_decode(json_encode($services), true);
        $data['formType'] = $formType;
        $data['id'] = $id;

        if ($formType == 'update') {
            $customerFile = DB::table('bookingfile')
                ->leftJoin('customer', 'customer.CustomerID', '=', 'bookingfile.CustomerID')
                ->leftJoin('port as p1', 'p1.PortID', '=', 'bookingfile.PortOfLoading')
                ->leftJoin('port as p2', 'p2.PortID', '=', 'bookingfile.PortOfDischarge')
                ->where('FileNumber', '=', $id)
                ->select('bookingfile.*', 'customer.*', 'p1.PortID as OriginPort', 'p1.PortName as OriginPortName', 'p2.PortID as DestinationPort', 'p2.PortName as DestinationPortName')
                ->get();
            $fetchedCustomerFile = json_decode(json_encode($customerFile), true);
            $data['fetchedResult'] = $fetchedCustomerFile[0];

            $bookingDetail = DB::table('bookingfiledetails')
                ->where('FileNumber', '=', $id)
                ->select()
                ->get();
            $fetchedBookingDetail = json_decode(json_encode($bookingDetail), true);
            $data['bookingDetail'] = $fetchedBookingDetail;

            $bookingExpense = DB::table('bookingfileexpense')
                ->where('FileNumber', '=', $id)
                ->select()
                ->get();
            $fetchedBookingExpense = json_decode(json_encode($bookingExpense), true);
            $data['bookingExpense'] = $fetchedBookingExpense;
        }

        return View::make('forms.customerfile_form')->with($data);
    }

    function forgetPasswordForm()
    {
        return view('forms.forgetpassword_form');
    }

    function resetPasswordForm()
    {
        $token = \Illuminate\Support\Facades\Input::get('token');
        $forgetPassword = DB::table('user_forget_password')
            ->select()
            ->where('Status', '=', 'ACTIVE')
            ->where('Token', '=', $token)
            ->where(DB::raw('FROM_UNIXTIME(ExpireTime)'), '>', \Carbon\Carbon::now())
            ->get();
        if (count($forgetPassword) > 0)
            return view('forms.resetpassword_form')->with(['token' => $token]);
        else
            return view('forms.forgetpassword_form')->withErrors('No Token Found, Enter Email Again');
    }

    //Role list via pagination
    function RoleListViaPagination(Request $request)
    {
        $offset = $request->input('p');
        $limit = $request->input('c');
        $keyword = $request->input('s');

        //error_log($keyword);
        $val = GenericModel::simpleFetchGenericWithPaginationByWhereWithSortOrderAndSearchKeyword
        ('role', '=', 'IsActive', true, $offset, $limit, 'SortOrder', $keyword, 'Name');

        $resultArray = json_decode(json_encode($val), true);
        $data = $resultArray;
        if (count($data) > 0) {
            return response()->json(['data' => $data, 'message' => 'Roles fetched'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Roles not found'], 200);
        }
    }

    //ROle list for combo box

    function RoleList()
    {

        $val = GenericModel::simpleFetchGenericByWhere
        ('role', '=', 'IsActive', true, 'SortOrder');

        $resultArray = json_decode(json_encode($val), true);
        $data = $resultArray;
        if (count($data) > 0) {
            return response()->json(['data' => $data, 'message' => 'Roles fetched'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Roles not found'], 200);
        }
    }

    //role list count API

    function RoleCount(Request $request)
    {
        $keyword = $request->input('s');

        $val = GenericModel::simpleFetchGenericCountWIthKeyword
        ('role', '=', 'IsActive', true, 'Name', $keyword);

        return response()->json(['data' => $val, 'message' => 'Roles count'], 200);
    }

    public function Index()
    {
        echo "Hi";
    }

    public function TestEmail()
    {
        $result = ForgetPasswordModel::forgetPassword();
        return response()->json(['data' => $result, 'message' => 'Check Email'], 200);
    }

    public function TestSms()
    {
        $twilioAccountSid   = getenv("TWILIO_SID");
        $twilioAuthToken    = getenv("TWILIO_TOKEN");
        $myTwilioNumber = getenv("TWILIO_NUMBER");

        $twilioClient = new TwilioClient($twilioAccountSid, $twilioAuthToken);

        $twilioClient->messages->create(
        // Where to send a text message
            '+923122410823',
            array(
                "from" => $myTwilioNumber,
                "body" => "Hey! Tech event begins in 2 days!"
            )
        );

//        return $this->sendTwilioSmsReminders();

        return response()->json(['data' => true, 'message' => 'Check SMS'], 200);
    }


    /**
     * Send messages using Twilio API client
     *
     * @param array $subscribers - Subscribers info
     *
     * @return void
     */
    public function sendTwilioSmsReminders()
    {

        foreach( $subscribers as $subscriber) {
            $this->twilioClient->messages->create(
            // Where to send a text message
                $subscriber[0],
                array(
                    "from" => $myTwilioNumber,
                    "body" => "Hey! ". $subscriber[1] . ", the ".$subscriber[2] ." Tech event begins in 2 days!"
                )
            );
        }
        return "Successfully sent ". count($subscribers) . " reminder(s)";
    }

    //Permission list via pagination
    function PermissionListViaPagination(Request $request)
    {
        error_log('In controller');
        $offset = $request->get('p');
        $limit = $request->get('c');
        $keyword = $request->get('s');

        //error_log($keyword);
        $val = GenericModel::simpleFetchGenericWithPaginationByWhereWithSortOrderAndSearchKeyword
        ('permission', '=', 'IsActive', true, $offset, $limit, 'SortOrder', $keyword, 'Name');

        $resultArray = json_decode(json_encode($val), true);
        $data = $resultArray;
        if (count($data) > 0) {
            return response()->json(['data' => $data, 'message' => 'Permission fetched'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Permission not found'], 200);
        }
    }

    //Permission list for combo box

    function PermissionList()
    {

        error_log('In controller');

        $val = GenericModel::simpleFetchGenericByWhere
        ('permission', '=', 'IsActive', true, 'SortOrder');

        $resultArray = json_decode(json_encode($val), true);
        $data = $resultArray;
        if (count($data) > 0) {
            return response()->json(['data' => $data, 'message' => 'Permission fetched'], 200);
        } else {
            return response()->json(['data' => null, 'message' => 'Permission not found'], 200);
        }
    }

    //role list count API

    function PermissionCount(Request $request)
    {
        error_log('In controller');

        $keyword = $request->get('s');

        $val = GenericModel::simpleFetchGenericCountWIthKeyword
        ('permission', '=', 'IsActive', true, 'Name', $keyword);

        return response()->json(['data' => $val, 'message' => 'Permission count'], 200);
    }

    function RolePermissionAssign(Request $request)
    {
        error_log('In controller');

        $roleId = $request->RoleId;

        $permissions = $request->Permission;

        DB::beginTransaction();

        //First get the record of role permission with respect to that given role id
        $checkRolePermission = UserModel::getPermissionViaRoleId($roleId);
        //Now check the permission if it exists
        if (count($checkRolePermission) > 0) {
            //then delete it from role_permission
            $result = GenericModel::deleteGeneric('role_permission', 'RoleId', $roleId);
            if ($result == false) {
                DB::rollBack();
            }
        }

        $data = array();

        foreach ($permissions as $item) {
            array_push
            (
                $data,
                array(
                    "RoleId" => $roleId,
                    "PermissionId" => $item['Id'],
                    "IsActive" => true
                )
            );
        }

        //Now inserting data
        $checkInsertedData = GenericModel::insertGeneric('role_permission', $data);
        error_log($checkInsertedData);
        if ($checkInsertedData == true) {
            DB::commit();
            return response()->json(['data' => $roleId, 'message' => 'Permission successfully assigned'], 200);
        } else {
            DB::rollBack();
            return response()->json(['data' => null, 'message' => 'Error in assigning permission'], 400);
        }
    }

}
