<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class Register extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'octave:register {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Helps to create an OctaveWallet account';

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
     * @return User
     */
    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->ask('Please let us now on which email address we can contact you:');
        $password = $this->secret('Please protect your account with a password:');
        $passwordConfirmation = $this->secret('Please confirm your password:');

        try {
            $userData = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation
            ];

            $validator = $this->validator($userData);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }
                throw new \Exception('There was a problem!');
            }

            app('db')->beginTransaction();

            $user = $this->create($userData);

            $this->info("Created new userData for email {$email}");

            app('db')->commit();

            return $user->getKey();
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            $this->error('The userData was not created!');

            app('db')->rollBack();
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
