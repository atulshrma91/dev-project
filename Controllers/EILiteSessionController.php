<?php

namespace App\Http\Controllers;

use App\FSIVRSession;
use App\MasterAudio;
use App\Models\IBMBehaviorAPIResult;
use App\Services\EILiteService;
use App\Jobs\Twilio\DownloadEILiteRecording;
use App\Jobs\CreateAudioFromFSIVRSession;
use App\Traits\CommonTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;
use Twilio\Twiml;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Storage;

class EILiteSessionController extends Controller
{

    use ResponseTrait, CommonTrait;

    protected $eiLiteService;

    public function __construct(EILiteService $liteService)
    {
        $this->eiLiteService = $liteService;
    }

    public function sayWelcome()
    {
        $response = new Twiml();
        $gather = $response->gather([
            'action' => route('ei_lite_session_create'),
            'timeout' => 10
        ]);

        $gather->play(__('enhancedinterview.lite.audio_landing'));

        return $response;
    }

    public function createSession(Request $request)
    {

        $login = null;
        if (!$request->session()->has('ei_lite')) {
            $request->session()->put('ei_lite.login');
            $login = [];
        } else {
            $login = $request->session()->get('ei_lite.login');
        }

        if (empty($login['pin'])) {
            return $this->askPin($request);
        }

        $login['ei_lite_post_id'] = $request->input('CallSid');
        $login['call_sid'] = $request->input('CallSid');
        $login['candidate_caller_id'] = $request->input('From');
        $login['employer_caller_id'] = $request->input('To');
        $session = $this->eiLiteService->createIVRSession($login);
        $request->session()->put('ei_lite', [
            'session_id' => $session->id,
            'questions' => $session->contents()->get(),
            'answer_count' => 0,
        ]);
        $response = new Twiml();
        $response->redirect(route('ei_lite_session_questions'));
        return $response;
    }

    public function askPin(Request $request)
    {
        $pin = $request->input('Digits');
        Log::info($pin);

        if (!empty($pin) && $this->eiLiteService->isEILitePostExists($pin)) {

            $key = $this->eiLiteService->eiLitePostExists($pin);
            if (!empty($key->session)){
                $response = new Twiml();
                $response->say('This pin has been used. Please create a new pin and call back again.', ['voice' => 'woman']);
                return $response;
            }
            Log::info($key->conference_key);
            $request->session()->put('ei_lite.login.conference_key', $key->conference_key);
            $request->session()->put('ei_lite.login.host_key', $key->host_key);
            $request->session()->put('ei_lite.login.pin', $pin);
            $request->session()->put('ei_lite.login.post_id', $key->id);

            $response = new Twiml();
            $response->redirect(route('ei_lite_session_ask_phone_num'));
            return $response;

        }


        $response = new Twiml();
        $gather = $response->gather([
            'numDigits' => 5,
            'timeout' => 10
        ]);

        $gather->say(__('fairscreen.ivr.wrong_pin'), ['voice' => 'woman']);
        return $response;
    }

    public function askPhoneNum(Request $request)
    {
        $phoneNum = $request->session()->get('ei_lite.tmp.phone_num');

        if (!empty($phoneNum)) {
            $action = $request->input('Digits');
            if ('2' === $action) {
                $request->session()->put('ei_lite.login.phone_num', $phoneNum);
                $response = new Twiml();
                $response->redirect(route('ei_lite_session_ask_candidate'));
                return $response;
            }
        }

        $response = new Twiml();
        $gather = $response->gather([
            'numDigits' => 20,
            'finishOnKey' => '#',
            'timeout' => 10,
            'action' => route('ei_lite_session_confirm_phone_num'),
        ]);

        $gather->say(__('enhancedinterview.lite.ask_phone_num'), ['voice' => 'woman']);
        return $response;
    }

    public function confirmPhoneNum(Request $request)
    {
        $response = new Twiml();

        $gather = $response->gather([
            'numDigits' => 1,
            'finishOnKey' => '',
            'action' => route('ei_lite_session_ask_phone_num'),
            'timeout' => 10
        ]);

        $number = $request->input('Digits');
        $request->session()->put('ei_lite.tmp.phone_num', $number);
        $formatted = implode(' ', str_split($number));
        Log::info($formatted);
        $gather->say(
            __('enhancedinterview.lite.confirm_phone_num', ['number' => $formatted]),
            ['voice' => 'woman']
        );

        return $response;
    }

