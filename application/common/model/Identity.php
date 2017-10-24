<?php
namespace app\common\model;
use think\Db;
use think\Model;

class Identity extends Model
{
    public function getIdentity($empNo){
        //获取当前用户的标签
        $labelIds = Model('LabelValue')->getIdsByEmpNo($empNo);

        //获取当前用户所在的组织
        $deptIds = Model('UserEmp')->getDeptNosByEmpNo($empNo);
        $compNo = getCompNo($empNo);
        $list = $this->
            whereor(function ($query) use ($labelIds){
                $query->where([
                    'type'  =>  'TAG',
                    'value'   =>  ['in',$labelIds],
                    'status'    =>  1
                ]);
            })
            ->whereor(function ($query) use ($empNo){
                $query->where([
                    'type'  =>  'PERSON',
                    'value'   =>  $empNo,
                    'status'    =>  1
                ]);
            })
            ->whereor(function ($query) use ($deptIds){
                $query->where([
                    'type'  =>  'DEPT',
                    'value'   =>  ['in',$deptIds],
                    'status'    =>  1
                ]);
            })
            ->whereor(function ($query) use ($compNo){
                $query->where([
                    'type'  =>  'COMP',
                    'value'   =>  $compNo,
                    'status'    =>  1
                ]);
            })
            ->group('identity')
            ->column('identity');
        return $list;
    }
}