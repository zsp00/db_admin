<?php
namespace app\index\controller;

use app\common\model\ParticipateComp;
use app\common\model\UserEmp;
use Bmzy\Uams\User;
use think\Config;
use think\Controller;
use think\Request;
use think\Cookie;


class Login extends Controller
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $token = Cookie::get('token_'.Config::get('uams.appNo'),'');
        $t_time = Cookie::get('t_time','');
        $empno = Cookie::get('empno','');
        $personid = Cookie::get('personid','');
        if($token != '' && $empno != '' && $t_time != '' && $personid != ''){
            $this->autoLogin($token,$empno,$t_time);
        }
    }
    /**
     * @param $token
     * @param $empno
     * @param $t_time
     * 自动登陆
     */
    public function autoLogin($token,$empno,$t_time){
        if(md5($empno.Config::get('uams.appKey').$t_time) == $token){
            //自动登陆
            $info = model('UserEmp')->exists($empno,'EMP_NO');
            if($info){
                $result = model('UserEmp')->login($info);
                if(!$result){
                    $this->error(model('UserEmp')->getError());
                }
            }else{
                $this->index();
            }

            //登录成功调用行为日志的方法
        }else{
            $this->index();
        }
    }
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
