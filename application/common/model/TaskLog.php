<?php
namespace app\common\model;
use think\Db;
use think\Model;

class TaskLog extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createTime';
    protected $updateTime = false;
    protected $type = [
        'createTime' =>  'timestamp'
    ];


    /*
     * 添加一条日志
     */
    public function addLog($tId,$tDId,$type,$empNo,$deptNo,$newData = [], $oldData = []){
        $data = [
            'tId'   =>  $tId,
            'tDId'  =>  $tDId,
            'type'  =>  $type,
            'empNo' =>  $empNo,
            'createTime'=>time()
        ];
        $tLId = $this->isupdate(false)->insert($data);
        $tLId = $this->getLastInsID();
        if(!$tLId) {
            return false;
        }
        if(count($newData) > 0){
            foreach($newData as $k=>$v){
                $taskLogData[$k] = [
                    'tLId'  =>  $tLId,
                    'field' =>  $k,
                    'new'   =>  $v,
                    'deptNo' => $deptNo
                ];
            }
            foreach($oldData as $k=>$v){
                if(!isset($taskLogData[$k])){
                    continue;
                }
                if($taskLogData[$k]['new'] == $v){
                    unset($taskLogData[$k]);
                }else{
                    $taskLogData[$k]['old'] = $v;
                }
            }
            $TaskLogDataModel = new TaskLogData();
            $result = $TaskLogDataModel->insertAll($taskLogData);
            if(!$result){
                return false;
            }
        }
        return true;
    }
}