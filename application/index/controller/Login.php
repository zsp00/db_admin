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

        $data = [
            'username'  =>  $username,
            'password'  =>  $password
        ];
        $data['appNo'] = Config::get('ldap.appNo');
        $data['appKey'] = Config::get('ldap.appKey');
        $result = post(Config::get('ldap.loginUrl'),$data);
        $result = json_decode($result,true);

        if($result['statusCode'] != '0x00000000' && !in_array($result['statusCode'],['0x00010004','0x00010005']))
        {
            if($result['statusCode'] == '0x00010006')
                $this->error('密码不正确！');
            $this->error($result['msg']);
        }
        elseif(in_array($result['statusCode'],['0x00010004','0x00010005']))
        {//需要重置密码
            $returnUrl = Url('/','',true,true);
            $updatePostData = [
                'appNo' =>  Config::get('ldap.appNo'),
                'appKey' =>  Config::get('ldap.appKey'),
                'returnUrl' =>  $returnUrl,
                'personid'  =>  $result['data']['personid'],
                'password'  =>  $password,
                'sign'  =>  md5(
                    Config::get('ldap.appNo').
                    Config::get('ldap.appKey').
                    $returnUrl.
                    $result['data']['personid'].
                    $password
                )
            ];
            $params = "";
            foreach ($updatePostData as $k=>$v){
                if($k == 'returnUrl'){
                    $v = urlencode($v);
                }
                $params .= "&".$k."=".$v;

            }
            $params = trim($params,"&");

            //去修改密码
            $url = Config::get('ldap.updatePasswordUrl')."?".$params;
            $this->error($result['msg'],$url,$data,1);
            exit;
        }
        else
        {
            $UserEmp = new UserEmp();
            session('personid',$result['data']['personid']);
            //查看人力资源库有没有该用户
            $info = $UserEmp->exists($result['data']['empNo']);
            if($info){
                //检查用户所属公司是否参与
                //
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