    public function askCandidatePin(Request $request)
    {

        try {

            /*$response = new VoiceResponse();
            $response->dial($request->session()->get('ei_lite.tmp.phone_num'),
                [
                    "callerId" => '+12066200607',
                    "record" => 'record-from-answer-dual',
                    'action' => route('ei_lite_session_callback'),
                    'url' => route('ei_lite_session_sip'),
                ]);
            echo $response;*/

            $response = new VoiceResponse();
            $dial = $response->dial('',[
                "callerId" => '+12066200607',
                "record" => 'record-from-answer-dual',
                'action' => route('ei_lite_session_callback'),
            ]);
            $dial->number($request->session()->get('ei_lite.tmp.phone_num'), [
                'url' => route('ei_lite_session_sip')
            ]);
            echo $response;

            /*$sid    = config('external_api.twilio.sid');
            $token  = config('external_api.twilio.token');
            $client = new Client($sid, $token);

            $call = $client->calls
                ->create($request->session()->get('ei_lite.tmp.phone_num'), // to
                    '+12062195371', // from
                    array(
                        "CallerId" => "+12062195371",
                        "record" => True,
                        "RecordingChannels" => 'dual',
                        "url" => route('ei_lite_session_ask_employer')
                    )
                );

            Log::info('outbound call sid');
            Log::info($call->sid);*/
            Log::info('outbound call sid');

        }catch (\Exception $exception){
            Log::info('outbound call exception log');
            Log::info($exception->getMessage());
        }
    }

    public function askEmployerPin(Request $request)
    {

        $response = new Twiml();
        $response->say(
            'Your the first person in this conference, please wait until candidate user will connect..',
            [
                'voice' => 'alice',
                'language' => 'en-GB'
            ]
        );
        $dial = $response->dial();
        $dial->conference(
            $request->session()->get('ei_lite.login.conference_key'),
            [
                'startConferenceOnEnter' => true,
                'endConferenceOnExit' => true,
                'waitUrl' => 'http://twimlets.com/holdmusic?Bucket=com.twilio.music.ambient',
                "record" => 'record-from-answer-dual',
                'recordingStatusCallback' => route('ei_lite_session_callback'),
            ]
        );

        return response($response)->header('Content-Type', 'application/xml');
    }

    public function interview(Request $request)
    {
        if ($request->input('Digits') === '1') {
            return $this->recordAnswer($request);
        } else {
            return $this->askQuestions($request);
        }
    }

    public function askQuestions(Request $request)
    {
        $response = new Twiml();

        $question = $this->getCurrentQuestion($request);
        if (null === $question) {
            $response->redirect(route('ei_lite_session_fin'));
            return $response;
        }

        $gather = $response->gather([
            'numDigits' => 1,
            'action' => route('ei_lite_session_interview'),
            'timeout' => 10
        ]);

        $count = $request->session()->get('ei_lite.answer_count');

        $gather->say(
            __('enhancedinterview.lite.question_number', ['count' => $count + 1]),
            ['voice' => 'woman']
        );

        $gather->pause(['length' => 2]);
        if ('audio' === $question->type) {
            $gather->play($question->content);
        } else {
            $gather->say($question->content, ['voice' => 'woman']);
        }
        $gather->pause(['length' => 1]);

        $gather->say(__('enhancedinterview.lite.question_instruction'), ['voice' => 'woman']);

        return $response;
    }

    public function recordAnswer(Request $request)
    {
        $response = new Twiml();
        $count = $request->session()->get('ei_lite.answer_count');
        $response->say(__('enhancedinterview.lite.prepare_answer'), ['voice' => 'woman']);
        $response->record([
            'timeout' => 10,
            'maxLength' => 600,
            'transcribe' => false,
            'action' => route('ei_lite_session_questions'),
            'recordingStatusCallback' => route(
                'ei_lite_session_recording_status',
                ['id' => $this->getCurrentQuestion($request)->id]
            )
        ]);

        $request->session()->put('ei_lite.answer_count', ++$count);

        return $response;
    }

