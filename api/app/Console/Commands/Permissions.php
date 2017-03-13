<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Permission;
use DB;

class Permissions extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'pull [export from databases] push [import to databases]';

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
        $do = $this->choice("what to do ? \n [pull] for export from databases\n [push] for import to databases", [
            'pull',
            'push'
        ]);
        $data = var_export(call_user_func([
            $this,
            $do
        ]), true);
        @file_put_contents(date("YmdHis") . ".log", $data);
    }

    public function pull()
    {
        return self::get_group_all();
    }

    public function push($exist_ids = null, $datas = null, $parent_id = NULL)
    {
        if (empty($exist_ids)) {
            $exist_ids = array_keys(self::get_format_all());
        }
        if (empty($datas)) {
            $datas = self::permissions_data();
        }
        foreach ($datas as $data) {
            $id = $data['id'];
            if (! in_array($id, $exist_ids)) {
                self::add($parent_id, $data);
            }
            if (isset($data['children'])) {
                $this->push($exist_ids, $data['children'], $id);
            }
        }
    }

    public static function add($parent_id, $data)
    {
        if (! is_null($parent_id)) {
            $data['inherit_id'] = $parent_id;
        }
        if(isset($data['children']))
        {
            unset($data['children']);
        }
        $data['created_at'] = $data['updated_at'] = date("Y-m-d H:i:s");
        DB::table('permissions')->insert($data);
    }

    public static function get_format_all()
    {
        $items = Permission::select([
            'id',
            'inherit_id',
            'title',
            'slug',
            'status',
            'description',
            'note',
            'sort',
            'show'
        ])->get()->toArray();
        $res = [];
        foreach ($items as $item) {
            $id = $item['id'];
            $inherit_id = $item['inherit_id'];
            $per = [
                'id' => $item['id'],
                'title' => $item['title'],
                'slug' => $item['slug'],
                'status' => $item['status'],
                'description' => $item['description'],
                'note' => $item['note'],
                'sort' => $item['sort'],
                'show' => $item['show']
            ];
            if (empty($per['title'])) {
                unset($per['title']);
            }
            if (empty($per['note'])) {
                unset($per['note']);
            }
            if (empty($per['description'])) {
                unset($per['description']);
            }
            
            if (! empty($inherit_id)) {
                if (! isset($res[$inherit_id])) {
                    $res[$inherit_id] = [];
                    $res[$inherit_id]['children'] = [
                        $id
                    ];
                } elseif (! isset($res[$inherit_id]['children'])) {
                    $res[$inherit_id]['children'] = [
                        $id
                    ];
                } else {
                    $res[$inherit_id]['children'][] = $id;
                }
            }
            
            if (! isset($res[$id])) {
                $res[$id] = $per;
            } else {
                $res[$id] = array_merge($per, $res[$id]);
            }
        }
        return $res;
    }

    public static function get_group_all()
    {
        $bases = self::get_format_all();
        $all_ids = array_keys($bases);
        $child_ids = [];
        foreach ($bases as $base) {
            if (isset($base['children'])) {
                $child_ids = array_merge($child_ids, $base['children']);
            }
        }
        $root_ids = array_diff($all_ids, $child_ids);
        return self::set_the_children($bases, $root_ids);
    }

    public static function set_the_children($datas, $root_ids)
    {
        $res = [];
        foreach ($root_ids as $root_id) {
            if (isset($datas[$root_id]['children'])) {
                $child_ids = $datas[$root_id]['children'];
                unset($datas[$root_id]['children']);
                $datas[$root_id]['children'] = self::set_the_children($datas, $child_ids);
            }
            $res[$root_id] = $datas[$root_id];
        }
        return $res;
    }

    public static function permissions_data()
    {
        return array(
            49 => array(
                'id' => 49,
                'title' => '系统管理',
                'slug' => '',
                'status' => 1,
                'sort' => 100,
                'show' => 1,
                'children' => array(
                    1 => array(
                        'id' => 1,
                        'title' => '权限管理',
                        'slug' => '',
                        'status' => 1,
                        'description' => 'ryr5',
                        'note' => '5yu',
                        'sort' => 100,
                        'show' => 1,
                        'children' => array(
                            2 => array(
                                'id' => 2,
                                'title' => '用户管理',
                                'slug' => 'user.index',
                                'status' => 1,
                                'description' => '33',
                                'note' => '333',
                                'sort' => 400,
                                'show' => 1,
                                'children' => array(
                                    3 => array(
                                        'id' => 3,
                                        'title' => '查看用户',
                                        'slug' => 'user.show',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 1
                                    ),
                                    4 => array(
                                        'id' => 4,
                                        'title' => '更新用户',
                                        'slug' => 'user.update',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    5 => array(
                                        'id' => 5,
                                        'title' => '新增用户',
                                        'slug' => 'user.create',
                                        'status' => 1,
                                        'description' => '344',
                                        'note' => '34',
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    6 => array(
                                        'id' => 6,
                                        'title' => '导出用户',
                                        'slug' => 'user.export',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    )
                                )
                            ),
                            7 => array(
                                'id' => 7,
                                'title' => '角色管理',
                                'slug' => 'role.index',
                                'status' => 1,
                                'sort' => 300,
                                'show' => 1,
                                'children' => array(
                                    8 => array(
                                        'id' => 8,
                                        'title' => '导出角色',
                                        'slug' => 'role.export',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    9 => array(
                                        'id' => 9,
                                        'title' => '新增角色',
                                        'slug' => 'role.create',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    10 => array(
                                        'id' => 10,
                                        'title' => '查看角色',
                                        'slug' => 'role.show',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    11 => array(
                                        'id' => 11,
                                        'title' => '修改角色',
                                        'slug' => 'role.update',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    )
                                )
                            ),
                            12 => array(
                                'id' => 12,
                                'title' => '权限管理',
                                'slug' => 'permission.index',
                                'status' => 1,
                                'description' => 's',
                                'note' => 's',
                                'sort' => 200,
                                'show' => 1,
                                'children' => array(
                                    13 => array(
                                        'id' => 13,
                                        'title' => '导出权限',
                                        'slug' => 'permission.export',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    14 => array(
                                        'id' => 14,
                                        'title' => '查看权限',
                                        'slug' => 'permission.show',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    15 => array(
                                        'id' => 15,
                                        'title' => '修改权限',
                                        'slug' => 'permission.update',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    )
                                )
                            ),
                            16 => array(
                                'id' => 16,
                                'title' => '日志管理',
                                'slug' => 'log.index',
                                'status' => 1,
                                'sort' => 100,
                                'show' => 1,
                                'children' => array(
                                    17 => array(
                                        'id' => 17,
                                        'title' => '导出日志',
                                        'slug' => 'log.export',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    )
                                )
                            )
                        )
                    )
                )
            ),
            50 => array(
                'id' => 50,
                'title' => '店铺管理',
                'slug' => '',
                'status' => 1,
                'sort' => 200,
                'show' => NULL,
                'children' => array(
                    18 => array(
                        'id' => 18,
                        'title' => '店铺管理',
                        'slug' => '',
                        'status' => 1,
                        'sort' => 300,
                        'show' => 1,
                        'children' => array(
                            24 => array(
                                'id' => 24,
                                'title' => '获取省市区商圈',
                                'slug' => 'salon.getProvinces',
                                'status' => 1,
                                'sort' => 0,
                                'show' => 2
                            ),
                            25 => array(
                                'id' => 25,
                                'title' => '获取省市区',
                                'slug' => 'salon.getBussesName',
                                'status' => 1,
                                'sort' => 0,
                                'show' => 2
                            ),
                            41 => array(
                                'id' => 41,
                                'title' => '店铺帐号',
                                'slug' => 'salonAccount.index',
                                'status' => 1,
                                'sort' => 100,
                                'show' => 1,
                                'children' => array(
                                    46 => array(
                                        'id' => 46,
                                        'title' => '添加账号',
                                        'slug' => 'salonAccount.save',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    47 => array(
                                        'id' => 47,
                                        'title' => '重置密码',
                                        'slug' => 'salonAccount.resetPwd',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    48 => array(
                                        'id' => 48,
                                        'title' => '删除账号',
                                        'slug' => 'salonAccount.del',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    )
                                )
                            ),
                            43 => array(
                                'id' => 43,
                                'title' => '店铺列表',
                                'slug' => 'salon.index',
                                'status' => 1,
                                'sort' => 200,
                                'show' => 1,
                                'children' => array(
                                    19 => array(
                                        'id' => 19,
                                        'title' => '添加店铺',
                                        'slug' => 'salon.save',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    20 => array(
                                        'id' => 20,
                                        'title' => '查看店铺',
                                        'slug' => 'salon.getSalon',
                                        'status' => 1,
                                        'description' => '88999',
                                        'note' => '90',
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    21 => array(
                                        'id' => 21,
                                        'title' => '终止合作',
                                        'slug' => 'salon.endCooperation',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    22 => array(
                                        'id' => 22,
                                        'title' => '删除店铺',
                                        'slug' => 'salon.del',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    23 => array(
                                        'id' => 23,
                                        'title' => '检测店铺编号',
                                        'slug' => 'salon.checkSalonSn',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    44 => array(
                                        'id' => 44,
                                        'title' => '更新店铺',
                                        'slug' => 'salon.update',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 1
                                    ),
                                    51 => array(
                                        'id' => 51,
                                        'title' => '店铺导出',
                                        'slug' => 'salon.export',
                                        'status' => 1,
                                        'sort' => NULL,
                                        'show' => 2
                                    )
                                )
                            )
                        )
                    ),
                    26 => array(
                        'id' => 26,
                        'title' => '商户管理',
                        'slug' => '',
                        'status' => 1,
                        'sort' => 200,
                        'show' => 1,
                        'children' => array(
                            27 => array(
                                'id' => 27,
                                'title' => '添加商户',
                                'slug' => 'merchant.save',
                                'status' => 1,
                                'sort' => 0,
                                'show' => 2
                            ),
                            28 => array(
                                'id' => 28,
                                'title' => '删除商户',
                                'slug' => 'merchant.del',
                                'status' => 1,
                                'sort' => 0,
                                'show' => 2
                            ),
                            29 => array(
                                'id' => 29,
                                'title' => '检测商户编号',
                                'slug' => 'merchant.checkMerchantSn',
                                'status' => 1,
                                'sort' => 0,
                                'show' => 2
                            ),
                            30 => array(
                                'id' => 30,
                                'title' => '查看商户',
                                'slug' => 'merchant.getMerchantList',
                                'status' => 1,
                                'sort' => 0,
                                'show' => 2
                            ),
                            42 => array(
                                'id' => 42,
                                'title' => '商户列表',
                                'slug' => 'merchant.index',
                                'status' => 1,
                                'sort' => 100,
                                'show' => 1
                            ),
                            45 => array(
                                'id' => 45,
                                'title' => '修改商户',
                                'slug' => 'merchant.update',
                                'status' => 1,
                                'sort' => 0,
                                'show' => 2
                            ),
                            52 => array(
                                'id' => 52,
                                'title' => '商户导出',
                                'slug' => 'merchant.export',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            )
                        )
                    ),
                    31 => array(
                        'id' => 31,
                        'title' => '店铺结算',
                        'slug' => '',
                        'status' => 1,
                        'sort' => 200,
                        'show' => 1,
                        'children' => array(
                            32 => array(
                                'id' => 32,
                                'title' => '转付单',
                                'slug' => 'shop_count.index',
                                'status' => 1,
                                'sort' => 300,
                                'show' => 1,
                                'children' => array(
                                    33 => array(
                                        'id' => 33,
                                        'title' => '查看转付单',
                                        'slug' => 'shop_count.show',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    34 => array(
                                        'id' => 34,
                                        'title' => '更新转付单',
                                        'slug' => 'shop_count.update',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    35 => array(
                                        'id' => 35,
                                        'title' => '预览转付单',
                                        'slug' => 'shop_count.preview',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    36 => array(
                                        'id' => 36,
                                        'title' => '新增转付单',
                                        'slug' => 'shop_count.create',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    37 => array(
                                        'id' => 37,
                                        'title' => '删除转付单',
                                        'slug' => 'shop_count.destroy',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    ),
                                    53 => array(
                                        'id' => 53,
                                        'title' => '转付单导出',
                                        'slug' => 'shop_count.export',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    )
                                )
                            ),
                            38 => array(
                                'id' => 38,
                                'title' => '代收单',
                                'slug' => 'shop_count.delegate_list',
                                'status' => 1,
                                'sort' => 200,
                                'show' => 1,
                                'children' => array(
                                    39 => array(
                                        'id' => 39,
                                        'title' => '查看代收单',
                                        'slug' => 'shop_count.delegate_detail',
                                        'status' => 1,
                                        'sort' => 0,
                                        'show' => 2
                                    )
                                )
                            ),
                            40 => array(
                                'id' => 40,
                                'title' => '往来余额',
                                'slug' => 'shop_count.balance',
                                'status' => 1,
                                'sort' => 100,
                                'show' => 1
                            ),
                            54 => array(
                                'id' => 54,
                                'title' => '代收单导出',
                                'slug' => 'shop_count.delegate_export',
                                'status' => 1,
                                'sort' => 0,
                                'show' => 2
                            ),
                            55 => array(
                                'id' => 55,
                                'title' => '往来余额导出',
                                'slug' => 'shop_count.balance_export',
                                'status' => 1,
                                'sort' => 0,
                                'show' => 2
                            )
                        )
                    )
                )
            ),
            56 => array(
                'id' => 56,
                'title' => '财务管理',
                'slug' => '',
                'status' => 1,
                'sort' => NULL,
                'show' => 1,
                'children' => array(
                    57 => array(
                        'id' => 57,
                        'title' => '收入管理',
                        'slug' => '',
                        'status' => 1,
                        'sort' => NULL,
                        'show' => 1,
                        'children' => array(
                            58 => array(
                                'id' => 58,
                                'title' => '佣金单',
                                'slug' => 'commission.index',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 1,
                                'children' => array(
                                    75 => array(
                                        'id' => 75,
                                        'title' => '导出佣金单',
                                        'slug' => 'commission.export',
                                        'status' => 1,
                                        'sort' => NULL,
                                        'show' => 2
                                    ),
                                    76 => array(
                                        'id' => 76,
                                        'title' => '查看佣金单',
                                        'slug' => 'commission.show',
                                        'status' => 1,
                                        'sort' => NULL,
                                        'show' => 2
                                    )
                                )
                            ),
                            59 => array(
                                'id' => 59,
                                'title' => '返佣单',
                                'slug' => 'rebate.index',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 1,
                                'children' => array(
                                    64 => array(
                                        'id' => 64,
                                        'title' => '创建返佣单',
                                        'slug' => 'rebate.create',
                                        'status' => 1,
                                        'sort' => NULL,
                                        'show' => 2
                                    ),
                                    66 => array(
                                        'id' => 66,
                                        'title' => '查看返佣单',
                                        'slug' => 'rebate.show',
                                        'status' => 1,
                                        'sort' => NULL,
                                        'show' => 2
                                    ),
                                    68 => array(
                                        'id' => 68,
                                        'title' => '导出返佣单',
                                        'slug' => 'rebate.export',
                                        'status' => 1,
                                        'sort' => NULL,
                                        'show' => 2
                                    ),
                                    70 => array(
                                        'id' => 70,
                                        'title' => '确认返佣单',
                                        'slug' => 'rebate.confirm',
                                        'status' => 1,
                                        'sort' => NULL,
                                        'show' => 2
                                    ),
                                    73 => array(
                                        'id' => 73,
                                        'title' => '上传返佣单',
                                        'slug' => 'rebate.upload',
                                        'status' => 1,
                                        'sort' => NULL,
                                        'show' => 2
                                    ),
                                    74 => array(
                                        'id' => 74,
                                        'title' => '删除返佣单',
                                        'slug' => 'rebate.destroy',
                                        'status' => 1,
                                        'sort' => NULL,
                                        'show' => 2
                                    )
                                )
                            )
                        )
                    ),
                    60 => array(
                        'id' => 60,
                        'title' => '收款管理',
                        'slug' => '',
                        'status' => 1,
                        'sort' => NULL,
                        'show' => 1,
                        'children' => array(
                            61 => array(
                                'id' => 61,
                                'title' => '收款列表',
                                'slug' => 'receivables.index',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            63 => array(
                                'id' => 63,
                                'title' => '添加收款',
                                'slug' => 'receivables.save',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            65 => array(
                                'id' => 65,
                                'title' => '修改收款',
                                'slug' => 'receivables.update',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            67 => array(
                                'id' => 67,
                                'title' => '确认收款',
                                'slug' => 'receivables.confirmAct',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            69 => array(
                                'id' => 69,
                                'title' => '收款导出',
                                'slug' => 'receivables.export',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            71 => array(
                                'id' => 71,
                                'title' => '收款详情',
                                'slug' => 'receivables.getone',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            72 => array(
                                'id' => 72,
                                'title' => '删除收款',
                                'slug' => 'receivables.del',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            )
                        )
                    ),
                    82 => array(
                        'id' => 82,
                        'title' => '付款管理',
                        'slug' => '',
                        'status' => 1,
                        'sort' => NULL,
                        'show' => 1,
                        'children' => array(
                            83 => array(
                                'id' => 83,
                                'title' => '付款列表',
                                'slug' => 'pay_manage.index',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 1
                            ),
                            84 => array(
                                'id' => 84,
                                'title' => '查看付款详情',
                                'slug' => 'pay_manage.show',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            85 => array(
                                'id' => 85,
                                'title' => '新增付款单',
                                'slug' => 'pay_manage.create',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            86 => array(
                                'id' => 86,
                                'title' => '修改付款单',
                                'slug' => 'pay_manage.update',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            87 => array(
                                'id' => 87,
                                'title' => '删除付款单',
                                'slug' => 'pay_manage.destroy',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            88 => array(
                                'id' => 88,
                                'title' => '付款单审批',
                                'slug' => 'pay_manage.check',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            89 => array(
                                'id' => 89,
                                'title' => '付款单确认',
                                'slug' => 'pay_manage.confirm',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            90 => array(
                                'id' => 90,
                                'title' => '付款单导出',
                                'slug' => 'pay_manage.export',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 2
                            ),
                            91 => array(
                                'id' => 91,
                                'title' => '付款单审批列表',
                                'slug' => 'pay_manage.check_list',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 1
                            ),
                            92 => array(
                                'id' => 92,
                                'title' => '付款单确认列表',
                                'slug' => 'pay_manage.confirm_list',
                                'status' => 1,
                                'sort' => NULL,
                                'show' => 1
                            ),
                        )
                    )
                )
            )
        );
    }
}
