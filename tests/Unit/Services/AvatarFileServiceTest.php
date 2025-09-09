<?php

namespace Tests\Unit\Services;

use App\Services\AvatarFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvatarFileServiceTest extends TestCase
{
    private AvatarFileService $fileService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileService = new AvatarFileService;

        // Mock the Storage facade
        Storage::shouldReceive('disk')
            ->with('public')
            ->andReturnSelf();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function it_can_upload_avatar_without_existing_file()
    {
        // Arrange
        $avatarFile = $this->createMock(UploadedFile::class);
        $expectedPath = 'avatars/test-avatar.jpg';

        // Mock Storage - exists should not be called when currentAvatarPath is null
        Storage::shouldReceive('exists')
            ->never(); // Should not be called since currentAvatarPath is null

        Storage::shouldReceive('delete')
            ->never(); // Should not be called since no existing file

        $avatarFile->expects($this->once())
            ->method('store')
            ->with('avatars', 'public')
            ->willReturn($expectedPath);

        // Act
        $result = $this->fileService->uploadAvatar($avatarFile, null);

        // Assert
        $this->assertEquals($expectedPath, $result);
    }

    #[Test]
    public function it_can_upload_avatar_and_delete_existing_file()
    {
        // Arrange
        $avatarFile = $this->createMock(UploadedFile::class);
        $currentAvatarPath = 'avatars/old-avatar.jpg';
        $newAvatarPath = 'avatars/new-avatar.jpg';

        // Mock Storage for existing file scenario
        Storage::shouldReceive('exists')
            ->once()
            ->with($currentAvatarPath)
            ->andReturn(true);

        Storage::shouldReceive('delete')
            ->once()
            ->with($currentAvatarPath)
            ->andReturn(true);

        $avatarFile->expects($this->once())
            ->method('store')
            ->with('avatars', 'public')
            ->willReturn($newAvatarPath);

        // Act
        $result = $this->fileService->uploadAvatar($avatarFile, $currentAvatarPath);

        // Assert
        $this->assertEquals($newAvatarPath, $result);
    }

    #[Test]
    public function it_can_upload_avatar_when_existing_file_does_not_exist()
    {
        // Arrange
        $avatarFile = $this->createMock(UploadedFile::class);
        $currentAvatarPath = 'avatars/nonexistent-avatar.jpg';
        $newAvatarPath = 'avatars/new-avatar.jpg';

        // Mock Storage for existing file not found scenario
        Storage::shouldReceive('exists')
            ->once()
            ->with($currentAvatarPath)
            ->andReturn(false);

        Storage::shouldReceive('delete')
            ->never(); // Should not be called since file doesn't exist

        $avatarFile->expects($this->once())
            ->method('store')
            ->with('avatars', 'public')
            ->willReturn($newAvatarPath);

        // Act
        $result = $this->fileService->uploadAvatar($avatarFile, $currentAvatarPath);

        // Assert
        $this->assertEquals($newAvatarPath, $result);
    }

    #[Test]
    public function it_can_delete_existing_avatar_file()
    {
        // Arrange
        $avatarPath = 'avatars/test-avatar.jpg';

        // Mock Storage for successful deletion
        Storage::shouldReceive('exists')
            ->once()
            ->with($avatarPath)
            ->andReturn(true);

        Storage::shouldReceive('delete')
            ->once()
            ->with($avatarPath)
            ->andReturn(true);

        // Act
        $result = $this->fileService->deleteAvatar($avatarPath);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_deleting_nonexistent_avatar_file()
    {
        // Arrange
        $avatarPath = 'avatars/nonexistent-avatar.jpg';

        // Mock Storage for file not found
        Storage::shouldReceive('exists')
            ->once()
            ->with($avatarPath)
            ->andReturn(false);

        Storage::shouldReceive('delete')
            ->never(); // Should not be called since file doesn't exist

        // Act
        $result = $this->fileService->deleteAvatar($avatarPath);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_handles_delete_failure_gracefully()
    {
        // Arrange
        $avatarPath = 'avatars/test-avatar.jpg';

        // Mock Storage for deletion failure
        Storage::shouldReceive('exists')
            ->once()
            ->with($avatarPath)
            ->andReturn(true);

        Storage::shouldReceive('delete')
            ->once()
            ->with($avatarPath)
            ->andReturn(false); // Deletion fails

        // Act
        $result = $this->fileService->deleteAvatar($avatarPath);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_handles_upload_failure_gracefully()
    {
        // Arrange
        $avatarFile = $this->createMock(UploadedFile::class);
        $currentAvatarPath = 'avatars/old-avatar.jpg';

        // Mock Storage for existing file
        Storage::shouldReceive('exists')
            ->once()
            ->with($currentAvatarPath)
            ->andReturn(true);

        Storage::shouldReceive('delete')
            ->once()
            ->with($currentAvatarPath)
            ->andReturn(true);

        // Mock upload failure
        $avatarFile->expects($this->once())
            ->method('store')
            ->with('avatars', 'public')
            ->willThrowException(new \Exception('Upload failed'));

        // Assert that exception is propagated
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Upload failed');

        // Act
        $this->fileService->uploadAvatar($avatarFile, $currentAvatarPath);
    }

    #[Test]
    public function it_handles_storage_exists_failure_gracefully()
    {
        // Arrange
        $avatarFile = $this->createMock(UploadedFile::class);
        $currentAvatarPath = 'avatars/old-avatar.jpg';

        // Mock Storage exists failure
        Storage::shouldReceive('exists')
            ->once()
            ->with($currentAvatarPath)
            ->andThrow(new \Exception('Storage check failed'));

        // Assert that exception is propagated
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Storage check failed');

        // Act
        $this->fileService->uploadAvatar($avatarFile, $currentAvatarPath);
    }

    #[Test]
    public function it_handles_storage_delete_failure_gracefully()
    {
        // Arrange
        $avatarFile = $this->createMock(UploadedFile::class);
        $currentAvatarPath = 'avatars/old-avatar.jpg';

        // Mock Storage for delete failure
        Storage::shouldReceive('exists')
            ->once()
            ->with($currentAvatarPath)
            ->andReturn(true);

        Storage::shouldReceive('delete')
            ->once()
            ->with($currentAvatarPath)
            ->andThrow(new \Exception('Delete failed'));

        // Assert that exception is propagated
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Delete failed');

        // Act
        $this->fileService->uploadAvatar($avatarFile, $currentAvatarPath);
    }

    #[Test]
    public function it_handles_storage_delete_check_failure_gracefully()
    {
        // Arrange
        $avatarPath = 'avatars/test-avatar.jpg';

        // Mock Storage exists failure
        Storage::shouldReceive('exists')
            ->once()
            ->with($avatarPath)
            ->andThrow(new \Exception('Storage check failed'));

        // Assert that exception is propagated
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Storage check failed');

        // Act
        $this->fileService->deleteAvatar($avatarPath);
    }
}
