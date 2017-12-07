<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 后台意见反馈控制器
 */
class FeedbackController extends CommonController{
	/**
	 * 显示意见反馈列表页
	 */
	public function index()
	{
		$feed = D('Feedback');
		//统计总条数
		$count = $feed->count();
		//实例化分页类
		$Page = new \Think\Page($count,1);
		//显示分页按钮
		$page = $Page->show();
		
		$list = $feed->limit($Page->firstRow,$Page->listRows)->getData();
		if(IS_AJAX){
			$data = $list;
			$data['page'] = $page;
			$this->ajaxReturn($data);
			exit;
		}
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->display('Feedback');
	}
}