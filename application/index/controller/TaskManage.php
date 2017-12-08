<?php
namespace app\index\controller;
use app\index\model;
use think\Controller;

class TaskManage extends Common
{
    //获取全部任务列表
    public function getTaskList($page = '1', $listRow = '10')
    {
        $result = Model('Task')->page($page, $listRow)->select();
        $allTotal = count( Model('Task')->select());
        foreach($result as $k=>$v){
            $result[$k]['deptNo'] = Model('OrgDept')->where(['DEPT_NO' => $v['deptNo']])->value('DEPT_NAME');
//            $result[$k]['typeId'] = Model('TaskType')->where(['id' => $v['typeId']])->value('typeName');
            $result[$k]['pId'] = Model('Process')->where(['id' => $v['pId']])->value('name');
            $result[$k]['timeLimit'] = substr($v['timeLimit'],0,4).'年'.substr($v['timeLimit'],4,6).'月';
            $result[$k]['releaseTime'] = date('Y-m-d H:i:s',$v['releaseTime']);
            $result[$k]['completeTime'] = date('Y-m-d H:i:s',$v['completeTime']);
        }
        $result = array(
            'list'		=>	$result,
            'page'		=>	(int)$page,
            'listRow'	=>	(int)$listRow,
            'total'		=>	$allTotal
        );
        if($result){
            $this->success($result);
        }
    }

    // 获取所有的流程
    public function getProcess()
    {
        $result = Model('Process')->select();
        if($result){
            $this->success($result);
        }else{
            $this->error('流程错误！');
        }
    }
	// 添加任务
	public function addTask($taskInfo)
	{
		$task = array();
		$timeLimit = explode('-',$taskInfo['timeLimit']);
		if($timeLimit['1'] > '10'){
		    if($timeLimit['1'] == '12'){
                $time = ($timeLimit['0'] + 1) . '01';
            }else{
                $time = $timeLimit['0'] . ($timeLimit['1'] + 1);
            }
        }else{
            $time = $timeLimit['0'] .'0'.($timeLimit['1'] + 1);
        }
		$task['timeLimit'] = $time;
		$task['deptNo'] = $taskInfo['deptValue'];
		$task['serialNumber'] = 0;
		$task['content'] = $taskInfo['content'];
		$task['releaseTime'] = time();
		$task['level'] = $taskInfo['tasklevel'];
		$task['pId'] = $taskInfo['processValue'];
		$result = Model('Task')->insert($task);

		//获取最后一条插入的数据,插入task_tasktype表
        $tId = Model('Task')->getLastInsID();
		$taskType = ['tId'=>$tId,'typeId'=>$taskInfo['typeValue']];
		$result2 = Model('TaskTasktype')->insert($taskType);
		if($result && $result2){
			$this->success('添加任务成功');
		}else{
			$this->error('添加失败');
		}
	}
    //删除任务
    public function delTask($id)
    {
        if($id == '0'){
            $this->error('选择数据!');
        }
        if(is_numeric($id)){
            $result = Model('Task')->where(['id' => $id])->update(['status'=>'-1']);
            if($result){
                $this->success('删除成功!');
            }else{
                $this->error('删除失败!');
            }
        }else{
            foreach($id as $k=>$v){
                Model('Task')->where(['id' => $v['id']])->update(['status'=>'-1']);
            }
            $this->success('删除成功!');
        }
    }

