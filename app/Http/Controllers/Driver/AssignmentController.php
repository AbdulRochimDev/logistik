<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\AssignmentStoreRequest;
use App\Http\Requests\Driver\AssignmentUpdateRequest;
use App\Http\Resources\Driver\AssignmentCollection;
use App\Http\Resources\Driver\AssignmentResource;
use App\Models\DriverAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AssignmentController extends Controller
{
    public function index(Request $request): AssignmentCollection
    {
        $assignments = Assignment::all();

        return new AssignmentCollection($assignments);
    }

    public function store(AssignmentStoreRequest $request): AssignmentResource
    {
        $assignment = Assignment::create($request->validated());

        return new AssignmentResource($assignment);
    }

    public function show(Request $request, Assignment $assignment): AssignmentResource
    {
        return new AssignmentResource($assignment);
    }

    public function update(AssignmentUpdateRequest $request, Assignment $assignment): Response
    {
        
    }

    public function destroy(Request $request, Assignment $assignment): Response
    {
        $assignment->delete();

        return response()->noContent();
    }
}
