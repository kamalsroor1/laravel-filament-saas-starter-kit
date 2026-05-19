<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Core\Tenancy\TenancyManager;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('tenancy.resolve-domain')->get('/whoami', function () {
    /** @var TenancyManager $tenancyManager */
    $tenancyManager = app(TenancyManager::class);

    return response()->json([
        'tenant_id' => $tenancyManager->currentId(),
        'project_identifier' => request()->attributes->get('project_identifier'),
        'resolved_domain' => request()->attributes->get('resolved_domain'),
        'is_custom_domain' => request()->attributes->get('is_custom_domain'),
    ]);
});
