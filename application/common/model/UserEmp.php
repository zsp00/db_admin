<?php
namespace app\common\model;
use think\Config;
use think\Db;
use think\Model;

class UserEmp extends \app\common\model\User implements UserInterface
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'HR_USER_EMP';
    // 设置当前模型的数据库连接
    protected $connection = "hr";

    protected $noRemember = '7200';
    protected $remember = '2592000';

    public function getUserName($empNo)
    {
        // TODO: Implement getUserName() method.
        return $this->where(['EMP_NO'=>$empNo])->value('EMP_NO');
    }

    //获取用户的真实姓名
    public function getUserRealName($empNo)
    {
        return $this->where(['EMP_NO'=>$empNo])->value('EMP_NAME');
    }

    public function getUserInfo($empNo){
        return $this->where(['EMP_NO'=>$empNo])->find();
    }

    public function exists($id,$field = 'EMPID'){
        $info = Model('UserEmp')->where([$field=>$id])->find();
        if(!$info){
            $this->error = '未在本地系统中找到该用户！(1)';
            return false;
        }else{
            return $info;
        }
    }
    
    //登录
    protected function doLogin($info,$remember=false){
        if($remember){
            $remember = true;
            $cookieTime = $this->remember;
        }else{
            $remember = false;
            $cookieTime = $this->noRemember;
        }
        
        //用户的岗位放到session中
        session('member',$info);
        cookie('member',
            [
                'EMP_NO'  =>  $info['EMP_NO'],
                'EMP_NAME'  =>  $info['EMP_NAME'],
                'F_CELL'  =>  $info['F_CELL'],
                'remember'  =>  $remember
            ],
            ['expire'=>$cookieTime]
        );
        Model('UserExtra')->updateAvatar($info['EMP_NO'],session('personid'));

        return true;
    }


    public function life(){
        $member = cookie('member');
        if($member['remember']){
            $cookieTime = $this->remember;
        }else{
            $cookieTime = $this->noRemember;
        }
        cookie('member',
            [
                'id'  =>  $member['id'],
                'realName'  =>  $member['realName'],
                'phone'  =>  $member['phone'],
                'remember'  =>  $member['remember']
            ],
            ['expire'=>$cookieTime]
        );
    }

    public function logout(){
        cookie('member',null);
        session('member',null);
        return true;
    }

    public function getUserListByDeptNo($deptNo){
        $map = [
            'DEPTNO'   =>  $deptNo,
            'F_EMP_TYPE_ID' =>  ['in',['正式员工','外勤人员']]
        ];
        return $this->where($map)->select();
    }
    public function getUserCountByDeptNo($deptNo){
        $map = [
            'DEPTNO'   =>  $deptNo,
            'F_EMP_TYPE_ID' =>  ['in',['正式员工','外勤人员']]
        ];
        return $this->where($map)->count();
    }

    public function toJstree($data){
        $result = [];

        foreach($data as $k=>$v){
            $result[$k] = [
                'id'	=>	$v['EMP_NO'],
                'text'	=>	$v['EMP_NAME'].$this->starFcell($v['F_CELL']),
                'data'  =>  ['type'=>'PERSON'],
                'children'  =>  false,
                'icon'  =>  'jstree-people-icon'
            ];
        }
        return $result;
    }

    public function toFormat($data){
        $result = [];

        foreach($data as $k=>$v){
            $result[$k] = [
                'id'	=>	$v['EMP_NO'],
                'text'	=>	$v['EMP_NAME'],
                'data'  =>  ['type'=>'PERSON'],
                'icon'  =>  'jstree-people-icon',
                'other'  =>  $this->getDeptName($v['DEPTNO']),
            ];
        }
        return $result;
    }

    public function getDeptName($deptNo){
        return Model('OrgDept')->where(['DEPT_NO'=>$deptNo])->value('DEPT_NAME');
    }
    public function starFcell($fcell){
        if($fcell == ''){
            return '';
        }else{
            return '';
            return '['.substr($fcell, 0, 3).'****'.substr($fcell, 7).']';
        }
    }


    //根据empNo获取所有部门
    public function getDeptNosByEmpNo($empNo){
        $list = [];
        $deptNo = $this->where(['EMP_NO'=>$empNo])->value('DEPTNO');
        return Model('OrgDept')->getParentIds($deptNo);
	}

    public function getAccess($empNo){
        //获取这个人身上的所有标签
        $labelIds = Model('EmpLabelValue')->getIdsByEmpNo($empNo);
        $labelFilter = [];
        foreach ($labelIds as $v){
            $labelFilter[] = [
                [
                    'bool'  =>  [
                        'must'=>[
                            [
                                'term' =>  [
                                    'access.id' =>  $v
                                ]

                            ],
                            [
                                'term' =>  [
                                    'access.type'   =>  'TAG'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }
        //获取这个人所有的组织
        $deptIds = Model('UserEmp')->getDeptNosByEmpNo($empNo);
        $deptFilter = [];
        foreach ($deptIds as $v){
            $deptFilter[] = [
                [
                    'bool'  =>  [
                        'must'=>[
                            [
                                'term' =>  [
                                    'access.id' =>  $v
                                ]

                            ],
                            [
                                'term' =>  [
                                    'access.type'   =>  'DEPT'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        //获取这个人所在的公司
        $compNo = $this->getCompNo($empNo);
        $deptFilter[] = [
            [
                'bool'  =>  [
                    'must'=>[
                        [
                            'term' =>  [
                                'access.id' =>  $compNo
                            ]

                        ],
                        [
                            'term' =>  [
                                'access.type'   =>  'COMP'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        //没有权限的文档
        $deptFilter[] = [
            [
                'bool'  =>  [
                    'must'=>[
                        [
                            'term' =>  [
                                'access.id' =>  'ALL'
                            ]

                        ],
                        [
                            'term' =>  [
                                'access.type'   =>  'ALL'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $accessFilter[] = [
            'bool'  =>  [
                'should'    =>  array_merge(
                    [
                        [
                            'bool'  =>  [
                                'must'=>[
                                    [
                                        'term' =>  [
                                            'access.id' =>  'BJDT'
                                        ]

                                    ],
                                    [
                                        'term' =>  [
                                            'access.type'   =>  'CLIQUE'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    $labelFilter,
                    $deptFilter
                ),
                'minimum_should_match'  =>  1
            ]
        ];
        return $accessFilter;
    }


    public function getCompNo($empNo){
        $info = $this->where(['EMP_NO'=>$empNo])->find();
        if(!$info){
            return 0;
        }else{
            return $info['COMP_NO'];
        }
    }

    public function getWealth($empNo){
        $info = Model('UserExtra')->where(['EMP_NO'=>$empNo])->find();
        if(!$info){
            return 0;
        }else{
            return $info['wealth'];
        }

    }


}
