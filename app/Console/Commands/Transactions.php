<?php

namespace App\Console\Commands;

use App\User;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class Transactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'octave:transactions {user-key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists transactions from an user';

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
     * @return integer
     */
    public function handle()
    {
        $userKey = $this->argument('user-key');

        try {
            $user = User::findOrFail($userKey);
            if (!$user->hasWallet('my-wallet')) {
                throw new \Exception('Sorry mate, you need to create a wallet first.');
            }

            /** @var MorphMany $transactions */
            $transactions = $user->transactions();
            $collection = $transactions->get(['id', 'type', 'amount', 'confirmed', 'meta', 'created_at']);
            $arrayCollection = $collection->map(function (Transaction $transaction) {
                /** @var Carbon $createdAt */
                $createdAt = $transaction->getAttribute('created_at');
                $W3CDate = $createdAt->ceilMinute()->toCookieString();
                $transactionArray = $transaction->toArray();
                $transactionArray['meta'] = (new Collection($transactionArray['meta']))->toJson();
                $transactionArray['created_at'] = $W3CDate;
                return $transactionArray;
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
            return $userKey;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return 0;
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'min:3', 'max:50'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}
