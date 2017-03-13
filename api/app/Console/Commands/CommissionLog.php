<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Order;
use DB;
use App\ShopCount;

class CommissionLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commissionLog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '初始化佣金LOG';
    
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
        $this->bounty();
        $this->order();
    }

    /**
     * 订单佣金流水
     */
    public function order(){
        $query = Order::leftJoin('commission_log','order.ordersn','=','commission_log.ordersn')
            ->leftJoin('salon','salon.salonid','=','order.salonid')
            ->leftJoin('recommend_code_order','recommend_code_order.ordersn','=','order.ordersn')
            ->where('commission_log.id','=',NULL)
            ->where('order.status','=',4)
            ->select('order.ordersn','order.priceall','order.pay_time','order.use_time','order.salonid','salon.salonGrade','salon.merchantId','recommend_code_order.recommend_code')
            ->orderBy('use_time','ASC');
        $count = $query->count();
        $pageSize = 3000;
        $totalPage = ceil($count/$pageSize);
        $this->info('总共'.$count.'条订单未计算佣金');
        for ($page=0; $page < $totalPage; $page++) { 
            $result = [];
            $offset = $page*$pageSize;
            $orders = $query->skip($offset)->take($pageSize)->get();
            foreach ($orders as $key => $order) {
                $data['ordersn'] = $order->ordersn;
                $data['type'] = 1;
                $data['salonid'] = $order->salonid;
                $rate = intval($order->pay_time)<strtotime('20150801')?9.09:9.00;
                $data['rate'] = $rate;
                $data['grade'] = $order->salonGrade;
                $amount = floatval($order->priceall)*$rate/100;
                $amount = round($amount,2);
                $data['amount'] = $amount;
                $date = date('Y-m-d H:i:s',$order->use_time);
                $data['updated_at'] = $date;
                $data['created_at'] = $date;
                $note = '佣金率'.$rate.'%';
                if($order->recommend_code)
                    $note.='</br>邀请码用户消费';
                $data['note'] = $note;
                $result[] = $data;
            }
            Self::export_ir_to_file($result,'order.sql');
            $this->info("第{$page}页数据处理完成");
        }
    }

    /**
     * 赏金单佣金流水
     */
    public function bounty(){
        $query = DB::table('bounty_task')
            ->leftJoin('commission_log','bounty_task.btSn','=','commission_log.ordersn')
            ->leftJoin('salon','salon.salonid','=','bounty_task.salonId')
            ->where('commission_log.id','=',NULL)
            ->where('bounty_task.btStatus','=',4)
            ->select('bounty_task.btSn as ordersn','bounty_task.money as priceall','bounty_task.payTime as pay_time','bounty_task.endTime as use_time','bounty_task.salonId as salonid','salon.salonGrade','salon.merchantId')
            ->orderBy('endTime','ASC');
        $count = $query->count();
        $pageSize = 10000;
        $totalPage = ceil($count/$pageSize);
        $this->info('总共'.$count.'条赏金单未计算佣金');
        for ($page=0; $page < $totalPage; $page++) { 
            $result = [];
            $offset = $page*$pageSize;
            $orders = $query->skip($offset)->take($pageSize)->get();
            foreach ($orders as $key => $order) {
                $data['ordersn'] = $order->ordersn;
                $data['type'] = 2;
                $data['salonid'] = $order->salonid;
                $rate = intval($order->pay_time)<strtotime('20150801')?9.09:9.00;
                $data['rate'] = $rate;
                $data['grade'] = $order->salonGrade?$order->salonGrade:0;
                $amount = floatval($order->priceall)*$rate/100;
                $amount = round($amount,2);
                $data['amount'] = $amount;
                $date = date('Y-m-d H:i:s',$order->use_time);
                $data['updated_at'] = $date;
                $data['created_at'] = $date;
                $note = '佣金率'.$rate.'%';
                $data['note'] = $note;
                $result[] = $data;
            }
            Self::export_ir_to_file($result,'bounty.sql');
            $this->info("第{$page}页数据处理完成");
        }
    }



    public static function export_ir_to_file($res,$file)
    {
        if(count($res)<1)
        {
            return;
        }
        $fp = fopen($file,"ab+");
        $prefix = "INSERT INTO `cm_choumeionline`.`cm_commission_log` ( `ordersn`, `type`, `salonid`, `amount`, `rate`, `grade`, `note`, `created_at`, `updated_at`) VALUES ";
        $items = [];
        foreach($res as $re)
        {
            extract($re);
            $items[]= "('{$ordersn}', '{$type}', '{$salonid}' , '{$amount}', '{$rate}',{$grade}, '{$note}','{$created_at}','{$updated_at}')";
        }
        $str =implode(",", $items);
        $write_str = $prefix.$str.";\n";
        fwrite($fp, $write_str);
        fclose($fp);
    }



}
