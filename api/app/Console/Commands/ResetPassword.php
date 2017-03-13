<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Manager;

class ResetPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:password';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重置超级管理员密码';
    
    protected $controller = null;

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
     * @return mixed
     */
    public function handle()
    {
        $password = $this->ask("input the new password ");    
        $manager = Manager::first();
        $password = bcrypt($password);
        $result = $manager->update(['password'=>$password]);
        if($result)
            $this->info('success');
        else
            $this->error('error');
    }
}
