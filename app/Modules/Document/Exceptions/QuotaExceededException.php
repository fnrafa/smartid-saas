<?php



namespace App\Modules\Document\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class QuotaExceededException extends Exception
{
    public function __construct(string $message = 'Document quota exceeded for your subscription tier.')
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
            'error' => 'QUOTA_EXCEEDED',
            'message' => $this->getMessage(),
        ], 403);
    }
}
