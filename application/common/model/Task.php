<?php
namespace app\common\model;
use think\Db;
use think\Model;

class Task extends Model
{
    protected $taskStatusMsg = [
        '0'     =>  '驳回',
        '1'     =>  '填报中',
        '2'     =>  '审核中'
    ];
    protected $statusMsg = [
        '-1'    =>  '删除',
        '0'     =>  '禁用',
        '1'     =>  '未完成',
        '2'     =>  '完成'
    ];
    protected $_participateLevel = null;

    public function getList($map, $tDate, $page = 1, $listRow = 20, $needToDo = true) {
        $result = [
            'data'  =>  null,
            'total' =>  0,
            'currPage' => $page
        ];
        $flag = false;    // 是否有查看所有任务列表的权限
        $where = [];

        $userInfo = getUserInfo();
        $empNo = $userInfo['EMP_NO'];
        $deptNo = model('OrgDept')->getDeptNo($userInfo['DEPTNO']);
		$ifStatus = '';
        foreach($map as $k=>$v)
        {
            if ($k == 'typeId'){
				$where['task_tasktype.'.$k] = $v;
			} else if ($k == 'ifStatus'){
				$ifStatus = $map['ifStatus'];
                $flag = true;				
			} else{
                $where['task.'.$k] = $v;				
			}

        }
        if ($needToDo == 'true')
        {
            $model = $this->alias('task')
                ->join('TaskLevelFirst t1', 'task.firstLevel=t1.id')
                ->join('TaskLevelSecond t2', 'task.secondLevel=t2.id')
                ->join('TaskLevelThird t3', 'task.thirdLevel=t3.id')
                ->join('task_data', 'task.id = task_data.tId and task_data.status=1 and task_data.tDate='.$tDate)
                ->join('task_tasktype', 'task.id=task_tasktype.tId')
                ->join('process_data', 'task_data.currentLevel=process_data.levelNo and task.pId=process_data.pId', 'left')
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
                    't1.leader'				=>	'leader1',
                    't1.title'				=>	'title1',
                    't1.detail'				=>	'detail1',
                    't2.leader'				=>	'leader2',
                    't2.title'				=>	'title2',
                    't2.detail'				=>	'detail2',
                    't2.deptNo'				=>	'deptNo2',
                    't3.serialNum'			=>	'serialNum',
                    't3.detail'				=>	'detail3',
                    't3.duty'				=>	'duty3',
                    't3.leader'				=>	'leader3',
                    'task_data.completeSituation',
                    'task_data.problemSuggestions',
                    'task_data.analysis',
                    'task_data.currentLevel'    =>  'taskDataStatus',
                    'task_data.status'          =>  'currMonthStatus',
                    'process_data.commitAll'    =>  'commitAll'
                ]);
            $list = $model->page($page,$listRow)
                ->group('task.id')->select();
            $result['total'] = $this->alias('task')
                ->join('task_data', 'task.id = task_data.tId and task_data.status=1 and task_data.tDate='.$tDate)
                ->join('task_tasktype', 'task.id=task_tasktype.tId')
                ->join('process_data', 'task_data.currentLevel=process_data.levelNo and task.pId=process_data.pId', 'left')
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
                })->group('task.id')->count();
            $result['dbCount'] = $this->alias('task')
                ->join('task_data', 'task.id = task_data.tId')
                ->join('task_tasktype', 'task.id=task_tasktype.tId')
                ->where($where)->group('task.id')->count();
        }
        else 
        {
            $model = $this->alias('task')
                ->join('TaskLevelFirst t1', 'task.firstLevel=t1.id')
                ->join('TaskLevelSecond t2', 'task.secondLevel=t2.id')
                ->join('TaskLevelThird t3', 'task.thirdLevel=t3.id')
                ->join('task_data', 'task.id = task_data.tId and task_data.tDate='.$tDate)
                ->join('task_tasktype', 'task.id=task_tasktype.tId')
                ->where($where)
                ->field([
                    'task.*',
                    't1.leader'				=>	'leader1',
                    't1.title'				=>	'title1',
                    't1.detail'				=>	'detail1',
                    't2.leader'				=>	'leader2',
                    't2.title'				=>	'title2',
                    't2.detail'				=>	'detail2',
                    't2.deptNo'				=>	'deptNo2',
                    't3.serialNum'			=>	'serialNum',
                    't3.detail'				=>	'detail3',
                    't3.duty'				=>	'duty3',
                    't3.leader'				=>	'leader3',
                    'task_data.completeSituation',
                    'task_data.problemSuggestions',
                    'task_data.analysis',
                    'task_data.currentLevel'    =>  'taskDataStatus',
                    'task_data.status'          =>  'currMonthStatus'
                ]);
            $list = $model->page($page,$listRow)
                ->group('task.id')->select();
            $result['total'] = $this->alias('task')
                ->join('task_data', 'task.id = task_data.tId')
                ->join('task_tasktype', 'task.id=task_tasktype.tId')
                ->where($where)->group('task.id')->count();
            $result['dbCount'] = $result['total'];
        }
        $commitNum = 0;
        $taskList = array();    // 当数组的键不是从0开始，ajax传输后会被转为object，所以重新定义数组
        if($list){
            $OrgDept = new OrgDept();
            foreach($list as $k=>$v)
            {
                $describe = model('ProcessData')->where(['pId'=>$v['pId'], 'levelNo'=>$v['taskDataStatus']])->value('pDescribe');
                $list[$k]['statusMsg'] = ($describe == '' ? ('步骤' . $v['taskDataStatus']) : $describe) . ($v['taskDataStatus'] == 1 ? '填报中' : ($v['currMonthStatus'] == 1 ? '审批中' : '完成审批'));
                $list[$k]['deptNo'] = $OrgDept->where(['DEPT_NO'=>$v['deptNo']])->value('DEPT_NAME');
                $list[$k]['timeLimit'] = substr_replace($v['timeLimit'], '年', 4, 0) . '月';
                $typeIds = model('TaskTasktype')->where('tId', $v['id'])->column('typeId');
                $list[$k]['typeName'] = implode(',', model('TaskType')->where(['id'=>['in', implode(',', $typeIds)]])->column('typeName'));
                $list[$k]['leader1'] = Model('UserEmp')->getUserRealName($v['leader1']);
                $list[$k]['leader2'] = Model('UserEmp')->getUserRealName($v['leader2']);
                $list[$k]['leader3'] = Model('UserEmp')->getUserRealName($v['leader3']);
                $list[$k]['deptNo2'] = Model('OrgDept')->getName($v['deptNo2']);
                $participateLevel = Model('ProcessData')->getStepIds($v['pId']);       // 当前用户能参与到的步骤
                $list[$k]['getStepIds'] = $participateLevel['0'];
                $list[$k]['getTaskStatusMsg'] = Model('Task')->getTaskStatusMsg($v['id']);
                if (count($participateLevel) < 1)   // 如果用户不能参与到任务的任何流程，则跳过该任务
                    continue;

                if ($v['taskDataStatus'] >= $participateLevel[0])    // 针对于当前登录用户  判断本月提交了多少个任务
                    $commitNum++;
/*                 if ($flag)                  // 需要按照是否提交检索
                {
                    if ($map['ifCommit'] == 'true')      // 按照已提交检索
                    {
                        if ($v['taskDataStatus'] >= $participateLevel[0])      // 该任务当前没有进行到该用户能参与到的步骤，未提交
                            $taskList[] = $list[$k];
                    }
                    elseif($map['ifCommit'] == 'false')  //　按照未提交检索
                    {
                        if ($v['taskDataStatus'] < $participateLevel[0])    // 该任务当前进行到该用户能参与到的步骤，已提交
                            $taskList[] = $list[$k];
                    }
                }
                else */				
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

        $result['commitNum'] = $commitNum;
        $result['data'] = $taskList;
        return $result;
    }

    public function getInfo($id)
    {
        $info = $this->where(['id'=>$id])->find();
        if($info) {
            $task = Model('Task')->where(['id'=>$id])->find();
            $TaskData = new TaskData();
            $taskDataList = $TaskData->where(['tId'=>$id])->order(['tDate desc'])->select();
            foreach($taskDataList as $k=>$v)
            {
                $taskDataList[$k]['taskSelect'] = $task['status'] == '1' ? false : true;
                $taskDataList[$k]['commitAll'] = model('ProcessData')->where(['pId'=>$info['pId'], 'levelNo'=>$v['currentLevel']])->value('commitAll');
                $steps = array();
                // 获取流程步骤，拼凑页面步骤条数据
                $processData = model('ProcessData')->where('pId', $info['pId'])->order('levelNo')->select();
                foreach ($processData as $kk => $vv)
                {
                    // 如果没有描述字段则显示步骤编号
                    if ($vv['pDescribe'] != '')
                        $label = $vv['pDescribe'];
                    else
                        $label = '步骤' . $kk;

                    if ($vv['levelNo'] < $v['currentLevel'])
                        $label .= '：已完成';
                    elseif ($vv['levelNo'] == $v['currentLevel'])
                    {
                        if ($v['status'] == 0)
                            $label .= '：已完成';
                        else
                            $label .= '：进行中';
                    }
                    else
                        $label .= '：待办';

                    $steps[$kk]['label'] = $label;
                    $steps[$kk]['participate'] = $this->getParticipateName($vv['audit_user']);
                }
                $taskDataList[$k]['steps'] = $steps;
                // 步骤条第一步，显示发起督办任务信息
                $superviseRecord = model('SuperviseRecord')->where(['tId'=>$id, 'srDate'=>$v['tDate']])->find();
                $stepFirst['fullName'] = getAllName('person', $superviseRecord['srUser'], true);  // 名字到公司的组织结构
                $stepFirst['name'] = substr($stepFirst['fullName'], strrpos($stepFirst['fullName'], '/') + 1);   // 名字
                $stepFirst['text'] = '于 ' . $superviseRecord['srTime'] . ' 对该任务发起了督办';
                $taskDataList[$k]['stepFirst'] = $stepFirst;

                // 页面显示月份用
                $taskDataList[$k]['tDate'] = substr($v['tDate'],4);
            }
            $info['identitys'] = $this->_participateLevel == null ? Model('ProcessData')->getStepIds($info['pId']) : $this->_participateLevel;

            $taskDataStatusMsg = new TaskData();
            $info['taskDataList'] = $taskDataList;
            
            return $info;
        }else{
            return false;
        }
    }

    private function getParticipateName($str)
    {
        $arr = json_decode($str, true);
        $participate = array();
        $notIn = array();
        foreach ($arr as $k => $v)
        {
            if ($v == '')
                continue;
            $ids = explode(',', $v);
            foreach ($ids as $kk => $vv)
            {
                if ($k == 'notIn')
                {
                    $fullName = getAllName('person', $vv, true);
                    $notIn[$kk]['name'] = substr($fullName, strrpos($fullName, '/') + 1);
                    $notIn[$kk]['fullName'] = $fullName;
                }
                else
                {
                    $fullName = getAllName($k, $vv, true);
                    $participate[$kk]['name'] = substr($fullName, strrpos($fullName, '/') + 1);
                    $participate[$kk]['fullName'] = $fullName;
                }
            }
        }
        return [$participate, $notIn];
    }
    /**
     * 获取任务某个月的状态
     * @param  int $id            任务Id
     * @return string             任务状态信息（0 =》驳回，1 =》填报中，2 =》审核中）
     */
    public function getTaskStatusMsg($id)
    {
        $status = '';
        $taskDataStatusMsg = new Task();
        $pId = Model('Task')->where(['id'=>$id])->value('pId');
        $maxLevel = Model('Process')->where('id', $pId)->value('level');
        $taskData = Model('TaskData')->where(['tId'=>$id,'status'=>'1'])->find();
		$currentLevel = $taskData['currentLevel'];
		$taskLog = Model('taskLog')->where(['tId'=>$id,'tDId'=>$taskData['id']])->select();
		$lastTaskLog = array_pop($taskLog);
        if($lastTaskLog['type'] == 'reject'){
			$status = $taskDataStatusMsg->taskStatusMsg[0];
		} else if($currentLevel < '2'){
            $status = $taskDataStatusMsg->taskStatusMsg[1];
        } else if($currentLevel <= $maxLevel){
            $status = $taskDataStatusMsg->taskStatusMsg[2];
        } 
        return $status;
    }

    /**
     * 获取任务某个月的状态
     * @param  int $pId             任务执行的流程Id
     * @param  int $currStep        任务当前进行到了第几步
     * @param  int $currMonthStatus 任务在当前月的状态，task_data表中的status
     * @return string                  任务状态信息
     */
    public function getStatusMsg($pId, $currStep, $currMonthStatus)
    {
        $msg = '';
        $stepIds = $this->_participateLevel == null ? Model('ProcessData')->getStepIds($pId) : $this->_participateLevel;
        $processLevel = model('Process')->where('id', $pId)->value('level');
        $taskDataStatusMsg = new TaskData();
        if ($stepIds[0] >= $processLevel - 1)
        {
            if ($currStep <= $processLevel - 1)
                $msg = $taskDataStatusMsg->statusMsg[1];
            elseif ($currStep == $processLevel && $currMonthStatus == 0)       // 已确认
                $msg = $taskDataStatusMsg->statusMsg[3];
            else
                $msg = $taskDataStatusMsg->statusMsg[2];
        }
        else
        {
            if ($currStep <= $stepIds[0])
                $msg = $taskDataStatusMsg->statusMsg[1];
            elseif ($currStep == end($stepIds) + 1)
                $msg = $taskDataStatusMsg->statusMsg[2];
            else
                $msg = $taskDataStatusMsg->statusMsg[3];
        }
        return $msg;
    }
}