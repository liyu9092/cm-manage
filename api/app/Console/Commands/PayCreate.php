<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Pay\PayController;

class PayCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pay:PayCreate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Pay Bill.';
    
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
        $params = [
            'type' => 2,
            'salon_id' => 1,
            'merchant_id' => 2,
            'money' => 333.66,
            'pay_type' => 1,
            'require_day' => date("Y-m-d"),
            'cycle' => 30,
            'cycle_day' => 1,
            'cycle_money' => 100,
        ];
        $this->controller->param = $params;
        $res = $this->controller->store();
        ShopcountStore::outputReturn($this, $res);
    }
}
