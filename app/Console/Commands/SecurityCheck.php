<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SensioLabs\Security\SecurityChecker;

/**
 * @codeCoverageIgnore
 */
class SecurityCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the SensioLab’s Security Check tool';

    /**
     * The Security Checker intergation service.
     *
     * @var SecurityChecker
     */
    protected $securityChecker;

    /**
     * Create a new command instance.
     *
     * @param  SecurityChecker $SecurityChecker
     * @return void
     */
    public function __construct(SecurityChecker $securityChecker)
    {
        parent::__construct();

        $this->securityChecker = $securityChecker;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $alerts = $this->securityChecker->check(base_path() . '/composer.lock');
        if (count($alerts) === 0) {
            $this->info('No security vulnerabilities found.');

            return 0;
        }
        $this->error('vulnerabilities found');

        return 1;
    }
}