    public function sayGoodbye(Request $request)
    {
        $response = new Twiml();
        $response->play(__('enhancedinterview.lite.audio_outro'));

        $sessionId = $request->session()->get('ei_lite.session_id');

        $job = new CreateAudioFromFSIVRSession(FSIVRSession::find($sessionId));
        dispatch($job);

        return $response;
    }

    public function recordingStatus(Request $request)
    {
        if ('completed' !== $request->input('RecordingStatus')) {
            return;
        }

        $recording = new \stdClass;
        $recording->sid = $request->input('RecordingSid');
        $recording->call_sid = $request->input('CallSid');
        $recording->recording_url = $request->input('RecordingUrl');
        $recording->content_id = $request->route('id');

        dispatch(new DownloadEILiteRecording($recording));
    }


    /**
     * @param Request $request
     * @return Twiml
     * @throws \Twilio\Exceptions\TwimlException
     */
    public function postCallCallbackStatus(Request $request){

        $attribute = $request->all();
        Log::info('Request keys', $attribute);

        /*if ('completed' !== $request->input('CallStatus')) {
            return;
        }*/

        $login['ei_lite_post_id'] = $request->session()->get('ei_lite.login.post_id');
        $login['call_sid'] = $request->input('CallSid');
        $login['from'] = $request->input('From');
        $login['to'] = $request->input('To');
        $login['employer_caller_id'] = $request->input('DialCallSid');
        $login['account_sid'] = $request->input('AccountSid');
        $login['recording_url'] = $request->input('RecordingUrl');
        $login['recording_sid'] = $request->input('RecordingSid');
        $login['call_status'] = $request->input('CallStatus');
        $login['dial_call_status'] = $request->input('DialCallStatus');
        $this->eiLiteService->createIVRSession($login);

        // download recording
        $recording = new \stdClass;
        $recording->sid = $request->input('RecordingSid');
        $recording->call_sid = $request->input('CallSid');
        $recording->recording_url = $request->input('RecordingUrl');
        $recording->content_id = $request->session()->get('ei_lite.login.post_id');

        // dispatch job for downloading ei
        dispatch(new DownloadEILiteRecording($recording));

        $response = new Twiml();
        $response->say(__('enhancedinterview.lite.goodbye'), ['voice' => 'woman']);
        return $response;
    }

    public function askSip(){

        $response = new Twiml();
        $response->say('Thank you for participation, This Interview is being recorded for training purposes.', ['voice' => 'woman']);
        return $response;
    }

    public function getIIResults($id){

        $audio = MasterAudio::with(['post', 'transcript', 'emotion'])->where('object_id',$id)->first();
        if (empty($audio)){
            return $this->returnResponse(null, 'Audio for selected recording is currently not available', 401);
        }

        if(count($audio->transcript->personality) <= 0 ){
            return $this->returnResponse(null, 'Personality profile for selected recording is currently not available', 401);
        }

        /*if (count($audio->emotion) <= 0){
            return $this->returnResponse(null, 'Emotion for selected recording is currently not available', 401);
        }*/

        $tone = $audio->transcript->tone;

        $score = $this->elev8a_score( [
            'objectId' => $audio->object_id,
            'objectName' => $audio->object_name,
            'resume' => $audio,
            'personalityData' => $audio->transcript->personality()->get()->toArray()
        ] );


        $data = [
            'videoUrl' => $audio->remote_mp4_url,
            'profile' => [
                'personality' => $audio->transcript->personality,
                'big5_personality' => $audio->transcript->personality()->groupBy('parent_trait_id')->selectRaw('*, sum(percentile) AS percentile, sum(raw_score) AS raw_score')->get(),
                'tone' => $tone,
                'emotion' => $audio->emotion,
                'score' => $score,
                'extraVideoUrl' => $audio->remote_mp4_url,
            ],
            'objectId' => $id,
            'objectName' => $audio->object_name,
            'objectData' => $audio->post,
            'ibi' => null,
            'resume' => null
        ];
        return $this->returnResponse($data, 'Loaded', 200);
    }
}
