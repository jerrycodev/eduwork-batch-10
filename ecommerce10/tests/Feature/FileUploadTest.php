<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->create());
});

test('an image can be uploaded', function () {
    $response = $this->postJson(route('file-uploads.store'), [
        'file' => UploadedFile::fake()->image('workspace.png', 800, 600),
    ]);

    $response->assertOk()
        ->assertJsonStructure(['path', 'url', 'name', 'size', 'extension'])
        ->assertJson([
            'name' => 'workspace.png',
            'extension' => 'png',
        ]);

    expect($response->json('url'))->toStartWith('/storage/uploads/');

    Storage::disk('public')->assertExists($response->json('path'));
});

test('an uploaded image can be deleted', function () {
    Storage::disk('public')->put('uploads/workspace.png', 'image');

    $this->deleteJson(route('file-uploads.destroy'), [
        'path' => 'uploads/workspace.png',
    ])->assertOk();

    Storage::disk('public')->assertMissing('uploads/workspace.png');
});

test('a file outside the uploads directory cannot be deleted', function () {
    $this->deleteJson(route('file-uploads.destroy'), [
        'path' => '../.env',
    ])->assertUnprocessable();
});
