<?php

namespace App\Services\Social\Contracts;

use App\Models\SocialPost;
use App\Models\SocialPostTarget;

interface SocialPlatformPublisher
{
    /**
     * Publish a post to the platform immediately.
     * Returns the external post/media ID on success.
     * Throws RuntimeException on API failure.
     */
    public function publish(SocialPost $post, SocialPostTarget $target): string;

    /**
     * Update a previously published platform post.
     * Throws RuntimeException when the platform or connector does not support it.
     */
    public function updatePublishedPost(SocialPost $post, SocialPostTarget $target): void;

    /**
     * Delete a previously published platform post.
     * Throws RuntimeException when the platform or connector does not support it.
     */
    public function deletePublishedPost(SocialPostTarget $target): void;

    /**
     * Publish a reply to a specific platform comment.
     * Returns the platform ID of the newly created reply comment.
     * Throws RuntimeException on API failure or if the platform does not support replies.
     */
    public function publishCommentReply(string $platformCommentId, string $message): string;
}
