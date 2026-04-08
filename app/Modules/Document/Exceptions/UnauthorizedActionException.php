<?php

namespace App\Modules\Document\Exceptions;

use Exception;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UnauthorizedActionException extends Exception
{
    public function __construct(string $message = 'You are not authorized to perform this action.')
    {
        parent::__construct($message, 403);
    }

    public function report(): bool
    {
        return false;
    }

    public function render(Request $request): JsonResponse|RedirectResponse|Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'UNAUTHORIZED_ACTION',
                'message' => $this->getMessage(),
            ], 403);
        }

        if ($request->is('admin/*')) {
            Notification::make()
                ->danger()
                ->title('Akses Ditolak')
                ->body($this->getMessage())
                ->persistent()
                ->send();

            return redirect()
                ->to('/admin/document-resource/documents')
                ->with('error', $this->getMessage());
        }

        abort(403, $this->getMessage());
    }
}
