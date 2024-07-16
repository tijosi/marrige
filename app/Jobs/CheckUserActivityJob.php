<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CheckUserActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        $dt = now('America/Sao_Paulo')->subMinutes(10);
        $hasActivity = DB::table('access_log')->where( 'dt_access', '>=', $dt )->exists();
        if ($hasActivity) return;

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
    }
}
