<?php
namespace app\index\controller;

class TaskSearch extends Common
{
	public function getTaskList($condition, $page = '1', $listRow = '10')
	{
		$condition = json_decode($condition);

		$userInfo = getUserInfo();
        $deptNo = getSubDeptNo(model('OrgDept')->getDeptNo($userInfo['DEPTNO']));
        $compNo = model('OrgDept')->getCompNo($deptNo);

        if ($condition->taskLevel != '')
            $where['task.level'] = $condition->taskLevel;
        if ($condition->taskType != '')
            $where['tt.typeId'] = $condition->taskType;
        if ($condition->deptValue != [])
        {
        	if ($condition->deptValue[0] != 0)
        	{
        		if ($condition->deptValue[1] == '011209')
        		{
        			$where['td.deptNo'] = $condition->deptValue[2];
	            	$comp = model('OrgDept')->getCompNo($condition->deptValue[2]);
	            	$returnDept = [(int)$comp, $condition->deptValue[1], $condition->deptValue[2]];
        		}
        		else 
        		{
        			$where['td.deptNo'] = $condition->deptValue[1];
            		$comp = model('OrgDept')->getCompNo($condition->deptValue[1]);
            		$returnDept = [(int)$comp, $condition->deptValue[1]];
        		}
        	}
        	else 
        	{
        		$returnDept = [0];
        	}
        }
        else 
        {
        	$where['td.deptNo'] = $deptNo;
        	$returnDept = [(int)$compNo, $deptNo];
        }
        if ($condition->timeLimit != '')
        {
        	$month = date('m', strtotime($condition->timeLimit));
        	$tDate = date('Ym', strtotime($condition->timeLimit));
        }
        else 
        {
        	$month = date('m', strtotime('-2 month'));
        	$tDate = date('Ym', strtotime('-2 month'));
        }
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
        $year = substr($tDate,0,4);
        $start = $year.'01';
        $end = $year.'12';
        $where = [
            'content'   =>  ['like','%'.$condition->keyword.'%'],
            'timeLimit' => ['between',"$start,$end"]
        ];
		$list = model('Task')->getTaskList($where, false, $tDate, $page, $listRow);
		// dump($list);exit;
		$total = model('Task')->alias('task')
			->join('TaskLevelFirst t1', 'task.firstLevel=t1.id')
			->join('TaskLevelSecond t2', 'task.secondLevel=t2.id')
			->join('TaskLevelThird t3', 'task.thirdLevel=t3.id')
            ->join('TaskData td', 'task.id=td.tId and td.tDate='.$tDate, 'left')
            ->join('TaskTasktype tt', 'task.id=tt.tId')->where($where)->group('task.id')->count();

		foreach ($list as $k => $v)   
		{
			$typeIds = model('TaskTasktype')->where('tId', $v['id'])->column('typeId');
			$list[$k]['taskType'] = implode(',', model('TaskType')->where('id', 'in', implode(',', $typeIds))->column('typeName'));
		}

		$result = array(
			'list'		=>	$list,
			'page'		=>	(int)$page,
			'listRow'	=>	(int)$listRow,
			'total'		=>	$total,
			'month'		=>	$month,
			'dept'		=>	$returnDept,
			'tDate'		=>	$tDate
		);

		$this->success('', '', $result);
	}

