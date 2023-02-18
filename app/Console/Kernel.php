<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<int, string>
     */
    protected $commands = [
        Commands\ParseCachedWebMentions::class,
        Commands\ReDownloadWebMentions::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @codeCoverageIgnore
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('cache:prune-stale-tags')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
