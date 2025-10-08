<?php

use App\Domain\Outbound\Models\Shipment;
use App\Models\User;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LogisticsDemoSeeder::class);
    $this->driverUser = User::where('email', 'driver@example.com')->firstOrFail();
    $this->shipment = Shipment::with('proofOfDelivery')->firstOrFail();

    Storage::fake('s3');
    config(['filesystems.default' => 's3', 'wms.storage.pod_disk' => 's3']);
});

it('handles PoD idempotency without duplicating files', function (): void {
    $payload = [
        'shipment_id' => $this->shipment->id,
        'signer_name' => 'Receiver QA',
        'signed_at' => now()->toISOString(),
        'notes' => 'All goods accepted',
    ];

    $response = $this->actingAs($this->driverUser, 'sanctum')
        ->withHeaders(['X-Idempotency-Key' => 'POD-KEY-123', 'User-Agent' => 'DriverApp/1.0'])
        ->post('/api/driver/pod', array_merge($payload, [
            'photo' => UploadedFile::fake()->image('pod.jpg', 600, 600),
        ]));

    $response->assertCreated();
    $response->assertHeader('Idempotency-Key', 'POD-KEY-123');
    $response->assertJsonPath('created', true);

    $files = Storage::disk('s3')->allFiles('pods/photos');
    expect($files)->toHaveCount(1);

    $podPath = $response->json('data.pod.photo_path');
    expect($podPath)->not->toBeNull();

    $replayResponse = $this->actingAs($this->driverUser, 'sanctum')
        ->withHeaders(['X-Idempotency-Key' => 'POD-KEY-123'])
        ->post('/api/driver/pod', array_merge($payload, [
            'photo' => UploadedFile::fake()->image('pod-retry.jpg', 600, 600),
        ]));

    $replayResponse->assertOk();
    $replayResponse->assertHeader('Idempotency-Key', 'POD-KEY-123');
    $replayResponse->assertJsonPath('created', false);
    $replayResponse->assertJsonPath('replayed', true);

    $filesAfterReplay = Storage::disk('s3')->allFiles('pods/photos');
    expect($filesAfterReplay)->toHaveCount(1);

    $this->shipment->refresh();
    expect($this->shipment->status)->toBe('delivered');
    expect($this->shipment->proofOfDelivery?->meta['idempotent_replay'] ?? false)->toBeTrue();
});
