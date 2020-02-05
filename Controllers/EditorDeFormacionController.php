<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;
use App\Models\EditorDeFormacion;
use App\Models\FormacionAgentes;
use App\Models\Comentar;
use App\Models\Responder;
use Illuminate\Support\Facades\Auth;
use Session;
use DB;
use Validator;
use Response;
use Illuminate\Support\Facades\Redirect;

class EditorDeFormacionController extends MainController {

    public function __construct(EditorDeFormacion $EditorDeFormacion, FormacionAgentes $FormacionAgentes, Comentar $Comentar, Responder $Responder) {
        parent::__construct();
        \View::share('titulo_pagina', 'EditorDeFormacion');
        $this->editordeformacion = $EditorDeFormacion;
        $this->formacionagentes = $FormacionAgentes;
        $this->comentar = $Comentar;
        $this->responder = $Responder;
    }

    public function index() {
        $editordeformacion = $this->editordeformacion->orderBy('id', 'desc')->paginate(10);
        if(!$editordeformacion->isEmpty()){
          foreach($editordeformacion as $ek => $cat){
            $id = $cat->id;
            $faqs = $this->formacionagentes->where('editordeformacions_id', $id)->count();
            if($faqs>0){
              $cat->delete_category =  0;
            }else{
              $cat->delete_category =  1;
            }
          }
        }
        $unansweredquestions = DB::table('comentars')
                ->join('formacionagentes', 'formacionagentes.id', '=', 'comentars.formacionagentes_id')
                ->join('editordeformacions', 'editordeformacions.id', '=', 'formacionagentes.editordeformacions_id')
                ->select('comentars.id', 'comentars.comentario', 'comentars.created_at', 'formacionagentes.faq', 'editordeformacions.faqcategoria')
                ->where('comentars.pregunta', '=', 1)
                ->where('comentars.pregunta_status', '=', 0)
                ->get();
        return view('editordeformacion.index', ['editordeformacion' => $editordeformacion, 'unansweredquestions' => $unansweredquestions]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        return view('editordeformacion.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $rules = [
            'faqcategoria' => 'required'
        ];
        $customMessages = [
            'faqcategoria.required' => 'El campo CategorÃ­a es obligatorio.'
        ];
        $validator = Validator::make($request->all(), $rules, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            return Redirect::to('editordeformacion/create')->withErrors($validator)->withInput();
        } else {
            $data = $request->all();
            $data['user_id'] = Auth::user()->id;

            $editordeformacion = $this->editordeformacion->create($data);

            Session::flash('flash_message', 'Categoria De Pregunta agregada correctamente!');
            return redirect()->route('editordeformacion');
        }
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
        $editordeformacion = $this->editordeformacion->find($id);
        return view('editordeformacion.edit', ['editordeformacion' => $editordeformacion]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $rules = [
            'faqcategoria' => 'required'
        ];
        $customMessages = [
            'faqcategoria.required' => 'El campo CategorÃ­a es obligatorio.'
        ];
        $validator = Validator::make($request->all(), $rules, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            return Redirect::to('editordeformacion/edit/' . $id)->withErrors($validator)->withInput();
        } else {
            $data = $request->all();
            $editordeformacion = $this->editordeformacion->find($id);
            $editordeformacion->update($data);

            Session::flash('flash_message', 'CategorÃ­a actualizada con Ã©xito!');
            return redirect()->route('editordeformacion');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
      Session::flash('flash_message', 'Categoria eliminada correctamente!');
      $editordeformacion = $this->editordeformacion->find($id);
      $editordeformacion->delete();
      return redirect()->route('editordeformacion');
    }

    public function createfaq() {
        $editordeformacion = $this->editordeformacion->all();
        return view('editordeformacion.formacionagentes.create', ['editordeformacion' => $editordeformacion]);
    }

    public function addfaq(Request $request) {
        $rules = [
            'editordeformacions_id' => 'required|integer',
            'faq' => 'required',
            'faqbody' => 'required'
        ];
        $customMessages = [
            'editordeformacions_id.required' => 'El campo CategorÃ­a es obligatorio.',
            'faq.required' => 'Se requiere campo de pregunta.',
            'faqbody.required' => 'Se requiere un cuerpo de pregunta.'
        ];
        $validator = Validator::make($request->all(), $rules, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            return Redirect::to('editordeformacion/createfaq')->withErrors($validator)->withInput();
        } else {
            $data = $request->all();
            $data['user_id'] = Auth::user()->id;

            $formacionagentes = $this->formacionagentes->create($data);
            Session::flash('flash_message', 'Preguntas Frecuentes agregada correctamente!');
            return redirect()->route('editordeformacion-showfaq');
        }
    }

    public function showfaq() {
        $formacionagentes = DB::table('formacionagentes')
                ->join('editordeformacions', 'formacionagentes.editordeformacions_id', '=', 'editordeformacions.id')
                ->select('formacionagentes.*', 'editordeformacions.faqcategoria')
                ->paginate(100);
        $all_categories = $this->editordeformacion->get();
        return view('editordeformacion.formacionagentes.index', ['formacionagentes' => $formacionagentes, 'all_categories'=>$all_categories]);
    }

    public function editfaq($id) {
        $editordeformacion = $this->editordeformacion->all();
        $formacionagentes = $this->formacionagentes->find($id);
        return view('editordeformacion.formacionagentes.edit', ['formacionagentes' => $formacionagentes, 'editordeformacion' => $editordeformacion]);
    }

    public function updatefaq(Request $request, $id) {
        $rules = [
            'editordeformacions_id' => 'required|integer',
            'faq' => 'required',
            'faqbody' => 'required'
        ];
        $customMessages = [
            'editordeformacions_id.required' => 'El campo CategorÃ­a es obligatorio.',
            'faq.required' => 'Se requiere campo de pregunta.',
            'faqbody.required' => 'Se requiere un cuerpo de pregunta.'
        ];
        $validator = Validator::make($request->all(), $rules, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            return Redirect::route('editordeformacion-editfaq', ['id' => $id])->withErrors($validator)->withInput();
        } else {
            $data = $request->all();
            $data['user_id'] = Auth::user()->id;
            $formacionagentes = $this->formacionagentes->find($id);
            $formacionagentes->update($data);
            Session::flash('flash_message', 'Pregunta actualizada con Ã©xito');
            return redirect()->route('editordeformacion-showfaq');
        }
    }

    public function storeresponder(Request $request) {
        $rules = [
            'comentars_id' => 'required|integer',
            'responder' => 'required'
        ];
        $customMessages = [
            'comentars_id.required' => 'El ID del comentario es obligatorio.',
            'responder.required' => 'La respuesta es requerida.'
        ];
        $validator = Validator::make($request->all(), $rules, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            return Response::json(['success' => false, 'data' => $messages]);
        } else {
            $data = $request->all();
            $data['user_id'] = Auth::user()->id;

            $responder = $this->responder->create($data);
            $updateArr = array(
                'pregunta_status' => 1
            );
            $comentar = $this->comentar->find($data['comentars_id']);
            $comentar->update($updateArr);
            $commentobj = DB::table('comentars')->select('user_id', 'formacionagentes_id', 'comentario')->where('id', $data['comentars_id'])->first();
            $comment_user_email = User::find($commentobj->user_id)->email;
            $comment_user_fullname = User::find($commentobj->user_id)->fullname();
            $faq = DB::table('formacionagentes')->select('faq')->where('id', $commentobj->formacionagentes_id)->first();
            $mail = new \PHPMailer(true);
            $mail->CharSet = "utf-8";
            $mail->isHTML(true);
            $mail->SMTPAuth = true;
            $mail->Host = env('MAIL_HOST');
            $mail->Port = env('MAIL_PORT');
            $mail->Username = env('MAIL_USERNAME');
            $mail->Password = env('MAIL_PASSWORD');
            $mail->setFrom(Auth::user()->email_smtp, Auth::user()->fullName());

            $mail->Subject = 'En cuanto a la respuesta a la pregunta';
            $view = \View::make('editordeformacion.partials.email', [
                        'responder' => $responder->responder,
                        'comentario' => $commentobj->comentario,
                        'faq' => $faq->faq,
                        'comment_user_fullname' => $comment_user_fullname,
            ]);

            $html = $view->render();
            $mail->MsgHTML($html);
            $mail->addAddress($comment_user_email);

            if (!$mail->Send()) {
                return Response::json(['success' => false, 'data' => $mail->ErrorInfo]);
            } else {
                return Response::json(['success' => true, 'data' => 'success']);
            }
        }
    }

    public function searchfaq(Request $request){
      $categories = $this->formacionagentes;
        if($request->input('q')){
          $categories = $categories->where('faq', 'like','%'.$request->input('q').'%');
        }
        if($request->input('category_id')){
          $categories = $categories->whereIn('editordeformacions_id', $request->input('category_id'));
        }
      $categories = $categories->get();

      $data = array();
      foreach ($categories as $catk => $faq) {
        $category = $this->editordeformacion->where('id',$faq->editordeformacions_id)->first();
        $data[$catk]['category'] = $category->faqcategoria;
        $data[$catk]['id'] = $faq->id;
        $data[$catk]['faq'] = $faq->faq;
        $data[$catk]['route'] = route("editordeformacion-editfaq",['id'=>$faq->id]);
        $data[$catk]['delroute'] = route("editordeformacion-deletefaq",['id'=>$faq->id]);
      }

      return Response::json(['success' => true, 'data' => (object)$data]);
    }

    public function deletefaq($id){
      Session::flash('flash_message', 'Faq eliminada correctamente!');
      $formacionagentes = $this->formacionagentes->find($id);
      $comments = $this->comentar->where('formacionagentes_id',$formacionagentes->id)->get();
      if(!$comments->isEmpty()){
        foreach($comments as $comment){
          $this->responder->where('comentars_id',$comment->id)->delete();
        }
      }
      $this->comentar->where('formacionagentes_id',$formacionagentes->id)->delete();
      $formacionagentes->delete();
      return redirect()->route('editordeformacion-showfaq');
    }

}
