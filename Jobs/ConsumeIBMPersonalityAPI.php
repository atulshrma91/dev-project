<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Events\PersonalityServiceConsumed;
use App\Mail\InterviewPracticeResultFailed;
use GuzzleHttp\Client;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\ScoringService;

class ConsumeIBMPersonalityAPI extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

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

    private $client;

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

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
        $this->client = new Client([
            'base_uri' =>'https://gateway.watsonplatform.net',
            'auth' => [
                config('app.external_api.ibm.personality.api_id'),
                config('app.external_api.ibm.personality.api_password')
            ]
        ]);

        $this->doHandle();
    }

    public function doHandle()
    {


        Log::info("Coming in IPB Personality Api");
        // WP-361: Only process interview_practice video with transcript
        // length >= app.external_api.ibm.personality.minimum_words. This rule
        // should be implemented in event listener.
        $minimumWords = (int) config('app.external_api.ibm.personality.minimum_words');


        if ('interview_practice' === $this->transcript->video->object_name &&
            str_word_count($this->transcript->result) < $minimumWords
        ) {
            $session = DB::table('app_interview_session AS session')
                ->where('session.id', $this->transcript->video->object_id)
                ->where('session.object_name', 'interview_practice')
                ->first();

            if (empty($session)) {
                return;
            }
             /********* Save Elv8 Score in Database *******/

            $data = new \stdClass;
            $data->id = $session->id;
            $data->name = $session->applicant_name;
            $data->email = $session->applicant_email;
            Mail::to($data)->queue(new InterviewPracticeResultFailed($data));
            return;
        }

        // Fill the transcript until it contains >= 100 words to prevent 400 error from IBM
        $this->transcript->wordFill();

        $result = $this->getResult();

        if ($result === null) {
            $this->release($this->delayBetweenRequest);
            return;
        }

        $warnings = empty($result['warnings']) ? [] : $result['warnings'];

        if (App::environment('production')) {
            DB::reconnect();
        }

        try {
            DB::beginTransaction();

            $this->clearPreviousResults();

            foreach ($result['personality'] as $trait) {
                $this->savePersonalityTree($trait);
            }


            $this->saveNeedsAndValues(array_merge($result['needs'], $result['values']));
            $this->saveBehaviors($result['behavior']);
            $this->saveConsumptionPreferences($result['consumption_preferences']);

            /********* Save Elv8 Score in Database *******/
            $Elv8score = ScoringService::callv8scorewp(array('objectId'=>$this->transcript->video->object_id,'objectName'=>$this->transcript->video->object_name,'transcriptId'=>$this->transcript->video->transcript_id));
            DB::insert('insert into application_interview_score (applicant_id, elv8_score) values (?, ?)', [$this->transcript->video->object_id, $Elv8score['elv8']]);
            DB::commit();

            Event::dispatch(new PersonalityServiceConsumed($this->transcript));
        } catch (\PDOException $e) {
            DB::rollback();

            $warnings[] = [
                'warning_id' => 'PDO_ERR_' . $e->getCode(),
                'message' => $e->getMessage()
            ];
        } finally {
            $this->saveWarnings($warnings);
        }
    }


    public function savePersonalityTree($trait, $parentTrait = null)
    {
        DB::table('ibm_personality_api_result')->insert([
            'transcript_id' => $this->transcript->id,
            'parent_trait_id' => $parentTrait,
            'trait_id' => $trait['trait_id'],
            'name' => $trait['name'],
            'category' => $trait['category'],
            'percentile' => $trait['percentile'],
            'raw_score' => $trait['raw_score']
        ]);

        if (!empty($trait['children'])) {
            foreach ($trait['children'] as $childrenTrait) {
                $this->savePersonalityTree($childrenTrait, $trait['trait_id']);
            }
        }
    }

    public function getResult()
    {
        $personalityAPIResponse = $this->client->request(
            'POST',
            '/personality-insights/api/v3/profile',
            [
                'json' => [
                    'contentItems' => [
                        [
                            'content' => $this->transcript->result,
                            'created' => time()
                        ]
                    ]
                ],
                'query' => [
                    'consumption_preferences' => 'true',
                    'behavior' => 'true',
                    'raw_scores' => 'true',
                    'version' => '2016-10-20'
                ]
            ]
        );

        if ($personalityAPIResponse->getStatusCode() !== 200) {
            return null;
        }

        return json_decode($personalityAPIResponse->getBody(), true);
    }

    public function clearPreviousResults()
    {
        DB::table('ibm_personality_api_result')->where('transcript_id', '=', $this->transcript->id)->delete();
        DB::table('ibm_behavior_api_result')->where('transcript_id', '=', $this->transcript->id)->delete();
        DB::table('ibm_conspref_api_result')->where('transcript_id', '=', $this->transcript->id)->delete();
    }

    public function saveNeedsAndValues($traits)
    {
        $needsAndValues = [];
        foreach ($traits as $trait) {
            $needsAndValues[] = [
                'transcript_id' => $this->transcript->id,
                'trait_id' => $trait['trait_id'],
                'name' => $trait['name'],
                'category' => $trait['category'],
                'percentile' => $trait['percentile'],
                'raw_score' => $trait['raw_score']
            ];
        }
        DB::table('ibm_personality_api_result')->insert($needsAndValues);
    }

    public function saveBehaviors($traits)
    {
        $behaviors = [];
        foreach ($traits as $behavior) {
            $behaviors[] = [
                'transcript_id' => $this->transcript->id,
                'trait_id' => $behavior['trait_id'],
                'name' => $behavior['name'],
                'category' => $behavior['category'],
                'percentage' => $behavior['percentage']
            ];
        }
        DB::table('ibm_behavior_api_result')->insert($behaviors);
    }

    public function saveConsumptionPreferences($consPref)
    {

        $consumptionPreferencesCategories = [];
        $consumptionPreferences = [];
        foreach ($consPref as $category) {
            $consumptionPreferencesCategories[] = [
                'transcript_id' => $this->transcript->id,
                'conspref_category_id' => $category['consumption_preference_category_id'],
                'name' => $category['name']
            ];

            if (!empty($category['consumption_preferences'])) {
                foreach ($category['consumption_preferences'] as $pref) {
                    $consumptionPreferences[] = [
                        'transcript_id' => $this->transcript->id,
                        'conspref_category_id' => $category['consumption_preference_category_id'],
                        'conspref_id' => $pref['consumption_preference_id'],
                        'name' => $pref['name'],
                        'score' => $pref['score']
                    ];
                }
            }
        }
        DB::table('ibm_conspref_api_result')->insert($consumptionPreferences);
    }

    public function saveWarnings($result)
    {
        $warnings = [];
        foreach ($result as $warning) {
            $warnings[] = [
                'transcript_id' => $this->transcript->id,
                'warning_id' => $warning['warning_id'],
                'message' => $warning['message']
            ];
        }
        DB::table('ibm_api_warning')->insert($warnings);
    }
}
