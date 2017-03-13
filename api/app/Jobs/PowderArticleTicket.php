<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Log;
use Service\NetDesCrypt;
use App\Model\SeedPool;
use App\Model\Present;
use App\Model\PresentArticleCode;
use DB;

class PowderArticleTicket extends Job implements SelfHandling,ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    public $presentId;
    public $presentInfo;
    
    public function __construct( $presentId ) {
        Log::info('初始化');
        $this->presentId = $presentId;
        $this->presentInfo = Present::where(['present_id'=>$presentId])->first();
        
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('开始处理');
        $presentId = $this->presentId;
            
        if($this->presentInfo === null){
            Log::info('无法获取活动相关信息');
            return true;
        }
        $this->presentInfo = $this->presentInfo->toArray();
        $presentId = $this->presentInfo['present_id'];
        $itemId = $this->presentInfo['item_id'];
        $ticketStatus = 2;  //未使用
        $presentType = 3;  //活动赠送
        $createdTime = time();
        $expireTime = $this->presentInfo['expire_at'];
        
        //赠送项目总数量
        $count = $this->presentInfo['quantity'];
        //做事物，每次插入两千条，全部成功，再提交
        if($count > 0){
            $seedRes = $this->getArticleTicketCodeFromSeedPool($count);
            if(!$seedRes){
                return true;
            }
            $pageSize = 2000;
            $totalPage = ceil($count/$pageSize);
            $insertTimes = 0;
            for($page=1; $page <= $totalPage; $page++){
                Log::info("正在处理第{$page}页数据");
                $offset = ($page-1)*$pageSize;
                $limit = min($count,$offset+$pageSize);
                $insert = ' INSERT cm_present_article_code (`present_id`,`ordersn`,`item_id`,`code`,`status`,`present_type`,`expire_at`,`created_at`) VALUES ';
                $i=$offset;
                while($i<$limit) { 
                    $code = $seedRes[$i];
                    $ordersn = PresentArticleCode::getOrderSn();
                    if( $i==$offset )
                        $insert .= " ( $presentId , '$ordersn', $itemId, '$code', $ticketStatus, $presentType, '$expireTime',$createdTime)";
                    else
                        $insert .= ",( $presentId , '$ordersn', $itemId,'$code', $ticketStatus, $presentType, '$expireTime',$createdTime)";
                    $i++;
                }
                $insert .= ';';
                try 
                {
                    $result = DB::insert( $insert );
                    Log::info("定妆数据第{$page}页数据处理完成");
                } 
                catch(\Exception $e) {
                    $message = $e->getMessage();
                    Log::info("定妆数据第{$page}页数据处理失败,正在重试:$message");
                    $page--;
                    $insertTimes++;
                } 
                if($insertTimes > 5){
                    Log::info("定妆数据第{$page}页数据处理失败次数过多，已停止重试,请稍后删除已插入数据");
                    return true;
                }
            }
            //更新券状态
            $this->updateArticleTicketStatus($seedRes);
        }
        
    }
    
    //生成券号
    private function getArticleTicketCodeFromSeedPool($limit){
        //从臭美池中获取
        $seedRes = SeedPool::getArticleTicketFromPool($limit);
        return $seedRes;
    }
    
    /***
     * 使用之后对臭美池中的券使用状态进行更新
     */
    private  function updateArticleTicketStatus($seedRes){
        foreach ($seedRes as $key => $value) {
            $seeds[] = substr($value,2);
        }
        $where = array('TYPE' => 'GSN');
        
        $pageSize = 5000;
        $totalPage = ceil(count($seeds)/$pageSize);
        //失败了尝试的次数
        $times = 0;
        //每次成功的总次数
        $succeedTimes = 0;
        for($page = 1; $page <= $totalPage; $page++){
            $eachSeeds = array();
            foreach ($seeds as $key => $value) {
                /**
                 * 0-4999  5000-9999 分批次
                 */
                if($key >= $pageSize * ($page - 1) && $key < $page * $pageSize){
                    $eachSeeds[] = $value;
                }                
            }
                      
            $updateRes  = SeedPool::where($where)->whereIn('SEED',$eachSeeds)->update(array('STATUS' => 'USD' ,'UPDATE_TIME' => date( "Y-m-d H:i:s" )));
            if($updateRes === false){
                Log::info("定妆赠送券状态更新sql错误,第{$page}页");
                $page--;
                $times++;
            }elseif($updateRes != count($eachSeeds)){
                Log::info("定妆赠送券状态部分数据更新失败,第{$page}页");
                $page--;
                $times++;
            }else{
                $succeedTimes++;
            }
        }
        if($succeedTimes == $totalPage){
            return 1;
        }else{
            Log::info("定妆赠送券状态更新失败，当前时间：". date("Y-m-d H:i:s"));
            $logInfoSeeds = implode(",", $seeds);
            Log::info("当前种子为：".$logInfoSeeds);
            return TRUE;
        }
                 
        
        
    }
}
