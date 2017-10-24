<?php
namespace app\common\model;
use think\Db;
use think\Model;

class MenuAuthority extends Model
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
     * 检查是否有写的权限
     */
    public function checkWriteAuthority($empNo,$pid){
        //获取当前用户的标签
        $labelIds = Model('LabelValue')->getIdsByEmpNo($empNo);

        //获取当前用户所在的组织
        $deptIds = Model('UserEmp')->getDeptNosByEmpNo($empNo);
        $compNo = getCompNo($empNo);
        return $this->
            whereor(function ($query) use ($pid,$labelIds){
                $query->where([
                    'mId'=>$pid,
                    'type'  =>  'TAG',
                    'value'   =>  ['in',$labelIds],
                    'status'    =>  1
                ]);
            })
            ->whereor(function ($query) use ($pid,$empNo){
                $query->where([
                    'categoryId'=>$pid,
                    'type'  =>  'PERSON',
                    'value'   =>  $empNo,
                    'status'    =>  1
                ]);
            })
            ->whereor(function ($query) use ($pid,$deptIds){
                $query->where([
                    'categoryId'=>$pid,
                    'type'  =>  'DEPT',
                    'value'   =>  ['in',$deptIds],
                    'status'    =>  1
                ]);
            })
            ->whereor(function ($query) use ($pid,$compNo){
                $query->where([
                    'categoryId'=>$pid,
                    'type'  =>  'COMP',
                    'value'   =>  $compNo,
                    'status'    =>  1
                ]);
            })
            ->count();

    }
}