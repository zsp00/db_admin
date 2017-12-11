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
    public function getList($page, $listRow, $keyword = '', $level = '', $typeId = '', $ifStatus = '', $dept = [], $needToDo = 'true')
    {
        $userInfo = getUserInfo();
        $ParticipateComp = new ParticipateComp();
        $pcInfo = $ParticipateComp->getInfo($userInfo['COMP_NO']);
        if(!$pcInfo){
            $this->error('您所在的分公司不参与');
        }
        $OrgDept = new OrgDept();
        $deptNo = $OrgDept->getDeptNo($userInfo['DEPTNO']);

        // 检索条件
        $Task = new \app\common\model\Task();
        $map = [
            'content'   =>  ['like','%'.$keyword.'%'],
            'status'    =>  ['in', '1,2'],
        ];

        // 查询当前用户有没有查看所有任务列表的权限
        $res = model('TasklistAuthority')->where(['type'=>'person', 'value'=>$userInfo['EMP_NO']])->find();
        $flag = false;       // 是否能查看所有任务列表的标识，还是只能查看本部门的任务
        if ($res){
            $flag = true;
        }else{
            $map['deptNo'] =  $deptNo;
        }

        if($level !== '')      // 级别
            $map['level'] = $level;
        if ($typeId !== '')    // 分类
            $map['typeId'] = $typeId;
        if ($ifStatus !== '')    // 是否提交
            $map['ifStatus'] = $ifStatus;
        if ($dept !== [])      // 部门
            $map['deptNo'] = $dept[1];

        $tDate = date('Ym', strtotime('-1 months'));
        $result = $Task->getList($map, $tDate, $page, $listRow, $needToDo, $flag);

        if (!$result)
            $this->error('暂无任务');

        $result['date'] = $pcInfo;
        $result['flag'] = $flag;
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
        $result['isParticipate'] = $ParticipateDept->isParticipate($userInfo['DEPTNO'],$userInfo['EMP_NO']) ? true : false;
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

    public function detail ($id,$tdDeptNo) {
        $userInfo = getUserInfo();
        $Task = new \app\common\model\Task();
        $result = $Task->getInfo($id,$tdDeptNo);
        if($result){
            $OrgDept = new OrgDept();
            $result['deptName'] = $OrgDept->getNameList($tdDeptNo);
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
    public function edit($id, $completeSituation='', $problemSuggestions='', $analysis='', $taskSelect=false){
        $TaskDataModel = new TaskData();
        $taskDataInfo = $TaskDataModel->where(['id'=>$id])->find();
        if(!$taskDataInfo){
            $this->error('该条记录未找到');
        }
        unset($taskDataInfo['status']);
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
                break;
            case '3':
                $this->error('任务已经完成。');
                break;
            default:
                $this->error('任务状态异常');
        }
        // 获取用户的身份
        $userInfo = getUserInfo();
        $deptNo = Model('OrgDept')->getDeptNo($userInfo['DEPTNO']);
        // 更新内容   先更新任务详情
        $update = [
            'completeSituation' =>  $completeSituation,
            'problemSuggestions'    =>  $problemSuggestions,
            'analysis'  =>  $analysis
        ];
        $updateDataStatus = $TaskDataModel->where(['id'=>$id])->update($update);
        // 第二步更新任务完成状态
        $taskSelect == false ? $taskSelect='1' : $taskSelect='2';
        $taskUpdate = Model('Task')->where(['id'=>$taskInfo['id']])->update(['status'=>$taskSelect]);
        $TaskLogModel = new TaskLog();
        if($taskUpdate){
            $update['status'] = $taskSelect;
            $taskDataInfo['status'] = $taskInfo['status'];
        }

        if ($updateDataStatus === false) {
            $this->error($TaskDataModel->getError());
        }
//         if($updateDataStatus === 0 && $taskUpdate === 0){
//             $this->error('您没做任何修改！');
//         }
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
    public function submits($id, $pId, $currentLevel, $nextLevel)
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
        //提交完成后，向这级的人微信推送消息
        $currentLevel = Model('TaskData')->where(['id'=>$id])->value('currentLevel');
        $empNoPushChat = Model('ProcessData')->where(['pId'=>$pId,'levelNo'=>$currentLevel])->value('empNos');
        if($empNoPushChat){
            $userId = Model('Task')->getUserId($empNoPushChat);
            $pushChat= Model('Task')->weChatPush($userId,'您有新的督办任务被提交，请您查看处理！');
        }
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
    public function reject($data, $reason='')
    {
        $id = $data['id'];
        $currentLevel = $data['currentLevel'];
        $nextLevel = $data['nextLevel'];
        $pId = $data['pId'];
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
        //驳回完成后，向这级的人微信推送消息
        //另外2级驳回到1级（1级一般为部门）
        $currentLevel = Model('TaskData')->where(['id'=>$id])->value('currentLevel');
        if($currentLevel == '1'){
            $deptNoAll[] = Model('TaskData')->where(['id'=>$id])->value('deptNo');
            Model('Task')->superviseChat($setText="1督办任务被驳回请您查看处理！".'</br>'.'驳回理由：'.$reason ,$deptNoAll);
        }else{
            $empNoPushChat = Model('ProcessData')->where(['pId'=>$pId,'levelNo'=>$currentLevel])->value('empNos');
            if($empNoPushChat){
                $userId = Model('Task')->getUserId($empNoPushChat);
                $pushChat= Model('Task')->weChatPush($userId,"2-6督办任务被驳回请您查看处理！".'</br>'.'驳回理由：'.$reason);
            }
        }
        if ($updateStatus === false){
            $this->error($TaskDataModel->getError());
        }else{
            //添加驳回日志
            $result = Model('TaskLog')->addRejectLog($taskDataInfo['tId'],$taskDataInfo['id'],'reject',$userInfo['EMP_NO'],$reason);
            $this->success('驳回成功!');
        }

    } 

    /**
     * 确认任务
     */
    public function confirm($data,$taskSelect=false)
    {
        if(is_array($data)){
            //获取用户的权限
            $userInfo = getUserInfo();
            $deptNo = Model('OrgDept')->getDeptNo($userInfo['DEPTNO']);
            $TaskDataModel = new TaskData();
            foreach($data as $k=>$v){
                $taskDataInfo = $TaskDataModel->where(['tId'=>$v['id']])->find();
                if($taskSelect){
                    Model('Task')->where(['id'=>$taskDataInfo['tId']])->update(['status'=>'3', 'completeTime'=>time()]);
                }
                //本月的任务确认task_data表
                $updateStatus = $TaskDataModel->where(['tId'=>$v['id'],'status'=>'1'])->update(['status' => 0]);

                if ($updateStatus === false) {
                    $this->error($TaskDataModel->getError());
                }else{
                    //添加确认日志
                    $result = Model('TaskLog')->addLog($taskDataInfo['tId'],$taskDataInfo['id'],'confirm',$userInfo['EMP_NO'],$deptNo);
                }
            }
        }else{
            //获取用户的权限
            $userInfo = getUserInfo();
            $deptNo = Model('OrgDept')->getDeptNo($userInfo['DEPTNO']);
            $TaskDataModel = new TaskData();

            $taskDataInfo = $TaskDataModel->where(['id'=>$data])->find();
            if(!$taskDataInfo){
                $this->error('该条记录未找到');
            }
            if($taskSelect){
                Model('Task')->where(['id'=>$taskDataInfo['tId']])->update(['status'=>'3', 'completeTime'=>time()]);
            }
            //本月的任务确认task_data表
            $updateStatus = $TaskDataModel->where(['id'=>$data])->update(['status' => 0]);

            if ($updateStatus === false) {
                $this->error($TaskDataModel->getError());
            }else{
                //添加确认日志
                $result = Model('TaskLog')->addLog($taskDataInfo['tId'],$taskDataInfo['id'],'confirm',$userInfo['EMP_NO'],$deptNo);
            }
        }

        $this->success('确认成功!');
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

    // 第三级用户批量提交任务
    public function commitAll()
    {
        $tDate = $tDate = date('Ym');
        $userInfo = getUserInfo();
        $empNo = $userInfo['EMP_NO'];
        $deptNo = model('OrgDept')->getDeptNo($userInfo['DEPTNO']);
        dump($deptNo);
        $list = model('Task')->alias('task')
            ->join('task_data', 'task.id = task_data.tId and task_data.status=1 and task_data.tDate='.$tDate)
            ->join('task_tasktype', 'task.id=task_tasktype.tId')
            ->join('process_data', 'task_data.currentLevel=process_data.levelNo and task_data.pId=process_data.pId', 'left')
            ->where(['task.status'=>['in', '1,2']])
            ->where(function ($query) use ($deptNo, $empNo) {
                $query->where([
                    'process_data.deptNos'   =>  ['like', '%' . $deptNo . '%']
                ])->whereOr([
                    'process_data.empNos'    =>  ['like', '%' . $empNo . '%']
                ]);
            })->where(function ($query) use ($empNo) {
                $query->where([
                    'process_data.notInIds'  =>  ['not like', '%' . $empNo . '%']
                ]);
            })->field([
                'task.*',
                'task_data.id'              =>  'currMonthId',
                'task_data.currentLevel'    =>  'taskDataStatus',
                'task_data.nextLevel'       =>  'taskDataNextStatus'
            ])->group('task.id')->select();
        $result = true;
        foreach ($list as $k => $v)
        {
            $update = array(
                'currentLevel'  =>  $v['taskDataStatus'] + 1,
                'nextLevel'     =>  $v['taskDataNextStatus'] + 1
            );
            if (model('TaskData')->where(['id'=>$v['currMonthId']])->update($update) !== false)
                $result = model('TaskLog')->addLog($v['id'],$v['currMonthId'],'submit',$empNo,$deptNo) && $result;
            else
                $result = false && $result;
        }
        //提交完成后，向这级的人微信推送消息
        $listAll = count($list);
        if($listAll > 1){
            $currentLevel = Model('TaskData')->where(['tId'=>$list[0]['id']])->value('currentLevel');
            $empNoPushChat = Model('ProcessData')->where(['pId'=>$list[0]['pId'],'levelNo'=>$currentLevel])->value('empNos');
            $userId = Model('Task')->getUserId($empNoPushChat);
            $pushChat= Model('Task')->weChatPush($userId,'这是全部提交督办任务被提交请您查看');
        }
        if ($result){
            $this->success('全部提交成功！');
        }else{
            $this->error();
        }

    }

    /**
     * 判断第三级用户
     * @return [type] [description]
     */
    public function checkCount($countDoing)
    {
        $tDate = $tDate = date('Ym');
        $userInfo = getUserInfo();
        $empNo = $userInfo['EMP_NO'];
        $deptNo = model('OrgDept')->getDeptNo($userInfo['DEPTNO']);

        $countAll = model('Task')->alias('task')
            ->join('task_data', 'task.id = task_data.tId and task_data.status=1 and task_data.tDate='.$tDate)
            ->join('task_tasktype', 'task.id=task_tasktype.tId')
            ->where(['task.status'=>['in', '1,2']])->group('task_data.id')->count();

        if ($countDoing == $countAll)
            $this->success();
        elseif ($countDoing > $countAll)
            $this->error('系统出现故障，请联系管理员！');
        else
            $this->error('尚有任务未填报，暂不能全部提交！');
    }
    // 导出任务填报列表
    public function exportFillinList($condition)
    {
        $params = json_decode($condition);
        // dump($params);exit;
        $keyword = $params->keyword;
        $typeId = $params->typeId;
        $ifStatus = $params->ifStatus;
        $dept = $params->dept;
        $needToDo = $params->needToDo;

        $userInfo = getUserInfo();
        $empNo = $userInfo['EMP_NO'];
        $OrgDept = new OrgDept();
        $deptNo = $OrgDept->getDeptNo($userInfo['DEPTNO']);

        // 检索条件
        $where = [
            'task.content'   =>  ['like','%'.$keyword.'%'],
            'task.status'    =>  ['in', '1,2'],
        ];

        // 查询当前用户有没有查看所有任务列表的权限
        $res = model('TasklistAuthority')->where(['type'=>'person', 'value'=>$userInfo['EMP_NO']])->find();
        $flag = false;       // 是否能查看所有任务列表的标识
        if ($res){
            $flag = true;
        }else{
            $where['task_data.deptNo'] =  $deptNo;
        }

        if ($typeId !== '')    // 分类
            $where['task_tasktype.typeId'] = $typeId;
        if ($dept !== [])      // 部门
            $where['task_data.deptNo'] = $dept[1];

        $tDate = date('Ym', strtotime('-1 months'));

        if ($needToDo == true)
        {
            $model = model('Task')->alias('task')
                ->join('TaskLevelFirst t1', 'task.firstLevel=t1.id')
                ->join('TaskLevelSecond t2', 'task.secondLevel=t2.id')
                ->join('TaskLevelThird t3', 'task.thirdLevel=t3.id')
                ->join('task_data', 'task.id = task_data.tId and task_data.status=1 and task_data.tDate='.$tDate)
                ->join('task_tasktype', 'task.id=task_tasktype.tId')
                ->join('process_data', 'task_data.currentLevel=process_data.levelNo and task_data.pId=process_data.pId', 'left')
                ->where($where)
                ->where(function ($query) use ($deptNo, $empNo) {
                    $query->where([
                        'process_data.deptNos'   =>  ['like', '%' . $deptNo . '%']
                    ])->whereOr([
                        'process_data.empNos'    =>  ['like', '%' . $empNo . '%']
                    ]);
                })->where(function ($query) use ($empNo) {
                    $query->where([
                        'process_data.notInIds'  =>  ['not like', '%' . $empNo . '%']
                    ]);
                })->field([
                    'task.*',
                    't1.leader'             =>  'leader1',
                    't1.title'              =>  'title1',
                    't1.detail'             =>  'detail1',
                    't2.leader'             =>  'leader2',
                    't2.title'              =>  'title2',
                    't2.detail'             =>  'detail2',
                    't2.deptNo'             =>  'deptNo2',
                    't3.serialNum'          =>  'serialNum',
                    't3.detail'             =>  'detail3',
                    't3.duty'               =>  'duty3',
                    't3.leader'             =>  'leader3',
                    'task_data.deptNo'      =>  'tdDeptNo',
                    'task_data.completeSituation'   =>  'complete',
                    'task_data.problemSuggestions'  =>  'problem',
                    'task_data.analysis'            =>  'analysis',
                    'task_data.currentLevel'    =>  'taskDataStatus',
                    'task_data.status'          =>  'currMonthStatus',
                    'GROUP_CONCAT(task_tasktype.typeId)'    =>  'typeId'
                ]);
            $list = $model->group('task_data.id')->select();
        }
        else
        {
            $model = model('Task')->alias('task')
                ->join('TaskLevelFirst t1', 'task.firstLevel=t1.id')
                ->join('TaskLevelSecond t2', 'task.secondLevel=t2.id')
                ->join('TaskLevelThird t3', 'task.thirdLevel=t3.id')
                ->join('task_data', 'task.id = task_data.tId and task_data.status=1 and task_data.tDate='.$tDate)
                ->join('task_tasktype', 'task.id=task_tasktype.tId')
                ->where($where)
                ->field([
                    'task.*',
                    't1.leader'             =>  'leader1',
                    't1.title'              =>  'title1',
                    't1.detail'             =>  'detail1',
                    't2.leader'             =>  'leader2',
                    't2.title'              =>  'title2',
                    't2.detail'             =>  'detail2',
                    't2.deptNo'             =>  'deptNo2',
                    't3.serialNum'          =>  'serialNum',
                    't3.detail'             =>  'detail3',
                    't3.duty'               =>  'duty3',
                    't3.leader'             =>  'leader3',
                    'task_data.deptNo'      =>  'tdDeptNo',
                    'task_data.completeSituation'  =>  'complete',
                    'task_data.problemSuggestions' =>  'problem',
                    'task_data.analysis'           =>  'analysis',
                    'task_data.currentLevel'    =>  'taskDataStatus',
                    'task_data.status'          =>  'currMonthStatus',
                    'GROUP_CONCAT(task_tasktype.typeId)'    =>  'typeId'
                ]);
            $list = $model->group('task_data.id')->order('serialNum, id')->select();
        }
        $taskList = array();    // 当数组的键不是从0开始，ajax传输后会被转为object，所以重新定义数组
        if($list)
        {
            $OrgDept = new OrgDept();
            foreach($list as $k=>$v)
            {
                $list[$k]['deptNo'] = $OrgDept->where(['DEPT_NO'=>$v['tdDeptNo']])->value('DEPT_NAME');
                $taskList[] = $list[$k];
            }
            if($ifStatus != ''){
                foreach($taskList as $k2=>$v2){
                    if($ifStatus != $v2['getTaskStatusMsg']){
                        unset($taskList[$k2]);
                    }
                }
            }
        }

        $count = count($taskList);
        try
        {
            //导出Excel
            $objPHPExcel = new \PHPExcel();
            //应用第一个sheet页
            $objPHPExcel->setActiveSheetIndex(0);
            $objActSheet = $objPHPExcel->getActiveSheet();

            // 写入数据（表头）
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, 1, '《北京市地铁运营有限公司“十三五”发展规划》任务分解及年度实施计划');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, 2, '序号');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, 2, '一级目标任务（目标）');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, 2, '牵头领导');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(4, 2, '二级目标任务（任务）');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(6, 2, '责任领导');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(7, 2, '责任部室');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(8, 2, '三级目标任务（举措）');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(10, 2, '责任领导');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(11, 2, '责任部室、二级单位目标任务');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(13, 2, '年度实施计划');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(13, 3, '2017年');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(14, 3, '完成情况');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(15, 3, '问题建议');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(16, 3, '原因分析');

            // 定义几个合并单元格要用到的变量
            $serialNum = $title1 = $title2 = $detail2 = $detail3 = $duty3  = $duty = '';
            $serialNumIndex = $title1Index = $title2Index = $detail2Index = $detail3Index = $duty3Index = $dutyIndex = 0;
            $fileds = array('serialNum' => 0, 'title1' => 1, 'detail1' => 2, 'leader1' => 3, 'title2' => 4, 'detail2' => 5, 'leader2' => 6, 'deptNo2' => 7, 'detail3' => 8, 'duty3' => 9, 'leader3' => 10, 'duty' => 12, 'content' => 13, 'complete' => 14, 'problem' => 15, 'analysis' => 16);

            foreach ($taskList as $k => $v)
            {
                foreach ($fileds as $kk => $vv)
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($vv, 4 + $k, $v[$kk]);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(11, 4 + $k, preg_match_all('/^\d*$/', $v['deptNo']) ? model('OrgDept')->getName($v['deptNo']) : $v['deptNo']);
            }

            // 合并单元格（表头）
            $objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(0, 1, 16, 1);
            $objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(0, 2, 0, 3);
            $objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(1, 2, 2, 3);
            $objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(3, 2, 3, 3);
            $objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(4, 2, 5, 3);
            $objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(6, 2, 6, 3);
            $objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(7, 2, 7, 3);
            $objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(8, 2, 9, 3);
            $objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(10, 2, 10, 3);
            $objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(11, 2, 12, 3);
            $objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(13, 2, 16, 2);

            // 修改样式
            $objActSheet->getColumnDimension('C')->setWidth(15);
            $objActSheet->getColumnDimension('D')->setWidth(12);
            $objActSheet->getColumnDimension('F')->setWidth(30);
            $objActSheet->getColumnDimension('G')->setWidth(15);
            $objActSheet->getColumnDimension('H')->setWidth(15);
            $objActSheet->getColumnDimension('I')->setWidth(13);
            $objActSheet->getColumnDimension('J')->setWidth(13);
            $objActSheet->getColumnDimension('K')->setWidth(15);
            $objActSheet->getColumnDimension('M')->setWidth(12);
            $objActSheet->getColumnDimension('N')->setWidth(28);
            $objActSheet->getColumnDimension('O')->setWidth(28);
            $objActSheet->getColumnDimension('P')->setWidth(28);
            $objActSheet->getColumnDimension('Q')->setWidth(28);

            // 全部居中
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(0, 0, 16, 3 + $count)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(0, 0, 16, 3 + $count)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            // 全部自动换行
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow(0, 0, 16, 3 + $count)->getAlignment()->setWrapText(true);

            ob_end_clean();
            ob_start();
            //保存文件
            $fileName = '导出任务填报' . time();
            header('pragma:public');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');  
            header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$fileName.'.xlsx"');
            header('Content-Disposition:attachment;filename=' . $fileName . '.xlsx');//attachment新窗口打印inline本窗口打印
            header('Cache-Control: max-age=0');
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  
            $objWriter->save('php://output');  

            exit();  
        }
        catch (\PHPExcel_Exception $ex)
        {
            return $ex->getMessage();
        }
    }

    //测试微信的推送
    public function ceshi()
    {
        Model('Task')->testPush();
    }
}
