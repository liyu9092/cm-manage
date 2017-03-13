<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\ShopCountApi;

class ShopcountFastCountOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopcount:fast_count_order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fast the order only can used one time!';
    
 
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
		ShopCountApi::importAllOrder();
    }

}
