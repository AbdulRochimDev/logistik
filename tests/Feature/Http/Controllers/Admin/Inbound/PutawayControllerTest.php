<?php

namespace Tests\Feature\Http\Controllers\Admin\Inbound;

use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Inbound\PutawayController
 */
final class PutawayControllerTest extends TestCase
{
    use AdditionalAssertions, WithFaker;

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Admin\Inbound\PutawayController::class,
            'store',
            \App\Http\Requests\Admin\Inbound\PutawayControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_behaves_as_expected(): void
    {
        $response = $this->post(route('putaways.store'));
    }
}
