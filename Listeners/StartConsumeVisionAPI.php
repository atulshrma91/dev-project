<?php

namespace App\Listeners;

use App\VisionAPIResult;
use App\Jobs\ConsumeVisionAPI;
use App\Events\VideoScreenshotCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class StartConsumeVisionAPI
{
    /**
     * Handle the event.
     *
     * @param  VideoScreenshotCreated  $event
     * @return void
     */
    public function handle(VideoScreenshotCreated $event)
    {
        $tag = new VisionAPIResult();
        $tag->status = VisionAPIResult::STATUS_NEW;
        $tag->from_video = true;

        $tag->video()->associate($event->getVideo());
        $tag->setOutputPath($event->getOutputPath());
        $tag->setUrl();
        $tag->save();

        dispatch(new ConsumeVisionAPI($tag));
    }
}
