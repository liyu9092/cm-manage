<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class SalonItemFormats extends  Model
{
    protected $table = 'salon_item_formats';
    
    public $timestamps = false;
    
    
    public static function getItemsByNormscatids($norms_cat_ids)
    {        
        $norms_cat_ids = array_map('intval', $norms_cat_ids);
        $norms_cat_ids = array_unique(array_filter($norms_cat_ids));
        if(count($norms_cat_ids)<1)
        {
            return [];
        }
        
        $salon_norms_items = SalonNorms::whereIn('salon_norms_cat_id',$norms_cat_ids)->select(['salon_norms_cat_id','salon_item_format_id'])->groupBy('salon_norms_cat_id')->orderBy('salon_norms_id')->get()->toArray();
        $salon_norms_item_idx = [];
        $all_salon_item_format_ids = [];
        foreach ($salon_norms_items as $tmp_item)
        {
            $salon_norms_cat_id = $tmp_item['salon_norms_cat_id'];
            $salon_item_format_ids = array_map('intval',explode(',', $tmp_item['salon_item_format_id']));
            $salon_norms_item_idx[$salon_norms_cat_id] = $salon_item_format_ids;
            $all_salon_item_format_ids = array_merge($all_salon_item_format_ids,$salon_item_format_ids);
        }
        $all_salon_item_format_ids = array_unique($all_salon_item_format_ids);
        
        $salon_item_format_res = SalonItemFormat::whereIn('salon_item_format_id',$all_salon_item_format_ids)->select(['salon_item_format_id','salon_item_formats_id'])->get()->toArray();
        
        $salon_item_formats_ids = array_column($salon_item_format_res, 'salon_item_formats_id');
        
        $salon_item_formats_ids = array_unique($salon_item_formats_ids);
        
        $salon_item_formats_arr = SalonItemFormats::whereIn('salon_item_formats_id',$salon_item_formats_ids)->lists('formats_name','salon_item_formats_id');
        
        $salon_item_format_idx = [];
        foreach($salon_item_format_res as $format)
        {
            $format_id = $format['salon_item_format_id'];
            $formats_id = $format['salon_item_formats_id'];
            if(isset($salon_item_formats_arr[$formats_id]))
            {
                $salon_item_format_idx[$format_id] = $salon_item_formats_arr[$formats_id];
            }
        }
        unset($salon_item_formats_ids,$all_salon_item_format_ids,$salon_norms_items,$salon_item_format_res,$salon_item_formats_arr);
        
        $res = [];
        foreach ($salon_norms_item_idx as $cat_id => $format_ids)
        {
            $format_infos = [];
            foreach ($format_ids as $format_id)
            {
                if(isset($salon_item_format_idx[$format_id]))
                {
                    $format_infos[] = $salon_item_format_idx[$format_id];
                }
            }
            $res[$cat_id] = $format_infos;
        }
        return $res;
    }
}