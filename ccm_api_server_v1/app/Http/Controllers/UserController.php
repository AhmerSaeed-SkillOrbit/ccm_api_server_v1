<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use View;
use App\Models\UserModel;
use App\Models\GenericModel;
use App\Models\HelperModel;
use Config;

class UserController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {

    //    Input::merge(array_map('trim', Input::except('selectedRoles')));
     //   $validationRules = UserController::getValidateRules();
      //  $validator = Validator::make($request->all(), $validationRules);

        $redirectUserForm = url('/user/add/0');
        $redirectUser = url('/');

  //      if ($validator->fails())
 //           return redirect($redirectUserForm)->withErrors($validator)->withInput(Input::all());


        $isInserted = UserModel::addUser();
     //   if ($isInserted == 'unmatchPassword')
    //        return redirect($redirectUserForm)->withErrors(['confirm password must match the password']);
         if ($isInserted == 'duplicate')
            return redirect($redirectUserForm)->withErrors(['Duplication Error! This First Name and Last Name is already exist'])->withInput(Input::all());
        else if ($isInserted == 'success')
            return redirect($redirectUser)->with(['success' => Config::get('settings.form_save_success_message')]);
        else
            return redirect($redirectUser)->withErrors([Config::get('settings.form_save_failed_message')]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storePatient(Request $request) {

        $redirectUserForm = url('/admin/home');
        $redirectUser = url('/admin/home');
        $isInserted = UserModel::addPatient();
        //   if ($isInserted == 'unmatchPassword')addPatient
        //        return redirect($redirectUserForm)->withErrors(['confirm password must match the password']);
        if ($isInserted == 'duplicate')
            return redirect($redirectUserForm)->withErrors(['Duplication Error! This First Name and Last Name is already exist'])->withInput(Input::all());
        else if ($isInserted == 'success')
            return redirect($redirectUser)->with(['success' => Config::get('settings.form_save_success_message')]);
        else
            return redirect($redirectUser)->withErrors([Config::get('settings.form_save_failed_message')]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request) {
        //
        Input::merge(array_map('trim', Input::except('selectedRoles')));
        $validationRules = UserController::getValidateRulesForUpdate();
        $validator = Validator::make($request->all(), $validationRules);
        $id = $request->input('userID');

        $redirectUserForm = url('/user_form/update/'.$id);
        $redirectUser = url('/user');

        if ($validator->fails())
            return redirect($redirectUserForm)->withErrors($validator)->withInput(Input::all());


        $isUpdated = UserModel::updateUser($request);
        if($isUpdated == 'duplicate')
            return redirect(url('/user_form/update/' . $id))->withErrors(['Duplication Error! This First Name and Last Name is already exist']);
        else if ($isUpdated == 'success' && HelperModel::getUserSessionID() != $id)
            return redirect($redirectUser)->with(['success' => Config::get('settings.form_update_success_message ')]);
        else if ($isUpdated == 'success' && HelperModel::getUserSessionID() == $id)
            return redirect($redirectUser)->with(['success' => Config::get('settings.form_update_success_message ').'.Login again to see changes']);
        else
            return redirect($redirectUser)->withErrors([Config::get('settings.form_update_failed_message')]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $genericModel1 = new GenericModel;
        $row1 = $genericModel1->deleteGeneric('userrole','UserID',$id);
        $genericModel = new GenericModel;
        $row = $genericModel->deleteGeneric('user','UserID',$id);

        if ($row > 0 && $row1 > 0)
            return redirect(url('/user'))->with(['success' => Config::get('settings.form_delete_success_message')]);
        else
            return redirect(url('/user'))->with(['success' => Config::get('settings.form_delete_failed_message')]);
        //
    }

    public function lock($id){
        $result = UserModel::find($id);
        if(isset($result)){
            $fetchresult = json_decode(json_encode($result[0]), true);
//            echo ' '.$fetchresult['Status'].' ';
            $row = UserModel::lock($id,$fetchresult);

            if($row == 'success' && HelperModel::getUserSessionID() != $id){
                return redirect(url('/user'))->with(['success' => 'Lock status successfully changed']);
            }
            else if($row == 'success' && HelperModel::getUserSessionID() == $id){
                return redirect(url('/user'))->with(['success' => 'Lock status successfully changed, login again to see changes']);
            }
            else{
                return redirect(url('/user'))->with(['success' => 'Lock status failed to changed']);
            }
        }else{
            return "Problem in fetching data in view";
        }
    }

    public function find(){
        return UserModel::searchUser();
    }

    private function getValidateRules() {
        $rules = array('firstName' => 'required|alpha|min:2|max:25',
            'lastName' => 'required|alpha|min:2|max:25',
            'password' => ['required','regex:/^(?=.*[a-zA-Z])(?=.*[-=!@#$%^&*_<>?|,.;:\(){}]).{8,}$/'],
            'confirmPassword' => ['required','regex:/^(?=.*[a-zA-Z])(?=.*[-=!@#$%^&*_<>?|,.;:\(){}]).{8,}$/'],
            'email' => 'required|email');

        return $rules;
    }

    private function getValidateRulesForUpdate() {
        $rules = array('firstName' => 'required|alpha|min:2|max:25',
            'lastName' => 'required|alpha|min:2|max:25',
            'email' => 'required|email');

        return $rules;
    }

}
