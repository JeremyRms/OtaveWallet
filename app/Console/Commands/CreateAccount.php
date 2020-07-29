<?php

namespace App\Console\Commands;

use App\Exceptions\CreateAccountException;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'octave:create-account {name}';

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
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        $this->comment('Hey! I have retrieved your name! => '. $name);
        $email = $this->ask('Please let us now on which email address we can contact you:');
        $password = $this->secret('Please protect your account with a password:');
        $password = bcrypt($password);

        try {
//            $this->validateEmail($email);
            $validator = Validator::make([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ], [
                'name' => ['required', 'min:3', 'max:50'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'min:8'],
            ]);
//            Validator::make($data, [
//                'name' => ['required', 'string', 'max:255'],
//                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
//                'password' => ['required', 'string', 'min:8', 'confirmed'],
//            ]);

            if ($validator->fails()) {
                $this->info('There is a problem:');

                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }
                return 1;
            }

            app('db')->beginTransaction();

            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = $password;
            $user->save();


//            User::create([
//                'name' => $name,
//                'email' => $email,
//                'password' => $password,
//            ]);




            $this->info("Created new user for email {$email}");

            app('db')->commit();
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            $this->error('The user was not created');

            app('db')->rollBack();
        }
        return 0;
    }

    /**
     * Determine if the given email address already exists.
     *
     * @param  string $email
     * @return boolean
     *
     * @throws CreateAccountException
     */
    private function validateEmail($email)
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw CreateAccountException::invalidEmail($email);
        }

        if (app(config('auth.providers.users.model'))->where('email', $email)->exists()) {
            throw CreateAccountException::emailExists($email);
        }
        return true;
    }
}
