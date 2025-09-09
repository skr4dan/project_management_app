<?php

namespace App\Services;

use App\Services\Contracts\AvatarFileServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AvatarFileService implements AvatarFileServiceInterface
{
    /**
     * Handle avatar upload for a user.
     * Deletes old avatar if exists and stores the new one.
     *
     * @return false|string The path where the avatar was stored
     */
    public function uploadAvatar(UploadedFile $avatarFile, ?string $currentAvatarPath = null): false|string
    {
        // Delete old avatar if exists
        if ($currentAvatarPath && Storage::disk('public')->exists($currentAvatarPath)) {
            Storage::disk('public')->delete($currentAvatarPath);
        }

        // Store the new avatar
        return $avatarFile->store('avatars', 'public');
    }

    /**
     * Delete an avatar file.
     */
    public function deleteAvatar(string $avatarPath): bool
    {
        if (Storage::disk('public')->exists($avatarPath)) {
            return Storage::disk('public')->delete($avatarPath);
        }

        return false;
    }
}