    //获取督办任务列表
    public function getSuperviceList($keyword = '', $level = '', $typeId = '', $deptNo = '', $taskdataStatus = '', $page = '1', $listRow = '10')
    {
        //督办的查询条件
        $where = [
            'content'   =>  ['like','%'.$keyword.'%'],
            't.status'    =>  ['in', '1,2']
        ];
        if($level !== ''){
            $where['level'] = $level;
        }
        if($typeId !== ''){
            $where['typeId'] = $typeId;
        }
        if($deptNo !== ''){
            $where['t.deptNo'] = $deptNo[1];
        }
        if($taskdataStatus !== ''){
            if ($taskdataStatus == 'true')
                $where['td.status'] = 1;
            else
                $where['td.status'] = null;
        }
        $tDate = date('Ym', time());
        //督办的页面显示查询
        $result = Model('Task')
            ->alias('t')
            ->join('TaskTasktype tt','t.id =tt.tId ')
            ->join('TaskData td', 't.id=td.tid and td.tDate=' . $tDate, 'left')
            ->where($where)
            ->page($page, $listRow)
            ->group('t.id')
            ->field(['t.*','tt.typeId','tt.tId','td.status'=>'currMonthStatus'])
            ->select();
        //分页的数量
        $total = count(Model('Task')
            ->alias('t')
            ->join('TaskTasktype tt','t.id =tt.tId ')
            ->join('TaskData td', 't.id=td.tid and td.tDate=' . $tDate, 'left')
            ->where($where)
            ->group('t.id')
            ->field(['t.*'])
            ->select());
        //统计的所有需要督办的任务数量
        $allTotal = count(Model('Task')->where(['status' => ['in','1,2']])->select());
        $supNum = count(Model('Task')->alias('t')->join('TaskData td', 't.id=td.tid and td.tDate=' . $tDate, 'left')->where(['td.status'=>'1'])->group('td.tId')->select());

        foreach($result as $k=>$v){
            $rule = '/^\d*$/';
            $ruleResult = preg_match($rule, $v['deptNo'], $matches);
            if($ruleResult){
                $result[$k]['deptNo'] = Model('OrgDept')->where(['DEPT_NO' => $v['deptNo']])->value('DEPT_NAME');
            }else{
                $result[$k]['deptNo'] = $v['deptNo'];
            }
            $result[$k]['timeLimit'] = substr($v['timeLimit'],0,4).'年'.substr($v['timeLimit'],4,6).'月';
            $result[$k]['taskDataValue'] = Model('TaskData')->getTaskDataValue($v['id']);
            $typeNum = Model('TaskTasktype')->where(['tId'=> $v['id']])->select();
            foreach($typeNum as $k2=>$v2){
                $typeNum[$k2] = Model('TaskType')->where(['id'=>$v2['typeId']])->value('typeName');
            }
            $result[$k]['taskType'] = implode(',',$typeNum);
        }
        $result = array(
            'list'		=>	$result,
            'page'		=>	(int)$page,
            'listRow'	=>	(int)$listRow,
            'total'		=>	$total
        );
        $result['number']['allTotal'] = $allTotal;
        $result['number']['supNum'] = $supNum;
        $this->success($result);

    }

