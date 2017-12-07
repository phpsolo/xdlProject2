<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 公共控制器
 */
class CommonController extends Controller {
	/**
	 * 最先触发,用于判断登录状态
	 */
	public function _initialize(){
		if(!session('?AdminUser')){
			$this->redirect('Login/login');
		}
		$node = CONTROLLER_NAME.'/'.ACTION_NAME;
		if(!in_array_case($node,session('nodeList'))){
			echo '<script>alert("您没有权限访问");top.location.href="'.__MODULE__.'/Index";</script>';
		}
	}
	
	/**
	 * 空操作，返回404页面
	 */
	public function _empty(){
		$this->display('Empty/index');
	}

}