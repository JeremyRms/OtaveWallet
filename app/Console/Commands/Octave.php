<?php

namespace App\Console\Commands;

use App\User;
use App\UserWithWallet;
use Dompdf\Exception;
use Illuminate\Console\Command;
use Stripe\Charge;

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

        //////////////////////////////
        try {
            $user = User::findOrFail(1);
            $user->createOrGetStripeCustomer();
            $stripe = new \Stripe\StripeClient(
                'sk_test_ZFlQBeF9Kx3di1HtuX2DuX4s'
            );
//            $amount = $this->ask('What amount do you want to transfer to your wallet? (USD)');
//            $cardNumber = $this->choice('What is your card number?', ['4242424242424242']);
//            $cardExpirationMonth = $this->choice('What is the card expiration month?', ['7']);
//            $cardExpirationYear = $this->choice('What is the card expiration year?', ['2021']);
//            $cardCVC = $this->choice('What is the card CVC?', ['314']);

            $amount = 10;
            $amount *= 100; // in cents
            $cardNumber = '4242424242424242';
            $cardExpirationMonth = '7';
            $cardExpirationYear = '2021';
            $cardCVC = '314';

            $creditCard = $stripe->paymentMethods->create([
                'type' => 'card',
                'card' => [
                    'number' => $cardNumber,
                    'exp_month' => $cardExpirationMonth,
                    'exp_year' => $cardExpirationYear,
                    'cvc' => $cardCVC,
                ],
            ]);

            // User payment methods
            $user->addPaymentMethod($creditCard);
            $user->updateDefaultPaymentMethod($creditCard);

            $payment = $user->charge($amount, $creditCard->id);
            $paymentIntent = $payment->asStripePaymentIntent();
            /** @var Charge $charge */
            // it's a one off payment, so only 1 charge element data
            $charge = $paymentIntent->charges->data[0];
            if (!$charge instanceof Charge) {
                throw new Exception('Invalid Charge data!');
            }
            $receiptUrl = $charge->receipt_url;
            $chargeStatus = $charge->status;
//            $balanceTransaction = $charge->balance_transaction;
//            $transactionId = $balanceTransaction->id;
//            $transaction = $stripe->balanceTransactions->retrieve($transactionId);
//            $transactionStatus = $transaction->status;
            $this->info('Payment status: '. $chargeStatus);
            $this->info('Payment receipt: '. $receiptUrl);
//            $applicationFeeAmount = $charge->application_fee_amount;
//            $applicationFee = $charge->application_fee;
//            $this->info('Fee: '. $applicationFee);
//            $this->info('Fee amount: '. $applicationFeeAmount);
            if ($payment->isSucceeded()) {
                // TODO: use a currency library to check the decimals. Here we are in USD, so x100
                $user->deposit(
                    $amount,
                    [
                        'paymentProvider' => 'Stripe',
                        'type' => $paymentIntent->payment_method,
                        'currency' => $paymentIntent->currency,
                        'amount' => $paymentIntent->amount/100,
                    ]
                );
            }
            $userKey = $this->call('octave:transactions', [
                'user-key' => $user->getKey(),
            ]);
            $this->info('Your new balance is: '. $user->balance);
            dd();
            return $userKey;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }

        //////////////////////////////

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
            ['Transactions', 'Add money to your wallet', 'Auto-refill', 'exit'],
            0,
            $maxAttempts = 2,
            $allowMultipleSelections = false
        );
    }
}
