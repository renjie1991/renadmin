<?php
namespace app\admin\controller;
use think\Db;

class News extends Base
{
    public function category()
    {
        $list = Db::name('news_cate')->select();
        $this->assign('list',$list);
        return $this->fetch();
    }

}
