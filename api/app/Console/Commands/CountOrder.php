<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Order;
use App\Http\Controllers\ShopCount\ShopCountController;
use App\ShopCountApi;
use App\BountyTask;

class CountOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'count_order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'count last day order and bounty!';
    
    private  $_controller = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ShopCountController $controller)
    {
        if(empty($this->_controller))
        {
            $this->_controller = $controller;
        }
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $start_at = strtotime(date("Y-m-d 00:00:00",strtotime("-1 day")));
        $end_at = strtotime(date("Y-m-d 23:59:59",strtotime("-1 day")));
        $this->count_order($start_at, $end_at);
        $this->count_bounty($start_at, $end_at);
    }
    
    public function count_order($min_time,$max_time)
    {
        $offset = 0;
        $size = 5000;
        do{
            $bases = Order::where('status',4)->where('use_time','<=',$max_time)->where('use_time','>=',$min_time)->select('ordersn')->skip($offset)->take($size)->get()->toArray();
            $count = count($bases);
            if($count<1)
            {
                break;
            }
            $offset += $size;
            foreach($bases as $order)
            {
               $ordersn = $order['ordersn'];
               $params = ['type'=>1,'ordersn'=>$ordersn];
               $this->info("count order : ordersn {$ordersn}");
               ShopCountApi::makeToken($params); 
               $this->_controller->param = $params;
               $this->_controller->countOrder();
            }
            unset($bases);
        }while ($count>=$size);
    }
    
    public function count_bounty($min_time,$max_time)
    {
        $offset = 0;
        $size = 5000;
        do{
            $bases = BountyTask::where('btStatus',4)->where('endTime','<=',$max_time)->where('endTime','>=',$min_time)->select('btSn')->skip($offset)->take($size)->get()->toArray();
            $count = count($bases);
            if($count<1)
            {
                break;
            }
            $offset += $size;
            foreach($bases as $bounty)
            {
                $ordersn = $bounty['btSn'];
                $params = ['type'=>2,'ordersn'=>$ordersn];
                $this->info("count bounty : ordersn {$ordersn}");
                ShopCountApi::makeToken($params);
                $this->_controller->param = $params;
                $this->_controller->countOrder();
            }
            unset($bases);
        }while ($count>=$size);
    }
}
