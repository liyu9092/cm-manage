<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Order;
use DB;
use App\ShopCount;

class Commission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成佣金单';
    
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
        $commissions = DB::table('commission_log')
            ->select('ordersn','salonid','rate','grade','note',DB::raw("DATE_FORMAT(`created_at`, '%Y-%m-%d') as date,sum(amount) as amount"))
            ->groupBy('salonid','date')
            ->get();

        foreach ($commissions as $key => $commission) {
            $model = new \App\Commission;
            $model->sn = $model::getSn();
            $model->salonid = $commission->salonid;
            $model->amount = $commission->amount;
            $model->date = $commission->date;
            $date = date('Y-m-d H:i:s');
            $model->created_at = $date;
            $model->updated_at = $date;
            $model->save();
        }
    
        $this->info('done!');
    }

}
