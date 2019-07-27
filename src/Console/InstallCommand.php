<?php

namespace Fygarciaj\Passport\Console;

use Illuminate\Console\Command;
use Fygarciaj\Passport\Passport;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:install
                            {--conn= : Connection for generate keys}
                            {--force : Overwrite keys they already exist}
                            {--length=4096 : The length of the private key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the commands necessary to prepare Passport for use';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->hasOption('conn')){
            $conn = config('database.default');
        }
        else {
            $conn = $this->option('conn');
        }

        Passport::setConnection($conn);

        $this->call('passport:keys', ['--force' => $this->option('force'), '--length' => $this->option('length')]);
        $this->call('passport:client', ['--personal' => true, '--name' => config('app.name').' Personal Access Client', '--conn' => $conn]);
        $this->call('passport:client', ['--password' => true, '--name' => config('app.name').' Password Grant Client', '--conn' => $conn]);
    }
}
