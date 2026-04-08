<?php



namespace App\Modules\Document\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

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

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'UNAUTHORIZED_ACTION',
            'message' => $this->getMessage(),
        ], 403);
    }
}
