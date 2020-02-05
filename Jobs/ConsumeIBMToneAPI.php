<?php

namespace App\Jobs;

use App\Jobs\Job;
use GuzzleHttp\Client;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

class ConsumeIBMToneAPI extends Job implements ShouldQueue
{
    use InteractsWithQueue;


    /**
     * Delay between request
     *
     */
    private $delayBetweenRequest;

    /**
     * Instance of \App\GoogleSpeechAPIResult
     *
     */
    private $transcript;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($transcript, $delayBetweenRequest = 15)
    {
        $this->transcript = $transcript;
        $this->delayBetweenRequest = $delayBetweenRequest;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @todo Refactor into classes
     */
    public function handle()
    {
        $client = new Client([
            'base_uri' => 'https://gateway.watsonplatform.net',
            'auth' => [
                config('app.external_api.ibm.tone.api_id'),
                config('app.external_api.ibm.tone.api_password')
            ]
        ]);

        $this->doHandle($client);
    }

    public function doHandle($client)
    {
        if (App::environment('production')) {
            DB::reconnect();
        }

        // In case the transcript result is emtpy
        $this->transcript->wordFill();

        $response = $client->request(
            'POST',
            '/tone-analyzer/api/v3/tone?version=2016-05-19',
            [
                'json' => ['text' => $this->transcript->result]
            ]
        );

        if ($response->getStatusCode() !== 200) {
            $this->release($this->delayBetweenRequest);
            return;
        }

        $result = json_decode($response->getBody(), true)['document_tone'];
        $row = ['transcript_id' => $this->transcript->id];

        foreach ($result['tone_categories'] as $toneCategory) {
            foreach ($toneCategory['tones'] as $tone) {
                $row[$toneCategory['category_id'] . '_' . $tone['tone_id']] = $tone['score'];
            }
        }

        DB::table('ibm_tone_api_result')->where('transcript_id', '=', $this->transcript->id)->delete();
        DB::table('ibm_tone_api_result')->insert($row);

        $client = null;
    }
}
