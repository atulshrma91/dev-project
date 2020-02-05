<?php

namespace App\Http\Controllers;

use \App\Models\CompanyJobReq;
use App\Http\Requests\EILitePost;
use App\MasterAudio;
use App\Repositories\EILitePostRepository;
use App\Traits\CommonTrait;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;


class EILiteController extends Controller
{
    use CommonTrait, ResponseTrait;

    protected $eiLiteRepo;
    
    public function __construct(EILitePostRepository $eiLiteRepo)
    {
        $this->eiLiteRepo = $eiLiteRepo;
    }

    public function index(){

        $posts = $this->eiLiteRepo->getAll();
        if (count($posts) > 0){
            foreach ($posts as $post){
                if (empty($post->score)) {
                    /********* Save Elv8 Score in Database *******/
                    $audio = MasterAudio::with(['transcript'])->where('object_id', $post->id)->orderByDesc('id')->first();
                    if (!empty($audio)) {
                        $score = $this->elev8a_score([
                            'objectId' => $audio->object_id,
                            'objectName' => $audio->object_name,
                            'resume' => $audio,
                            'personalityData' => $audio->transcript->personality()->get()->toArray()
                        ]);
                        $post->update([
                            'score' => $score['elv8']
                        ]);
                    }
                }
            }
        }

        return view('ei-lite.index', compact('posts'));
    }


    /**
     * @return mixed
     * @throws \Exception
     */
    public function anyData()
    {
        $posts = $this->eiLiteRepo->getAll();
        return DataTables::of($posts)->editColumn( 'action', function ( $posts ) {
            return view( 'ei-lite.status', compact( 'posts' ) )->render();
        } )->editColumn( 'company_job_req_id', function ( $posts ) {
            return view( 'ei-lite.position', compact( 'posts' ) )->render();
        } )->editColumn( 'session', function ( $posts ) {
            return view( 'ei-lite.session', compact( 'posts' ) )->render();
        } )->make(true);
    }

    public function create(){
        $jobs = CompanyJobReq::where('company_id', Auth::user()->company->id)->where('is_ii_lite', true)->where('is_active', true)->pluck('req_desc', 'id')->toArray();

        $employer_pin = Auth::user()->companyUsers->fs_lite_pin;
        return view('ei-lite.create', compact('employer_pin', 'jobs'));
    }

    public function updateCandidatePin($jobId){

        $result = null;
        $job_pin = CompanyJobReq::where('id', $jobId)->first()->pin;

        $candidate_pin = \App\Models\EILitePost::where('company_job_req_id', $jobId)->orderBy('pin', 'desc')->first();
        if (!empty($candidate_pin)){
            $candidate_pin = $candidate_pin->pin;
        }else{
            $candidate_pin = 0;
        }

        $candidate_pin = (int) filter_var(substr($candidate_pin, -4), FILTER_SANITIZE_NUMBER_INT);
        $candidate_pin = sprintf("%04d",++$candidate_pin);
        $full_pin = Auth::user()->company->pin.$job_pin.$candidate_pin;

        $result = [
            'full_pin' => Auth::id().'-'.$full_pin,
            'candidate_pin' => $full_pin,
            'job_pin' => $job_pin,
        ];

        return $this->returnResponse($result, 'Update Candidate pin', 200, 200);

    }
    
    public function store(EILitePost $request)
    {
        $data = $request->all();
        $data['user_id'] = Auth::id();

        $post = $this->eiLiteRepo->create($data);
        
        return response()->json([
            'id' => $post->id
        ]);
    }
    
    public function show($id){
        if (empty($id)){
            return 'Post id is required';
        }
        
        $post = $this->eiLiteRepo->getById($id);
        if (!$post){
            return 'Post not found';
        }

        $number_ = null;
        if (!empty($post->candidate_number)){

            $regex = "/(\\d{3})(\\d{3})(\\d{4})/";
            $replacement = "$1-$2-$3";

            $number_ = preg_replace($regex, $replacement, $post->candidate_number);
        }

        $pin = null;
        if (!empty($post->pin)){

            $regex = "/(\\d{4})(\\d{3})(\\d{4})/";
            $replacement = "$1 $2 $3";

            $pin = preg_replace($regex, $replacement, $post->pin);
        }

        return view('ei-lite.show', compact('post', 'number_', 'pin'));
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy( $id )
    {

        $post = $this->eiLiteRepo->getById($id);
        if (empty($post)){
            return redirect( url( 'a/ei/lite/index' ) );
        }
        $this->eiLiteRepo->delete( $id );
        return redirect( url( 'a/ei/lite/index' ) );
    }

}
