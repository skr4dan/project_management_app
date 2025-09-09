<?php

namespace App\Services\Contracts;

use Illuminate\Http\UploadedFile;

interface AvatarFileServiceInterface
{
    /**
     * Handle avatar upload for a user.
     * Deletes old avatar if exists and stores the new one.
     *
     * @return string The path where the avatar was stored
     */
    public function uploadAvatar(UploadedFile $avatarFile, ?string $currentAvatarPath = null): false|string;

    /**
     * Delete an avatar file.
     */
    public function deleteAvatar(string $avatarPath): bool;
}
