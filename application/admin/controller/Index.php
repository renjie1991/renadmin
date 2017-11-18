<?php
namespace app\admin\controller;

class Index extends Base
{
    public function index()
    {
        return $this->fetch();
    }

    public function welcome(){
    	return $this->fetch();
    }

    public function logout(){
    	session('admin_info',null);
    	$this->redirect('Login/index');
    }
}
