<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ShopCount\ShopCountController;
use App\ShopCountDetail;
use App\ShopCountApi;
use App\BountyTask;

class ShopcountCountBountyBalance extends Command
{

 /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopcount:count_bounty_balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'count the bounty order !';

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
        $last_time = $this->get_count_start_time();
        if (empty($last_time))
        {
            $last_time =$this->get_last_count_time();
        }
        $size = 10;
        $page = 0;
        $count = 10;
        do{
            $ordersns = BountyTask::where("btStatus",4)
            ->where("endTime",">",$last_time)
            ->where("salonId","!=",0)
            ->orderBy('endTime','DESC')
            ->skip($size*$page)
            ->take($size)
            ->lists('btSn')
            ->toArray();
            $count = count($ordersns);
            if($count>=1)
            {
                $ordersn_str = implode(",", $ordersns);
                $type = ShopCountDetail::TYPE_OF_BOUNTY;
                $params = ['type'=>$type,'ordersn'=>$ordersn_str];
                ShopCountApi::makeToken($params);
                $this->controller->param = $params;
                $ret = $this->controller->countOrder();
                ShopcountStore::outputReturn($this, $ret);
            }
            $page++;
        }
        while($count >= $size);
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
        $detail = ShopCountDetail::where('type',ShopCountDetail::TYPE_OF_BOUNTY)
        ->orderBy('created_at','DESC')->first();
        if (empty($detail))
        {
            return 0;
        }
        return strtotime($detail->created_at);
    }
}

