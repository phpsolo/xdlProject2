<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 友情链接控制器
 */
class FriendShipController extends CommonController{
	/**
	 * 显示友情链接列表页
	 */
	public function index()
	{
		$friend = D('FriendShip');
		$count = $friend->count();
		$Page = new \Think\Page($count,2);
		$page = $Page->show();
		$list = $friend->limit($Page->firstRow,$Page->listRows)->getData();
		if(IS_AJAX){
			$data = $list;
			$data['page'] = $page;
			$this->ajaxReturn($data);
			exit;
		}
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->display('Friendship');
	}

	/**
	 * 显示添加友情链接页面
	 */
	public function add()
	{	
		if(IS_POST){
			$friend = D('FriendShip');

			$vali = $friend->create();
			if($_FILES['pic']['error'] ==4){
				$this->error('请上传图片');
			}
			if($vali){
				//将数据交给Model层处理,返回文件名
				$res = $friend->getPic($_FILES['pic']);
				$vali['pic'] = $res;
				$vali['addtime'] = time();
				// //如果返回的是数组，且下标为0，则出错
				if($res[0] === 'false'){
					$this->error($res[1]);
				}
				// echo $res;exit;
				if($friend->add($vali)){
					$this->success('添加友链成功',U('index'));
				} else {
					$this->error('添加友链失败');
				}
			} else {
				$this->error($friend->getError());
			}
		} else {
			$this->display();
		}
			
	}

	/**
	 * del()  删除AJAX提交id的对应数据
	 * * @param  [type] $id [友链id]
	 */
	public function del($id){
		
	    $friend = M('FriendShip');
	    $arr = $friend->find($id);
	   	$path = './Public/Upload/'.$arr['pic'];
	   	//删除友链的同时删除图片
	    if($path){
	    	unlink($path);
	    }
	    $info = $friend->delete($id);
		if(IS_AJAX){
		    if($info){
		    	$data['status'] = 1;
		    	$data['msg'] = '删除成功';
		   		$this->ajaxReturn($data);
		    } else {
		   	 	$data['status'] = 0;
		    	$data['msg'] = '删除失败';
		   		$this->ajaxReturn($data);
		    }
		}
	}

	/**
	 * editStatus() 修改友情链接的状态方法
	 * @return array 返回修改的受影响行数
	 */
	public function editStatus() {
		if(IS_AJAX) {
			$friend = M('FriendShip');
			$info = $friend->field('status')->where($_POST)->find();
			if($info['status'] == 0) {
				$info = ['status' => '1'];
			} else {
				$info = ['status' => '0'];
			}
			$data = $friend->where($_POST)->save($info);
			if($data) {
				$this->success();
			} else {
				$this->error();
			}

		}
	}
}