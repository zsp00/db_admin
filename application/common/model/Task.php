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
                'task_data.status'  =>  'taskDataStatus'
            ]);
        $list = $model->page($page,$listRow)
            ->select();
        $result['total'] = $model->count();
        if($list){
            $OrgDept = new OrgDept();
            $taskDataStatusMsg = new TaskData();
            foreach($list as $k=>$v){
                $list[$k]['statusMsg'] = $this->statusMsg[$v['status']];
                $list[$k]['deptName'] = $OrgDept->where(['DEPT_NO'=>$v['deptNo']])->value('DEPT_NAME');
                if(isset($v['taskDataStatus']) && $v['taskDataStatus'] != null){
                    $list[$k]['taskDataStatusMsg'] = $taskDataStatusMsg->statusMsg[$v['taskDataStatus']];
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
            $taskDataList = $TaskData->where(['tId'=>$id])->order(['mouth desc'])->select();
            $taskDataStatusMsg = new TaskData();
            $info['taskDataList'] = $taskDataList;
            $info['statusMsg'] = $this->statusMsg[$info['status']];
            $info['taskDataStatusMsg'] = $taskDataStatusMsg->statusMsg;
            return $info;
        }else{
            return false;
        }
    }
}