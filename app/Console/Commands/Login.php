<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\SessionGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class Login extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'octave:login {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Helps to login to an OctaveWallet account';

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
        $name = $this->argument('name');
        $email = $this->ask('Please enter your account\'s email address');
        $password = $this->secret('Please enter your password');

        try {
            $userData = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ];

            $validator = $this->validator($userData);
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }
                throw new \Exception('There was a problem!');
            }

            $guard = new SessionGuard(
                'test',
                new EloquentUserProvider(app('hash'), User::class),
                app('session.store')
            );

            $validUser = $guard->once($userData);
            if (!$validUser) {
                throw new \Exception('We cannot retrieve your account.');
            }

            $this->info("We retrieved your account!");

            return $guard->id();
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            $this->error('We cannot log you in. Please try again.');
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
            'email' => 'required|string',
            'password' => 'required|string',
        ]);
    }
}
