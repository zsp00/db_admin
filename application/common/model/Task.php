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
            ->join('task_data', 'task.id = task_data.tId and task_data.tDate = "'.$tDate.'"')
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
            }
        }
        $result['data'] = $list;

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
                        $label .= '：进行中';
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
            // dump($taskDataList);exit;
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