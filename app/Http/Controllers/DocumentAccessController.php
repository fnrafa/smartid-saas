<?php

namespace App\Http\Controllers;

use App\Modules\Document\Enums\AccessLevel;
use App\Modules\Document\Models\DocumentAccess;
use App\Modules\Document\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentAccessController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService
    ) {}

    public function update(Request $request, DocumentAccess $access): JsonResponse
    {
        $user = auth()->user();
        
        // Check authorization
        if (!Gate::allows('share', $access->document)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'access_level' => 'required|string|in:read,edit,full',
        ]);

        $access->update([
            'access_level' => AccessLevel::from($validated['access_level']),
            'granted_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Access updated successfully',
            'access' => $access->fresh(),
        ]);
    }

    public function destroy(DocumentAccess $access): JsonResponse
    {
        $user = auth()->user();
        
        // Check authorization
        if (!Gate::allows('share', $access->document)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $targetUser = $access->user;
        $this->documentService->revokeAccess($access->document, $targetUser);

        return response()->json([
            'message' => 'Access removed successfully',
        ]);
    }
}
