<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\PrepayBill;
use App\ShopCount;
use App\InsteadReceive;
use App\Rebate;
use App\PayManage;
use App\Receivables;

class ShopcountRepairRes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopcount:repair_res';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'recount the shop count table';

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
        //付款
        $this->info("begin to count pay_money !");
        self::repair_pay_money();
        
        //消费
        $this->info("begin to count spend_money !");        
        self::repair_spend_money();
        
        //应收佣金       因为延迟一天  暂时不统计  
//         $this->info("begin to count commission_money !");
//         self::repair_pay_money();
        
        //返佣
        $this->info("begin to count commission_return_money !");
        self::repair_commission_return_money();
        
        //付投资款
        $this->info("begin to count invest_money !");
        self::repair_invest_money();
        
        //付投资款返还
        $this->info("begin to count invest_return_money !");
        self::repair_invest_return_money();

    }
    
    public function repair_pay_money()
    {
        $prepays = PrepayBill::selectRaw("`salon_id`,SUM(`pay_money`) as `pay_money`")->where('state',PrepayBill::STATE_OF_COMPLETED)->groupBy('salon_id')->get();
        foreach ($prepays as $prepay)
        {
            $salon_id = $prepay->salon_id;
            $money = $prepay->pay_money;
            //echo "salon {$salon_id} , money  {$money} ! \n" ;
            //
            ShopCount::where('salon_id',$salon_id)->update(['pay_money'=>$money]);
        }
    }
    
    public function repair_spend_money()
    {
        $irs = InsteadReceive::selectRaw("`salon_id`,SUM(`money`) as `money`")->groupBy('salon_id')->get();
        foreach ($irs as $ir)
        {
            $salon_id = $ir->salon_id;
            $money = $ir->money;
            //echo "salon {$salon_id} , money  {$money} ! \n" ;
            //
            ShopCount::where('salon_id',$salon_id)->update(['spend_money'=>$money]);
        }
    }
    
    public function repair_commission_return_money()
    {
        $rebates = Rebate::selectRaw("`salon_id`,SUM(`amount`) as `money`")->where('status',1)->groupBy('salon_id')->get();
        foreach ($rebates as $rebate)
        {
            $salon_id = $rebate->salon_id;
            $money = $rebate->money;
            //echo "salon {$salon_id} , money  {$money} ! \n" ;
            //
            ShopCount::where('salon_id',$salon_id)->update(['commission_return_money'=>$money]);
        }
    }
    
    public function repair_invest_money()
    {
        $pms = PayManage::selectRaw("`salon_id`,SUM(`money`) as `money`")->where('type',PayManage::TYPE_OF_FTZ)->where('state',PayManage::STATE_OF_PAIED)->groupBy('salon_id')->get();
        foreach ($pms as $pm)
        {
            $salon_id = $pm->salon_id;
            $money = $pm->money;
            //echo "salon {$salon_id} , money  {$money} ! \n" ;
            //
            ShopCount::where('salon_id',$salon_id)->update(['invest_money'=>$money]);
        }
    }
    
    public function repair_invest_return_money()
    {
        $revs = Receivables::selectRaw("`salonid` as `salon_id`,SUM(`money`) as `money`")->where('type',1)->where('status',2)->groupBy('salon_id')->get();
        foreach ($revs as $rev)
        {
            $salon_id = $rev->salon_id;
            $money = $rev->money;
            //echo "salon {$salon_id} , money  {$money} ! \n" ;
            //
            ShopCount::where('salon_id',$salon_id)->update(['invest_return_money'=>$money]);
        }
    }
}
