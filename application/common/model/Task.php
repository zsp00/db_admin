<?php
namespace app\common\model;
use think\Db;
use think\Model;

class Task extends Model
{
    protected $statusMsg = [
        '-1'    =>  '删除',
        '0'     =>  '禁用',
        '1'     =>  '未完成',
        '2'     =>  '完成'
    ];
    protected $_participateLevel = null;

    public function getList($map, $tDate, $page = 1, $listRow = 20) {
        $result = [
            'data'  =>  null,
            'total' =>  0,
            'currPage' => $page
        ];
        $where = [];
        foreach($map as $k=>$v){
            $where['task.'.$k] = $v;
        }
        $model = $this->alias('task')
            ->join('task_data', 'task.id = task_data.tId and task_data.tDate = "'.$tDate.'"', 'left')
            ->where($where)
            ->field([
                'task.*',
                'task_data.completeSituation',
                'task_data.problemSuggestions',
                'task_data.analysis',
                'task_data.currentLevel'    =>  'taskDataStatus',
                'task_data.status'          =>  'currMonthStatus'
            ]);
        $list = $model->page($page,$listRow)
            ->select();
        $result['total'] = $model->count();
        if($list){
            $OrgDept = new OrgDept();
            $taskDataStatusMsg = new TaskData();
            foreach($list as $k=>$v)
            {
                // 获取当前用户能参与到流程的那些步骤
                $stepIds = model('ProcessData')->getStepIds($v['pId']);

                $list[$k]['statusMsg'] = $this->statusMsg[$v['status']];
                $list[$k]['deptName'] = $OrgDept->where(['DEPT_NO'=>$v['deptNo']])->value('DEPT_NAME');
                $list[$k]['timeLimit'] = substr_replace($v['timeLimit'], '年', 4, 0) . '月';
                $list[$k]['typeName'] = model('TaskType')->where('id', $v['typeId'])->value('typeName');

                $currStep = $v['taskDataStatus'];
                // 获取任务显示的当前状态
                if(isset($currStep) && $currStep != null){
                    $list[$k]['taskDataStatusMsg'] = $this->getStatusMsg($v['pId'], $currStep, $v['currMonthStatus']);
                }else{
                    $list[$k]['taskDataStatusMsg'] = '';
                }
            }
        }
        $result['data'] = $list;

        return $result;
    }

    public function getInfo($id) {
        $info = $this->where(['id'=>$id])->find();
        if($info) {
            $TaskData = new TaskData();
            $taskDataList = $TaskData->where(['tId'=>$id])->order(['tDate desc'])->select();
            foreach($taskDataList as $k=>$v){
                $taskDataList[$k]['tDate'] = substr($v['tDate'],4);
                $taskDataList[$k]['taskDataStatusMsg'] = $this->getStatusMsg($info['pId'], $v['currentLevel'], $v['status']);
            }
            $info['identitys'] = $this->_participateLevel == null ? Model('ProcessData')->getStepIds($info['pId']) : $this->_participateLevel;

            $taskDataStatusMsg = new TaskData();
            $info['taskDataList'] = $taskDataList;
            $info['statusMsg'] = $this->statusMsg[$info['status']];
            
            return $info;
        }else{
            return false;
        }
    }

    /**
     * 获取某个人某个月的状态
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
        /* 如果参与到倒数第一、二步:
         * 未提交：任务流程进行到倒数第一、二步或还没有进行到倒数第一、二步，
         * 待确认：任务流程进行到倒数第一部，
         * 已确认：任务流程进行到最后一步，并且该任务该月已关闭
         */
        if ($stepIds[0] >= $processLevel - 1)
        {
            if ($currStep <= $processLevel - 1)
                $msg = $taskDataStatusMsg->statusMsg[1];
            elseif ($currStep == $processLevel && $currMonthStatus == 0)       // 已确认
                $msg = $taskDataStatusMsg->statusMsg[3];
            else
                $msg = $taskDataStatusMsg->statusMsg[2];
        }
        /* 如果任务在其他的步骤中
         * 未提交：任务流程进行到当前用户能参与到的步骤中
         * 待确认：任务流程进行到用户能参与到的下一步
         * 已确认：任务流程进行到用户能参与到的下两步
         */
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