<?php
namespace app\common\model;
use think\Db;
use think\Model;

class ParticipateComp extends Model
{
    /*
     * 公司是否参与
     */
    public function isParticipate($compNo){
        return $this->where(['compNo'=>$compNo,'status'=>1])->find();
    }

    public function getInfo($compNo) {
        return $this->where(['compNo'=>$compNo,'status'=>1])->find();
    }
}