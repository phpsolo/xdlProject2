<?php
namespace Admin\Model;
use Think\Model;
 
/**
 * UserModel  用于处理用户数据
 */
class UserModel extends Model {
	/**
	 * $_validate  自动验证添加用户的信息
	 */
	protected $_validate = [
		//检测用户名是否存在
		['username', '', '用户名已经存在', 0, 'unique'],
		//必须输入用户名
		['username', 'require', '请输入用户名'],
		//必须输入密码
		['password', 'require', '请输入密码',2],
		//检测邮箱是否存在
		['email', '', '该邮箱已被使用', 0, 'unique'],
		//表示必须输入邮箱
		['email', 'require', '请输入邮箱'],
		//角色自能为管理员
		['role',[4,5,6,7],'角色不正确',2,'in'],
		//使用正则
		['username', '/^[\w]{6,12}$/', '请输入6~12位的由字母、数字和下划线组成的用户名'],
		['email','email','请输入正确格式的邮箱'],
		//验证两次密码是否一致
		['password2', 'password', '两次密码不一致', 2, 'confirm'],
		//验证两次密码是否一致
		['password', 'password2', '两次密码不一致', 2, 'confirm'],
	];

	/**
	 * $_auto  自动完成
	 */
	protected $_auto = [
		//密码进行hash加密
		['password','password_hash',3,'function',PASSWORD_DEFAULT],
		//添加注册时间
		['addtime', 'time', 1, 'function'],
		//后台添加用户账号默认是已激活状态
		['email_status','1',1],  
	];

	/**
	 * getdata()  用于获取和处理User表数据
	 * @return  array  User表的数据
	 */
	public function getdata(){
		//得到User表数据
		$info = $this->select();
		//准备处理的数据
		$sex  = [1=>'男', '女'];
		$role = [1=>'普通用户','VIP用户','钻石用户','超级管理员','用户管理员','商品管理员','订单管理员'];
		$status = [1=>'正常', '禁用'];
		$email_status = ['未激活','已激活'];

		//循环遍历处理数据
		foreach ($info as $k => $v) {
			$info[$k]['sex'] = $sex[$v['sex']];
			$info[$k]['role'] = $role[$v['role']];
			$info[$k]['addtime'] = date('Y-m-d H:i:s',$info[$k]['addtime']);
			$info[$k]['status'] = $status[$v['status']];
			$info[$k]['email_status'] = $email_status[$v['email_status']];
		}
		//返回处理后的数据
		return $info;
	}

	/**
	 * 用于处理后台显示个人信息的数据
	 * @return [array] [格式化后的数据]
	 */
	public function getPersonal(){
		$list = $this->find();
		//处理数据
		$status = ['','正常','禁用'];
		$role = ['','','','','超级管理员','用户管理员','商品管理员','订单管理员'];
		$list['role'] = $role[$list['role']];
		$list['status'] = $status[$list['status']];
		//拥有的权限
		$nodeList = $_SESSION['nodeList'];
		foreach ($nodeList as $k => $v) {
			$pers[] = M('Node')->where(['node'=>$v])->find()['name'];
		}
		$pers = array_filter($pers);
		return [$list,$pers];
	}
}