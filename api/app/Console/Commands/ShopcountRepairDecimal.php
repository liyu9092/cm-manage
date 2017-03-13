<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\ShopCountDetail;
use App\ShopCount;
use App\InsteadReceive;
use App\Commission;

class ShopcountRepairDecimal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopcount:repair_decimal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'repair decimal.';

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
        $items = ShopCountDetail::select(DB::raw("distinct(salon_id)"))->whereRaw("(money*100%100) > 0")->get()->toArray();
        $bad_salon_ids = array_column($items, "salon_id");
        unset($items);
        //修复往来余额表
        $this->info("repair count!");
        $items = ShopCountDetail::select(DB::raw("salon_id,SUM(money) as money"))->whereIn('salon_id',$bad_salon_ids)->groupBy('salon_id')->get()->toArray();      
        foreach ($items as $item)
        {
            $salon_id = $item['salon_id'];
            $money = floatval($item['money']);
            $num=ShopCount::where('salon_id',$salon_id)->update(['spend_money'=>$money]);
            if($num)
            {
                //$this->info("ok ! salon_id : {$salon_id} ,money:{$money}");
            }
            else 
            {
                $this->error("[count] error ! salon_id : {$salon_id} ,money:{$money}");
            }
        }
        
        //修复代收单
        $items = ShopCountDetail::select(DB::raw("salon_id,SUM(money) as money,DATE_FORMAT(`created_at`,'%Y-%m-%d') as `day`"))->whereIn('salon_id',$bad_salon_ids)->groupBy(['salon_id','day'])->get()->toArray();
        $this->info("repair instead receive!");
        foreach ($items as $item)
        {
            $salon_id = $item['salon_id'];
            $money = floatval($item['money']);
            $day = $item['day'];
            $num=InsteadReceive::where('salon_id',$salon_id)->where('day',$day)->update(['money'=>$money]);
            if($num)
            {
                //$this->info("ok ! salon_id : {$salon_id} , day:{$day} , money:{$money}");
            }
            else 
            {
                $this->error("[instead receive] error !  salon_id : {$salon_id}, day:{$day} , money:{$money}");
            }
        }
        
        //修复佣金单
        $items = Commission::select(DB::raw("salonid,SUM(amount) as money"))->groupBy('salonid')->get()->toArray();
        $this->info("repair Commission!");
        foreach ($items as $item)
        {
            $salon_id = $item['salonid'];
            $money = floatval($item['money']);
            $num=ShopCount::where('salon_id',$salon_id)->update(['commission_money'=>$money]);
            if($num)
            {
                //$this->info("ok ! salon_id : {$salon_id} ,money:{$money}");
            }
            else
            {
                $this->error("[commission] error ! salon_id : {$salon_id} ,money:{$money}");
            }
        }
    }
}