	public function exportList($condition)
	{
		$userInfo = getUserInfo();
        $deptNo = getSubDeptNo(model('OrgDept')->getDeptNo($userInfo['DEPTNO']));
		$condition = json_decode($condition);
        $where = [
            'content'   =>  ['like','%'.$condition->keyword.'%']
        ];
        if ($condition->taskLevel != '')
            $where['task.level'] = $condition->taskLevel;
        if ($condition->taskType != '')
            $where['tt.typeId'] = $condition->taskType;
        if ($condition->deptValue != [])
        {
        	if ($condition->deptValue[0] != 0)
        	{
        		if ($condition->deptValue[1] == '011209')
        			$where['td.deptNo'] = $condition->deptValue[2];
        		else 
        			$where['td.deptNo'] = $condition->deptValue[1];
        	}
        }
        else 
        	$where['td.deptNo'] = $deptNo;
        if ($condition->timeLimit != '')
        {
        	$month = date('m', strtotime($condition->timeLimit));
        	$tDate = date('Ym', strtotime($condition->timeLimit));
        }
        else 
        {
        	$month = date('m', strtotime('-2 month'));
        	$tDate = date('Ym', strtotime('-2 month'));
        }
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
		$list = model('Task')->getTaskList($where, true, $tDate);
		$count = count($list);

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
	    	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(14, 3, $month . '月完成情况');
	    	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(15, 3, $month . '月问题建议');
	    	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(16, 3, $month . '月原因分析');

	    	// 定义几个合并单元格要用到的变量
	    	$serialNum = $title1 = $title2 = $detail2 = $detail3 = $duty3  = $duty = '';
	    	$serialNumIndex = $title1Index = $title2Index = $detail2Index = $detail3Index = $duty3Index = $dutyIndex = 0;
	    	$fileds = array('serialNum' => 0, 'title1' => 1, 'detail1' => 2, 'leader1' => 3, 'title2' => 4, 'detail2' => 5, 'leader2' => 6, 'deptNo2' => 7, 'detail3' => 8, 'duty3' => 9, 'leader3' => 10, 'duty' => 12, 'content' => 13, 'complete' => 14, 'problem' => 15, 'analysis' => 16);

	    	foreach ($list as $k => $v)
	    	{
	    		foreach ($fileds as $kk => $vv)
	    			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($vv, 4 + $k, $v[$kk]);
	    		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(11, 4 + $k, $v['deptName']);

	    		// 判断单元格是否需要合并，$k+4是当前要合并的行号，$index是合并起始的行号
	    		// 序号列，三级目标任务
	    		if ($v['serialNum'] == $serialNum)
	    		{
	    			if ($k + 4 - $serialNumIndex > 1)   // 当要合并3行以上时，先把之前合并的两行或者多行解除合并
	    				$objPHPExcel->getActiveSheet()->unmergeCellsByColumnAndRow(0, $serialNumIndex, 0, 4 + $k - 1);
	    			$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(0, $serialNumIndex, 0, 4 + $k);
	    		}
	    		else
	    		{
	    			$serialNum = $v['serialNum'];
	    			$serialNumIndex = $k + 4;
	    		}
	    		// 一级任务
	    		if ($v['title1'] == $title1)
	    		{
	    			if ($k + 4 - $title1Index > 1)
	    			{
	    				$objPHPExcel->getActiveSheet()->unmergeCellsByColumnAndRow(1, $title1Index, 1, 4 + $k - 1);
	    				$objPHPExcel->getActiveSheet()->unmergeCellsByColumnAndRow(2, $title1Index, 2, 4 + $k - 1);  
	    				$objPHPExcel->getActiveSheet()->unmergeCellsByColumnAndRow(3, $title1Index, 3, 4 + $k - 1);  
	    			}
	    			$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(1, $title1Index, 1, 4 + $k);
	    			$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(2, $title1Index, 2, 4 + $k);
	    			$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(3, $title1Index, 3, 4 + $k);
	    		}
	    		else
	    		{
	    			$title1 = $v['title1'];
	    			$title1Index = $k + 4;
	    		}
	    		// 二级目标任务
	    		if ($v['title2'] == $title2)
	    		{
	    			if ($k + 4 - $title2Index > 1)
	    				$objPHPExcel->getActiveSheet()->unmergeCellsByColumnAndRow(4, $title2Index, 4, 4 + $k - 1);
	    			$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(4, $title2Index, 4, 4 + $k);
	    		}
	    		else
	    		{
	    			$title2 = $v['title2'];
	    			$title2Index = $k + 4;
	    		}
	    		// 二级目标任务
	    		if ($v['detail2'] == $detail2)
	    		{
	    			if ($k + 4 - $detail2Index > 1)
	    			{
	    				$objPHPExcel->getActiveSheet()->unmergeCellsByColumnAndRow(5, $detail2Index, 5, 4 + $k - 1);
	    				$objPHPExcel->getActiveSheet()->unmergeCellsByColumnAndRow(6, $detail2Index, 6, 4 + $k - 1);  
	    				$objPHPExcel->getActiveSheet()->unmergeCellsByColumnAndRow(7, $detail2Index, 7, 4 + $k - 1);  
	    			}
	    			$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(5, $detail2Index, 5, 4 + $k);
	    			$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(6, $detail2Index, 6, 4 + $k);
	    			$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(7, $detail2Index, 7, 4 + $k);
	    		}
	    		else
	    		{
	    			$detail2 = $v['detail2'];
	    			$detail2Index = $k + 4;
	    		}
	    		// 三级目标任务
	    		if ($v['detail3'] == $detail3)
	    		{
    				if ($v['duty3'] == '-' || $v['duty3'] == '')   // 如果是一列则把第二列也一同拆分
    					$objPHPExcel->getActiveSheet()->unmergeCellsByColumnAndRow(8, $detail3Index, 9, 4 + $k - 1);
    				else
    					$objPHPExcel->getActiveSheet()->unmergeCellsByColumnAndRow(8, $detail3Index, 8, 4 + $k - 1);
	    		}
	    		else
	    		{
	    			$detail3 = $v['detail3'];
	    			$detail3Index = $k + 4;
	    		}
	    		if ($v['duty3'] == '-' || $v['duty3'] == '')   // 如果是一列则把第二列也一同合并
    				$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(8, $detail3Index, 9, 4 + $k);
    			else
    			{
    				$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(8, $detail3Index, 8, 4 + $k);   // 单合并两列
    				if ($v['duty3'] == $duty3)        // 三级目标任务的责任列合并
		    		{
		    			if ($k + 4 - $duty3Index > 1)
		    				$objPHPExcel->getActiveSheet()->unmergeCellsByColumnAndRow(9, $duty3Index, 9, 4 + $k - 1);
		    			$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(9, $duty3Index, 9, 4 + $k);
		    		}
		    		else
		    		{
		    			$duty3 = $v['duty3'];
		    			$duty3Index = $k + 4;
		    		}
    			}
    			// 责任部室、二级单位、目标任务
    			if ($v['duty'] == '-')           // 如果目标任务为‘-’，把责任部室与其合并
    			{
    				$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(11, 4 + $k, 12, 4 + $k);
    			}
    			else 
    			{
    				// 四级任务的目标任务
		    		if ($v['duty'] == $duty)
		    		{
		    			if ($k + 4 - $dutyIndex > 1)
		    				$objPHPExcel->getActiveSheet()->unmergeCellsByColumnAndRow(12, $dutyIndex, 12, 4 + $k - 1);
		    			$objPHPExcel->getActiveSheet()->mergeCellsByColumnAndRow(12, $dutyIndex, 12, 4 + $k);
		    		}
		    		else
		    		{
		    			$duty = $v['duty'];
		    			$dutyIndex = $k + 4;
		    		}
    			}
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

	    	//保存文件
	    	$fileName = '导出任务' . time();  


	    	ob_end_clean();
            ob_start();
            //保存文件
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
}