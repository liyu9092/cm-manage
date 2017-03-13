<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ShopCount\ShopCountController;


class ShopcountStore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopcount:store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'save a new prepay bill!';
    
    protected $controller = NULL;

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
        $default = ['merchant_id'=>2,'type'=>1,'salon_id'=>2,'uid'=>2,'pay_money'=>'25','cost_money'=>30,'day'=>'2015-06-01'];
        $args = self::getInput($this,"input the create info ");
        if($args && is_array($args))
        {
            $args = array_merge($default,$args);
        }
        else
        {
            $args = $default;
        }
        $this->controller->param = $args;
        $ret = $this->controller->store();
        self::outputReturn($this, $ret);
    }
    
    
    public static function getInput($model,$info)
    {    
        $input_str = $model->ask($info);
        $input_str = trim($input_str);
        $str_sign = $input_str{0};
        if($str_sign == "{" || $str_sign == "[")
        {
            return  json_decode($input_str,true);
        }
        else
        {
            return parse_str($input_str);
        }
    }
    
    public static function outputReturn($model,$info)
    {
        $model->info("output:");
        $model->info("");
        $model->info($info);
    }
}
