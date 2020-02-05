<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\EnviarContracto;
use App\Models\Suministro;
use App\Models\Comercializadora;
use App\Models\Tarifaacceso;
use App\Models\OfertasContracto;
use App\Models\ConversacionesSeguimientos;
use App\Models\ConversacionesSuministro;
use App\Models\ResultadosComparador;
use App\User;
use Session;
use DB;
use Validator;
use Response;
use Illuminate\Support\Facades\Redirect;

class EnviarContractoController extends Controller {

    public function __construct(EnviarContracto $EnviarContracto, OfertasContracto $OfertasContracto, ConversacionesSeguimientos $ConversacionesSeguimientos) {
        $this->middleware('auth', ['except' => ['index', 'store', 'cancelcontract', 'contractochangenumber', 'resendmsg', 'show', 'error', 'ofertaInactiva']]);
        $this->middleware('contracto');
        $this->enviarcontracto = $EnviarContracto;
        $this->ofertascontracto = $OfertasContracto;
        $this->conversacionesseguimientos = $ConversacionesSeguimientos;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $suministro_id, $oferta_id, $resultado_id) {

        $tarifaacceso_cambio = '';
        $confirmAcc = \Hashids::encode(1);
        try {
            $decrpyt_suministro_id = \Hashids::decode($suministro_id);
            $decrpyt_oferta_id = \Hashids::decode($oferta_id);
            $decrpyt_resultado_id = \Hashids::decode($resultado_id);
            $suministro = Suministro::where('id', $decrpyt_suministro_id)->first();

            if ($suministro->tarifaacceso_cambio_id) {
                $tarifaacceso_cambio = Tarifaacceso::find($suministro->tarifaacceso_cambio_id);
            }
            $ofertas = DB::table('ofertas as o')
                    ->select('o.*', 'c.*', 'p.*', 'tp.nombre as tipo_precio')
                    ->leftJoin('comercializadora as c', 'c.idcomercializadora', '=', 'o.idcomercializadora')
                    ->leftJoin('tipoprecio as tp', 'tp.id', '=', 'o.tipoprecio_id')
                    ->join('plantillas_de_contratos as p', 'p.id', '=', 'o.idplantilla')
                    ->where('o.id', $decrpyt_oferta_id[0]);

            $ofertas = $ofertas->first();
            if ($ofertas) {
                $ofertas->dato = '';
                $ofertas->obligacion_permanencia_desc = (($ofertas->obligacion_permanencia == 1) ? 'Si' : 'No');
                $tarifa = calcularTarifa($ofertas);
                if (!isset($tarifa['valle_final_dto'])) {
                    $tarifa['valle_final_dto'] = 0;
                }
                $comercializadora = Comercializadora::find($ofertas->idcomercializadora);
                $suministro_existing_contract = DB::table('enviar_contractos')
                        ->where('oferta_id', $decrpyt_oferta_id[0])
                        ->where('suministro_id', $decrpyt_suministro_id[0])
                        ->where('estado', 0)
                        ->orderBy('updated_at', 'desc')
                        ->first();

                return view('enviarcontracto.index', ['confirmAcc' => $confirmAcc, 'ofertas' => (object) $ofertas, 'comercializadora' => $comercializadora, 'suministro' => $suministro, 'oferta_id' => $oferta_id, 'resultado_id' => $resultado_id,'suministro_id' => $suministro_id, 'tarifa' => (object) $tarifa, 'suministro_existing_contract' => $suministro_existing_contract, 'tarifaacceso_cambio' => $tarifaacceso_cambio]);
            } else {
                return view('errors.401');
            }
        } catch (\Exception $e) {
            return array('error' => $e->getMessage());
            return view('errors.401');
        }
    }

