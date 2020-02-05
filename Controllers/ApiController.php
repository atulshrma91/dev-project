<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Storage;
use Auth;
use App\Models\ContactForm;
use Illuminate\Support\Facades\Validator;
use App\Notifications\ContactMessage;
use Illuminate\Support\Facades\Notification;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use View;

class ApiController extends Controller{

    public function __construct(Request $request){
      $tangolanguage =  $request->header('tangolanguage');
      \App::setLocale($tangolanguage);
    }

    public function savecontactform(Request $request){
      $rules = [
        'email' => 'required|email',
        'age' => 'required',
        'captcha' => 'required',
        'files' => 'required',
      ];
      $customErrMsg = [
          'email.required' => 'Email id is required.',
      ];
      $validator = Validator::make($request->all(), $rules, $customErrMsg);
      if ($validator->fails()) {
          $errMsg = $validator->messages();
          return response()->json(['success' => false, 'data' => $errMsg]);
      } else {
        $age = '';
        if($files=$request->file('files')){
          foreach($files as $file){
              $filename = rand().'_'.$file->getClientOriginalName();
              Storage::disk('uploads')->put('contact_form/'.$filename, \File::get($file));
              $images[]= $filename;
          }
        }
        if($request->get('age')){
          $ageObj = new \DateTime($request->get('age'));
          $age = $ageObj->format('d-m-Y');
        }
        $data = array(
          'name' => ($request->get('name'))?$request->get('name'):'',
          'email' => ($request->get('email'))?$request->get('email'):'',
          'phone' => ($request->get('phone'))?$request->get('phone'):'',
          'age' => $age,
          'height' => ($request->get('height'))?$request->get('height'):'',
          'country' => ($request->get('country'))?$request->get('country'):'',
          'language' => ($request->get('language'))?$request->get('language'):'',
          'captcha' => ($request->get('captcha'))?$request->get('captcha'):'',
          'files' => implode("|", $images),
        );
        ContactForm::create($data);

        $view = View::make('mail.thankyou');
        $contents = (string) $view;
        $client = new Client();
        $result = $client->post('https://api.postmarkapp.com/email', [
            'json' => [
                'From' => 'hello@tangomodels.com',
                'To' => $request->get('email'),
                'Subject' => 'Thank you',
                'HtmlBody' => $contents,
            ],
            'headers' => [
                'Accept'     => 'application/json',
                'Content-Type'     => 'application/json',
                'X-Postmark-Server-Token'     => '653c84d3-c32d-44c5-aff3-a0f7b353fbf7',
            ]
        ]);
        if($result->getStatusCode() == 200){
          return response()->json(['success' => true, 'data' =>  trans('messages.thanks')]);
        }
        /*Notification::route('mail', $request->get('email'))
            ->notify(new ContactMessage($request->get('name')));*/
      }

    }

    public function set_locale(Request $request){
      return response()->json(['success' => true, 'data' => \App::getLocale()]);
    }

    public function authlogin(Request $request){
      $rules = [
        'email' => 'required|email',
        'password' => 'required',
      ];
      $customErrMsg = [
          'email.required' => 'Email id is required',
      ];
      $validator = Validator::make($request->all(), $rules, $customErrMsg);
      if ($validator->fails()) {
          $errMsg = $validator->messages();
          return response()->json(['success' => false, 'data' => $errMsg]);
      } else {
        if (Auth::attempt(['email' => $request->get('email'), 'password' => $request->get('password')], $request->get('remember'))) {
            $user = Auth::user();
            $api_token = rand();
            $user->api_token = $api_token;
            $user->update();
            $userdata = array(
              "is_login" => true,
              "name" => $user->name,
              "api_token" => $api_token
            );
            return response()->json(['success' => true, 'data' => $userdata]);
        }else{
          $object = new \stdClass;
          $object->validationErr = array('Please check your credentials and try again.');
          return response()->json(['success' => false, 'data' => $object]);
        }
      }
    }

    public function authlogout(Request $request){
      if(\Auth::check()){
         $user = Auth::user();
         $user->api_token = "";
         $user->update();
         return response()->json(['success' => true]);
       }else{
        return response()->json(['success' => false]);
       }
    }

    public function get_contactform(Request $request){
      if(\Auth::check()){
        $contact_form = ContactForm::orderBy('id', 'desc');
        $contact_form = $contact_form->get();
        if(!$contact_form->isEmpty()){
          $data = [];
          foreach($contact_form as $cfk => $contact){
            $data[$cfk]['name'] = $contact->name;
            $data[$cfk]['email'] = $contact->email;
            $data[$cfk]['phone'] = $contact->phone;
            $data[$cfk]['age'] = $contact->age;
            $data[$cfk]['country'] = $contact->country;
            $data[$cfk]['id'] = $contact->id;
          }
          return response()->json(['success' => true, 'data' => $data]);
        }else{
          return response()->json(['success' => false]);
        }
       }else{
        return response()->json(['success' => false]);
       }
    }

    public function get_contactuser(Request $request, $id){
      if(\Auth::check()){
        $contact_form = ContactForm::find($id);
        if($contact_form){
          return response()->json(['success' => true, 'data' => $contact_form]);
        }else{
          return response()->json(['success' => false]);
        }
       }else{
        return response()->json(['success' => false]);
       }
    }
    public function delete_contactuser(Request $request, $id){
      if(\Auth::check()){
        $contact_form = ContactForm::find($id);
        if($contact_form){
          $contact_form->delete();
          $contact_form = ContactForm::orderBy('id', 'desc');
          $contact_form = $contact_form->get();
          $data = [];
          if(!$contact_form->isEmpty()){
            foreach($contact_form as $cfk => $contact){
              $data[$cfk]['name'] = $contact->name;
              $data[$cfk]['email'] = $contact->email;
              $data[$cfk]['phone'] = $contact->phone;
              $data[$cfk]['age'] = $contact->age;
              $data[$cfk]['country'] = $contact->country;
              $data[$cfk]['id'] = $contact->id;
            }
          }
          return response()->json(['success' => true, 'data' =>$data]);
        }else{
          return response()->json(['success' => false]);
        }
       }else{
        return response()->json(['success' => false]);
       }
    }

}
