<?php
namespace app\common\model;
use think\Model;
use think\db;

class OrgDept extends Model
{
	// 设置当前模型对应的完整数据表名称
	protected $table = 'HR_ORG_DEPT';
	// 设置当前模型的数据库连接
	protected $connection = "hr";

	public function getCompLevel3s($compNo){
		$map = [
			'COMP_NO'=>$compNo,
			'LEVEL'=>3,
			'ISSEALUP'=>0
		];
		return $this->where($map)->order('DISP_SN asc')->select();
	}
	public function getCompLevel3sCount($compNo){
		$map = [
			'COMP_NO'=>$compNo,
			'LEVEL'=>3,
			'ISSEALUP'=>0
		];
		return $this->where($map)->order('DISP_SN asc')->count();
	}

	public function getDeptChild($deptNo){
		$map = [
			'PARENT_DEPT_NO'=>$deptNo,
			'ISSEALUP'=>0
		];
		return $this->where($map)->order('DISP_SN asc')->select();
	}

	public function toSelect2($data){
		$result = [];

		foreach($data as $k=>$v){
			$result[$k] = [
				'id'	=>	$v['DEPT_NO'],
				'text'	=>	$v['DEPT_NAME']
			];
		}
		return $result;
	}


	public function toJstree($data,$person=true){
		$result = [];

		foreach($data as $k=>$v){
			$count1 = $this->where(['PARENT_DEPT_NO'=>$v['DEPT_NO'],'ISSEALUP'=>0])->count();
			if($person){
				$count2 = Model('UserEmp')->getUserCountByDeptNo($v['DEPT_NO']);
			}else{
				$count2 = 0;
			}
			$result[$k] = [
				'id'	=>	$v['DEPT_NO'],
				'text'	=>	$v['DEPT_NAME'],
				'children'	=> $count2+$count1>0?true:false,
				'icon'	=>	'icon_folder_blue',
				'data'	=>	['type'=>'DEPT']
			];
		}
		return $result;
	}
	
	public function toFormat($data){
		$result = [];

		foreach($data as $k=>$v){
			$result[$k] = [
				'id'	=>	$v['DEPT_NO'],
				'text'	=>	$v['DEPT_NAME'],
				'icon'	=>	'icon_folder_blue',
				'data'	=>	['type'=>'DEPT']
			];
		}
		return $result;
	}

	/**
	 * 功能：成员向上查询所属组织和公司
	 * @param $value 为用户所在的组织DEPTNO
	 * return  返回用户所在的组织或公司的名字
	 */
	public function getNameList($value)
	{
		$info = Model('OrgDept')->where(array('DEPT_NO'=>$value))->find();
		$str = $info['DEPT_NAME'];
		if(!$info){
			return false;
		}
        if(model('OrgDept')->where('DEPT_NO',$info['PARENT_DEPT_NO'])->find())
		{
			return $this->getNameList($info['PARENT_DEPT_NO']).'/'.$str;
		} else {
			$compname =  Model('OrgComp')->where(array('COMP_NO'=>$info['COMP_NO']))->find();
			$compname = $compname['COMP_NAME'];
			return $compname.'/'.$str;
		}
	}
    /**
     * 功能：成员向上查询所属组织和公司
     * @param $value 为用户所在的组织DEPTNO
     * return  返回用户所在的组织或公司的id
     */
	public function getParentIds($value)
	{
		$info = Model('OrgDept')->where(array('DEPT_NO'=>$value))->find();
		$id[] = $info['DEPT_NO'];
		if(!$info){
			return false;
		}
		$result = $this->getParentIds($info['PARENT_DEPT_NO']);
		if($result){
			return array_merge($result,$id);
		}else{
			return $id;
		}

	}

	//根据组织id查询在组织的名字
	public function getName($value)
    {
		$info = Model('OrgDept')->where(array('DEPT_NO'=>$value))->find();
		if(!$info){
			return false;
		}else{
			return $info['DEPT_NAME'];
		}
	}

	public function getDeptNo($deptNo){
		$deptInfo = $this->where(['DEPT_NO'=>$deptNo])->find();
		if($deptInfo['LEVEL'] === 3){
			return $deptNo;
		}else if($deptInfo['LEVEL'] === 4){
			return $deptInfo['PARENT_DEPT_NO'];
		}
	}
    //根据组织id查询所在公司的id
	public function getCompNo($deptNo)
    {
        $deptNo = $this->getDeptNo($deptNo);
        $compNo = $this->where(['DEPT_NO'=>$deptNo])->value('COMP_NO');
        return $compNo;
    }

}