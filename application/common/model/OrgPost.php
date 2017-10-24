<?php
namespace app\common\model;
use think\Model;
use think\db;

class OrgPost extends Model
{
	// 设置当前模型对应的完整数据表名称
	protected $table = 'HR_ORG_POST';
	// 设置当前模型的数据库连接
	protected $connection = "hr";
    /**
     * 功能：成员所属的岗位
     * @param $value 为用户所在的岗位POSTNO
     * return  返回用户所在的组织
     */
    public function getPost($value)
    {
        $info = Model('OrgPOST')->where(array('POSTNO'=>$value))->find();
        if(!$info){
            return false;
        }
        $str = $info['POSTNAME'];
        return $str;

    }
}