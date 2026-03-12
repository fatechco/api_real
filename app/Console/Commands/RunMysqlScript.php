<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use Throwable;

class RunMysqlScript extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
    public function handle(): int
	{
        try {
		    DB::unprepared(file_get_contents('dropdb.sql'));
		    sleep(1);
		    DB::unprepared(file_get_contents('demand24.sql'));

            auth()->attempt(['email' => 'owner@githubit.com', 'password' => 'githubit']);

            $token = auth()->user()->createToken('api_token')->plainTextToken;

            if ((int)explode('|', $token)[0] == 0) {
                $this->handle();
            }

            exec('php artisan optimize:clear');

        } catch (Throwable $e) {
            dd(\Str::limit($e->getMessage(), 1000));
            \Log::error('mysql:backup', [$e->getMessage(), $e->getFile(), $e->getLine(), $e->getCode()]);
        }

        return 0;
    }
}
