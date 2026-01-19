<?php

namespace Tigusigalpa\FileOutput;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Tigusigalpa\FileOutput\Http\Controllers\FileDownloadController;

class FileOutputServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-fileoutput');
        
        $this->registerRoutes();
    }

    public function register(): void
    {
        //
    }

    protected function registerRoutes(): void
    {
        Route::get('/filament-fileoutput/download', [FileDownloadController::class, 'download'])
            ->name('filament-fileoutput.download')
            ->middleware(['web']);
    }
}