    // 获取任务列表里面的部门（task表）
    public function getTaskDeptNo()
    {
        $taskInfo = Model('Task')->where('status','<',3)->group('deptNo')->column('deptNo');
        foreach($taskInfo  as $k=>$v){
            $deptInfo = Model('OrgDept')->where(['DEPT_NO'=>$v])->find();
            $deptName = Model('OrgComp')->where(['COMP_NO'=>$deptInfo['COMP_NO']])->value('COMP_SHORT') . '/' .$deptInfo['DEPT_NAME'];
            $taskInfo[$k]= ['deptName' => $deptName, 'deptNo' => $v];
        }
        $this->success($taskInfo);
    }
    // 获取任务的分类
    public function getTaskType()
    {
        $taskType = Model('TaskType')->select();
        $this->success($taskType);
    }
    //督办任务
    public function taskSupervice($id)
    {
        $userInfo = getUserInfo();
        if(empty($id)){
            $this->error('请选择督办任务！');
        }
        $tDate = date('Ym',time());
        $previousMonth = date('Ym',strtotime("-1 month"));
        if(is_array($id)){
           $taskList = Model('Task')->where(['status' => ['in','1,2']])->select();
           $taskDataAll = array();
           $superviseRecordAll = array();
           foreach($taskList as $k=>$v){
               if(Model('TaskData')->where(['tId'=>$v['id'],'tDate'=>$tDate,'status'=>'1'])->find()){
                   continue;
               }
               $pId = Model('Task')->where(['id'=>$v['id']])->value('pId');
               $deptNo = Model('Task')->where(['id'=>$v['id']])->value('deptNo');
               //正则匹配是组织id还是各部室各部门
               $rule = '/^\d*$/';
               $ruleResult = preg_match($rule, $deptNo, $matches);
               if($ruleResult){
                   $deptNo = [$deptNo];
               }else{
                   $deptNo = Model('RelevantDepartments')->where('relevantName', 'in', str_replace('、', ',', $deptNo))->column('deptNo');
               }
               foreach($deptNo as $k2 => $v2){
                   $taskDateInfo = [
                       'tId' => $v['id'],
                       'pId' => $pId,
                       'deptNo' => $v2,
                       'currentLevel' => 1,
                       'nextLevel' => 2,
                       'tDate' => $tDate,
                   ];
                   //查看上个月有没有督办这个任务
                   $taskDataPreMonth = Model('TaskData')->where(['tDate'=>$previousMonth,'deptNo'=>$v2,'tId'=>$v['id'],'status'=>'0'])->find();
                   if($taskDataPreMonth){
                       $taskDateInfo['completeSituation'] = $taskDataPreMonth['completeSituation'];
                       $taskDateInfo['problemSuggestions'] = $taskDataPreMonth['problemSuggestions'];
                       $taskDateInfo['analysis'] = $taskDataPreMonth['analysis'];
                   }
//                   //查看督办记录里面有没有该记录
                   $superviseRecord = Model('SuperviseRecord')->where(['SrdeptNo'=>$v2,'srDate'=>$tDate,'tId'=>$v['id'],'srUser'=>$userInfo['EMP_NO']])->find();
                   if(!$superviseRecord){
                       $SupRecord = [
                           'srUser' => $userInfo['EMP_NO'],
                           'tId' => $v['id'],
                           'srDeptNo' => $v2,
                           'srDate' => $tDate,
                           'srTime' => time()
                       ];
                       $superviseRecordAll[] = $SupRecord;
                   }
                   $taskDataAll[] = $taskDateInfo;
               }
           }
            $result =Model('TaskData')->insertAll($taskDataAll);
            if(!empty($superviseRecordAll)){
                $result2 = Model('SuperviseRecord')->insertAll($superviseRecordAll);
            }
            if($result){
                $this->superviseChat(); //全部插入成功执行微信通知
                $this->success('任务全部督办成功！');
            }else{
                $this->error('任务督办失败！');
            }
        }else{
            $pId = Model('Task')->where(['id'=>$id])->value('pId');
            //正则匹配是组织id还是各部室各部门
            $deptNo = Model('Task')->where(['id'=>$id])->value('deptNo');
            $rule = '/^\d*$/';
            $ruleResult = preg_match($rule, $deptNo, $matches);
            if($ruleResult){
                $deptNo = [$deptNo];
            }else{
                $deptNo = Model('RelevantDepartments')->where('relevantName', 'in', str_replace('、', ',', $deptNo))->column('deptNo');
            }
            //数据处理保存数据库
            foreach($deptNo as $k => $v){
                $taskDateInfo = [
                    'tId' => $id,
                    'pId' => $pId,
                    'deptNo' => $v,
                    'currentLevel' => 1,
                    'nextLevel' => 2,
                    'tDate' => $tDate,
                ];
                //查看上个月有没有督办这个任务
                $taskDataPreMonth = Model('TaskData')->where(['tDate'=>$previousMonth,'deptNo'=>$v,'tId'=>$id,'status'=>'0'])->find();
                if($taskDataPreMonth){
                    $taskDateInfo['completeSituation'] = $taskDataPreMonth['completeSituation'];
                    $taskDateInfo['problemSuggestions'] = $taskDataPreMonth['problemSuggestions'];
                    $taskDateInfo['analysis'] = $taskDataPreMonth['analysis'];
                }
                $result =Model('TaskData')->insert($taskDateInfo);
                $superviseRecord = Model('SuperviseRecord')->where(['SrdeptNo'=>$v,'srDate'=>$tDate,'tId'=>$id,'srUser'=>$userInfo['EMP_NO']])->find();
                if($superviseRecord){
                    $SupRecord = [
                        'srUser' => $userInfo['EMP_NO'],
                        'tId' => $id,
                        'srDeptNo' => $v,
                        'srDate' => $tDate,
                        'srTime' => time()
                    ];
                    $result2 = Model('SuperviseRecord')->insert($SupRecord);
                }
            }
            if($result){
                $this->success('任务督办成功！');
            }else{
                $this->error('任务督办失败！');
            }
        }
    }

    //全部督办触发该方法，向用户推送消息
    public function superviseChat()
    {
        //查询所有的不重复的部门下面的所有的人获取他的userId
        $AlldeptNo = array_unique(Model('TaskData')->column('deptNo'));
        foreach($AlldeptNo as $k=>$v){
            $empNo= Model('UserEmp')->where(['DEPTNO'=>$v])->column('EMP_NO');
            foreach($empNo as $k2=>$v2){
                $empNoAll[] = $v2;
            }
        }
        $userId = Model('Task')->getUserId($empNoAll);
        array_push($userId,'37162');

        $pushChat= Model('Task')->weChatPush($userId,'督办任务被下发请您开始填报');
    }
}



