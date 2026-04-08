<?php



namespace App\Modules\Document\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class UnauthorizedVisibilityException extends Exception
{
    public function __construct(string $message = 'You do not have permission to set this document visibility.')
    {
        parent::__construct($message, 403);
    }

    public function report(): bool
    {
        return false;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'UNAUTHORIZED_VISIBILITY',
            'message' => $this->getMessage(),
        ], 403);
    }
}
