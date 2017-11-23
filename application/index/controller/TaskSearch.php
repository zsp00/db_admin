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
            ->join('Process p', 'task.pId=p.id')
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
				'td.analysis'			=>	'analysis',
				'p.name'				=>	'pName'
			])->where($where)->select();

		$serialNum = $title1 = $title2 = $detail2 = $detail3 = $duty3 = array([], []);
		$serialNumIndex = $title1Index = $title2Index = $detail2Index = $detail3Index = $duty3Index = 0;

		foreach ($list as $k => $v)
		{
			$list[$k]['leader1'] = model('UserEmp')->getUserRealName($v['leader1']);
			$list[$k]['leader2'] = model('UserEmp')->getUserRealName($v['leader2']);
			$list[$k]['leader3'] = model('UserEmp')->getUserRealName($v['leader3']);
			$list[$k]['deptNo2'] = model('OrgDept')->getName($v['deptNo2']);
			$deptNo = model('OrgDept')->getName($v['deptNo']);
			$list[$k]['deptNo'] = $deptNo == '' ? $v['deptNo'] : $deptNo;
			$list[$k]['timeLimit'] = substr($v['timeLimit'],0,4).'年'.substr($v['timeLimit'],4,6).'月';
			$typeIds = model('TaskTasktype')->where('tId', $v['id'])->column('typeId');
			$list[$k]['taskType'] = implode(',', model('TaskType')->where('id', 'in', implode(',', $typeIds))->column('typeName'));

			/* 计算序号列哪些行要合并单元格
			 * 组成数组形如：array(2, 0, 2, 0, 4, 0, 0, 0……)
			 * 连续相同的元素有几个，就在第一次出现的位置记录个数，剩余出现的位置用0占位
			 */
			if (count($serialNum[1]) == 0)
			{
				$serialNum[0][0] = 1;
				$serialNum[1][0] = $v['serialNum'];
			}
			else 
			{
				if ($v['serialNum'] === $list[$k - 1]['serialNum'])
				{
					$serialNum[0][$serialNumIndex]++;
					$serialNum[0][$k] = 0;
					$serialNum[1][$k] = $v['serialNum'];
				}
				else
				{
					$serialNum[0][$k] = 1;
					$serialNumIndex = $k;
					$serialNum[1][$k] = $v['serialNum'];
				}
			}

			// 计算一级目标任务的两列合并单元格
			if (count($title1[1]) == 0)
			{
				$title1[0][0] = 1;
				$title1[1][0] = $v['title1'];
			}
			else 
			{
				if ($v['title1'] === $list[$k - 1]['title1'])
				{
					$title1[0][$title1Index]++;
					$title1[0][$k] = 0;
					$title1[1][$k] = $v['title1'];
				}
				else
				{
					$title1[0][$k] = 1;
					$title1Index = $k;
					$title1[1][$k] = $v['title1'];
				}
			}

			// 计算二级目标任务的第一列列合并单元格
			if (count($title2[1]) == 0)
			{
				$title2[0][0] = 1;
				$title2[1][0] = $v['title2'];
			}
			else 
			{
				if ($v['title2'] === $list[$k - 1]['title2'])
				{
					$title2[0][$title2Index]++;
					$title2[0][$k] = 0;
					$title2[1][$k] = $v['title2'];
				}
				else
				{
					$title2[0][$k] = 1;
					$title2Index = $k;
					$title2[1][$k] = $v['title2'];
				}
			}

			// 计算二级目标任务的第二列合并单元格
			if (count($detail2[1]) == 0)
			{
				$detail2[0][0] = 1;
				$detail2[1][0] = $v['detail2'];
			}
			else 
			{
				if ($v['detail2'] === $list[$k - 1]['detail2'])
				{
					$detail2[0][$detail2Index]++;
					$detail2[0][$k] = 0;
					$detail2[1][$k] = $v['detail2'];
				}
				else
				{
					$detail2[0][$k] = 1;
					$detail2Index = $k;
					$detail2[1][$k] = $v['detail2'];
				}
			}

			// 计算三级目标任务合并单元格
			if (count($detail3[1]) == 0)
			{
				$detail3[0][0] = 1;
				$detail3[1][0] = $v['detail3'];
			}
			else 
			{
				if ($v['detail3'] === $list[$k - 1]['detail3'])
				{
					$detail3[0][$detail3Index]++;
					$detail3[0][$k] = 0;
					$detail3[1][$k] = $v['detail3'];
				}
				else
				{
					$detail3[0][$k] = 1;
					$detail3Index = $k;
					$detail3[1][$k] = $v['detail3'];
				}
			}

			// 计算三级目标任务第二列合并单元格
			if ($v['duty3'] == '-')
			{
				$duty3[0][$k] = 0;
				$duty3Index = $k;
				$duty3[1][$k] = $v['duty3'];
			}
			else 
			{
				if ($v['duty3'] === $list[$k - 1]['duty3'])
				{
					$duty3[0][$duty3Index]++;
					$duty3[0][$k] = 0;
					$duty3[1][$k] = $v['duty3'];
				}
				else
				{
					$duty3[0][$k] = 1;
					$duty3Index = $k;
					$duty3[1][$k] = $v['duty3'];
				}
			}
		}

		foreach ($detail3[0] as $k => $v)
		{
			if ($v != 0 && $duty3[0][$k] == 0)
				$duty3[0][$k] = $v;
		}

		$result = [$list, $serialNum[0], $title1[0], $title2[0], $detail2[0], $detail3[0], $duty3[0]];

		$this->success('', '', $result);
	}
}