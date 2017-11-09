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
    public function getList($page, $listRow, $keyword = '', $level = '', $typeId = '')
    {
        $userInfo = getUserInfo();
        $ParticipateComp = new ParticipateComp();
        $pcInfo = $ParticipateComp->getInfo($userInfo['COMP_NO']);
        if($pcInfo){
        }else{
            $this->error('您所在的分公司不参与');
        }
        $OrgDept = new OrgDept();
        $deptNo = $OrgDept->getDeptNo($userInfo['DEPTNO']);
        $map = [
            'deptNo'    =>  $deptNo,
            'content'   =>  ['like','%'.$keyword.'%'],
            'status'    =>  ['in', '1,2']
        ];
        //获取权限
        $Identity = new Identity();
        $identitys = $Identity->getIdentity($userInfo['EMP_NO']);

        $Task = new \app\common\model\Task();

        if($level !== ''){
            $map['level'] = $level;
        }
        if ($typeId !== '')
            $map['typeId'] = $typeId;

        $tDate = date('Ym');

        $result = $Task->getList($map, $tDate, $page, $listRow);
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
            $result['timeLimit'] = substr_replace($result['timeLimit'], '年', 4, 0);
            $result['releaseTime'] = date('Y', $result['releaseTime']);
            $result['stepsNum'] = Model('Process')->where('id', $result['pId'])->value('level');
            $this->success($result);
        }else{
            $this->error('未找到');
        }
    }

    /*
     * 更新
     */
    public function edit($id, $completeSituation, $problemSuggestions, $analysis, $status){
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
        // 获取用户的身份
        $userInfo = getUserInfo();
        $deptNo = Model('OrgDept')->getDeptNo($userInfo['DEPTNO']);
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
        $result = $TaskLogModel->addLog($taskInfo['id'],$taskDataInfo['id'],'edit',$userInfo['EMP_NO'],$deptNo,$update,$taskDataInfo->toArray());
        if($result){
            $this->success('修改成功');
        }else{
            $this->error('添加日志失败!');
        }
    }
    
    /**
     * 用户提交任务
     * @param  int $id           任务每月详情Id，task_data表中的Id
     * @param  int $currentLevel 当前流程进行到哪一步
     * @param  int $nextLevel    当前流程的下一步
     * @return array               提交结果
     */
    public function submits($id, $currentLevel, $nextLevel)
    {
        //获取用户的信息
        $userInfo = getUserInfo();
        $deptNo = Model('OrgDept')->getDeptNo($userInfo['DEPTNO']);
        $TaskDataModel = new TaskData();
        $taskDataInfo = $TaskDataModel->where(['id'=>$id])->find();
        if(!$taskDataInfo)
            $this->error('该条记录未找到');
        // 获取流程总的等级
        $level = Model('Process')->where('id', $taskDataInfo['pId'])->value('level');

        //如果部门办事员和主任都在流程1  2 级那么跳过2级到3级
        $identitys = Model('ProcessData')->getStepIds($taskDataInfo['pId']);
        if(count($identitys) > 1 && ($identitys['0'] == 1 && $identitys['1'] == 2)){
            //如果当前的等级为1 则+2跳到3级 如果当前等级为2 则+1跳到3级
            if($currentLevel == 1){
                $update = ['currentLevel' => $currentLevel + 2, 'nextLevel' => $nextLevel + 2];
            }else{
                $update = ['currentLevel' => $currentLevel + 1, 'nextLevel' => $nextLevel + 1];
            }
        }else{
            // 下一步不能大于总步数
            if ($nextLevel < $level){
                $update = ['currentLevel' => $currentLevel + 1, 'nextLevel' => $nextLevel + 1];
            }else{
                $update = ['currentLevel' => $currentLevel + 1];
            }
        }

        $updateStatus = $TaskDataModel->where(['id'=>$id])->update($update);
        if ($updateStatus === false){
            $this->error($TaskDataModel->getError());
        }else{
            //添加提交日志
            $result = Model('TaskLog')->addLog($taskDataInfo['tId'],$taskDataInfo['id'],'submit',$userInfo['EMP_NO'],$deptNo);
            $this->success('提交成功!');
        }
    }

    /**
     * 撤回任务
     * @param  int $id           任务每月详情Id，task_data表中的Id
     * @param  int $currentLevel 当前流程进行到哪一步
     * @param  int $nextLevel    当前流程的下一步
     * @return array               撤回结果
     */
    public function withdraw($id, $currentLevel, $nextLevel)
    {
        //获取用户的权限
        $userInfo = getUserInfo();
        $deptNo = Model('OrgDept')->getDeptNo($userInfo['DEPTNO']);
        $TaskDataModel = new TaskData();
        $taskDataInfo = $TaskDataModel->where(['id'=>$id])->find();
        if(!$taskDataInfo)
            $this->error('该条记录未找到');
        $level = Model('Process')->where('id', $taskDataInfo['pId'])->value('level');
        if ($currentLevel >= $level){
            $update = ['currentLevel' => $currentLevel - 1];
        }else{
            $update = ['currentLevel' => $currentLevel - 1, 'nextLevel' => $nextLevel - 1];
        }
        $updateStatus = $TaskDataModel->where(['id'=>$id])->update($update);
        if ($updateStatus === false){
            $this->error($TaskDataModel->getError());
        }else{
            //添加撤回日志
            $result = Model('TaskLog')->addLog($taskDataInfo['tId'],$taskDataInfo['id'],'withdraw',$userInfo['EMP_NO'],$deptNo);
            $this->success('撤回成功!');
        }

    }

    /**
     * 驳回任务请求
     * @param  int $id           任务每月详情Id，task_data表中的Id
     * @param  int $currentLevel 当前流程进行到哪一步
     * @param  int $nextLevel    当前流程的下一步
     * @return array               驳回结果
     */
    public function reject($id, $currentLevel, $nextLevel)
    {
        //获取用户的权限
        $userInfo = getUserInfo();
        $deptNo = Model('OrgDept')->getDeptNo($userInfo['DEPTNO']);
        $TaskDataModel = new TaskData();
        $taskDataInfo = $TaskDataModel->where(['id'=>$id])->find();
        if(!$taskDataInfo)
            $this->error('该条记录未找到');
        $level = Model('Process')->where('id', $taskDataInfo['pId'])->value('level');
        if ($currentLevel >= $level){
            $update = ['currentLevel' => $currentLevel - 1];
        }else{
            $update = ['currentLevel' => $currentLevel - 1, 'nextLevel' => $nextLevel - 1];
        }
        $updateStatus = $TaskDataModel->where(['id'=>$id])->update($update);
        if ($updateStatus === false)
            $this->error($TaskDataModel->getError());
        else
            //添加驳回日志
            $result = Model('TaskLog')->addLog($taskDataInfo['tId'],$taskDataInfo['id'],'reject',$userInfo['EMP_NO'],$deptNo);
            $this->success('驳回成功!');
    }

    /**
     * 确认任务
     */
    public function confirm($id)
    {
        //获取用户的权限
        $userInfo = getUserInfo();
        $deptNo = Model('OrgDept')->getDeptNo($userInfo['DEPTNO']);
        $TaskDataModel = new TaskData();
        $taskDataInfo = $TaskDataModel->where(['id'=>$id])->find();
        if(!$taskDataInfo){
            $this->error('该条记录未找到');
        }
        //本月的任务确认task_data表
        $updateStatus = $TaskDataModel->where(['id'=>$id])->update(['status' => 0]);

        if ($updateStatus === false) {
            $this->error($TaskDataModel->getError());
        }else{
            //添加确认日志
            $result = Model('TaskLog')->addLog($taskDataInfo['tId'],$taskDataInfo['id'],'confirm',$userInfo['EMP_NO'],$deptNo);
            $this->success('确认成功!');
        }
    }

    /**
     * 完成任务
     */
    public function complete($id)
    {
        $userInfo = getUserInfo();
        $deptNo = Model('OrgDept')->getDeptNo($userInfo['DEPTNO']);
        $tId = Model('TaskData')->where(['id' => $id])->value('tId');
        $TaskModel = new \app\common\model\Task();
        //任务总的完成task表
        $result = $TaskModel->where(['id' => $tId])->update(['status' => '2']);
        if($result){
            //添加完成日志
            $result = Model('TaskLog')->addLog($tId,$id,'complete',$userInfo['EMP_NO'],$deptNo);
            return $this->success('任务完成!');
        }else{
            return $this->error('任务未完成');
        }
    }

    /*
     * 获取日志
     */
    public function getLogs($tId,$mouth)
    {
        //根据登录的用户查询它的三级组织id
        $userInfo = getUserInfo();
        $deptNo = Model('OrgDept')->getDeptNo($userInfo['DEPTNO']);

        $year = date('Y',time());
        $tDate =  $year . $mouth;
        $tDId = Model('TaskData')->where(['tId'=>$tId,'tDate'=>$tDate])->value('id');
        $result = Model('TaskLog')->where(['tDId'=>$tDId])->order('createTime asc')->select();
        if($result){
            foreach($result as $k=>$v){
                $result[$k]['empNo'] = Model('UserEmp')->getUserRealName($v['empNo']);
                $result[$k]['logData'] = Model('TaskLogData')->getLogData($v['id'],$deptNo);
            }
        }
        $this->success($result);

    }

    /*
     * 任务分类列表
     */
    public function getTypeList()
    {
        $result = Model('TaskType')->getTypeList();
        if($result){
            $this->success($result);
        }
    }

    /*
    * 任务分类的添加
    */
    public function checkRepeat($typeName)
    {
        $result = model('TaskType')->where('typeName',$typeName)->find();
        if($result){
            $this->error();
        }else{
            $this->success();
        }
    }
    public function addType($typeInfo)
    {
        if(!isset($typeInfo)){
            $this->error('没有添加数据');
        }
        $userInfo = getUserInfo();
        $typeInfo['creator'] = $userInfo['EMP_NO'];
        $result = Model('TaskType')->save($typeInfo);
        if($result){
            $this->success('添加成功！');
        }else{
            $this->error('添加失败!');
        }

    }

    /*
    * 任务分类的编辑
    */
    public function editTypeSelt($typeId)
    {
        $result = Model('TaskType')->where(['id' => $typeId])->find();
        if($result){
            $this->success($result);
        }
    }
    public function editType($typeInfo)
    {
        if(!isset($typeInfo)){
            $this->error('没有修改数据');
        }
        $result = Model('TaskType')->update($typeInfo);
        if($result){
            $this->success('修改成功!');
        }else{
            $this->error('修改失败!');
        }

    }

    /*
    * 任务分类的删除
    */
    public function delType($typeId)
    {
		if($typeId == '0'){
			$this->error('选择数据!');
		}
        if(is_numeric($typeId)){
            $result = Model('TaskType')->where(['id' => $typeId])->delete();
            if($result){
                $this->success('删除成功!');
            }else{
                $this->error('删除失败!');
            }
        }else{
            foreach($typeId as $k=>$v){
                Model('TaskType')->where(['id' => $v['id']])->delete();
            }
            $this->success('删除成功!');
        }

    }
    /**
   * 任务分类的启用禁用
   */
    public function oprationSta($typeId,$status)
    {
        if($status == '0'){
            Model('TaskType')->where(['id' => $typeId])->update(['status' => '1']);
            $this->success('启用成功');
        }else{
            Model('TaskType')->where(['id' => $typeId])->update(['status' => '0']);
            $this->success('禁用成功');
        }
    }
}
