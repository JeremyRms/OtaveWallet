<?php

namespace App\Console\Commands;

use App\User;
use App\UserWithWallet;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\SessionGuard;
use Illuminate\Console\Command;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Interfaces\Wallet;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use function MongoDB\BSON\toJSON;

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
            $userKey = $this->call('octave:register', [
                'name' => $name,
            ]);
        } else {
            $userKey = $this->call('octave:login', [
                'name' => $name,
            ]);
        }

        if ($userKey === 0) return 1;

        $service = $this->serviceChoice();

        while ($service != 'exit') {
            $userKey = $this->call('octave:transactions', [
                'user-key' => $userKey,
            ]);

            $service = $this->serviceChoice();
        }

        return 0;
    }

    /**
     * @return array|string
     */
    public function serviceChoice()
    {
        return $this->choice(
            'What service are you looking for?',
            ['Transactions', 'exit'],
            0,
            $maxAttempts = 2,
            $allowMultipleSelections = false
        );
    }
}
