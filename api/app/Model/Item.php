<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class Item extends Model {

	protected $table = 'salon_item';
    protected $primaryKey = 'itemid';
    public $timestamps = false;
     protected $fillable = ['status','sort_in_type','updateTime'];
    CONST SALE = 1;//默认在售
    CONST ONSALE = 2;//闲时特价
    CONST UP = 1;//上架
    CONST DOWN = 2;//下架
    CONST DELETE = 3;//删除


	public static function getQueryByParam($param=[]){

        $query = Self::leftJoin('salon_itemtype','salon_item.typeid','=','salon_itemtype.typeid')
            ->Leftjoin('salon_item_buylimit','salon_item_buylimit.salon_item_id','=','salon_item.itemid');
        //店铺筛选
        if(!empty($param['salonid'])){
            $query = $query->where('salonid','=',$param['salonid']);
        }          

        //状态筛选
        if(!empty($param['status'])){
            $query = $query->where('salon_item.status','=',$param['status']);
        }  

        //项目名称筛选
        if(!empty($param['itemname'])){
        	$itemname = '%'.$param['itemname'].'%';
            $query = $query->where('itemname','like',$itemname);
        }  

        //项目类型筛选
        if(!empty($param['item_type'])){
            $query = $query->where('item_type','=',$param['item_type']);
        }  

	    //项目分类筛选
        if(!empty($param['typeid'])){
            $query = $query->where('salon_item.typeid','=',$param['typeid']);
        }	    

        //有无规格筛选
        if(!empty($param['norms_cat_id'])){
        	$norms_cat_id = intval($param['norms_cat_id']);
        	if($norms_cat_id == 1)
            	$query = $query->where('norms_cat_id','>',0);
            if($norms_cat_id == 2)
            	$query = $query->where('norms_cat_id','=',0);
        }        

        //有无期限筛选
        if(!empty($param['exp_time'])){
        	$exp_time = intval($param['exp_time']);
        	if($exp_time == 1)
            	$query = $query->where('exp_time','>',0);
            if($exp_time == 2)
            	$query = $query->where('exp_time','=',0);
        }        

        //有无库存限制筛选
        if(!empty($param['total_rep'])){
        	$total_rep = intval($param['total_rep']);
        	if($total_rep == 1)
            	$query = $query->where('total_rep','>',0);
            if($total_rep == 2)
            	$query = $query->where('total_rep','=',0);
        }        

        //有无资格限制筛选
        if(!empty($param['buylimit'])){
        	$buylimit = intval($param['buylimit']);
        	if($buylimit == 1)
        		$query = $query->where('salon_item_buylimit.limit_first','=',1);

            if($buylimit == 2)
            	$query = $query->where('salon_item_buylimit.limit_invite','=',1);
        }


        //排序
        $sort_key = empty($param['sort_key'])?'sort_in_type':'salon_item.'.$param['sort_key'];
        $sort_type = empty($param['sort_type'])?'DESC':$param['sort_type'];
        $query = $query->orderBy($sort_key,$sort_type);

        return $query;
    }


    //根据id获取项目
    public static function get($id){
        $item = Self::leftJoin('salon_itemtype','salon_itemtype.typeid','=','salon_item.typeid')
            ->leftJoin('salon_item_buylimit','salon_item_buylimit.salon_item_id','=','salon_item.itemid')
            ->leftJoin('managers','managers.id','=','salon_item.userId')
            ->select(
                'itemid',
                'salon_item.itemname',
                'salon_item.typeid',
                'salon_item.norms_cat_id',
                'logo',
                'typename',
                'addserviceStr',
                'desc',
                'exp_time',
                'timingAdded',
                'timingShelves',
                'limit_time',
                'total_rep',
                'limit_invite',
                'limit_first',
                'sold',
                'up_time',
                'managers.name',
                'salon_item.UPDATE_date',
                'sort_in_type',
                'salon_item.status',
                'salon_item.userId',
                'salon_item.useLimit',
                'salon_item.repertory',
                'salon_item.innage',
                'salon_item.updateTime'
                )
            ->find($id);
        if(!$item)
            throw new ApiException('未知项目ID', ERROR::ITEM_NOT_FOUND);
        $item->prices = Self::getPrice($item->itemid);
        return $item;
    }


    //获取价格
    public static function getPrice($id){
        $prices = DB::table('salon_item_format_price')
            ->leftJoin('salon_norms','salon_norms.salon_norms_id','=','salon_item_format_price.salon_norms_id')
            ->where('itemid','=',$id)
            ->select('price','price_dis','price_group','salon_norms.salon_item_format_id')
            ->get();

        foreach ($prices as $key => $price) {
            $formats_id = explode(',', $price->salon_item_format_id);
            $formats = DB::table('salon_item_format')
                ->whereIn('salon_item_format_id',$formats_id)
                ->leftJoin('salon_item_formats','salon_item_formats.salon_item_formats_id','=','salon_item_format.salon_item_formats_id')
                ->select('format_name','formats_name','salon_item_format_id','salon_item_format.salon_item_formats_id')->get();
            $price->formats = $formats;
            $prices[$key] = $price;
        }

        return $prices;
    }

    public static function type(){
        return DB::table('salon_itemtype')->select('typeid','typename')->get();
    }
}
