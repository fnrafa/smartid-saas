<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Modules\Tenant\Providers\TenantServiceProvider::class,
    App\Modules\Document\Providers\DocumentServiceProvider::class,
];
