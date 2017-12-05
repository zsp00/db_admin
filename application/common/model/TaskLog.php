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
        if (count($newData) > 0)
        {
            $tempArr = 0;
            foreach ($newData as $k => $v)
            {
                if ($oldData[$k] != $v)
                    $tempArr++;
            }
            if ($tempArr == 0)
                return true;
        }
        
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
	//驳回记录插入日志
	 public function addRejectLog($tId,$tDId,$type,$empNo,$reason){
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
		$logData = [
			'tLId' => $tLId,
			'field' => $type,
			'old' => '0',
			'new' => $reason,
			'deptNo' => '0'
		];
		$TaskLogDataModel = new TaskLogData();
        $result = $TaskLogDataModel->insert($logData);
        return true;
    }
}































