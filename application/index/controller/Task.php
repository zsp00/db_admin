<?php
namespace app\index\controller;

use app\common\model\Identity;
use app\common\model\OrgDept;
use app\common\model\ParticipateComp;
use app\common\model\ParticipateDept;
use app\common\model\TaskData;
use app\common\model\TaskLog;

class Task extends Common
{
    public function getList($page, $listRow, $keyword = '', $level = '')
    {
        $userInfo = getUserInfo();
        $ParticipateComp = new ParticipateComp();
        $pcInfo = $ParticipateComp->getInfo($userInfo['COMP_NO']);
        if($pcInfo){
        }else{
            $this->error('您所在的分公司不参与');
        }
        $map = [
            'year'  =>  $pcInfo['currYear'],
            'content'   =>  ['like','%'.$keyword.'%'],
            'status'    =>  '1'
        ];
        //获取权限
        $Identity = new Identity();
        $identitys = $Identity->getIdentity($userInfo['EMP_NO']);

        $Task = new \app\common\model\Task();
        if(!in_array('2',$identitys)){
            $OrgDept = new OrgDept();
            $deptNo = $OrgDept->getDeptNo($userInfo['DEPTNO']);
            $map['deptNo'] = $deptNo;
        }


        if($level !== ''){
            $map['level'] = $level;
        }
        $result = $Task->getList($map,$pcInfo['currMouth'],$page,$listRow);
        $Identity = new Identity();
        $identitys = $Identity->getIdentity($userInfo['EMP_NO']);
        $result['identitys'] = $identitys;
        $result['date'] = $pcInfo;
        $this->success($result);
    }

    public function info()
    {
        $result = [
            'deptName'  =>  null,
            'isParticipate' =>  false,
            'date'  =>  null
        ];
        $userInfo = getUserInfo();
        $ParticipateDept = new ParticipateDept();
        $result['isParticipate'] = $ParticipateDept->isParticipate($userInfo['DEPT_NO']) ? true : false;
        $OrgDept = new OrgDept();
        $result['deptName'] = $OrgDept->getNameList($userInfo['DEPTNO']);
        $ParticipateComp = new ParticipateComp();
        $pcInfo = $ParticipateComp->getInfo($userInfo['COMP_NO']);
        if($pcInfo){
            $result['date'] = $pcInfo;
        }else{
            $this->error('您所在的分公司不参与');
        }
        $this->success($result);
    }

    public function detail ($id) {
        $userInfo = getUserInfo();
        $Task = new \app\common\model\Task();
        $result = $Task->getInfo($id);
        if($result){
            $OrgDept = new OrgDept();
            $result['deptName'] = $OrgDept->getNameList($result['deptNo']);
            $Identity = new Identity();
            $identitys = $Identity->getIdentity($userInfo['EMP_NO']);
            $result['identitys'] = $identitys;
            $this->success($result);
        }else{
            $this->error('未找到');
        }
    }

