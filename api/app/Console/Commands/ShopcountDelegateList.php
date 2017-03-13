<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ShopCount\ShopCountController;

class ShopcountDelegateList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopcount:delegate_list';
    
    protected $controller = NULL;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ShopCountController $contoller)
    {
        $this->controller = $contoller;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $default = ['merchant_id'=>2,'type'=>1,'salon_id'=>2,'uid'=>2,'pay_money'=>'25','cost_money'=>30,'day'=>'2015-06-01'];
        $args = ['key'=>1,'keyword'=>'嘉美'];
        // $args = [];
        //         if($args && is_array($args))
            //         {
            //             $args = array_merge($default,$args);
            //         }
        //         else
            //         {
            //             $args = $default;
            //         }
        $this->controller->param = $args;
        $ret = $this->controller->delegate_list();
        ShopcountStore::outputReturn($this, $ret);
    }
}
