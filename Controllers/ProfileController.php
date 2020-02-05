<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClienteRegistrado;
use App\Models\ArchivosClienteRegistrado;
use App\Models\TipoClienteRegistrado;
use App\Models\ClienteTipoSuministro;
use App\Models\TipoSuministro;
use App\Models\Suministro;
use App\Models\ClientRegistradoAppointment;
use App\Models\ConversacionesSeguimientos;
use App\Models\Tarifaacceso;
use App\Models\Territorio;

use Response;
use DB;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Notifications\ClientAuthResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redirect;

class ProfileController extends Controller{


  public function __construct() {

      \View::share('titulo_pagina', 'Tarifasdeluz');
  }

  public function dashboard(Request $request){
    $user = Auth::user();
    $profile_scrore = 0;
    if($user->imagen){
      if (filter_var($user->imagen, FILTER_VALIDATE_URL)) {
         $user->imagen = $user->imagen;
      } else {
        $user->imagen = url('uploads/clienteregistrados/client_imagen/'.$user->imagen);
      }
      $profile_scrore += 20;
    }else{
      $user->imagen = '//placehold.it/100';
    }

    if($user->mobile){
      $profile_scrore += 20;
    }
    if($user->is_mobile_verified == 2){
      $profile_scrore += 20;
    }
    if($user->contract_email){
      $profile_scrore += 20;
    }
    if($user->telephone){
      $profile_scrore += 20;
    }
    $profile_percentage = ($profile_scrore*100)/100;
    $suministros = Suministro::leftJoin('clientes_registrados', function($join) {
                    $join->on('clientes_registrados.id', '=', 'contacto_id');
                })->where('contacto_id',  Auth::user()->id)
                ->select('suministros.*')->get();

    return view('profile.dashboard',compact('user','suministros','profile_percentage'));
  }

  public function missuministros(Request $request){
    $user = Auth::user();
    $provincias = Territorio::orderBy('nombre')->get();
    $tarifaaccesos = Tarifaacceso::orderBy('tarifa')->get();

    return view('profile.mis-suministros',compact('user','provincias','tarifaaccesos'));
  }

  public function misofertas(){
    $user = Auth::user();

    return view('profile.mis-ofertas',compact('user'));
  }

  public function misavisos(Request $request){
    $user = Auth::user();
    return view('profile.mis-avisos',compact('user'));
  }

  public function profile(Request $request){
    $user = ClienteRegistrado::find(Auth::user()->id);
    $tipoclientesregistrados = TipoClienteRegistrado::all();
    $tipossuministros = TipoSuministro::all();
    $clientestiposuministros = ClienteTipoSuministro::where('cliente_id', $user->id)->get()->toArray();
    $cli_sumi = array();
    if(!empty($clientestiposuministros)){
        foreach($clientestiposuministros as $cli){
            $cli_sumi[$user->id][$cli['tiposuministro_id']] = 1;
        }
    }
    if($user->suministros->count() > 0){
        foreach ($user->suministros as $suministro) {
            $cli_sumi[$user->id][$suministro->tarifaacceso->tiposuministro_id] = 2;
        }
    }
    if($user->busquedas->count() > 0){
        foreach ($user->busquedas as $busqueda) {
            if($busqueda->categoria == '1-10'){
                $cli_sumi[$user->id][1] = 2;
            }
            elseif($busqueda->categoria == '10-15'){
                $cli_sumi[$user->id][2] = 2;
            }
            elseif($busqueda->categoria == 'mas-15'){
                $cli_sumi[$user->id][3] = 2;
            }
            elseif($busqueda->categoria == 'alta'){
                if($busqueda->categoria == '3.1a'){
                    $cli_sumi[$user->id][4] = 2;
                }
                else{
                    $cli_sumi[$user->id][5] = 2;
                }
            }
        }
    }
    if($user->imagen){
      if (filter_var($user->imagen, FILTER_VALIDATE_URL)) {
         $user->imagen = $user->imagen;
      } else {
        $user->imagen = url('uploads/clienteregistrados/client_imagen/'.$user->imagen);
      }
    }else{
      $user->imagen = '//placehold.it/100';
    }
    if($user->mobile_verification_code){
      $mbArr = explode('_', $user->mobile_verification_code);
      if(array_key_exists(1, $mbArr)){
        $user->mb_validation_no = $mbArr[1];
      }
      if(array_key_exists(2, $mbArr)){
        $user->mb_validation_date = $mbArr[2];
      }

    }else{
      $user->mb_validation_no = '';
      $user->mb_validation_date ='';
    }

    return view('profile.index',compact('user','cli_sumi','tipossuministros'));
  }

