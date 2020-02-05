<?php

namespace App\Jobs;

use App\VisionAPIResult;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;

class ConsumeVisionAPI extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private $result;

    private $client;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(VisionAPIResult $result)
    {
        $this->result = $result;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @todo Refactor into classes
     */
    public function handle()
    {
        DB::reconnect();

        $this->client = new Client([
            'base_uri' => 'https://westus.api.cognitive.microsoft.com/vision/v1.0/',
            'headers' => [
                'Ocp-Apim-Subscription-Key' => config('app.external_api.microsoft.vision_api_key')
            ]
        ]);

        $this->execute();
    }

    public function execute()
    {
        $response = $this->client->request(
            'POST',
            'tag',
            [
                'json' => [
                    'url' => $this->result->url
                ]
            ]
        );

        if ($response->getStatusCode() == 200) {
            $this->result->result = $response->getBody();
            $this->result->status = VisionAPIResult::STATUS_SUCCEED;
            $this->result->save();
        }
    }

    public function setClient($client)
    {
        $this->client = $client;
    }
}
