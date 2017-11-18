<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;

class Login extends Controller
{
    public function index(Request $request)
    {
        if($request->isPost()){

        }else{
        	return $this->fetch();
        }
    }
}