  public function editbasicprofile(Request $request){
    $rules = [
      'name' => 'required',
    ];
    $customerrMessages = [
        'name.required' => 'name es requerida.',
    ];
    $validator = Validator::make($request->all(), $rules, $customerrMessages);
    if ($validator->fails()) {
        $messages = $validator->messages();
        return Response::json(['success' => false, 'data' => $messages]);
    } else {
      $client = ClienteRegistrado::find(Auth::user()->id);
      $phone = ($request->get('phone'))?$request->get('phone'):'';
      $contract_email = ($request->get('contract_email'))?$request->get('contract_email'):'';
      $client->update(['name' => $request->get('name'), 'phone'=> $phone, 'contract_email' => $contract_email]);
      return Response::json(['success' => true, 'data' => '', 'url' => url('profile')]);
    }
  }

  public function changepassword(Request $request){
    $rules = [
      'oldpassword' => 'required|max:255',
      'password' => 'required|min:6|confirmed',
    ];
    $customerrMessages = [
        'password.required' => 'Password es requerida.',
    ];
    $validator = Validator::make($request->all(), $rules, $customerrMessages);
    if ($validator->fails()) {
        $messages = $validator->messages();
        return Response::json(['success' => false, 'data' => $messages]);
    } else {
      $user = Auth::user();
      if(!Hash::check($request->input('oldpassword'), $user->password)){
         return Response::json(['success' => false, 'data' => array('old password donot match')]);
      }else{
        ClienteRegistrado::find(Auth::user()->id)->update(['password' => bcrypt($request->get('password'))]);
        return Response::json(['success' => true, 'data' => '', 'url' => url('profile')]);
      }

    }
  }

  public function subirarchivos(Request $request){
    foreach($request->file('archives') as $ak => $archive){
      $nombre_archivo = $archive->getClientOriginalName();
      $tipo = $archive->getClientOriginalExtension();
      $name = time().'.'.$tipo;
      $nombre = 'uploads/clienteregistrados/' . Auth::user()->id . '/' . $name;
      \Storage::disk('local')->put($nombre, \File::get($archive));
      $data = array(
          'clientes_registrados_id' => Auth::user()->id,
          'nombre' => $name,
          'nombre_archivo' => $name,
          'tipo' => $tipo,
          'descripcion' => '',
          'created_by' => 2
      );
      ArchivosClienteRegistrado::create($data);
      $ret[] = $name;
    }
    return json_encode($ret);
  }

