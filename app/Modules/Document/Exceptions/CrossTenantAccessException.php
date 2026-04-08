<?php



namespace App\Modules\Document\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class CrossTenantAccessException extends Exception
{
    public function __construct(string $message = 'Cannot access resources across different tenants.')
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
            'error' => 'CROSS_TENANT_ACCESS',
            'message' => $this->getMessage(),
        ], 403);
    }
}
