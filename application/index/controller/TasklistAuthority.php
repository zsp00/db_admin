<?php
namespace app\index\controller;
use think\Db;

class TasklistAuthority extends Common
{
	/**
	 * 保存查看全部列表权限的人员Id
	 * @param  string $empNo 人员Id字符串
	 * @return array        保存结果
	 */
	public function save($empNo)
	{
		$newNo = explode(',', $empNo);
		$oldNo = model('TasklistAuthority')->column('value');

		$insert = array_diff($newNo, $oldNo);
		$delete = array_diff($oldNo, $newNo);


		foreach ($insert as $k => $v)
		{
			if (trim($v) == '')
				continue;
			$value = trim($v);
			unset($insert[$k]);

			$insert[$k]['type'] = 'person';
			$insert[$k]['value'] = $value;
		}
		$delete = implode(',', $delete);

		Db::startTrans();
		$resIns = model('TasklistAuthority')->saveAll($insert);
		$resDel = model('TasklistAuthority')->where(['value'=>['in', $delete]])->delete();

		if ($resIns !== false && $resDel !== false)
		{
			Db::commit(); 
			$this->success();
		}
		else
		{
			Db::rollback(); 
			$this->error();
		}
	}

	/**
	 * 获取有权限人员id集合
	 * @return string id集合
	 */
	public function getAuthorityEmpList()
	{
		$result = model('TasklistAuthority')->column('value');
		if ($result)
			$this->success('', '', implode(',', $result));
		else
			$this->error();
	}
}