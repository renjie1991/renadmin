<?php
namespace app\admin\controller;
use think\Db;
use think\Request;

class News extends Base
{
    public function category()
    {
        $list = Db::name('news_cate')->select();
        $this->assign('list',$list);

        return $this->fetch();
    }

    public function category_add(Request $request){
    	if($request->isPost()){

    	}else{
    		// echo 123;die;
    		return $this->fetch();
    	}
    }

}
