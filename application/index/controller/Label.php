<?php
namespace app\index\controller;

class Label extends Common
{
	/**
	 * 检查标签名称是否重复
	 * @param  string $labelName 标签名称
	 * @return object            检查结果
	 */
	public function checkRepeat($labelName)
	{
		$result = model('Label')->where('name',$labelName)->find();
		if($result)
			$this->error();
		else
			$this->success();
	}

	/**
	 * 添加标签
	 * @param string $labelName 标签名称
	 */
	public function add($labelName)
	{
		$member = session('member');
		$data = array(
			'name'	=>	$labelName,
			'author'=>	$member['EMP_NO']
		);
		$result = model('Label')->save($data);
		$data['id'] = model('Label')->id;
		if($result)
			$this->success('','',$data);
		else
			$this->error();
	}

	/**
	 * 获取标签列表
	 * @return array 标签列表
	 */
	public function getLabelList()
	{
		$list = model('Label')->where('status',1)->order('id')->select();
		if($list)
			$this->success('','',$list);
		else
			$this->error();
	}

	/**
	 * 编辑标签功能中获取标签信息
	 * @param  int $lid biaoqian Id
	 * @return array      标签信息
	 */
	public function edit($lid)
	{
		$info = model('Label')->where('id',$lid)->find();
		if($info)
			$this->success('','',$info);
		else
			$this->error();
	}

	/**
	 * 保存修改
	 * @param  int $lid       标签Id
	 * @param  string $labelName 标签名称
	 * @return array            修改结果
	 */
	public function saveEdit($lid, $labelName)
	{
		$result = model('Label')->save(['name'=>$labelName], ['id'=>$lid]);
		if($result !== false)
			$this->success();
		else
			$this->error();
	}

	/**
	 * 删除标签
	 * @param  int $lid 标签Id
	 * @return array      删除结果
	 */
	public function del($lid)
	{
		$result = model('Label')->where('id',$lid)->delete();
		if($result)
			$this->success();
		else
			$this->error();
	}

	/**
	 * 获取标签内容
	 * @param  int $lid 标签Id
	 * @return array      标签内容
	 */
	public function getLabelValue($lid)
	{
		$result = model('LabelValue')->where(['lid'=>$lid, 'status'=>1])->select();
		foreach ($result as $key => $value)
		{
			switch ($value['type'])
			{
				case 'person':
					$name = model('UserEmp')->getUserRealName($value['value']);
					$type = '成员';
					break;
				case 'comp':
					$name = model('OrgComp')->getCompName($value['value']);
					$type = '公司';
					break;
				case 'dept':
					$name = model('OrgDept')->getName($value['value']);
					$type = '部门';
					break;
				case 'clique':
					$name = '北京地铁集团';
					$type = '集团';
					break;
				default:
					$name = '';
					break;
			}
			$result[$key]['name'] = $name;
			$result[$key]['type'] = $type;
		}

		if($result)
			$this->success('', '', $result);
		else
			$this->error();
	}
}