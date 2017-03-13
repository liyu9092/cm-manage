<?php  namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use DB;

class ImageStyle  extends Model{
   
    protected $table = 'style_img';
    protected $fillable = ['id', 'style', 'length','curl','color','img','status'];
    public $timestamps = false;

    public static function getAllImage($param){
        
    	$query = Self::getQuery();
        
        if(!empty($param['style'])){
	        $query = $query->where('style','=',$param['style']);
	}
        if(!empty($param['length'])){
	        $query = $query->where('length','=',$param['length']);
	}
	 if(!empty($param['curl'])){
	        $query = $query->where('curl','=',$param['curl']);
	}
         if(!empty($param['color'])){
	        $query = $query->where('color','=',$param['color']);
	}
         if(!empty($param['img'])){
	        $query = $query->where('img','=',$param['img']);
	}
        $query = $query->where('status','=',1);  
        $query = $query->orderBy('id','desc');  
        $page = isset($param['page'])?max($param['page'],1):1;
	$page_size = isset($param['page_size'])?$param['page_size']:20;
           //手动设置页数
      AbstractPaginator::currentPageResolver(function() use ($page) {
  	    return $page;
  	  });

      $fields = ['id', 'style', 'length','curl','color','img'];  
      $result = $query->select($fields)->paginate($page_size)->toArray();
     
      unset($result['next_page_url']);
      unset($result['prev_page_url']);
      return $result;
    }

}
