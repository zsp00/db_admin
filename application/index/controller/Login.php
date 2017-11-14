<?php
namespace app\index\controller;

use app\common\model\ParticipateComp;
use app\common\model\UserEmp;
use Bmzy\Uams\User;
use think\Config;
use think\Controller;

class Login extends Controller
{
    public function index($username = '', $password = '', $remember = false)
    {
        $vUser = new \app\index\validate\User();
        if(!$vUser->check([
            'username'  =>  $username,
            'password'  =>  $password,
            'remember'  =>  $remember
        ])) {
            $this->error($vUser->getError());
        }
        $User = new User(Config::get('uams'));
        $result = $User->login($username, $password);
        if(!$result){
            $this->error($User->getErrorMsg());
        }else{
            $UserEmp = new UserEmp();
            session('personid',$result['personid']);
            //查看人力资源库有没有该用户
            $info = $UserEmp->exists($result['empNo']);
            if($info){
                //检查用户所属公司是否参与
                $ParticipateComp = new ParticipateComp();
                if(!$ParticipateComp->isParticipate($info['COMP_NO'],$info['EMP_NO'])){
                    $this->error('您所在的公司不参与');
                }
                $result = $UserEmp->login($info,$remember);
                if(!$result){
                    $this->error(model('UserEmp')->getError());
                }
            }else{
                $this->error('本地系统中不存在此用户');
            }
            $this->success($result);
        }
    }

    public function logout(){
        $UserEmp = new UserEmp();
        $UserEmp->logout();
        $this->success('成功退出');
    }

    public function checkLogin(){
        if(checkLogin()){
            $this->success();
        }else{
            $this->error();
        }
    }
}
