<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * UserController  控制对用户模块的操作
 */
class UserController extends CommonController{
	/**
	 *index() 显示用户列表页
	 */
	public function index()
	{
		$user = D('User');
		//只查用户
		$where['role'] = ['LT' , '4'];
		//处理搜索
    	if (strlen(trim(I('username'))) > 0) {
    		$where['username'] = ['like', '%'.I('username').'%'];
    	}
		$count = $user->where($where)->count();
		$Page = new \Think\Page($count,3);
		$show = $Page->show();
		//从Model层得到处理后的User表数据
		$list = $user->where($where)->limit($Page->firstRow, $Page->listRows)->getdata();
		if(IS_AJAX){
			$data = $list;
            $data['page'] = $show;
            $this->ajaxReturn($data);
            exit;
		}
		//分配数据
		$this->assign('list',$list);
		$this->assign('page', $show);
		//显示用户列表模板
		$this->display();
	}
	
	/**
	 * 查询管理员列表
	 */
	public function manager()
	{
		$user = D('User');
		//只查管理员
		$where['role'] = ['EGT' , '4'];
		//处理搜索
    	if (strlen(trim(I('username'))) > 0) {
    		$where['username'] = ['like', '%'.I('username').'%'];
    	}
		$count = $user->where($where)->count();
		$Page = new \Think\Page($count,3);
		$show = $Page->show();
		//从Model层得到处理后的User表数据
		$list = $user->where($where)->limit($Page->firstRow, $Page->listRows)->getdata();
		if(IS_AJAX){
			$data = $list;
            $data['page'] = $show;
            $this->ajaxReturn($data);
            exit;
		}
		//分配数据
		$this->assign('list',$list);
		$this->assign('page', $show);
		//显示用户列表模板
		$this->display();
	}


	/**
	 * add() 用于显示添加用户页面和修改用户信息
	 */
	public function add()
	{
		if(IS_POST) {
			$user = D('User');
			//进行自动验证判断
			$data = $user->create();
			if($data) {
				// var_dump($data);
				if($user->add($data)) {
					$this->success('添加成功',U('index'));
				} else {
					$this->error('添加失败');
				}
			} else {
				$this->error($user->getError());
			}
		} else {
			$this->display();
		}
	}

	/**
	 * edit()  显示修改用户页面和处理修改用户信息 
	 * @param  string $id 用于修改用户的ID值
	 * 
	 */
	public  function edit($id)
	{	
		if(I('username')){
			$this->error('不能修改用户名！');
		}
		$user = D('User');
		$id = ['id'=> $id];
		if(IS_POST){
			if($_POST['id'] == 1) {
				if(I('role') != 4) {
					$this->error('不能修改主管理员的权限');
				}
			}
			//自动验证和自动完成
			$data = $user->create();
			if($data) {
				//如果么有修改密码，则处理提交的空密码
				if(!I('password')) {
					unset($data['password']);
				}
				//判断数据是否修改成功
				if($user->where($id)->save($data)) {
					$this->success('修改成功',U('manager'));
				} else {
					$this->error('修改失败');
				}
			} else {
				$this->error($user->getError());
			}
		} else {
			//获取要编辑的用户数据
			$info = $user->where($id)->find();
			//分配数据
			$this->assign('user',$info);
			$this->display();			
		}
	}


	/**
	 * del()  删除AJAX提交id的对应数据
	 */
	public function del(){
		if(IS_AJAX){
			if(I('id') == 1) {
				$this->error('不能删除主超级管理员');
			}
		    $user = M('User');
		    $info = $user->where($_POST)->delete();
		    if($info) {
		   		$this->success('删除成功');
		    } else {
		   	 	$this->error('删除失败');
		    }
		} else {
			$this->redirect('Admin/Index/index');
		}
	}


	/**
	 * editStatus() 修改账号的状态方法
	 * @return array 返回修改的受影响行数
	 */
	public function editStatus() {
		if(IS_AJAX) {
			if(I('id') == 1) {
				$this->error('不能禁用主超级管理员');
			}
			$user = M('User');
			$info = $user->field('status')->where($_POST)->find();
			if($info['status'] == 1) {
				$info = ['status' => '2'];
			} else {
				$info = ['status' => '1'];
			}
			$data = $user->where($_POST)->save($info);
			if($data) {
				$this->success();
			} else {
				$this->error();
			}

		} else {
			$this->redirect('Admin/Index/index');
		}
	} 


	/**
	 * 显示后台管理员个人信息
	 */
	public function personal(){
		$user = D('User');
		$list = $user->where(['id'=>$_SESSION['AdminUser']['id']])->getPersonal();
		//返回的个人信息
		$info = $list[0];
		//返回的权限
		$per = $list[1];
		$this->assign('info',$info);
		$this->assign('per',$per);
		$this->display();
	}
	
}