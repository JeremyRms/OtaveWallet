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


        ///////////
        $userData = [
            'name' => 'Jeremy',
            'email' => 'jeremy@gmail.com',
            'password' => 'testtest',
        ];

        $guard = new SessionGuard(
            'test',
            new EloquentUserProvider(app('hash'), User::class),
            app('session.store')
        );

        $validUser = $guard->once($userData);
        $user = $guard->user();
//        $user->hasWallet('my-wallet');
//        $wallet = $user->createWallet([
//            'name' => 'New Wallet',
//            'slug' => 'my-wallet',
//        ]);
//        $user->deposit(10000);
//        $user->withdraw(1);
//        $user->forceWithdraw(200, ['description' => 'payment of taxes']);
//        var_dump($user->balance);
        /** @var MorphMany $transactions */
        $transactions = $user->transactions();
        $collection = $transactions->get();
        $arrayCollection = $collection->map(function (Transaction $transaction) {
            /** @var Carbon $updatedAt */
            $updatedAt = $transaction->getAttribute('created_at');
            $W3CDate = $updatedAt->ceilMinute()->toCookieString();
            $transactionCollection = new Collection($transaction->toArray());
            $transactionCollection->forget([
                'payable_type',
                'payable_id',
                'wallet_id',
                'uuid',
                'updated_at'
            ]);
            $transactionCollection['meta'] = (new Collection($transactionCollection['meta']))->toJson();
            $transactionCollection['created_at'] = $W3CDate;
            return $transactionCollection->all();
        });

        $this->table(
            [
                'id',
                'type',
                'amount',
                'confirmed',
                'addendum',
                'created_at',
            ],
            $arrayCollection->toArray()
        );




        exit;


        /// ///////////////

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


        return 0;
    }
}
