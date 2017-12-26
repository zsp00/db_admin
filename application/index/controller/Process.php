<?php
namespace app\index\controller;
use think\Db;

class Process extends Common
{
    /**
     * 获取公司以及部门Id及名称
     * @return array 公司及部门列表，格式[{label:运一, value:1010, children: [{}, {}]}, ...]
     */
    public function getCompDept()
    {
        $compList = model('OrgComp')->field('COMP_NO, COMP_NAME')->where('COMP_NO', '<>', 0)->order('COMP_NO')->select();
        $deptList = model('OrgDept')->field('DEPT_NO, DEPT_NAME, COMP_NO')->where(['LEVEL'=>3, 'ISSEALUP'=>0, 'COMP_NO'=>['<>', 0]])->order('COMP_NO, DISP_SN')->select();
        $result = array();

        foreach ($compList as $k => $v)
        {
            $result[$v['COMP_NO']] = array(
                'label'		=>	$v['COMP_NAME'],
                'value'		=>	$v['COMP_NO'],
                'children'	=>	array()
            );
        }

        foreach ($deptList as $k => $v)
        {
            if ($v['DEPT_NO'] == '011209')
            {
                $subDept = model('OrgDept')->where('PARENT_DEPT_NO', '011209')->select();
                $subDeptList = array();
                foreach ($subDept as $kk => $vv)
                {
                    $subDeptList[] = array(
                        'value'		=>	$vv['DEPT_NO'],
                        'label'		=>	$vv['DEPT_NAME']
                    );
                }
                $result[$v['COMP_NO']]['children'][] = array(
                    'value'		=>	$v['DEPT_NO'],
                    'label'		=>	$v['DEPT_NAME'],
                    'children'	=>	$subDeptList
                );
            }
            else
            {
                $result[$v['COMP_NO']]['children'][] = array(
                    'value'	=>	$v['DEPT_NO'],
                    'label'	=>	$v['DEPT_NAME']
                );
            }

        }
        foreach($result as $k=>$v){
            if(empty($v['children'])){
                $deptNo = Model('OrgDept')->where(['DEPT_NAME'=>$v['label']])->value('DEPT_NO');
                if($deptNo != null){
                    $deptNo = Model('OrgDept')->where(['PARENT_DEPT_NO'=>$deptNo])->select();
                    foreach($deptNo as $k2=>$v2){
                        $result[$k]['children'][] = ['label'=>$v2['DEPT_NAME'],'value'=>$v2['DEPT_NO']];
                    }
                }else{
                    unset($result[$k]['children']);
                }
            }
        }
        array_pop($result);
        if (!is_null($result))
            $this->success('', '', array_values($result));
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
            'deptNo'		=>	$data['deptValue'][1],
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
     * @param  int $page    当前页码
     * @param  int $listRow 每页显示记录数
     * @return array 流程列表信息
     */
    public function getProcessList($page=1, $listRow=12)
    {
        $result = model('Process')->where('status', 1)->page($page, $listRow)->select();
        foreach ($result as $k => $v)
        {
            $result[$k]['dept'] = model('OrgDept')->getNameList($v['deptNo']);
            $result[$k]['creator'] = model('UserEmp')->getUserRealName($v['creator']);
            $result[$k]['lastModifier'] = model('UserEmp')->getUserRealName($v['lastModifier']);
        }
        $list['list'] = $result;
        $list['total'] = model('Process')->where('status', 1)->count();
        $list['listRow'] = $listRow;
        $list['page'] = $page;
        if ($result)
            $this->success('', '', $list);
        else
            $this->error();
    }

    /**
     * 删除流程
     * @param  array $ids 要删除的流程Id组成的数组
     * @return array      删除结果
     */
    public function del($ids)
    {
        $id = implode(',', $ids);
        $resProcess = model('Process')->where(['id'=>['in', $id]])->delete();
        $resProcessData = model('ProcessData')->where(['pId'=>['in', $id]])->delete();

        if ($resProcess && $resProcessData)
            $this->success();
        else
            $this->error();
    }

    /**
     * 修改流程时查询流程信息
     * @param  int $pId 流程Id
     * @return array      流程信息
     */
    public function getProcessInfo($pId)
    {
        $result = model('Process')->where('id', $pId)->find();

        if ($result)
        {
            $process = model('ProcessData')->where('pId', $pId)->select();
            $stpts = array();
            foreach ($process as $k => $v)
            {
                $audit_user = json_decode($v['audit_user']);
                $step = array();
                foreach ($audit_user as $kk => $vv)
                {
                    if ($kk == 'notIn')
                        $step['notIn'] = $vv;
                    else
                        $step['auditor'] = $vv;
                }
                $step['describe'] = $v['pDescribe'];
                $steps[] = $step;
            }
            $result['process'] = $steps;
            $result['compNo'] = model('OrgDept')->where('DEPT_NO', $result['deptNo'])->value('COMP_NO');
            $this->success('', '', $result);
        }
        else
            $this->error();
    }

    /**
     * 更新流程
     * @param  array $data 更新流程的数据
     * @return array       更新结果
     */
    public function edit($data)
    {
        $member = session('member');
        unset($data['process'][0]);

        $updateProcess = array(
            'name'			=>	trim($data['name']),
            'deptNo'		=>	$data['deptValue'][1],
            'level'			=>	count($data['process']),
            'lastModifier'	=>	$member['EMP_NO']
        );

        // Db::startTrans();
        $resProcess = model('Process')->save($updateProcess, ['id'=>$data['id']]);
        if ($resProcess !== false)
        {
            $pId = $data['id'];
            $oldStepCount = model('ProcessData')->where('pId', $pId)->count();
            // 如果更新后流程步骤数量等于原步骤数量，直接更新
            // 如果更新后流程步骤数量大于原步骤数量，修改原有的数量，多出来的插入
            if (count($data['process']) >= $oldStepCount)
            {
                foreach ($data['process'] as $k => $v)
                {
                    $audit = array(
                        'dept'	=>	$v['auditor'],
                        'notIn'	=>	$v['notIn']
                    );
                    $update = array(
                        'pId'		=>	$pId,
                        'levelNo'	=>	$k,
                        'audit_user'=>	json_encode($audit),
                        'pDescribe'	=>	strip_tags(trim($v['describe']))
                    );
                    $where = array(
                        'pId'		=>	$pId,
                        'levelNo'	=>	$k
                    );
                    if ($k <= $oldStepCount)    // 小于等于原来的步骤执行更新
                    {
                        if (model('ProcessData')->save($update, $where) === false)
                        {
                            Db::rollback();
                            $this->error();
                        }
                    }
                    else    // 多出原来的步骤执行插入
                    {
                        if (model('ProcessData')->isUpdate(false)->save($update) === false)
                        {
                            Db::rollback();
                            $this->error();
                        }
                    }
                }
            }
            // 如果更新后流程步骤数量小于原步骤数量，修改更新后的数量，原来多出来的删除
            else
            {
                foreach ($data['process'] as $k => $v)
                {
                    $audit = array(
                        'dept'	=>	$v['auditor'],
                        'notIn'	=>	$v['notIn']
                    );
                    $update = array(
                        'pId'		=>	$pId,
                        'levelNo'	=>	$k,
                        'audit_user'=>	json_encode($audit),
                        'pDescribe'	=>	strip_tags(trim($v['describe']))
                    );
                    $where = array(
                        'pId'		=>	$pId,
                        'levelNo'	=>	$k
                    );
                    if (model('ProcessData')->save($update, $where) === false)
                    {
                        Db::rollback();
                        $this->error();
                    }
                }
                if (model('ProcessData')->where(['pId'=>$pId, 'levelNo'=>['>', count($data['process'])]])->delete() === false)
                {
                    Db::rollback();
                    $this->error();
                }
            }

            Db::commit();
            $this->success();
        }
        else
        {
            Db::rollback();
            $this->error();
        }
    }
}