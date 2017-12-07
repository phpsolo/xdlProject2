<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 前台公共控制器
 */
class CommonController extends Controller {
	
	/**
	 * 最先触发,用于判断登录状态
	 */
	public function _initialize(){
		if(!session('?HomeUser')){
			$this->redirect('Login/login');
		}
		// 实例化类
        $seo = M('IndexSeo');
        // 获取seo数据
        $seoarr = $seo->select();
        // 分配数据
        $this->assign('seo',$seoarr);
        
        // 导入第三方类库
        vendor('xunsearch.lib.XS');
        $xs = new \XS('goods');
        $searchobj = $xs->search;
		// 获取热搜词
        $hotwords = $searchobj->getHotQuery(50);
        arsort($hotwords);
        $hotwords = array_slice($hotwords,0,6,true);
        $search = '';
        // 分配热搜词和提交的搜索词的数据
        $this->assign('hotwords',$hotwords);
        $this->assign('search',$search);
        //操作用户表
        $user = D('User');
        $user = $user->where(['id'=>$_SESSION['HomeUser']['id']])->getData();
        $this->assign('user',$user);
	}

	/**
	 * 空操作，返回404页面
	 */
	public function _empty(){
		$this->display('Admin@Empty/index');
	}

}