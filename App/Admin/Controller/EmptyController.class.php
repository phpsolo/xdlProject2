<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 空控制器
 */
class EmptyController extends Controller {
	/**
	 * 空操作，返回404页面
	 */
	public function _empty(){
		$this->display('Empty/index');
	}
}