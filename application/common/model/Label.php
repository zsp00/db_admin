<?php
namespace app\common\model;
use think\Db;
use think\Model;

class Label extends Model
{
    protected $createTime = 'createTime';
    protected $updateTime = 'updateTime';

    protected $auto = [];
    protected $insert = [
        'status'=>1
    ];
    protected $update = [];

    public function getLabelName($type,$value)
    {
        switch($type)
        {
            case 'CLIQUE':
                return '北京地铁';
                break;
            case 'COMP':
                return Model('OrgComp')->where(['COMP_NO'=>$value])->value('COMP_NAME');
                break;
            case 'DEPT':
                return Model('OrgDept')->where(['DEPT_NO'=>$value])->value('DEPT_NAME');
                break;
            case 'PERSON':
                return Model('UserEmp')->where(['EMP_NO'=>$value])->value('EMP_NAME');
                break;
        }
    }

}