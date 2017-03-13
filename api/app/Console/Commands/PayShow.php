<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Pay\PayController;

class PayShow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pay:show';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'show pay bill detail.';

    protected $controller= NULL;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PayController $controller)
    {
        $this->controller = $controller;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $id = $this->ask("input the id ",1);    
        $ret = $this->controller->show($id);
        
        ShopcountStore::outputReturn($this, $ret);
    }
}
