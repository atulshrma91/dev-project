<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\Noticia;
use App\Models\Testimonios;
use App\Models\Comercializadora;
use App\Models\Suministro;
use App\Models\ClienteRegistrado;
use App\Models\MisComentario;
use Response;
use DB;
use Auth;
use Illuminate\Support\Facades\Validator;
use PHPMailer\PHPMailer;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */


    public function __construct(Faq $Faq, Noticia $Noticia, Testimonios $Testimonios, Comercializadora $Comercializadora, MisComentario $MisComentario) {
        $this->faq = $Faq;
        $this->noticia = $Noticia;
        $this->testimonios = $Testimonios;
        $this->comercializadora = $Comercializadora;
        $this->miscomentario = $MisComentario;
        \View::share('titulo_pagina', 'Tarifasdeluz');
    }

    public function index(Request $request) {
        $noticia_1 = $this->noticia->where('destacado', '=', 1)->where('foto', '!=', '')->orderBy('idnoticia', 'desc')->offset(3)->limit(4)->get();
        $noticia_2 = $this->noticia->where('destacado', '=', 1)->where('foto', '!=', '')->orderBy('idnoticia', 'desc')->offset(8)->limit(4)->get();
        $noticia_3 = $this->noticia->where('destacado', '=', 1)->where('foto', '!=', '')->orderBy('idnoticia', 'desc')->offset(12)->limit(4)->get();
        $noticia_4 = $this->noticia->where('destacado', '=', 1)->where('foto', '!=', '')->orderBy('idnoticia', 'desc')->offset(16)->limit(4)->get();
        $noticia_5 = $this->noticia->where('destacado', '=', 1)->where('foto', '!=', '')->orderBy('idnoticia', 'desc')->offset(20)->limit(4)->get();
        $testimonios = $this->testimonios->orderBy('destacado', 'desc')->orderBy('fecha', 'desc')->get();
        $faqs_1 = $this->faq->offset(0)->orderBy('idfaq', 'asc')->offset(0)->limit(5)->get();
        $faqs_2 = $this->faq->offset(0)->orderBy('idfaq', 'asc')->offset(5)->limit(5)->get();
        $faqs_3 = $this->faq->offset(0)->orderBy('idfaq', 'asc')->offset(10)->limit(5)->get();
        $faqs_img = $this->faq->offset(0)->orderBy('idfaq', 'asc')->offset(15)->limit(2)->get();
        $resultados = DB::table('resultados_comparador')
                  ->join('suministros', 'resultados_comparador.suministro_id', '=', 'suministros.id')
                  ->where('resultados_comparador.factura','=','1')
                  ->where('resultados_comparador.suministro_id','<>','1')
                  ->orderBy('resultados_comparador.id','desc')
                  ->offset(0)->limit(120)
                  ->get();
        $resultArr = array();
        if(!empty($resultados)){
          $counter = 0;
          foreach($resultados as $rk => $res){
            $suministro = Suministro::find($res->suministro_id);
            $contacto = ClienteRegistrado::where('id', $suministro->contacto_id)->get()->first();
            $oferta = json_decode($res->resultado);
            $oferta = (array) $oferta;
            if ($oferta['importe_factura'] > $oferta['total_con_iva']) {

                $ahorro = $oferta['importe_factura'] - $oferta['total_con_iva'];
                $fname_f = $fname_l = $lname_f = $lname_f = '';
                if(property_exists('name',$contacto)){
                  if($contacto->name){
                    $fNameArr = explode(' ',$contacto->name);
                    $fname_f = strtoupper(substr($fNameArr[0], 0, 1));
                    if(array_key_exists('1',$fNameArr)){
                      $fname_l = strtoupper(substr($fNameArr[1], 0, 1));
                    }else{
                      $fname_l = '';
                    }

                  }
                }
                if(property_exists('lastname',$contacto)){
                  if($contacto->lastname){
                    $lname_f = strtoupper(substr($contacto->lastname, 0, 1));
                  }else{
                    $lname_f = $fname_l;
                  }
                }
                $resultArr[$counter]['name'] = utf8_encode($fname_f.' '.$lname_f) ;
                $resultArr[$counter]['provincia'] = $suministro->poblacion_suministro;
                $resultArr[$counter]['total_con_iva'] = number_format((float)$oferta['total_con_iva'],2);
                $resultArr[$counter]['importe_factura'] = number_format((float)$oferta['importe_factura'],2);
                $resultArr[$counter]['ahorro'] = $ahorro;
                $resultArr[$counter]['created'] = date('d', strtotime($res->created_at)).' '.date('M', strtotime($res->created_at));
                $counter++;
            }

          }
        }
        $comentarios = $this->miscomentario->where('is_published', 1)->orderBy('rating', 'desc')->orderBy('created_at', 'desc')->offset(0)->limit(4)->get();
        if(!$comentarios->isEmpty()){
          foreach($comentarios as $comentario){
            if($comentario->user_id){
              $client = ClienteRegistrado::where('id','=', $comentario->user_id)->first();

              $fname_f = $fname_l = $lname_f = $lname_f = '';

                if($client->name){
                  $fNameArr = explode(' ',$client->name);
                  $fname_f = strtoupper(substr($fNameArr[0], 0, 1));
                  if(array_key_exists('1',$fNameArr)){
                    $fname_l = strtoupper(substr($fNameArr[1], 0, 1));
                  }else{
                    $fname_l = '';
                  }
                }


                if($client->lastname){
                  $lname_f = strtoupper(substr($client->lastname, 0, 1));
                }else{
                  $lname_f = $fname_l;
                }
                $comentario->client_name = $fname_f.' '.$lname_f;

            }else{
              $comentario->client_name = $comentario->initials;
            }
            if($comentario->survey_type == 2){
              $comentario->nombre = $comentario->companiascomentario->nombre;
            }else{
              $comentario->nombre = 'tarifasdeluz.com';
            }
            $comentario->date = date('d/m/Y',strtotime($comentario->created_at));
          }

        }
        return view('home', ['noticia_1' => $noticia_1, 'noticia_2' => $noticia_2, 'noticia_3' => $noticia_3, 'noticia_4' => $noticia_4, 'noticia_5' => $noticia_5, 'testimonios' => $testimonios, 'faqs_1'=>$faqs_1, 'faqs_2'=>$faqs_2, 'faqs_3'=>$faqs_3, 'faqs_img'=> $faqs_img, 'resultArr'=> $resultArr, 'comentarios' => $comentarios]);
    }

    public function search(Request $request) {
        if ($request->get('search_input') != '') {
            $comercializadoras = $this->comercializadora->where('destacado', '=', 1)
                    ->where('nombre', 'like', '%' . $request->get('search_input') . '%')
                    ->orWhere('nombre_comercial', 'like', '%' . $request->get('search_input') . '%')
                    ->orderBy('destacado', 'desc')
                    ->orderBy('cne', 'asc')
                    ->offset(0)->limit(10)
                    ->get();
        } else {
            $comercializadoras = $this->comercializadora->where('destacado', '=', 1)->orderBy('destacado', 'desc')->orderBy('cne', 'asc')->offset(0)->limit(10)->get();
        }
        if (!$comercializadoras->isEmpty()) {
            foreach ($comercializadoras as $ck => $comercializadora) {
                $comercializadora->cne = $comercializadora->cne;
                $comercializadora->substr_nombre_comercial = substr($comercializadora->nombre_comercial, 0, 20);
                $comercializadora->image_url = asset('uploads/imagenes/logo-empresas/' . $comercializadora->imagen1 . '');
                $comercializadora->route = route('companias-electricas');
            }
            return Response::json(['success' => true, 'data' => $comercializadoras]);
        } else {
            return Response::json(['success' => false, 'data' => array()]);
        }
    }

    public function crmregister(Request $request){

      $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:clientes_registrados',
        'password' => 'required|min:6|confirmed',
      ];
      $customerrMessages = [
          'email.required' => 'Email id es requerida.',
      ];
      $validator = Validator::make($request->all(), $rules, $customerrMessages);
      if ($validator->fails()) {
          $messages = $validator->messages();
          return Response::json(['success' => false, 'data' => $messages]);
      } else {
        $data['rol_id'] = 20;
        $data['estado'] = 1;
        $user = ClienteRegistrado::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'contract_email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'password' => bcrypt($request->input('password')),
            'lastname' => '', 'mobile' => '','fax' => '',
            'phone' => '',
            'rol_id' => 20, 'estado' => 1,
            'provider_id'=>'','provider'=>'',
            'mobile_verification_code' => '',
            'is_mobile_verified' => 0,
            'sms_count' => 0,
            'pago_anual_suministros' => 0.00,
            'confirmation_code' => '','is_published' => 1,
            'delegacion_id' => 1, 'created_at' => 2,
            'last_login' => date('Y-m-d H:i:s'),
        ]);
        Auth::login($user);
        return Response::json(['success' => true, 'data' => 'valid login', 'url' => url('dashboard')]);
      }
    }

    public function crmlogin(Request $request){
      $rules = [
        'email' => 'required|string|email|max:255',
        'password' => 'required|string'
      ];
      $customerrMessages = [
          'email.required' => 'Email id es requerida.',
      ];
      $validator = Validator::make($request->all(), $rules, $customerrMessages);
      if ($validator->fails()) {
          $messages = $validator->messages();
          return Response::json(['success' => false, 'data' => 'Invalid fields']);
      } else {
        $user = array(
          'email' => $request->get('email'),
          'password' => $request->get('password')
        );

        if (Auth::attempt($user)) {
            return Response::json(['success' => true, 'data' => 'success', 'url' => url('dashboard')]);
        } else {
            return Response::json(['success' => false, 'data' => 'Invalid login']);
        }
      }
    }

    public function crmforgotpassword(Request $request){
        $rules = [
          'email' => 'required|email|max:255',
        ];
        $customerrMessages = [
            'email.required' => 'Email id es requerida.',
        ];
        $validator = Validator::make($request->all(), $rules, $customerrMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            return Response::json(['success' => false, 'data' => $messages]);
        } else {
          $user = ClienteRegistrado::where('email', $request->get('email'))->get();
          if(!$user->isEmpty()){
            $mail =new PHPMailer\PHPMailer(true);
            try {
              $mail->isSMTP();
              $mail->CharSet = "utf-8";
              $mail->SMTPAuth = true;
              $mail->Host = env("MAIL_HOST");
              $mail->Port =env("MAIL_PORT");;
              $mail->SMTPSecure = env("MAIL_ENCRYPTION");
              $mail->Username = env("MAIL_USERNAME");
              $mail->Password = env("MAIL_PASSWORD");
              $mail->setFrom(env("MAIL_USERNAME"), env("MAIL_USERNAME"));
              $mail->Subject = 'Forgot Password';
              /*$view = \View::make('correoelectronico.partials.inboxemail', [
                          'content' => $content
              ]);

              $html = $view->render();*/
              $password = rand();
              $html = '<p>'.$password.'</p>';
              $mail->Body = html_entity_decode($html);
              $mail->addAddress($request->input('email'));
              $mail->isHTML(true);
              $mail->send();
              ClienteRegistrado::where('email', $request->get('email'))->update(['password' => bcrypt($password)]);
              return Response::json(['success' => true, 'data' => 'Password updated successfully']);
            } catch (phpmailerException $e) {
                return \Response::json(['success' => FALSE, 'data' => str_replace(":", " ", $e->getMessage())]);
            } catch (\Exception $e) {
                return \Response::json(['success' => FALSE, 'data' => str_replace(":", " ", $e->getMessage())]);
            }
          }else{
            return Response::json(['success' => false, 'data' => 'User not Found']);
          }

        }
    }

    public function moreopiniones(Request $request){
      $rules = [
        'offset' => 'required',
      ];
      $customerrMessages = [
          'offset.required' => 'offset id es requerida.',
      ];
      $validator = Validator::make($request->all(), $rules, $customerrMessages);
      if ($validator->fails()) {
          $messages = $validator->messages();
          return Response::json(['success' => false, 'data' => 'Invalid fields']);
      } else {
        $offest = ($request->get('offset') * 4 );
        $comentarios = $this->miscomentario->orderBy('rating', 'desc')->orderBy('created_at', 'desc')->offset($offest)->limit(4)->get();
        if(!$comentarios->isEmpty()){
          foreach($comentarios as $comentario){
            if($comentario->user_id){
              $client = ClienteRegistrado::where('id','=', $comentario->user_id)->first();

              $fname_f = $fname_l = $lname_f = $lname_f = '';

                if($client->name){
                  $fNameArr = explode(' ',$client->name);
                  $fname_f = strtoupper(substr($fNameArr[0], 0, 1));
                  if(array_key_exists('1',$fNameArr)){
                    $fname_l = strtoupper(substr($fNameArr[1], 0, 1));
                  }else{
                    $fname_l = '';
                  }
                }


                if($client->lastname){
                  $lname_f = strtoupper(substr($client->lastname, 0, 1));
                }else{
                  $lname_f = $fname_l;
                }
                $comentario->client_name = $fname_f.' '.$lname_f;

            }else{
              $comentario->client_name = $comentario->initials;
            }
            if($comentario->survey_type == 2){
              $comentario->nombre = $comentario->companiascomentario->nombre;
            }else{
              $comentario->nombre = 'tarifasdeluz.com';
            }

            $comentario->date = date('d/m/Y',strtotime($comentario->created_at));
          }
          $next_comentarios = $this->miscomentario->orderBy('rating', 'desc')->orderBy('created_at', 'desc')->offset(($offest + 1) * 4)->limit(4)->get();
          if(!$next_comentarios->isEmpty()){
            return Response::json(['success' => true, 'data' => $comentarios, 'offset' => $request->get('offset')+1, 'is_hide' => 0]);
          }else{
            return Response::json(['success' => true, 'data' => $comentarios, 'offset' => $request->get('offset')+1, 'is_hide' => 1]);
          }

        }else{
          return Response::json(['success' => '', 'data' => 'No more results', 'offset' => $request->get('offset')]);
        }
      }

    }

    public function home(){
      die('hehe');
    }
}
