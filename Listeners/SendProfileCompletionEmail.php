<?php

namespace App\Listeners;

use App\Mail\InterviewPracticeResultCreated;
use App\Events\PersonalityServiceConsumed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use phpseclib\Crypt\Blowfish;

class SendProfileCompletionEmail
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  PersonalityServiceConsumed  $event
     * @return void
     */
    public function handle(PersonalityServiceConsumed $event)
    {
        $video = $event->getTranscript()->video;

        if ('interview_practice' !== $video->object_name) {
            return;
        }

        $session = DB::table('app_interview_session AS session')
            ->where('session.id', $video->object_id)
            ->where('session.object_name', 'interview_practice')
            ->first();

        if (empty($session)) {
            return;
        }

        $data = new \stdClass;
        $data->id = $session->id;
        $data->name = $session->applicant_name;
        $data->email = $session->applicant_email;

        Mail::to($data)
            ->bcc(config('app.mailing.interview_practice_bcc'))
            ->queue(new InterviewPracticeResultCreated($data));
    }
}
