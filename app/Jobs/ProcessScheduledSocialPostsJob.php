<?php

namespace App\Jobs;

use App\Models\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessScheduledSocialPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        SocialPost::query()
            ->where('status', 'scheduled')
            ->where('scheduled_for', '<=', now())
            ->with(['targets' => fn ($q) => $q->where('status', 'scheduled')])
            ->get()
            ->each(function (SocialPost $post) {
                foreach ($post->targets as $target) {
                    PublishSocialPostJob::dispatch($target->id);
                }
            });
    }
}
