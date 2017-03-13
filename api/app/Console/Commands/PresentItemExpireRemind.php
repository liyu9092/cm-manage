<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Log;
use App\ThriftHelperModel;
use App\BeautyItem;
use App\Model\Present;
use App\Model\PresentArticleCode;
use App\Push;

class PresentItemExpireRemind extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'itemExpire:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'when presentItem will be expire a head of 5 days, remind users by SMS and Messsage ';

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
        //获取即将5天后过期的赠送项目券的信息
        
        // 1. 获取线上活动信息
        $presentInfo = Present::select('present_id','item_id')->where(array('article_type'=> 2))->first();
        if($presentInfo === null){
            Log::info("找不到线上活动信息");
            echo "找不到线上活动信息";
            return ;
        }else{
            $presentInfoRes = $presentInfo->toArray();
        }
        
        $select = array(
            'present_article_code.mobilephone',
            'present_article_code.user_id',
            'present_article_code.article_code_id',
            'user.os_type'
        );
        $where = array(
            'present_article_code.present_id' => $presentInfoRes['present_id'],
            'present_article_code.status' => 2  //未使用状态
        );
        $presentArticleCodeInfo =  PresentArticleCode::select($select)
                                    ->leftJoin('user','user.user_id','=','present_article_code.user_id')
                                    ->where($where)
                                    ->whereRaw('DATEDIFF(cm_present_article_code.expire_at,NOW()) = 5')
                                    ->get()->toArray();
        
        if(empty($presentArticleCodeInfo)){
            log::info("今天没有到期前五天的赠送项目");
            echo "今天没有到期前五天的赠送项目";
            return ;
        }
        
        // 2. 获取赠送项目信息
        $beautyItemInfo = BeautyItem::select('name','price')->where(array('item_id' => $presentInfoRes['item_id']))->first();
        if($beautyItemInfo === null){
            Log::info("无法获取赠送项目相关信息,item_id:".$presentInfoRes['item_id']);
            echo "无法获取赠送项目相关信息,item_id:".$presentInfoRes['item_id'];
            return ;
        }else{
            $beautyItemInfoRes = $beautyItemInfo->toArray();
        }
        //发短信
        $this->sendSms($presentArticleCodeInfo,$beautyItemInfoRes);
        
        // 发送推送消息
        $this->pushMessage($presentArticleCodeInfo,$beautyItemInfoRes);   
        
        echo "赠送项目到期发送短信和推送消息执行完毕";
        return;
    }
    
    //发送短信
    private function sendSms($presentArticleCodeInfo,$beautyItemInfoRes){
        $thrift = new ThriftHelperModel();
        $beautyItemName = $beautyItemInfoRes['name'];
        $beautyItemPrice = $beautyItemInfoRes['price'];
        $sms = "你有一个臭美赠送的价值{$beautyItemPrice}元的{$beautyItemName}项目再有5天就到期啦，快去体验吧！下载臭美查看详情Apphttp://t.cn/RZXyLPg 退订回复TD";
        //发送短信
        foreach($presentArticleCodeInfo as $key => $value){
            $res = $thrift->request('sms-center', 'sendSmsByType', array($value['mobilephone'], $sms, '127.0.0.1', 5));
            if($res != 1){
                $msg = '定时任务线上活动赠送项目发送信息失败 , 用户手机号码 ：' . $value['mobilephone'];
                Log::info( $msg );
            }           
        }
    }
    
    //发送推送，写数据，java推送
    private function pushMessage($presentArticleCodeInfo,$beautyItemInfoRes){
        $beautyItemName = $beautyItemInfoRes['name'];
        $beautyItemPrice = $beautyItemInfoRes['price'];
        // 写入到推送表
        $page = 1;
        $pageSize = 100;
        $count = ceil(count($presentArticleCodeInfo)/$pageSize);
        do{
            $dataPush = array();
            foreach($presentArticleCodeInfo as $key => $val){
                if($key>=($page-1)*$pageSize && $key< $page*$pageSize){
                    if( !empty($val['user_id']) ){                       
                        $dataPush[$key]['RECEIVER_USER_ID'] = $val['user_id'];
                        $dataPush[$key]['TYPE'] = 'USR';
                        if( !empty( $val['os_type']) && in_array($val['os_type'], array(1,2))){
                            $os_type = $val['os_type'] == 1 ? 'ANDROID':'IOS';
                            $dataPush[$key]['OS_TYPE'] =  $os_type;
                        }                        
                        $dataPush[$key]['TITLE'] = '项目到期提醒';
                        $dataPush[$key]['MESSAGE'] = "你有一个臭美赠送的价值{$beautyItemPrice}元的{$beautyItemName}项目再有5天就到期啦，快去体验吧！点击查看";
                        $dataPush[$key]['PRIORITY'] = 1;
                        $dataPush[$key]['EVENT'] = '{"event":"presentItem","userId":"'.$val['user_id'].'","articleCodeId":"'.$val['article_code_id'].'","msgType":11}';
                        $dataPush[$key]['STATUS'] = 'NEW';
                        $dataPush[$key]['CREATE_TIME'] = date('Y-m-d H:i:s');
                    }
                }                
            }
            Push::insert( $dataPush );
            $page++;
            $count--;
        }while($count>0);                        
        
    }
}