    /*
     * 更新
     */
    public function edit($id, $completeSituation, $problemSuggestions, $analysis){
        $TaskDataModel = new TaskData();
        $taskDataInfo = $TaskDataModel->where(['id'=>$id])->find();
        if(!$taskDataInfo){
            $this->error('该条记录未找到');
        }
        $TaskModel = new \app\common\model\Task();
        $taskInfo = $TaskModel->where(['id'=>$taskDataInfo['tId']])->find();
        if(!$taskInfo){
            $this->error('主数据丢失，意外，请联系管理员');
        }

        // 检查任务状态
        switch ($taskInfo['status']) {
            case '-1':
                $this->error('任务已被删除，不允许修改');
                break;
            case '0':
                $this->error('任务已被禁用');
                break;
            case '1':
                break;
            case '2':
                $this->error('任务已经完成。');
                break;
            default:
                $this->error('任务状态异常');
        }

        // 获取用户权限

        $userInfo = getUserInfo();
        $Identity = new Identity();
        $identitys = $Identity->getIdentity($userInfo['EMP_NO']);

        switch ($taskDataInfo['status']) {
            // 如果部门领导未提交
            case '1':
                break;
            // 部门领导已经提交办公室未确认
            case '2':
                // 如果是部门领导
                if (in_array('1',$identitys)) {
                    $this->error('部门领导已经提交此任务，您无权修改');
                } else if(in_array('2',$identitys)){
                    break;
                } else {
                    $this->error('部门领导已经提交此任务，您无权修改');
                }
                break;
            // 办公室已经确认
            case '3':
                $this->error('办公室已经确认，禁止修改');
                break;
            default:
                $this->error('该月任务状态异常');
        }
        // 更新内容
        $update = [
            'completeSituation' =>  $completeSituation,
            'problemSuggestions'    =>  $problemSuggestions,
            'analysis'  =>  $analysis
        ];
        $updateStatus = $TaskDataModel->where(['id'=>$id])->update($update);
        if ($updateStatus === false) {
            $this->error($TaskDataModel->getError());
        }

        if($updateStatus === 0){
            $this->error('您没做任何修改！');
        }

        // 添加修改日志
        $TaskLogModel = new TaskLog();
        $result = $TaskLogModel->addLog($taskInfo['id'],$taskDataInfo['id'],'edit',$userInfo['EMP_NO'],$update,$taskDataInfo->toArray());
        if($result){
            $this->success('修改成功');
        }else{
            $this->error('添加日志失败!');
        }
    }
    //确认
    public function confirm($id)
    {
        //获取用户的权限
        $userInfo = getUserInfo();
        $Identity = new Identity();
        $identitys = $Identity->getIdentity($userInfo['EMP_NO']);
        $TaskDataModel = new TaskData();
        $taskDataInfo = $TaskDataModel->where(['id'=>$id])->find();
        if(!$taskDataInfo){
            $this->error('该条记录未找到');
        }
        switch ($taskDataInfo['status']) {
            // 如果部门领导未提交
            case '1':
                break;
            // 部门领导已经提交办公室未确认
            case '2':
                // 如果是部门领导
                if (in_array('1',$identitys)) {
                    $this->error('部门领导已经提交此任务，您无权修改');
                } else if(in_array('2',$identitys)){
                    break;
                } else {
                    $this->error('部门领导已经提交此任务，您无权修改');
                }
                break;
            // 办公室已经确认
            case '3':
                $this->error('办公室已经确认，禁止修改');
                break;
            default:
                $this->error('该月任务状态异常');
        }
        $updateStatus = $TaskDataModel->where(['id'=>$id])->update(['status' => '3']);
        if ($updateStatus === false) {
            $this->error($TaskDataModel->getError());
        }else{
            $tasklog = ['tId'=>$taskDataInfo['tId'],'tDId'=>$taskDataInfo['id'],'type'=>'confirm','empNo'=>$userInfo['EMP_NO']];
            $result = Model('TaskLog')->save($tasklog);
            $this->success('确认成功!');
        }
    }

    //完成
    public function complete($id)
    {
        $userInfo = getUserInfo();
        $tId = Model('TaskData')->where(['id' => $id])->value('tId');
        $TaskModel = new \app\common\model\Task();
        $result = $TaskModel->where(['id' => $tId])->update(['status' => '2']);
        if($result){
            $tasklog = ['tId'=>$tId,'tDId'=>$id,'type'=>'complete','empNo'=>$userInfo['EMP_NO']];
            $result = Model('TaskLog')->save($tasklog);
            return $this->success('完成成功!');
        }else{
            return $this->error('完成失败');
        }
    }

    //获取日志
    public function getLogs($tId,$mouth)
    {
        $tDId = Model('TaskData')->where(['tId'=>$tId,'mouth'=>$mouth])->value('id');
        $result = Model('TaskLog')->where(['tDId'=>$tDId])->select();
        if($result){
            foreach($result as $k=>$v){
                $result[$k]['empNo'] = Model('UserEmp')->getUserRealName($v['empNo']);
                $result[$k]['logData'] = Model('TaskLogData')->getLogData($v['id']);
            }
        }
        $this->success($result);
    }
    
    
}
