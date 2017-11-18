<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;
use app\admin\model\Admin as AdminModel;

class Login extends Controller
{
    public function index(Request $request)
    {
        if($request->isPost()){
        	$code = $request->param('data.code');
        	if(empty($code)){
        		return json(['code'=>1000,'msg'=>'请输入验证码']);
        	}
        	$captcha = new \think\captcha\Captcha();
	        if (!$captcha->check($code)) {
	        	return json(['code'=>1000,'msg'=>'验证码错误']);
	        }

        	$username = $request->param('data.username');
        	if(empty($username)){
        		return json(['code'=>1000,'msg'=>'请输入用户名']);
        	}
        	$password = $request->param('data.password');
        	if(empty($password)){
        		return json(['code'=>1000,'msg'=>'请输入用密码']);
        	}
        	
        	$admin = AdminModel::getByUsername($username);
        	if(empty($admin)){
        		return json(['code'=>1000,'msg'=>'用户名或密码错误']);
        	}
        	if( md5($password.md5($admin->salt)) != $admin->password ){
        		return json(['code'=>1000,'msg'=>'用户名或密码错误']);
        	}
        	$admin_info = $admin->hidden(['password','salt'])->toArray();
        	session('admin_info',$admin_info);

        	$admin->login_num += 1;
        	$admin->login_time = time();
        	$admin->login_ip = $request->ip();
        	$admin->save();
        	
        	return json(['code'=>0,'msg'=>'登录成功']);
        }else{
        	return $this->fetch();
        }
    }
}
