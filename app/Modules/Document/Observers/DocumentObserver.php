<?php



namespace App\Modules\Document\Observers;

use App\Models\AuditLog;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Models\DocumentVersion;
use Illuminate\Support\Facades\Request;

class DocumentObserver
{
    public function created(Document $document): void
    {
        $this->logAudit($document, 'created', [], $document->getAttributes());
    }

    public function updated(Document $document): void
    {
        $this->createVersion($document);
        $this->logAudit($document, 'updated', $document->getOriginal(), $document->getChanges());
    }

    public function deleted(Document $document): void
    {
        $this->logAudit($document, 'deleted', $document->getOriginal(), []);
    }

    private function createVersion(Document $document): void
    {
        if (!$document->wasChanged(['title', 'content', 'category'])) {
            return;
        }

        $latestVersion = DocumentVersion::where('document_id', $document->id)
            ->orderBy('version_number', 'desc')
            ->first();

        $nextVersionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

        DocumentVersion::create([
            'document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
            'user_id' => $document->user_id,
            'version_number' => $nextVersionNumber,
            'title' => $document->getOriginal('title'),
            'content' => $document->getOriginal('content'),
            'category' => $document->getOriginal('category'),
            'created_at' => now(),
        ]);
    }

    private function logAudit(Document $document, string $event, array $oldValues, array $newValues): void
    {
        AuditLog::create([
            'tenant_id' => $document->tenant_id,
            'user_id' => auth()->id() ?? $document->user_id,
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'event' => $event,
            'old_values' => $this->sanitizeValues($oldValues),
            'new_values' => $this->sanitizeValues($newValues),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    private function sanitizeValues(array $values): array
    {
        $sanitized = $values;

        if (isset($sanitized['content'])) {
            $sanitized['content'] = '[ENCRYPTED]';
        }

        unset($sanitized['password'], $sanitized['remember_token']);

        return $sanitized;
    }
}
