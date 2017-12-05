<?php
namespace app\index\controller;

class TaskSearch extends Common
{
	public function getTaskList($condition, $page = '1', $listRow = '10')
	{
		$condition = json_decode($condition);
        $where = [
            'content'   =>  ['like','%'.$condition->keyword.'%']
        ];
        if ($condition->taskLevel != '')
            $where['task.level'] = $condition->taskLevel;
        if ($condition->taskType != '')
            $where['tt.typeId'] = $condition->taskType;
        if ($condition->deptValue != [])
            $where['task.deptNo'] = $condition->deptValue[1];
        if ($condition->timeLimit != '')
            $where['task.timeLimit'] = date('Ym', strtotime($condition->timeLimit));
        if ($condition->leaderFirst != '')
            $where['t1.leader'] = $condition->leaderFirst;
        if ($condition->leaderSecond != '')
            $where['t2.leader'] = $condition->leaderSecond;
        if ($condition->leaderThird != '')
            $where['t3.leader'] = $condition->leaderThird;
        if ($condition->taskDataStauts != '')
        {
            if ($condition->taskDataStauts == '3')
                $where['task.status'] = '3';
            else 
                $where['task.status'] = ['<>', '3'];
        }
		$list = model('Task')->getTaskList($where, false, $page, $listRow);
		$total = model('Task')->alias('task')
			->join('TaskLevelFirst t1', 'task.firstLevel=t1.id')
			->join('TaskLevelSecond t2', 'task.secondLevel=t2.id')
			->join('TaskLevelThird t3', 'task.thirdLevel=t3.id')
            ->join('TaskData td', 'task.id=td.tid and td.tDate=(select max(tDate) from d_task_data where tId=task.id)', 'left')
            ->join('TaskTasktype tt', 'task.id=tt.tId')->where($where)->group('task.id')->count();

		foreach ($list as $k => $v)   
		{
			// 如果任务部门的Id是数字，查询出对应的部门名称
			if (preg_match_all('/^\d*$/', $v['deptNo']))
				$list[$k]['deptNo'] = model('OrgDept')->getName($v['deptNo']);
			else 
				// 如果不是数字，查询出关联的所有部门的填报情况，拼凑橙字符串显示
				$list[$k]['deptNo'] = $v['deptNo'];

			$typeIds = model('TaskTasktype')->where('tId', $v['id'])->column('typeId');
			$list[$k]['taskType'] = implode(',', model('TaskType')->where('id', 'in', implode(',', $typeIds))->column('typeName'));
		}

		$result = array(
			'list'		=>	$list,
			'page'		=>	(int)$page,
			'listRow'	=>	(int)$listRow,
			'total'		=>	$total
		);

		$this->success('', '', $result);
	}

	public function exportList($condition)
	{
		$condition = json_decode($condition);
        $where = [
            'content'   =>  ['like','%'.$condition->keyword.'%']
        ];
        if ($condition->taskLevel != '')
            $where['task.level'] = $condition->taskLevel;
        if ($condition->taskType != '')
            $where['tt.typeId'] = $condition->taskType;
        if ($condition->deptValue != [])
            $where['task.deptNo'] = $condition->deptValue[1];
        if ($condition->timeLimit != '')
            $where['task.timeLimit'] = date('Ym', strtotime($condition->timeLimit));
        if ($condition->leaderFirst != '')
            $where['t1.leader'] = $condition->leaderFirst;
        if ($condition->leaderSecond != '')
            $where['t2.leader'] = $condition->leaderSecond;
        if ($condition->leaderThird != '')
            $where['t3.leader'] = $condition->leaderThird;
        if ($condition->taskDataStauts != '')
        {
            if ($condition->taskDataStauts == '3')
                $where['task.status'] = '3';
            else 
                $where['task.status'] = ['<>', '3'];
        }
		$list = model('Task')->getTaskList($condition, true);

		try 
    	{
	    	//导出Excel
	    	$objPHPExcel = new \PHPExcel();
	    	//应用第一个sheet页
	    	$objPHPExcel->setActiveSheetIndex(0);
	    	
	    	
	    	
	    	//保存文件
			// Redirect output to a client’s web browser (Excel2007)
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="任务记录.xlsx"');
			header('Cache-Control: max-age=0');
			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');
			
			// If you're serving to IE over SSL, then the following may be needed
			header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
			header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header ('Pragma: public'); // HTTP/1.0
			
			$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			$objWriter->save('php://output');
			exit;
    	}
    	catch (\PHPExcel_Exception $ex)
    	{
    		return $ex->getMessage();
    	}
	}
}