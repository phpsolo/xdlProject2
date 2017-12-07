<?php 
namespace Home\Model;
use Think\Model;
/**
 * 用于处理前台用户数据
 */
class UserModel extends Model
{
	//设置自动验证规则	
	protected $_validate = [
		//检测用户名是否存在
		['username','','用户名已经存在',0,'unique'],
		//检测用户名是否唯一
		['username','require','请输入用户名'],
		//使用正则
		['username','/^\w{6,12}$/','请使用6-12位的数字字母下划线的用户名'],
		//判断邮箱是否存在
		['email','','邮箱已经存在',0,'unique'],
		['password','/^\S{6,18}$/','请使用6-18位的密码'],
		//检测邮箱是否唯一
		['email','require','请输入邮箱'],
		//验证密码是否一致
		['password','password2','两次密码不一致','2','confirm'],
		['password2','password','两次密码不一致','2','confirm']
	];

	//设置自动完成
	protected $_auto = [
		//使用hash自动加密密码
		['password','password_hash',3,'function',[PASSWORD_DEFAULT]],
		['addtime','time',1,'function']

	];
	
	/**
	 * 获取个人会员数据
	 * @return [array] [格式化后的收藏商品数据]
	 */
	public function getData()
	{
		$arr = $this->select();
		
		$role = ['','普通用户','VIP用户','钻石用户','超级管理员','用户管理员','商品管理员','订单管理员'];
			$arr[0]['role'] = $role[$arr[0]['role']];
		// 返回数据
		return $arr;		
	}
}