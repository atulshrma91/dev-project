<?php

namespace App\Http\Controllers;

use App\Http\Requests\CEIRiskKeywordReq;
use App\Repositories\CIERiskKeywordsReqRepository;
use App\Traits\CommonTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class CEIRiskKeywordsController extends Controller
{
    use CommonTrait, ResponseTrait;

    public function __construct(CIERiskKeywordsReqRepository $CIERiskKeywordsReqRepository){
      $this->ceiRiskKeywordReqRepo = $CIERiskKeywordsReqRepository;
    }

    public function index(){
        return view('keywords.index');
    }

    public function anyData(){
      $posts = $this->ceiRiskKeywordReqRepo->getAll();
      return DataTables::of($posts)->editColumn( 'status', function ( $posts ) {
          return view( 'keywords.status', compact( 'posts' ) )->render();
      } )->make(true);
    }

    public function create(){
        $msg = null;
        return view('keywords.create', compact('msg'));
    }

    public function store(CompanyJobReq $request){
        $data = $request->all();
        $data['company_id'] = company_info()->id;

        $validate = \App\Models\CEIRiskKeyword::where('risk_keyword', $request->input('risk_keyword'))->first();
        if (empty($validate)){
            $this->ceiRiskKeywordReqRepo->create($data);
            return redirect('a/ei/cei-risk-keywords/list');
        }else{
            $msg = 'You already used <b>'.$request->input('risk_keyword').'</b> name, please try some other job req description';
            return view('keywords.create', compact('msg'));
        }

    }

    public function status(Request $request){
        $data = $request->all();
        if ($request->has('column')){
            $keyword = $this->ceiRiskKeywordReqRepo->statusColumn($data);
        }else{
            $keyword = $this->ceiRiskKeywordReqRepo->status($data);
        }
        return $this->returnResponse($keyword, 'Loaded', 200);
    }

}
