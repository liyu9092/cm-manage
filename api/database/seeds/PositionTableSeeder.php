<?php
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Position;
use App\Department;
use Maatwebsite\Excel\Facades\Excel;
class PositionTableSeeder extends Seeder {

    public function run()
    {
        Excel::load('public/Uploads/20150729/position.xls', function($reader) {
            $results = $reader->get()->toArray();
            $header = array_shift($results);

            foreach ($results as $key => $value) {
                foreach ($header as $k => $v) {
                    if(!empty($value[$k]))
                        $array[$v][] = $value[$k];
                }
            }

            foreach ($array as $key => $value) {
                $department = Department::create(['title'=>$key,'description'=>$key]);
                foreach ($value as $k => $v) {
                    Position::create(['department_id'=>$department->id,'title'=>$v,'description'=>$v]);
                }
            }
        });
    }

}