    public function enviarmsg(Request $request) {
        try {
            $rules = [
                'email' => 'required|email',
                'mobile_no' => 'required|numeric',
                'suministro_id' => 'required',
                'oferta_id' => 'required',
                //'tipopersona' => 'required',
            ];
            $customerrMessages = [
                'email.required' => 'Email id es requerida.',
                'mobile_no.required' => 'Movil es requerida.'
            ];
            $validator = Validator::make($request->all(), $rules, $customerrMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return Response::json(['success' => false, 'data' => $messages]);
            } else {
                date_default_timezone_set('Europe/Amsterdam');
                if ($request->input('mobile_no') && $request->input('email')) {
                    $decode_suministro_id = \Hashids::decode($request->input('suministro_id'));
                    $decode_oferta_id = \Hashids::decode($request->input('oferta_id'));
                    $decode_resultado_id = \Hashids::decode($request->input('resultado_id'));
                    $suministro_existing_contract = DB::table('enviar_contractos')
                            ->where('oferta_id', $decode_oferta_id[0])
                            ->where('suministro_id', $decode_suministro_id[0])
                            ->orderBy('id', 'DESC')
                            ->first();
                    $suministro = Suministro::where('id', $decode_suministro_id)->first();
                    if (empty($suministro_existing_contract)) {
                        $redirect_url = url('/enviarcontracto/' . $request->input('suministro_id') . '/' . $request->input('oferta_id') . '/' . $request->input('resultado_id') . '?confirmar=' . \Hashids::encode(1));
                        $contracturl = url('/enviarcontracto/' . $request->input('suministro_id') . '/' . $request->input('oferta_id'). '/' . $request->input('resultado_id') );
                        $verify_num = mt_rand(10000, 99999);
                        $mensaje = 'URL: ' . $redirect_url;
                        $mensaje .= PHP_EOL . 'CODIGO: ' . $verify_num . ' para el suministro en: ' . $suministro->direccion_suministro . ' ' . $suministro->aclaratorio_suministro . ' ' . $suministro->poblacion_suministro . ' ' . $suministro->codigopostal_suministro . ' ';
                        $smsid = $this->esendexsms($request->input('mobile_no'), $mensaje);

                        if ($smsid) {
                            $mail = new \PHPMailer(true);
                            $mail->isSMTP();
                            $mail->CharSet = "utf-8";
                            $mail->SMTPAuth = true;
                            $mail->isHTML(true);
                            $mail->Host = env('MAIL_HOST');
                            $mail->Port = env('MAIL_PORT');
                            $mail->Username = env('MAIL_USERNAME');
                            $mail->Password = env('MAIL_PASSWORD');
                            $mail->setFrom("contratos@tarifasdeluz.com", "Tarifasdeluz");
                            $mail->Subject = "Tarifasdeluz - Contracto Verification Email";
                            $view = \View::make('enviarcontracto.partials.activationlinkemail', [
                                        'url' => $redirect_url,
                                        'suministro' => $suministro,
                            ]);

                            $html = $view->render();
                            $mail->MsgHTML($html);
                            $mail->addAddress($request->input('email'));
                            if ($mail->send()) {
                                $data = $request->all();
                                $resultado = ResultadosComparador::where('id', $decode_resultado_id[0])->first();
                                $data['suministro_id'] = $decode_suministro_id[0];
                                $data['oferta_id'] = $decode_oferta_id[0];
                                $data['reserva_id'] = $resultado->reserva_id;
                                $data['comercializadora_id'] = $resultado->comercializadora_id;
                                $data['estado'] = 0;
                                $data['codigo_de_verificacion'] = $verify_num;
                                $data['vencimiento'] = date('Y-m-d', strtotime("+2 days"));
                                $data['contracturl'] = $contracturl;
                                $data['tipopersona'] = $request->input('tipopersona');
                                $enviarcontracto = $this->enviarcontracto->create($data);

                                $resultado->update(['contrato_enviado' => date('Y-m-d H:i:s'), 'contrato_id' => $enviarcontracto->id]);



                                return Response::json(['success' => true, 'data' => 'Mensaje enviado']);
                            }
                        } else {
                            return Response::json(['success' => false, 'data' => array('Mensaje no enviado')]);
                        }
                    } else {
                        if ($suministro_existing_contract->estado == 2) {
                            /* if ($suministro_existing_contract->is_agent_cancelled == 1) {
                              return Response::json(['success' => false, 'data' => array('Contract cancelled.')]);
                              } else {

                              } */
                            $redirect_url = url('/enviarcontracto/' . $request->input('suministro_id') . '/' . $request->input('oferta_id') . '/' . $request->input('resultado_id') . '?confirmar=' . \Hashids::encode(1));
                            $contracturl = url('/enviarcontracto/' . $request->input('suministro_id') . '/' . $request->input('oferta_id') . '/' . $request->input('resultado_id'));
                            $verify_num = mt_rand(10000, 99999);
                            $mensaje = 'URL: ' . $redirect_url;
                            $mensaje .= PHP_EOL . 'CODIGO: ' . $verify_num . ' para el suministro en: ' . $suministro->direccion_suministro . ' ' . $suministro->aclaratorio_suministro . ' ' . $suministro->poblacion_suministro . ' ' . $suministro->codigopostal_suministro . ' ';
                            $smsid = $this->esendexsms($request->input('mobile_no'), $mensaje);

                            if ($smsid) {
                                $mail = new \PHPMailer(true);
                                $mail->isSMTP();
                                $mail->CharSet = "utf-8";
                                $mail->SMTPAuth = true;
                                $mail->isHTML(true);
                                $mail->Host = env('MAIL_HOST');
                                $mail->Port = env('MAIL_PORT');
                                $mail->Username = env('MAIL_USERNAME');
                                $mail->Password = env('MAIL_PASSWORD');
                                $mail->setFrom("contratos@tarifasdeluz.com", "Tarifasdeluz");
                                $mail->Subject = "Tarifasdeluz - Contracto Verification Email";
                                $view = \View::make('enviarcontracto.partials.activationlinkemail', [
                                            'url' => $redirect_url,
                                            'suministro' => $suministro,
                                ]);

                                $html = $view->render();
                                $mail->MsgHTML($html);
                                $mail->addAddress($request->input('email'));
                                if ($mail->send()) {
                                    $data = $request->all();
                                    $data['suministro_id'] = $decode_suministro_id[0];
                                    $data['oferta_id'] = $decode_oferta_id[0];
                                    $data['estado'] = 0;
                                    $data['codigo_de_verificacion'] = $verify_num;
                                    $data['vencimiento'] = date('Y-m-d', strtotime("+2 days"));
                                    $data['contracturl'] = $contracturl;
                                    $data['tipopersona'] = $request->input('tipopersona');
                                    $enviarcontracto = $this->enviarcontracto->create($data);
                                    return Response::json(['success' => true, 'data' => 'Mensaje enviado']);
                                }
                            } else {
                                return Response::json(['success' => false, 'data' => array('Mensaje no enviado')]);
                            }
                        } else if ($suministro_existing_contract->estado == 0) {
                            return Response::json(['success' => false, 'data' => array('You already sent contract to suministro, code is not verified')]);
                        } else if ($suministro_existing_contract->estado == 1) {
                            return Response::json(['success' => false, 'data' => array('Contract already sent')]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            return Response::json(['success' => false, 'data' => array(str_replace(":", " ", $e->getMessage()))]);
        }
    }

    private function esendexsms($mobile_no, $smsmsg) {
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

    private function create_ticket($c_url, $activationtime, $suminstro_id) {
        $suministro = Suministro::where('id', $suminstro_id)->first();
        $conversationnote = 'CONTRATO ACTIVADO VIA SMS. PROCEDER SEGUN COMERCIALIZADORA <br>';
        $conversationnote .= 'CONTRATO ' . $c_url . '<br>';
        $conversationnote .= 'FECHA ' . $activationtime . '<br>';
        $data = array(
            'usuario_id' => $suministro->contacto_id,
            'conversationnote' => $conversationnote,
            'alert_status' => 1,
            'alert_time' => $activationtime,
            'ticket_status' => 1,
            'ticket_number' => base64_encode(time()),
            'ticket_allocationdepartment' => 3,
            'ticket_processing_status' => 1,
            'ticket_propietario' => 2,
            //'contact_suministros_id' => json_encode(array($suminstro_id)),
            'created_by' => Auth::user()->id,
            'can_edit' => 1,
            'can_comment' => 1,
            'owned_by' => 0,
            'nivel' => 3,
        );
        $conversacionesseguimientos = $this->conversacionesseguimientos->create($data);
        $rel_data = [
            'conversaciones_seguimientos_id' => $conversacionesseguimientos->id,
            'suministro_id' => $suminstro_id,
        ];
        ConversacionesSuministro::create($rel_data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        try {
            $rules = [
                'confirmar' => 'required|numeric',
                'suministro_id' => 'required',
                'oferta_id' => 'required'
            ];
            $customerrMessages = [
                'confirmar.required' => 'Code id es requerida.',
            ];
            $validator = Validator::make($request->all(), $rules, $customerrMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return \Redirect::back()->withErrors($messages);
            } else {
                date_default_timezone_set('Europe/Amsterdam');
                if ($request->input('confirmar')) {
                    $decode_suministro_id = \Hashids::decode($request->input('suministro_id'));
                    $decode_oferta_id = \Hashids::decode($request->input('oferta_id'));
                    $suministro_existing_contract = DB::table('enviar_contractos')
                            ->where('oferta_id', $decode_oferta_id[0])
                            ->where('suministro_id', $decode_suministro_id[0])
                            ->where('estado', 0)
                            ->orderBy('id', 'DESC')
                            ->first();
                   if (!empty($suministro_existing_contract)) {
                        if ($suministro_existing_contract->codigo_de_verificacion) {
                            if ($suministro_existing_contract->codigo_de_verificacion == $request->input('confirmar')) {

                                $P = [];
                                $P[0] = 0;
                                $P[1] = 0;
                                $P[2] = 0;
                                $P[3] = 0;
                                $P[4] = 0;
                                $P[5] = 0;
                                $suministro = Suministro::where('id', $suministro_existing_contract->suministro_id)->first();
                                if (($suministro->tarifaacceso_id) >= 1 && ($suministro->tarifaacceso_id <= 2)):
                                    $P[0] = $suministro->P1;
                                endif;
                                if (($suministro->tarifaacceso_id) >= 3 && ($suministro->tarifaacceso_id <= 4)):
                                    $P[0] = $suministro->P1;
                                    $P[1] = $suministro->P2;

                                endif;
                                if (($suministro->tarifaacceso_id) >= 5 && ($suministro->tarifaacceso_id <= 6)):
                                    $P[0] = $suministro->P1;
                                    $P[1] = $suministro->P2;
                                    $P[2] = $suministro->P3;
                                endif;
                                if (($suministro->tarifaacceso_id) >= 7 && ($suministro->tarifaacceso_id <= 10)):
                                    $P[0] = $suministro->P1;
                                    $P[1] = $suministro->P2;
                                    $P[2] = $suministro->P3;
                                    $P[3] = $suministro->P4;
                                    $P[4] = $suministro->P5;
                                    $P[5] = $suministro->P6;
                                endif;

                                $contacto = User::where('id', $suministro->contacto_id)->get()->first();
                                $ofertas = DB::table('ofertas as o')
                                        ->select('o.*', 'c.*', 'p.*', 'tp.nombre as tipo_precio','o.id as oferta_id')
                                        ->leftJoin('comercializadora as c', 'c.idcomercializadora', '=', 'o.idcomercializadora')
                                        ->leftJoin('tipoprecio as tp', 'tp.id', '=', 'o.tipoprecio_id')
                                        ->join('plantillas_de_contratos as p', 'p.id', '=', 'o.idplantilla')
                                        ->where('o.id', $suministro_existing_contract->oferta_id);
                                $ofertas = $ofertas->first();
                                if ($suministro->tarifaacceso_cambio_id) {
                                    $tarifaacceso_cambio = Tarifaacceso::find($suministro->tarifaacceso_cambio_id);
                                } else {
                                    $tarifaacceso_cambio = '';
                                }
                                $ofertas->dato = '';
                                $ofertas->obligacion_permanencia_desc = (($ofertas->obligacion_permanencia == 1) ? 'Si' : 'No');
                                $tarifa = calcularTarifa($ofertas, 365, $P);
                                if (!isset($tarifa['valle_final_dto'])) {
                                    $tarifa['valle_final_dto'] = 0;
                                }
                                $resultado = ResultadosComparador::where('contrato_id', $suministro_existing_contract->id)->first();

                                $resultado->update(['contrato_firmado' => date('Y-m-d H:i:s')]);

                                $comercializadora = Comercializadora::find($ofertas->idcomercializadora);
                                $contractopdf = \PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'defaultPaperSize' => 'a4'])->loadView('enviarcontracto.pdf.contracto', ['ofertas' => (object) $ofertas, 'contacto' => $contacto, 'suministro' => $suministro, 'comercializadora' => $comercializadora, 'tarifa' => (object) $tarifa, 'suministro_existing_contract' => $suministro_existing_contract, 'tarifaacceso_cambio' => $tarifaacceso_cambio]);
                                $contractopdf->setPaper('A4');
                                $contractopdfoutput = $contractopdf->output();
                                $contractopdfname = $suministro->cups . '-' . date('d-m-Y-H-i', strtotime($suministro_existing_contract->updated_at)) . '.pdf';
                                $contarctpdflink = asset('uploads/contractopdf/' . $contractopdfname);
                                file_put_contents('uploads/contractopdf/' . $contractopdfname, $contractopdfoutput);
                                $mail = new \PHPMailer(true);
                                try {
                                    $mail->isSMTP();
                                    $mail->CharSet = "utf-8";
                                    $mail->SMTPAuth = true;
                                    $mail->isHTML(true);
                                    $mail->Host = env('MAIL_HOST');
                                    $mail->Port = env('MAIL_PORT');
                                    $mail->Username = env('MAIL_USERNAME');
                                    $mail->Password = env('MAIL_PASSWORD');
                                    $mail->setFrom("contratos@tarifasdeluz.com", "Tarifasdeluz");
                                    $mail->Subject = "Tarifasdeluz - Contrato";
                                    $view = \View::make('enviarcontracto.partials.pdfemail', [
                                                'suministro' => $suministro
                                    ]);

                                    $html = $view->render();
                                    $mail->MsgHTML($html);
                                    $mail->AddStringAttachment($contractopdfoutput, $contractopdfname);
                                    $mail->addAddress($suministro_existing_contract->email);
                                    $mail->addCustomHeader("CC: ");
                                    $mail->addCustomHeader("BCC: comercial@tarifasdeluz.com");
                                   if ($mail->send()) {
                                        $res = EnviarContracto::find($suministro_existing_contract->id);
                                        $res->update(['estado' => 1, 'codigo_de_verificacion' => '', 'pdflink' => $contarctpdflink, 'updated_at' => date('Y-m-d H:i:s')]);
                                        $oferta_id = $ofertas->oferta_id;
                                        $dataArr = array(
                                            'ofertas_id' => $decode_oferta_id[0],
                                            'suministro_id' => $decode_suministro_id[0],
                                            'status' => 1,
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'updated_at' => date('Y-m-d H:i:s')
                                        );
                                        $oferta_contrato = OfertasContracto::create($dataArr);
                                        $result = DB::table('ofertas')
                                                ->where('id', $oferta_id)
                                                ->update(['contractstatus' => 1]);
                                        $this->create_ticket($suministro_existing_contract->contracturl, date('Y-m-d H:i:s'), $decode_suministro_id[0]);

                                        return redirect()->route('enviarcontracto-contractosuccess')->with([ 'url' => url('/enviarcontracto/' . $request->input('suministro_id') . '/' . $request->input('oferta_id').'/'.$resultado->id )]);
                                    } else {
                                        return \Redirect::back()->withErrors(array('Email not sent'));
                                    }
                                } catch (phpmailerException $e) {
                                    return \Redirect::back()->withErrors(array(str_replace(":", " ", $e->getMessage())));
                                } catch (\Exception $e) {
                                    return \Redirect::back()->withErrors(array(str_replace(":", " ", $e->getMessage())));
                                }
                            } else {
                                return \Redirect::back()->withErrors(array('Codigo incorrecto'));
                            }
                        } else {
                            return \Redirect::back()->withErrors(array('Verification code not sent'));
                        }
                    } else {
                        return \Redirect::back()->withErrors(array('No record found'));
                    }
                }
            }
        } catch (\Exception $e) {
            return \Redirect::back()->withErrors(array(str_replace(":", " ", $e->getMessage())));
        }
    }

    public function contractochangenumber(Request $request) {
        try {
            $rules = [
                'mobile_no' => 'required|numeric',
                'suministro_id' => 'required',
                'oferta_id' => 'required',
                'resultado_id' => 'required',
            ];
            $customerrMessages = [
                'mobile_no.required' => 'Movil es requerida.'
            ];
            $validator = Validator::make($request->all(), $rules, $customerrMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return Response::json(['success' => false, 'data' => $messages]);
            } else {
                if ($request->input('mobile_no')) {
                    $decode_suministro_id = \Hashids::decode($request->input('suministro_id'));
                    $decode_oferta_id = \Hashids::decode($request->input('oferta_id'));
                    $decode_resultado_id = \Hashids::decode($request->input('resultado_id'));
                    $suministro_existing_contract = DB::table('enviar_contractos')
                            ->where('oferta_id', $decode_oferta_id[0])
                            ->where('suministro_id', $decode_suministro_id[0])
                            ->where('estado', 0)
                            ->orderBy('id', 'DESC')
                            ->first();
                    if ($suministro_existing_contract) {

                        $suministro = Suministro::where('id', $decode_suministro_id[0])->first();
                        $redirect_url = url('/enviarcontracto/' . $request->input('suministro_id') . '/' . $request->input('oferta_id') . '/'.$request->input('resultado_id').'?confirmar=' . \Hashids::encode(1));
                        $verify_num = mt_rand(10000, 99999);
                        $mensaje = 'URL: ' . $redirect_url;
                        $mensaje .= PHP_EOL . 'CODIGO: ' . $verify_num . ' para el suministro en: ' . $suministro->direccion_suministro . ' ' . $suministro->aclaratorio_suministro . ' ' . $suministro->poblacion_suministro . ' ' . $suministro->codigopostal_suministro . ' ';
                        $smsid = $this->esendexsms($request->input('mobile_no'), $mensaje);
                        if ($smsid) {
                            $updateArr = array(
                                'mobile_no' => $request->input('mobile_no'),
                                'codigo_de_verificacion' => $verify_num,
                                'vencimiento' => date('Y-m-d', strtotime("+2 days")),
                                'updated_at' => date('Y-m-d H:i:s')
                            );
                            $enviarcontracto = $this->enviarcontracto->find($suministro_existing_contract->id);
                            $enviarcontracto->update($updateArr);
                            return Response::json(['success' => true, 'data' => 'Phone number updated successfully']);
                        }
                    } else {
                        return Response::json(['success' => false, 'data' => array('No records found.')]);
                    }
                }
            }
        } catch (\Exception $e) {
            return Response::json(['success' => false, 'data' => array(str_replace(":", " ", $e->getMessage()))]);
        }
    }

    public function resendmsg(Request $request) {
        try {

            $decode_suministro_id = \Hashids::decode($request->input('suministro_id'));
            $decode_oferta_id = \Hashids::decode($request->input('oferta_id'));
            $suministro_existing_contract = DB::table('enviar_contractos')
                    ->where('oferta_id', $decode_oferta_id[0])
                    ->where('suministro_id', $decode_suministro_id[0])
                    ->where('estado', 0)
                    ->orderBy('id', 'DESC')
                    ->first();

            if ($suministro_existing_contract) {

                $suministro = Suministro::where('id', $decode_suministro_id[0])->first();

                $redirect_url = url('/enviarcontracto/' . $request->input('suministro_id') . '/' . $request->input('oferta_id') . '/'.$request->input('resultado_id').'?confirmar=' . \Hashids::encode(1));
                $verify_num = mt_rand(10000, 99999);
                $mensaje = 'URL: ' . $redirect_url;
                $mensaje .= PHP_EOL . 'CODIGO: ' . $verify_num . ' para el suministro en: ' . $suministro->direccion_suministro . ' ' . $suministro->aclaratorio_suministro . ' ' . $suministro->poblacion_suministro . ' ' . $suministro->codigopostal_suministro . ' ';
                $smsid = $this->esendexsms($suministro_existing_contract->mobile_no, $mensaje);

                if ($smsid) {
                    $updateArr = array(
                        'codigo_de_verificacion' => $verify_num,
                        'vencimiento' => date('Y-m-d', strtotime("+2 days")),
                        'updated_at' => date('Y-m-d H:i:s')
                    );
                    $enviarcontracto = $this->enviarcontracto->find($suministro_existing_contract->id);
                    $enviarcontracto->update($updateArr);
                    return Response::json(['success' => true, 'data' => 'Mensaje enviado']);
                } else {
                    return Response::json(['success' => false, 'data' => array('Mensaje no enviado')]);
                }
            } else {
                return Response::json(['success' => false, 'data' => array('No records found.')]);
            }
        } catch (\Exception $e) {
            return Response::json(['success' => false, 'data' => array(str_replace(":", " ", $e->getMessage()))]);
        }
    }

    public function cancelcontract(Request $request) {
        try {
            date_default_timezone_set('Europe/Amsterdam');
            $decode_suministro_id = \Hashids::decode($request->input('suministro_id'));
            $decode_oferta_id = \Hashids::decode($request->input('oferta_id'));
            $suministro_existing_contract = DB::table('enviar_contractos')
                    ->where('oferta_id', $decode_oferta_id[0])
                    ->where('suministro_id', $decode_suministro_id[0])
                    ->where('estado', 0)
                    ->orderBy('id', 'DESC')
                    ->first();
            if ($suministro_existing_contract) {
                $updateArr = array(
                    'estado' => 2,
                    'updated_at' => date('Y-m-d H:i:s')
                );
                if (\Auth::check()) {
                    $updateArr['is_agent_cancelled'] = 1;
                } else {
                    $updateArr['is_agent_cancelled'] = 0;
                }
                $enviarcontracto = $this->enviarcontracto->find($suministro_existing_contract->id);
                $enviarcontracto->update($updateArr);
                return Response::json(['success' => true, 'data' => 'Contract cancelled']);
            } else {
                return Response::json(['success' => false, 'data' => array('No records found.')]);
            }
        } catch (\Exception $e) {
            return Response::json(['success' => false, 'data' => array(str_replace(":", " ", $e->getMessage()))]);
        }
    }

    private function telefacil() {

        $this->usuario = 637471397;
        $this->pass = 7969;
        $this->mascara = 'contrato';
        $curl = curl_init("https://scgi.duocom.es/cgi-bin/telefacil2/apisms?accion=confirming&principal=" . $this->usuario . "&pass=" . $this->pass . "&movil1=" . $request->input('mobile_no') . "&mensaje=" . $mensaje . "&mascara=" . $this->mascara . "&url_redir=" . $redirect_url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        $output = curl_exec($curl);
        curl_close($curl);
    }

    private function getpdf($oferta_id, $suministro_id) {

        $decrpyt_oferta_id = \Hashids::decode($oferta_id);
        $decrpyt_suministro_id = \Hashids::decode($suministro_id);
        $suministro = Suministro::find($decrpyt_suministro_id[0]);
        $suministro_existing_contract = DB::table('enviar_contractos')
                ->where('oferta_id', $decrpyt_oferta_id[0])
                ->where('suministro_id', $decrpyt_suministro_id[0])
                ->where('estado', 0)
                ->first();
        $P = [];
        if (($suministro->tarifaacceso_id) >= 1 && ($suministro->tarifaacceso_id <= 2)):
            $P[0] = $suministro->P1;
        endif;
        if (($suministro->tarifaacceso_id) >= 3 && ($suministro->tarifaacceso_id <= 4)):
            $P[0] = $suministro->P1;
            $P[1] = $suministro->P2;

        endif;
        if (($suministro->tarifaacceso_id) >= 5 && ($suministro->tarifaacceso_id <= 6)):
            $P[0] = $suministro->P1;
            $P[1] = $suministro->P2;
            $P[2] = $suministro->P3;
        endif;
        if (($suministro->tarifaacceso_id) >= 7 && ($suministro->tarifaacceso_id <= 10)):
            $P[0] = $suministro->P1;
            $P[1] = $suministro->P2;
            $P[2] = $suministro->P3;
            $P[3] = $suministro->P4;
            $P[4] = $suministro->P5;
            $P[5] = $suministro->P6;
        endif;
        $contacto = User::where('id', $suministro->contacto_id)->get()->first();
        $ofertas = DB::table('ofertas as o')
                ->select('o.*', 'c.*', 'p.*', 'tp.nombre as tipo_precio')
                ->leftJoin('comercializadora as c', 'c.idcomercializadora', '=', 'o.idcomercializadora')
                ->leftJoin('tipoprecio as tp', 'tp.id', '=', 'o.tipoprecio_id')
                ->join('plantillas_de_contratos as p', 'p.id', '=', 'o.idplantilla')
                ->where('o.id', $decrpyt_oferta_id[0]);
        $ofertas = $ofertas->first();
        if ($suministro->tarifaacceso_cambio_id) {
            $tarifaacceso_cambio = Tarifaacceso::find($suministro->tarifaacceso_cambio_id)->select('tarifa')->first();
        } else {
            $tarifaacceso_cambio = '';
        }
        $ofertas->dato = '';
        $ofertas->obligacion_permanencia_desc = (($ofertas->obligacion_permanencia == 1) ? 'Si' : 'No');
        $tarifa = calcularTarifa($ofertas);
        if (!isset($tarifa['valle_final_dto'])) {
            $tarifa['valle_final_dto'] = 0;
        }
        $comercializadora = Comercializadora::find($ofertas->idcomercializadora);
        $pdf = \PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'defaultPaperSize' => 'a4'])->loadView('enviarcontracto.pdf.contracto', ['ofertas' => (object) $ofertas, 'contacto' => $contacto, 'suministro' => $suministro, 'comercializadora' => $comercializadora, 'tarifa' => (object) $tarifa, 'suministro_existing_contract' => $suministro_existing_contract, 'tarifaacceso_cambio' => $tarifaacceso_cambio]);
        return $pdf->download('invoice.pdf');
    }

    public function checkcrmusario(Request $request) {
        if ($request->ajax()) {
            if (\Auth::check()) {
                $decrpyt_oferta_id = \Hashids::decode($request->input('territorioid'));
                $decrpyt_suministro_id = \Hashids::decode($request->input('suministroid'));
                $suministro_existing_contract = DB::table('enviar_contractos')
                        ->where('oferta_id', $decrpyt_oferta_id[0])
                        ->where('suministro_id', $decrpyt_suministro_id[0])
                        ->orderBy('id', 'DESC')
                        ->first();
                if (empty($suministro_existing_contract)) {
                    die(json_encode(array('success' => true, 'titular_tipo' => $request->input('titular_tipo'))));
                }
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show() {
        return view('enviarcontracto.partials.success');
    }

    public function error() {
        return view('errors.401');
    }

    public function ofertaInactiva() {
        return view('enviarcontracto.partials.oferta-inactiva');
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
      try {
          $rules = [
              'email' => 'required|email',
              'suministro_id' => 'required',
              'oferta_id' => 'required',
              'resultado_id' => 'required',
          ];
          $customerrMessages = [
              'email.required' => 'Email es requerida.'
          ];
          $validator = Validator::make($request->all(), $rules, $customerrMessages);
          if ($validator->fails()) {
              $messages = $validator->messages();
              return Response::json(['success' => false, 'data' => $messages]);
          } else {
              if ($request->input('email')) {
                  $decode_suministro_id = \Hashids::decode($request->input('suministro_id'));
                  $decode_oferta_id = \Hashids::decode($request->input('oferta_id'));
                  $decode_resultado_id = \Hashids::decode($request->input('resultado_id'));
                  $suministro_existing_contract = DB::table('enviar_contractos')
                          ->where('oferta_id', $decode_oferta_id[0])
                          ->where('suministro_id', $decode_suministro_id[0])
                          ->where('estado', 0)
                          ->orderBy('id', 'DESC')
                          ->first();
                  if ($suministro_existing_contract) {

                    $updateArr = array(
                        'email' => $request->input('email'),
                        'updated_at' => date('Y-m-d H:i:s')
                    );
                    $enviarcontracto = $this->enviarcontracto->find($suministro_existing_contract->id);
                    $enviarcontracto->update($updateArr);
                    return Response::json(['success' => true, 'data' => 'Email updated successfully']);
                  } else {
                      return Response::json(['success' => false, 'data' => array('No records found.')]);
                  }
              }
          }
      } catch (\Exception $e) {
          return Response::json(['success' => false, 'data' => array(str_replace(":", " ", $e->getMessage()))]);
      }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
    }

}
