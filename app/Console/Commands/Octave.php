<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Interfaces\Wallet;

class Octave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'octave:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Basic introduction to the product';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Welcome to Octave Wallet!');

        $name = $this->choice(
            'What is your name?',
            ['Manish', 'Jeremy', 'Ying', 'Octave', 'Godzilla'],
            0,
            $maxAttempts = 2,
            $allowMultipleSelections = false
        );

        $this->info('Hi ' . $name . '!');

        $hasAccount = $this->confirm('Do you have an account?');

        if (!$hasAccount) {
            $this->call('octave:register', [
                'name' => $name,
            ]);
        }
        return 0;
    }
}
