<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyJobReq;
use App\Repositories\CompanyJobReqRepository;
use App\Traits\CommonTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class CompanyJobReqController extends Controller
{
    use CommonTrait, ResponseTrait;

    protected $companyJobReqRepo;
    
    public function __construct(CompanyJobReqRepository $companyJobReqRepo)
    {
        $this->companyJobReqRepo = $companyJobReqRepo;
    }

    public function index(){
        return view('company-job-req.index');
    }


    /**
     * @return mixed
     * @throws \Exception
     */
    public function anyData()
    {
        $posts = $this->companyJobReqRepo->getAll();
        return DataTables::of($posts)->editColumn( 'status', function ( $posts ) {
            return view( 'company-job-req.status', compact( 'posts' ) )->render();
        } )->make(true);
    }

    public function create(){
        $msg = null;
        return view('company-job-req.create', compact('msg'));
    }


    public function store(CompanyJobReq $request)
    {
        $data = $request->all();
        $data['company_id'] = company_info()->id;
        $data['user_id'] = Auth::id();
        $data['pin'] = $this->getPin();

        $validate = \App\Models\CompanyJobReq::where('company_id',company_info()->id)
            ->where('req_desc', $request->input('req_desc'))->first();
        if (empty($validate)){
            $this->companyJobReqRepo->create($data);
            return redirect('a/ei/company-job-req');
        }else{
            $msg = 'You already used <b>'.$request->input('req_desc').'</b> name, please try some other job req description';
            return view('company-job-req.create', compact('msg'));
        }

    }

    public function status(Request $request)
    {
        $data = $request->all();
        if ($request->has('column')){
            $job = $this->companyJobReqRepo->statusColumn($data);
        }else{
            $job = $this->companyJobReqRepo->status($data);
        }
        return $this->returnResponse($job, 'Loaded', 200);
    }

    private function getPin(){

        $job = \App\Models\CompanyJobReq::where('company_id', company_info()->id)->orderBy('pin', 'desc')->first();
        if (empty($job)){
            $job_last_pin = 0;
        }else{
            $job_last_pin = $job->pin;
        }
        $job_last_pin = (int) filter_var($job_last_pin, FILTER_SANITIZE_NUMBER_INT);
        return $job_pin = sprintf("%03d",++$job_last_pin);

    }

}
