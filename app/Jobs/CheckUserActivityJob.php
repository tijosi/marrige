<?php

namespace App\Jobs;

use AWS\CRT\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log as FacadesLog;

class CheckUserActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        $hasActivity = DB::table('access_log')->where( 'dt_access', '>=', now('America/Sao_Paulo')->subMinutes(5) )->exists();
        if (!$hasActivity) return;

        $this->scaleDownDynos();
    }

    private function scaleDownDynos() {
        $herokuApiKey   = 'HRKU-0ebc5eb7-5b9c-4a65-b4b8-0b0e70efe112';
        $herokuApp      = 'marrige-back';

        $request = Http::withToken($herokuApiKey);
        $request->withHeader('Accept', 'application/vnd.heroku+json; version=3');
        $request->patch(
            "https://api.heroku.com/apps/{$herokuApp}/formation/web",
            ['quantity' => 0]
        );

        FacadesLog::info('Dyno Destivado');
    }
}