  public function getsubirarchivos(Request $request){
    $archives = ArchivosClienteRegistrado::where('clientes_registrados_id', Auth::user()->id)->orderBy('id','desc')->get();
    $archives_rec = ArchivosClienteRegistrado::where('clientes_registrados_id', Auth::user()->id)->orderBy('id','desc')->count();
    $numItems = $archives_rec;
    $i = 0;
    $output = '{
    "draw": ' . $_GET['draw'] . ',
    "recordsTotal": ' . $archives_rec . ',
    "recordsFiltered": ' . $archives_rec . ',
    "data": [';
    if (isset($archives) && !empty($archives)) {


        foreach ($archives as $key => $archive) {
            $action = "";
            $action .= "<a target = '_blank' href='".asset("uploads/clienteregistrados/".$archive->clientes_registrados_id."/".$archive->nombre_archivo)."'><i class='fa fa-download'></i></a>";
            if (++$i === $numItems) {
                $output .= '["' . $archive->nombre . '<br>'.date('d/m/Y H:i:s', strtotime($archive->created_at)).'",
                "' . $action . '"]';
            } else {
                $output .= '["' . $archive->nombre . '<br>'.date('d/m/Y H:i:s', strtotime($archive->created_at)).'",
                "' . $action . '"],';
            }
        }
    }
    $output .= ']}';
    echo $output;

  }

  public function uploadclientimage(Request $request){
    $imagen = $request->file('imagen');
    $nombre_archivo = $imagen->getClientOriginalName();
    $tipo = $imagen->getClientOriginalExtension();
    $nombre = 'uploads/clienteregistrados/client_imagen/'.$nombre_archivo;
    \Storage::disk('local')->put($nombre, \File::get($imagen));
    ClienteRegistrado::find(Auth::user()->id)->update(['imagen' => $nombre_archivo, 'updated_at'=> date('Y-m-d H:i:s')]);
    return Response::json(['success' => true, 'data' => '', 'url' => url('profile')]);
  }

  public function clientresetpassword(Request $request){
    $token = $token = hash_hmac('sha256', Str::random(40), env('APP_KEY'));
    $toUser = ClienteRegistrado::find(Auth::user()->id);
    DB::table('password_resets')->insert(['email' => $request->get('email'), 'token' => $token, 'created_at' => new Carbon]);
    $toUser->notify(new ClientAuthResetPassword($token));
    return Response::json(['success' => true,'url' => url('profile')]);
  }

  public function clientsetnewpassword(Request $request, $token){
    return view('auth.passwords.clientreset',compact('token'));
  }

  public function clientresetrequest(Request $request){
    $rules = [
      'email' => 'required|email',
      'password' => 'required|min:6|confirmed',
    ];
    $customerrMessages = [
        'email.required' => 'Email id es requerida.',
    ];
    $validator = Validator::make($request->all(), $rules, $customerrMessages);
    if ($validator->fails()) {
        $messages = $validator->messages();
        return Redirect::back()->withInput()->withErrors($messages);
    } else {
      $user_reset_req = DB::table('password_resets')->where('email',$request->get('email'))->first();
      if($user_reset_req){
        if($user_reset_req->token == $request->get('token')){
          ClienteRegistrado::find(Auth::user()->id)->update(['password' => bcrypt($request->get('password'))]);
          DB::table('password_resets')->where('email', $request->get('email'))->delete();
          return Redirect::route('profile')->with('successmsg', 'IT WORKS!');
        }else{
          return Redirect::back()->withInput()->with('errmsg','Invalid token');
        }
      }else{
        return Redirect::back()->withInput()->with('errmsg','We can find user with the email');
      }
    }

  }

  public function clientadvanedprofile(Request $request){
    $data = $request->all();
    $usuario = ClienteRegistrado::find(Auth::user()->id);
    if($data['suministros_luz']){
      $usuario->update(['suministros_luz'=>$data['suministros_luz'], 'updated_at'=> date('Y-m-d H:i:s')]);
    }

    if($data['suministros_luz']){
      $usuario->update(['pago_anual_suministros'=>$data['pago_anual_suministros'], 'updated_at'=> date('Y-m-d H:i:s')]);
    }

    ClienteTipoSuministro::where('cliente_id',$usuario->id)->delete();
    if(!empty($data['tipossuministros'])){
        foreach ($data['tipossuministros'] as $key => $dato) {
            ClienteTipoSuministro::create(['cliente_id' => $usuario->id, 'tiposuministro_id' => $key]);
        }
    }
      return Response::json(['success' => true,'url' => url('profile')]);
  }

  public function clientappointment(Request $request){
    $rules = [
      'mobile' => 'required',
      'appointment_date' => 'required',
    ];
    $customerrMessages = [
        'mobile.required' => 'Movil es requerida.',
    ];
    $validator = Validator::make($request->all(), $rules, $customerrMessages);
    if ($validator->fails()) {
        $messages = $validator->messages();
        return Response::json(['success' => false, 'data' => $messages]);
    } else {
      $data = $request->all();
      $is_mobile_replaced = 0;
      if(array_key_exists('do_replace', $data)){
        $usuario = ClienteRegistrado::find(Auth::user()->id);
        $usuario->update(['mobile'=>$data['mobile'], 'updated_at'=> date('Y-m-d H:i:s')]);
        $is_mobile_replaced = 1;
      }
      $client_appointment = ClientRegistradoAppointment::create([
        'client_registrado_id' => Auth::user()->id,
        'mobile' => $data['mobile'],
        'appointment_date' => date('Y-m-d H:i', strtotime($data['appointment_date'])),
        'is_mobile_replaced' => $is_mobile_replaced,
        'status' => 1,
      ]);
      if($client_appointment){
        $data = array(
            'usuario_id' => Auth::user()->id,
            'conversationnote' => '',
            'alert_status' => 1,
            'alert_time' => date('Y-m-d H:i:s', strtotime($data['appointment_date'])),
            'ticket_status' => 1,
            'ticket_number' => base64_encode(time()),
            'ticket_allocationdepartment' => 1,
            'ticket_processing_status' => 1,
            'comercializadora_id' => 0,
            'ticket_propietario' => 2,
            'contact_suministros_id' => 0,
            'created_by' => 2,
            'can_edit' => 1,
            'can_comment' => 1,
            'owned_by' => 0,
            'nivel' => 1,
            'appointment_status' => 1
        );
        ConversacionesSeguimientos::create($data);
        return Response::json(['success' => true, 'data' => 'success']);
      }
    }
  }

  public function clientsendverificationcode(Request $request){
    $rules = [
      'mobile' => 'required|numeric',
    ];
    $customerrMessages = [
      'mobile.required' => 'movil es requerida.',
    ];
    $validator = Validator::make($request->all(), $rules, $customerrMessages);
    if ($validator->fails()) {
        $messages = $validator->messages();
        return Response::json(['success' => false, 'data' => 'movil es requerida']);
    } else {
      $usuario = ClienteRegistrado::find(Auth::user()->id);
      $start_date = new \DateTime();
      $updated_at = new \DateTime($usuario->updated_at);
      $interval = $start_date->diff($updated_at);
      $hours   = $interval->format('%h');
      $minutes = $interval->format('%i');
      $total_min = ($hours * 60 + $minutes);
      if($usuario->sms_count < 4 && $total_min > 1){
        $code =  mt_rand(10000, 99999);
        $smsid = $this->esendexsms($request->get('mobile'), $code);
        if ($smsid) {
          $sms_count = $usuario->sms_count + 1;
          $usuario->update(['mobile_verification_code' => $code.'_'.$request->get('mobile').'_'.date('Y-m-d H:i:s'), 'is_mobile_verified' => 1,'sms_count'=> $sms_count, 'updated_at'=> date('Y-m-d H:i:s')]);
          return Response::json(['success' => true, 'data' => 'Valide su movil con el codigo enviado', 'url' => url('profile')]);
        }else{
          return Response::json(['success' => false, 'data' => 'Message not sent']);
        }
      }else{
        return Response::json(['success' => false, 'data' => 'Prueba de nuevo en un minuto']);
      }

    }
  }

  private function esendexsms($mobile_no, $smsmsg) {
      $mobile_no = '34'.$mobile_no;
      $message = new \Esendex\Model\DispatchMessage(
              "Tarifas", $mobile_no, $smsmsg, \Esendex\Model\Message::SmsType
      );
      $authentication = new \Esendex\Authentication\LoginAuthentication(
              "EX0243525", "administracion@tarifasdeluz.com", "i5X3)kFNsiLq"
      );
      $service = new \Esendex\DispatchService($authentication);
      $result = $service->send($message);
      if (is_object($result)) {
          return $result->id();
      } else {
          return false;
      }
  }

  public function clientverifyverificationcode(Request $request){
    $rules = [
      'verification_code' => 'required|numeric',
    ];
    $customerrMessages = [
        'verification_code.required' => 'verification_code es requerida.',
    ];
    $validator = Validator::make($request->all(), $rules, $customerrMessages);
    if ($validator->fails()) {
        $messages = $validator->messages();
        return Response::json(['success' => false, 'data' => 'verification_code es requerida', 'mismatch' => false]);
    } else {
      $usuario = ClienteRegistrado::find(Auth::user()->id);
      $mobile_verificationArr = explode('_', $usuario->mobile_verification_code);
      if($request->get('verification_code') == $mobile_verificationArr[0]){
        $usuario->update(['mobile_verification_code'=>'', 'mobile'=>$mobile_verificationArr[1], 'is_mobile_verified'=>2, 'updated_at'=> date('Y-m-d H:i:s')]);
        return Response::json(['success' => true, 'data' => 'Perfecto. Bienvenido.', 'url' => url('profile')]);
      }else{
        return Response::json(['success' => false, 'data' => 'Código No coincide', 'mismatch' => true]);
      }

    }
  }

  public function clientreverifymobile(Request $request){
    $usuario = ClienteRegistrado::find(Auth::user()->id);
    $usuario->update(['is_mobile_verified'=> 0]);
    return Response::json(['success' => true, 'data' => 'Perfecto. Bienvenido.', 'url' => url('profile')]);
  }

  public function centro_de_resolutiones(){
    $user = Auth::user();
    return view('profile.centro-de-resolutiones',compact('user'));
  }

  public function mis_notficaciones(){
    $user = Auth::user();
    return view('profile.centro-de-resolutiones',compact('user'));
  }

  public function bateria_de_condensadores(){
    $user = Auth::user();
    return view('profile.bateria-de-condensadores',compact('user'));
  }

  public function paneles_solares(){
    $user = Auth::user();
    return view('profile.paneles-solares',compact('user'));
  }

}
