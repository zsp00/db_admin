<?php
namespace app\index\controller;
use app\common\model\Identity;
/**
 * Created by PhpStorm.
 * User: TT
 * Date: 2017/9/22
 * Time: 17:08
 */
class Menu extends Common{
    
    public function getMenus(){
        $userInfo = getUserInfo();
        $list = Model('Menu')->getListByEmpNo($userInfo['EMP_NO']);
        $list = Model('Menu')->toTree($list);
        if($list){
            $this->success($list);
        }else{
            $this->error('您无权访问此系统');
        }
    }

    //获取菜单列表
    public function getMenuList()
    {
        $userInfo = getUserInfo();
        $list = Model('Menu')->select();
        $list = Model('Menu')->toTree($list);

        if($list){
            $this->success($list);
        }else{
            $this->error('您无权访问此系统');
        }
    }

    //添加菜单
	public function checkRepeat($menuName)
    {
        $result = model('Menu')->where('name',$menuName)->find();
        if($result){
            $this->error();
        }else{
            $this->success();
        }
    }
    public function addMenu($MenuInfo)
    {
        if(empty($MenuInfo)){
            $this->error('请填写菜单信息！');
        }
        $userInfo = getUserInfo();
        $Identity = new Identity();
        $identitys = $Identity->getIdentity($userInfo['EMP_NO']);
        if(!in_array('2',$identitys)){
            $this->error('您没有添加的权限！');
        }
        $MenuInfo['rootId'] = 0;
        $result = Model('Menu')->save($MenuInfo);
        if($result){
            $this->success('添加菜单成功!');
        }else{
            $this->error('添加菜单失败！');
        }
    }
    //编辑菜单
    public function editMenuId($MenuId)
    {
        $result = Model('Menu')->where(['id' => $MenuId])->find();
        $this->success($result);
    }    
    public function editMenu($Menu)
    {
        $MenuInfo =[
            'pId' => $Menu['pId'],
            'name' => $Menu['name'],
            'icon' => $Menu['icon'],
            'router' => $Menu['router'],
            'group' => $Menu['group'],
            'sort' => $Menu['sort'],
        ];
        $result = Model('Menu')->where(['id' => $Menu['id']])->update($MenuInfo);
        if($result){
            $this->success('修改成功');
        }
    }

    //删除菜单
    public function delMenu($MenuId)
    {
        if(empty($MenuId)){
            $this->error('请选择删除菜单！');
        }
        $MenuChild = Model('Menu')->where(['pId' => $MenuId])->find();
        if($MenuChild){
            $this->error('该菜单下面有子菜单请先删除！');
        }
        $result = Model('Menu')->where(['id' => $MenuId])->delete();
        if($result){
            $this->success('删除菜单成功!');
        }else{
            $this->error('删除菜单失败！');
        }
    }
	
	//菜单启用
	public function enable($id)
	{
		if(empty($id)){
			$this->error('请选择数据');
		}
		foreach($id as $k=>$v){
			Model('Menu')->where(['id' => $v['id']])->update(['status' => '1']);
		}
		$this->success('启用成功');
	}
	
	//菜单禁用
	public function disable($id)
	{
		if(empty($id)){
			$this->error('请选择数据');
		}
		foreach($id as $k=>$v){
			Model('Menu')->where(['id' => $v['id']])->update(['status' => '0']);
		}
		$this->success('禁用成功');
	}	
	
	
	
	
	
	
	
	
	
	
	
}