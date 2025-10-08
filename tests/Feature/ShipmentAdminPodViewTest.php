<?php

use App\Domain\Outbound\Models\Shipment;
use App\Models\User;
use Database\Seeders\LogisticsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LogisticsDemoSeeder::class);
    $this->adminUser = User::where('email', 'admin@example.com')->firstOrFail();
    $this->shipment = Shipment::with('proofOfDelivery')->firstOrFail();

    Storage::fake('s3');
    config(['filesystems.default' => 's3', 'wms.storage.pod_disk' => 's3']);

    Storage::disk('s3')->put('pods/photos/demo-proof.jpg', 'fake-image');

    $this->shipment->proofOfDelivery()->updateOrCreate(
        ['shipment_id' => $this->shipment->id],
        [
            'signed_by' => 'Receiver QA',
            'signed_at' => now(),
            'photo_path' => 'pods/photos/demo-proof.jpg',
            'meta' => ['idempotent_replay' => true],
            'external_idempotency_key' => 'POD-ADMIN-1',
        ]
    );

    $this->shipment->update(['status' => 'delivered']);
});

it('renders PoD details and replay badge on the shipment admin view', function (): void {
    $response = $this->actingAs($this->adminUser)
        ->get(route('admin.shipments.show', $this->shipment));

    $response->assertOk();
    $response->assertSee('Ditandatangani oleh Receiver QA');
    $response->assertSee('Lihat bukti');
    $response->assertSee('Idempotent replay');
});
