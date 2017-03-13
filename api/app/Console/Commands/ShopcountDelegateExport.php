<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ShopCount\ShopCountController;

class ShopcountDelegateExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopcount:delegate_export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';


    protected $controller = null;
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
        $this->controller->delegate_export();
        
    }
}
