<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 意见反馈公共控制器
 */
class FeedbackController extends CommonController {

	/**
	 * 显示个人中心意见反馈页面
	 * @return void
	 */
	public function Feedback(){
		if(IS_POST){
			//处理数据
			$_POST['uid'] = $_SESSION['HomeUser']['id'];
			$_POST['addtime'] = time();
			$Feedback = D('Feedback');
			$vali = $Feedback->create();
			if($vali){
				//将数据交给Model层处理，返回文件名
				$_POST['pic'] = $Feedback->getPic($_FILES['pic']);
				// //如果返回的是数组，且下标为0，则出错
				if($_POST['pic'][0] === 'false'){
					$this->error($_POST['pic'][1]);
				}
				if($Feedback->add($_POST)){
					$this->success('反馈意见成功');
				} else {
					$this->error('反馈意见失败');
				}
			} else {
				$this->error($Feedback->getError());
			}
		} else {

			$this->display();
		}
	}
}
