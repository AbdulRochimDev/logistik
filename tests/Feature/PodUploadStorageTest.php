<?php

use App\Domain\Outbound\Models\Shipment;
use App\Models\User;
use App\Support\Storage\TemporaryUrlGenerator;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LogisticsDemoSeeder::class);
    $this->driverUser = User::where('email', 'driver@example.com')->firstOrFail();
    $this->shipment = Shipment::firstOrFail();

    Storage::fake('s3');
    config(['filesystems.default' => 's3', 'wms.storage.pod_disk' => 's3']);
});

it('stores PoD on configured disk with metadata and generates a temporary URL', function (): void {
    $response = $this->actingAs($this->driverUser, 'sanctum')
        ->withHeaders(['User-Agent' => 'DriverApp/2.0'])
        ->post('/api/driver/pod', [
            'shipment_id' => $this->shipment->id,
            'signer_name' => 'Receiver QA',
            'signed_at' => now()->toISOString(),
            'notes' => 'Arrived in good condition',
            'meta' => ['temperature' => 'ambient'],
            'device_id' => 'device-123',
            'photo' => UploadedFile::fake()->image('pod.png', 500, 500),
        ]);

    $response->assertCreated();

    $podData = $response->json('data.pod');
    expect($podData['photo_path'] ?? null)->not->toBeNull();

    Storage::disk('s3')->assertExists($podData['photo_path']);

    $pod = $this->shipment->fresh()->proofOfDelivery;
    expect($pod?->meta)->toMatchArray([
        'temperature' => 'ambient',
        'user_agent' => 'DriverApp/2.0',
        'device_id' => 'device-123',
    ]);

    $url = app(TemporaryUrlGenerator::class)->generate('s3', $podData['photo_path'], 120);
    expect($url)->not->toBeEmpty();
});

it('rejects PoD uploads with invalid mimetype or oversized files', function (): void {
    $this->actingAs($this->driverUser, 'sanctum')
        ->post('/api/driver/pod', [
            'shipment_id' => $this->shipment->id,
            'signer_name' => 'Receiver QA',
            'signed_at' => now()->toISOString(),
            'photo' => UploadedFile::fake()->create('pod.pdf', 100, 'application/pdf'),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['photo']);

    $this->actingAs($this->driverUser, 'sanctum')
        ->post('/api/driver/pod', [
            'shipment_id' => $this->shipment->id,
            'signer_name' => 'Receiver QA',
            'signed_at' => now()->toISOString(),
            'photo' => UploadedFile::fake()->image('pod-large.png')->size(6000),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['photo']);
});
