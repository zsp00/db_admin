<?php
namespace app\common\model;
use think\Model;
use think\db;

class OrgComp extends Model
{
	// 设置当前模型对应的完整数据表名称
	protected $table = 'HR_ORG_COMP';
	// 设置当前模型的数据库连接
	protected $connection = "hr";

	public function getInfo($COMP_NO){
		return $this->where(['COMP_NO'=>$COMP_NO])->find();
	}
	
	/**
	 * 获取公司名称
	 * @param  int $compNo 公司Id
	 * @return string         公司名称
	 */
	public function getCompName($compNo)
	{
		return $this->where(['COMP_NO'=>$compNo])->value('COMP_NAME');
	}

	public function getList(){
		return $this->select();
	}


	public function toSelect2($data){
		$result = [];

		foreach($data as $k=>$v){
			$result[$k] = [
				'id'	=>	$v['COMP_NO'],
				'text'	=>	$v['COMP_NAME']
			];
		}
		return $result;
	}
	
	public function toJstree($data){
		$result = [];

		foreach($data as $k=>$v){
			$count = Model('OrgDept')->getCompLevel3sCount($v['COMP_NO']);
			$result[$k] = [
				'id'	=>	$v['COMP_NO'],
				'text'	=>	$v['COMP_NAME'],
				'children'	=> $count>0?true:false,
				'data'	=>	['type'=>'COMP'],
				'icon'	=>	'icon_folder_blue'
			];
		}
		return $result;
	}
}