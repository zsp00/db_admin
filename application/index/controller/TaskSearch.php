<?php
namespace app\index\controller;

class TaskSearch extends Common
{
	public function getTaskList($condition)
	{
		$condition = json_decode($condition);
		$where = array();
		if ($condition->taskLevel != '')
			$where['task.level'] = $condition->taskLevel;
		if ($condition->taskType != '')
			$where['tt.typeId'] = $condition->taskType;
		if ($condition->deptValue != '')
			$where['task.deptNo'] = $condition->deptValue;
		if ($condition->timeLimit != '')
			$where['task.timeLimit'] = date('Ym', strtotime($condition->timeLimit));
		if ($condition->leaderFirst != '')
			$where['t1.leader'] = ['in', implode(',', model('UserEmp')->where('EMP_NAME', $condition->leaderFirst)->column('EMP_NO'))];
		if ($condition->leaderSecond != '')
			$where['t2.leader'] = ['in', implode(',', model('UserEmp')->where('EMP_NAME', $condition->leaderSecond)->column('EMP_NO'))];
		if ($condition->leaderThird != '')
			$where['t3.leader'] = ['in', implode(',', model('UserEmp')->where('EMP_NAME', $condition->leaderThird)->column('EMP_NO'))];

		$tDate = date('Ym');

		$list = model('Task')->alias('task')
			->join('TaskLevelFirst t1', 'task.firstLevel=t1.id')
			->join('TaskLevelSecond t2', 'task.secondLevel=t2.id')
			->join('TaskLevelThird t3', 'task.thirdLevel=t3.id')
            ->join('TaskData td', 'task.id=td.tid and td.tDate=(select max(tDate) from d_task_data where tId=task.id)', 'left')
            ->join('TaskTasktype tt', 'task.id =tt.tId')
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
				'td.completeSituation'	=>	'complete',
				'td.problemSuggestions'	=>	'problem',
				'td.analysis'			=>	'analysis'
			])->where($where)->select();

		foreach ($list as $k => $v)
		{
			if (preg_match_all('/^\d*$/', $v['deptNo']))
				$list[$k]['deptNo'] = model('OrgDept')->getName($v['deptNo']);
			else 
			{
				$list[$k]['deptNo'] = $v['deptNo'];
				$tIds = model('RelevantDepartments')->where('relevantName', 'in', str_replace('、', ',', $v['deptNo']))->column('deptNo');

			}
			
			$typeIds = model('TaskTasktype')->where('tId', $v['id'])->column('typeId');
			$list[$k]['taskType'] = implode(',', model('TaskType')->where('id', 'in', implode(',', $typeIds))->column('typeName'));
		}
		$this->success('', '', $list);
	}
}