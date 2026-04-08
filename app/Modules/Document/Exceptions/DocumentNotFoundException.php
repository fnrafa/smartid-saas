<?php



namespace App\Modules\Document\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class DocumentNotFoundException extends Exception
{
    public function __construct(string $message = 'Document not found.')
    {
        parent::__construct($message, 404);
    }

    public function report(): bool
    {
        return false;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'DOCUMENT_NOT_FOUND',
            'message' => $this->getMessage(),
        ], 404);
    }
}
