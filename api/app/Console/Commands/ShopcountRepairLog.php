<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\ShopCountLog;

class ShopcountRepairLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopcount:repair_log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'repair log balance money!';

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
        $items = ShopCountLog::selectRaw("DISTINCT(`salon_id`) as `salon_id`")->get()->toArray();
        $salon_ids = array_column($items, "salon_id");
        asort($salon_ids); 
        $file = "cm_tmp_shop_balance_log.sql";
        
        foreach ($salon_ids as $salon_id)
        {
            $this->info("start to count NO {$salon_id} salon");
            $items = ShopCountLog::select(['id','type','money'])->where('salon_id',$salon_id)->orderBy('count_at','ASC')->orderBy('id','DESC')->get()->toArray();
            $last_balance = NULL;
            $records =[];
            foreach ($items as $item)
            {
                $id = $item['id'];
                $type = intval($item['type']);
                $money = floatval($item['money']);
                $change_money = $money;
                if($type == ShopCountLog::TYPE_OF_COMMISSION_RETURN || $type == ShopCountLog::TYPE_OF_SPEND)
                {
                    $change_money = bcmul($change_money,-1,2) ;
                }
                if(is_null($last_balance))
                {
                    $last_balance = 0;
                }
                $last_balance =bcadd($change_money, $last_balance,2) ;
                $records[] = ['id'=>$id,'balance'=>$last_balance];
                if(count($records) >= 3500)
                {
                    self::export_ir_to_file($records,$file);
                    $records = [];
                }                
               // ShopCountLog::where('id',$id)->update(['balance_money'=>$last_balance]);
            }
            self::export_ir_to_file($records,$file);
        }       
    }
    
    public static function export_ir_to_file($res,$file)
    {
        if(count($res)<1)
        {
            return;
        }
        $fp = fopen($file,"ab+");
        $prefix = "INSERT INTO `cm_choumeionline`.`cm_tmp_shop_balance_log` ( `id`, `balance`) VALUES ";
        $items = [];
        foreach($res as $re)
        {
            $id = $re['id'];
            $balance = $re['balance'];
            $items[]= "('{$id}', '{$balance}')";
        }
        $str =implode(",", $items);
        $write_str = $prefix.$str.";\n";
        fwrite($fp, $write_str);
        fclose($fp);
    }
    
}
