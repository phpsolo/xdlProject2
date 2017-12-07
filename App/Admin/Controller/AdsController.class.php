<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 广告控制器
 */
class AdsController extends CommonController{
	/**
	 * 显示广告列表页
	 * @return void
	 */
	public function index()
	{
		$ads = D('Ads');
		$count = $ads->count();
		$Page = new \Think\Page($count,1);
		$page = $Page->show();
		$list = $ads->limit($Page->firstRow,$Page->listRows)->getData();
		// var_dump($list);
		if(IS_AJAX){
			$data = $list;
			$data['page'] = $page;
			$this->ajaxReturn($data);
			exit;
		}
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->display('Ads');
	}

	/**
	 * 显示添加广告页面
	 * @return void
	 */
	public function add()
	{	
		if(IS_POST){
			$ads = D('Ads');

			$vali = $ads->create();
			if($_FILES['pic']['error'] ==4){
				$this->error('请上传图片');
			}
			if($vali){
				//将数据交给Model层处理,返回文件名
				$res = $ads->getPic($_FILES['pic']);
				$vali['pic'] = $res;
				// //如果返回的是数组，且下标为0，则出错
				if($res[0] === 'false'){
					$this->error($res[1]);
				}
				// echo $res;exit;
				if($ads->add($vali)){
		    		//清除缓存
		    		S('ads',null);
					S('count',null);
					$this->success('添加广告成功',U('index'));
				} else {
					$this->error('添加广告失败');
				}
			} else {
				$this->error($ads->getError());
			}
		} else {
			$this->display();
		}
			
	}

	/**
	 * del()  删除AJAX提交id的对应数据
	 * @return void
	 */
	public function del(){
		if(IS_AJAX){
		    $ads = M('Ads');
		    $info = $ads->where($_POST)->delete();
		    if($info) {
		    	//清除缓存
		    	S('ads',null);
				S('count',null);
		   		$this->success('删除成功');
		    } else {
		   	 	$this->error('删除失败');
		    }
		}
	}

	/**
	 * editStatus() 修改广告的状态方法
	 * @return array 返回修改的受影响行数
	 */
	public function editStatus() {
		if(IS_AJAX) {
			$ads = M('Ads');
			$info = $ads->field('status')->where($_POST)->find();
			if($info['status'] == 0) {
				$info = ['status' => '1'];
			} else {
				$info = ['status' => '0'];
			}
			$data = $ads->where($_POST)->save($info);
			if($data) {
				//清除缓存
		    	S('ads',null);
				S('count',null);
				$this->success();
			} else {
				
				$this->error();
			}

		}
	}
}