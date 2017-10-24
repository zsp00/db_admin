<?php
namespace app\common\model;
use think\Db;
use think\Model;

class LabelValue extends Model
{

    //根据人获取这个人所有的标签
    public function getIdsByEmpNo($empNo){
        $compNo = getCompNo();
        $typeEmpList = $this->where([
            'value' =>  $empNo,
            'status'    =>  1,
            'type'  =>  'person'
        ])->group('lId')->column('lId');
        $typeDeptList = $this->where([
            'value' =>  ['in',Model('UserEmp')->getDeptNosByEmpNo($empNo)],
            'status'    =>  1,
            'type'  =>  'dept'
        ])->group('lId')->column('lId');
        $typeCompList = $this->where([
            'value' =>  $compNo,
            'status'    =>  1,
            'type'  =>  'comp'
        ])->group('lId')->column('lId');
        $typeCliqueList = $this->where([
            'status'    =>  1,
            'type'  =>  'clique'
        ])->group('lId')->column('lId');
        return array_merge($typeEmpList,$typeDeptList,$typeCompList,$typeCliqueList);
    }
}