<?php
namespace app\common\model;
use think\Model;

class ProcessData extends Model
{
	protected $autoWriteTimestamp = false;

	/**
     * 获取当前登录用户能参与到哪个流程中    
     * @param  int $pId 流程Id
     * @return array      用户能参与到的步骤
     * @author lu
     */
    public function getStepIds($pId)
    {
        $userInfo = getUserInfo();
        $empNo = $userInfo['EMP_NO'];
        // 获取当前用户所属的标签Id
        $labelIds = Model('LabelValue')->getIdsByEmpNo($empNo);
        //获取当前用户所在的组织Id
        $deptIds = Model('UserEmp')->getDeptNosByEmpNo($empNo);
        // 用户所在公司的Id
        $compNo = getCompNo($empNo);

        $audit_user = $this->where('pId', $pId)->order('levelNo')->select();
        $stepNums = array();
        // 循环流程的每一步，判断用户能不能参与该步骤
        foreach ($audit_user as $k => $v)
        {
            $audit = json_decode($v['audit_user']);
            $flag = 1;
            foreach ($audit as $key => $value)
            {
                if ($value == '')
                    continue;
                $value = explode(',' ,$value);
                switch ($key)
                {
                    case 'notIn':
                        if (in_array($empNo, $value))
                            $flag = false && $flag;
                        break;
                    case 'clique':
                        $flag = true && $flag;
                        break;
                    case 'comp':
                        if ($compNo == $value[0])
                            $flag = true && $flag;
                        break;
                    case 'dept':
                        // 判断用户所在的每一个部门是不是在$value中
                        foreach ($deptIds as $kk => $vv)
                        {
                            if (in_array($vv, $value))
                                $flag = true && $flag;
                        }
                        break;
                    case 'person':
                        if (in_array($empNo, $value))
                            $flag = true && $flag;
                        break;
                    case 'tag':
                        // 判断用户所在的每一个标签是不是在$value中
                        foreach ($labelIds as $kk => $vv)
                        {
                            if (in_array($vv, $value))
                                $flag = true && $flag;
                        }
                        break;
                    default:
                        break;
                }
            }
            if ($flag === true)
                $stepNums[] = $v['levelNo'];     
        }
        dump($stepNums);
        // return $stepNums;
    }
}