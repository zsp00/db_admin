<?php
namespace app\index\controller;
use think\Db;

class Process extends Common
{
	/**
	 * 获取公司以及部门Id及名称
	 * @return array 公司及部门列表，格式[1010:[name:'运一', dept:[[deptNo:010101, deptName: '5号线乘务中心'], ...]], ...]
	 */
	public function getCompDept()
	{
		$compList = model('OrgComp')->field('COMP_NO, COMP_NAME')->where('COMP_NO', '<>', 0)->order('COMP_NO')->select();
		$deptList = model('OrgDept')->field('DEPT_NO, DEPT_NAME, COMP_NO')->where(['LEVEL'=>3, 'ISSEALUP'=>0, 'COMP_NO'=>['<>', 0]])->order('COMP_NO, DISP_SN')->select();
		$result = array();

		foreach ($compList as $k => $v)
		{
			$result[$v['COMP_NO']] = array(
				'name'	=>	$v['COMP_NAME'],
				'dept'	=>	array()
			);
		}

		foreach ($deptList as $k => $v)
		{
			$result[$v['COMP_NO']]['dept'][] = array(
				'deptNo'	=>	$v['DEPT_NO'],
				'deptName'	=>	$v['DEPT_NAME']
			);
		}

		if (!is_null($result))
			$this->success('', '', $result);
		else
			$this->error();
	}

	/**
	 * 新增流程
	 * @param array $data 流程数据
	 * @return array 添加结果
	 */
	public function addProcess($data)
	{
		$member = session('member');
		unset($data['process'][0]);

		$insertProcess = array(
			'name'			=>	trim($data['name']),
			'deptNo'		=>	$data['deptValue'],
			'level'			=>	count($data['process']),
			'creator'		=>	$member['EMP_NO'],
			'lastModifier'	=>	$member['EMP_NO']
		);
		Db::startTrans();
		$resProcess = model('Process')->save($insertProcess);
		if ($resProcess)
		{
			$pId = model('Process')->id;
			$insertStep = array();
			foreach ($data['process'] as $k => $v)
			{
				$audit = array(
					'dept'	=>	$v['auditor'],
					'notIn'	=>	$v['notIn']
				);
				$insertStep[] = array(
					'pId'		=>	$pId,
					'levelNo'	=>	$k,
					'audit_user'=>	json_encode($audit),
					'pDescribe'	=>	strip_tags(trim($v['describe']))
				);
			}

			$res = model('ProcessData')->isUpdate(false)->saveAll($insertStep);
			if ($res)
			{
				Db::commit(); 
				$this->success();
			}
			else
			{
				Db::rollback();
				$this->error('添加流程步骤失败');
			}
		}
		else
		{
			Db::rollback();
			$this->error('添加流程主体失败！');
		}
	}

	/**
	 * 获取流程列表
	 * @return array 流程列表信息
	 */
	public function getProcessList()
	{
		$result = model('Process')->where('status', 1)->select();
		if ($result)
		{
			foreach ($result as $k => $v)
			{
				$result[$k]['dept'] = model('OrgDept')->getNameList($v['deptNo']);
				$result[$k]['creator'] = model('UserEmp')->getUserRealName($v['creator']);
				$result[$k]['lastModifier'] = model('UserEmp')->getUserRealName($v['lastModifier']);
			}
			$this->success('', '', $result);
		}
		else
			$this->error();
	}
}