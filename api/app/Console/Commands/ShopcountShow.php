<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ShopCount\ShopCountController;

class ShopcountShow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopcount:show';

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
        $id = $this->ask("input the id ");    
        $ret = $this->controller->show($id);
        
        ShopcountStore::outputReturn($this, $ret);
    }
}
