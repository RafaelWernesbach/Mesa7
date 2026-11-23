<?php

use App\Support\PublicUploadPreview;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;

it('uses a same-origin path to preview files on the local public disk', function () {
    config(['app.url' => 'http://localhost:8000']);
    Storage::fake('public', ['url' => 'http://localhost:8000/storage']);
    Storage::disk('public')->put('restaurants/1/logo/test.jpg', 'example');
    $field = FileUpload::make('logo_path')->disk('public')->visibility('public');

    $information = PublicUploadPreview::fileInformation($field, 'restaurants/1/logo/test.jpg', null);

    expect($information['url'])->toBe('/storage/restaurants/1/logo/test.jpg');
});

it('preserves a CDN URL even when the public disk uses the local driver', function () {
    config(['app.url' => 'http://localhost:8000']);
    Storage::fake('public', ['url' => 'https://cdn.example.com/storage']);
    Storage::disk('public')->put('products/test.jpg', 'example');
    $field = FileUpload::make('image_path')->disk('public')->visibility('public');

    $information = PublicUploadPreview::fileInformation($field, 'products/test.jpg', null);

    expect($information['url'])->toBe('https://cdn.example.com/storage/products/test.jpg');
});

it('preserves remote disk preview URLs', function () {
    Storage::fake('public', ['url' => 'https://cdn.example.com/storage']);
    Storage::disk('public')->put('products/test.jpg', 'example');
    config(['filesystems.disks.public.driver' => 's3']);
    $field = FileUpload::make('image_path')->disk('public')->visibility('public');

    $information = PublicUploadPreview::fileInformation($field, 'products/test.jpg', null);

    expect($information['url'])->toBe('https://cdn.example.com/storage/products/test.jpg');
});
