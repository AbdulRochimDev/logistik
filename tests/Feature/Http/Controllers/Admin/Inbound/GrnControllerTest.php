<?php

namespace Tests\Feature\Http\Controllers\Admin\Inbound;

use App\Models\Grn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Inbound\GrnController
 */
final class GrnControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $grns = Grn::factory()->count(3)->create();

        $response = $this->get(route('grns.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Admin\Inbound\GrnController::class,
            'store',
            \App\Http\Requests\Admin\Inbound\GrnControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_behaves_as_expected(): void
    {
        $response = $this->post(route('grns.store'));
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $grn = Grn::factory()->create();

        $response = $this->get(route('grns.show', $grn));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Admin\Inbound\GrnController::class,
            'update',
            \App\Http\Requests\Admin\Inbound\GrnControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $grn = Grn::factory()->create();

        $response = $this->put(route('grns.update', $grn));

        $grn->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $grn = Grn::factory()->create();

        $response = $this->delete(route('grns.destroy', $grn));

        $response->assertNoContent();

        $this->assertModelMissing($grn);
    }


    #[Test]
    public function post_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Admin\Inbound\GrnController::class,
            'post',
            \App\Http\Requests\Admin\Inbound\GrnControllerPostRequest::class
        );
    }

    #[Test]
    public function post_behaves_as_expected(): void
    {
        $response = $this->get(route('grns.post'));
    }
}
