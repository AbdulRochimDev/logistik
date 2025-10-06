<?php

namespace App\Http\Controllers\Admin\Inbound;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inbound\GrnPostRequest;
use App\Http\Requests\Admin\Inbound\GrnStoreRequest;
use App\Http\Requests\Admin\Inbound\GrnUpdateRequest;
use App\Http\Resources\Admin\Inbound\GrnCollection;
use App\Http\Resources\Admin\Inbound\GrnResource;
use App\Models\Admin\Inbound\Grn;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GrnController extends Controller
{
    public function index(Request $request): GrnCollection
    {
        $grns = Grn::all();

        return new GrnCollection($grns);
    }

    public function store(GrnStoreRequest $request): Response
    {
        
    }

    public function show(Request $request, Grn $grn): GrnResource
    {
        return new GrnResource($grn);
    }

    public function update(GrnUpdateRequest $request, Grn $grn): GrnResource
    {
        $grn->update($request->validated());

        return new GrnResource($grn);
    }

    public function destroy(Request $request, Grn $grn): Response
    {
        $grn->delete();

        return response()->noContent();
    }

    public function post(GrnPostRequest $request): Response
    {
        
    }
}
