<?php



namespace App\Modules\Document\Providers;

use App\Modules\Document\Models\Document;
use App\Modules\Document\Observers\DocumentObserver;
use App\Modules\Document\Policies\DocumentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Document::observe(DocumentObserver::class);
        Gate::policy(Document::class, DocumentPolicy::class);
    }
}
