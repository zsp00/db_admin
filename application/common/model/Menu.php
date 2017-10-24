<?php
namespace app\common\model;
use think\Db;
use think\Model;

class Menu extends Model
{
    protected $createTime = 'createTime';
    protected $updateTime = 'updateTime';

    protected $auto = [];
    protected $insert = [
        'status'=>1
    ];
    protected $update = [];

    protected $type = [
        'createTime' =>  'timestamp',
        'updateTime' =>  'timestamp'
    ];
    /*
     * 获取当前用户有哪几个大分类
     */
    public function getListByEmpNo($empNo){
        //获取当前用户的标签
        $labelIds = Model('LabelValue')->getIdsByEmpNo($empNo);

        //获取当前用户所在的组织
        $deptIds = Model('UserEmp')->getDeptNosByEmpNo($empNo);
        $compNo = getCompNo($empNo);

        $menuIds = Model('MenuAuthority')
            ->whereor(function ($query) use ($labelIds){
                $query->where([
                    'type'  =>  'tag',
                    'value'   =>  ['in',$labelIds],
                    'status'    =>  1
                ]);
            })
            ->whereor(function ($query) use ($empNo){
                $query->where([
                    'type'  =>  'person',
                    'value'   =>  $empNo,
                    'status'    =>  1
                ]);
            })
            ->whereor(function ($query) use ($deptIds){
                $query->where([
                    'type'  =>  'dept',
                    'value'   =>  ['in',$deptIds],
                    'status'    =>  1
                ]);
            })
            ->whereor(function ($query) use ($compNo){
                $query->where([
                    'type'  =>  'comp',
                    'value'   =>  $compNo,
                    'status'    =>  1
                ]);
            })
            ->whereor(function ($query){
                $query->where([
                    'type'  =>  'clique',
                    'status'    =>  1
                ]);
            })
            ->group('mId')
            ->column('mId');

            $result = $this
                ->where(function ($query) use ($menuIds){
                    $query->where([
                        'id'=>['in',$menuIds],
                        'status'    =>  1
                    ]);
                })
                ->order('sort asc')
                ->select();


        return $result;
    }

    public function toTree($list){
        $tree = [];
        foreach($list as $k => $v){
            if($v['pId'] === 0){
                $list[$k]['child'] = $this->childs($v['id'],$list);
            }
        }
        foreach ($list as $k=>$v){
            if($v['pId'] === 0){
                $tree[] = $v;
            }
        }
        return $tree;
    }

    public function childs($id,$list){
        $result = [];
        foreach ($list as $k=>$v){
            if($v['pId'] === $id){
                $result[] = $v;
            }
        }
        return $result;
    }

    /*
     * 转化为二级分类
     */
    public function toSubmenu($list){
        $group = [];
        foreach($list as $k=>$v){
            if(!isset($group[$v['group']])) {
                $group[$v['group']] = [
                    'name'  =>  $v['group'],
                    'child' =>  []
                ];
            }
            $group[$v['group']]['child'][] = $v;
        }
        $result = [];
        foreach($group as $v){
            $result[] = $v;
        }
        return $result;
    }
}