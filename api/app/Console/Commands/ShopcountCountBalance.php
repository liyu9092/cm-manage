<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ShopCount\ShopCountController;
use App\ShopCountDetail;
use App\Order;
use App\ShopCountApi;

class ShopcountCountBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopcount:count_order_balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'count the order !';
    
    protected $controller = NULL;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ShopCountController $controller)
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
       $exist_ordersn = ShopCountDetail::where("type",ShopCountDetail::TYPE_OF_ORDER)->lists('code')->toArray();
       $ordersns = Order::where("status",4)
       ->whereNotIn('ordersn',$exist_ordersn)
       ->orderBy('use_time','DESC')  
       ->lists('ordersn')
       ->toArray();
       $total = count($ordersns);
       $page = 0;
       $size = 100;
       $all_page = ceil($total/$size);
       $tmp_ordersns = array_slice($ordersns, $page*$size,$size);
       while(count($tmp_ordersns) > 0)
       {
           $this->info("completed ".ceil($page/$all_page*100)."% ...");
           $ordersn_str = implode(",", $ordersns);
           $type = ShopCountDetail::TYPE_OF_ORDER;
           $params = ['type'=>$type,'ordersn'=>$ordersn_str];
           ShopCountApi::makeToken($params);
           $this->controller->param = $params;
           $this->controller->countOrder();
           
           $page++ ;
           $tmp_ordersns = array_slice($ordersns, $page*$size,$size);
       }
       $this->info("all completed!");
//        do{   
                 
//            $count = count($ordersns);
//            if($count>=1)
//            {
//                $ordersn_str = implode(",", $ordersns);
//                $type = ShopCountDetail::TYPE_OF_ORDER;
//                $params = ['type'=>$type,'ordersn'=>$ordersn_str];
//                ShopCountApi::makeToken($params);
//                $this->controller->param = $params;
//                $ret = $this->controller->countOrder();
//                ShopcountStore::outputReturn($this, $ret);
//            }
//            $page++;
//        }
//        while($count >= $size);
    }
    
    /**
     * 获取自定义统计开始时间
     * @return number
     */
    public function get_count_start_time()
    {
        $time_str = $this->ask("input the time  that start to count! format [YYYY-MM-DD]","2014-01-01");
        if($time_str == "0")
        {
            return 0;
        }
        $time = strtotime($time_str);
        return $time;
    }
    
    /**
     * 获取上次统计时间
     * @return number
     */
    public function get_last_count_time()
    {
        $detail = ShopCountDetail::where('type',ShopCountDetail::TYPE_OF_ORDER)
        ->orderBy('created_at','DESC')->first();
        if (empty($detail))
        {
            return 0;
        }
        return strtotime($detail->created_at);
    }
}
