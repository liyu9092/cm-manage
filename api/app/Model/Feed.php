<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Feed extends Model {

	protected $table = 'feed';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['feed_id', 'user_id', 'content','contact','add_time','is_del','source'];

	public $timestamps = false;

	protected $primaryKey = 'feed_id';

	public static function getQueryByParam($param=[]){
        $query = Self::where('is_del','=',0);

        if(!empty($param['date'])){
        	$start_at = strtotime($param['date']);
            $end_at = strtotime($param['date'])+3600*24;
            $query = $query->whereBetween('add_time',[$start_at,$end_at]);
        }

        if(!empty($param['keyword'])){
            $keyword = '%'.$param['keyword'].'%';
        	$query = $query->where('content','like',$keyword);
        }

        //排序
    	$sort_key = empty($param['sort_key'])?'add_time':$param['sort_key'];
    	$sort_type = empty($param['sort_type'])?'DESC':$param['sort_type'];
        $query = $query->orderBy($sort_key,$sort_type);
 
        return $query;
    }

    public static function getSource($source=0){
    	$source = intval($source);
    	$mapping = [
    		1=>'android',
    		2=>'ios',
    		3=>'微信',
    		4=>'未知'
    	];
    	if(empty($mapping[$source]))
    		return '未知';
    	return $mapping[$source];
    }

}
