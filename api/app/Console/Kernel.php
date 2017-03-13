<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\Inspire::class,
        //店铺往来数据相关
        \App\Console\Commands\ShopcountCreate::class,
        \App\Console\Commands\ShopcountIndex::class,
        \App\Console\Commands\ShopcountPreview::class,
        \App\Console\Commands\ShopcountShow::class,
        \App\Console\Commands\ShopcountStore::class,
        \App\Console\Commands\ShopcountUpdate::class,
        \App\Console\Commands\ShopcountDestroy::class,
        \App\Console\Commands\ShopcountBalance::class,
        \App\Console\Commands\ShopcountDelegateDetail::class,
        \App\Console\Commands\ShopcountDelegateList::class,
        \App\Console\Commands\ShopcountCountBalance::class,
        \App\Console\Commands\ShopcountCountBountyBalance::class,
        \App\Console\Commands\ShopcountImportPrepay::class,
        \App\Console\Commands\ShopcountFastCountOrder::class,
        \App\Console\Commands\ResetPassword::class,
        \App\Console\Commands\ShopcountDelegateExport::class,
        \App\Console\Commands\PayCreate::class,
        \App\Console\Commands\PayShow::class,
        \App\Console\Commands\ShopcountRepairDecimal::class,
        \App\Console\Commands\ShopcountRepairLog::class,
        \App\Console\Commands\ShopcountRepairRes::class,
        \App\Console\Commands\Permissions::class,
        \App\Console\Commands\Commission::class,
        \App\Console\Commands\CommissionLog::class,
        \App\Console\Commands\CountOrder::class,
        //定妆赠送券过期后，修改状态
        \App\Console\Commands\PresentArticleCodeExpire::class,  
        //定妆赠送券过期提醒
        \App\Console\Commands\PresentItemExpireRemind::class,  
        //修复预约日历数据问题
        \App\Console\Commands\CalendarRepair::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('inspire')
                 ->hourly();
    }
}
