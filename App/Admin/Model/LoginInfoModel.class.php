<?php
namespace Admin\Model;
use Think\Model;
 
/**
 * LoginInfoModel  用于处理后台首页数据
 */
class LoginInfoModel extends Model {
	/**
	 * 处理管理员角色数据
	 * @return [array] [格式化后的数据]
	 */
	public function getData(){
		$arr = $this->select();
		foreach ($arr as $k => $v) {
			//查出管理员对应的角色ID
			$roleIds = M('UserRole')->where(['u_id'=>$v['uid']])->getField('r_id',true);
			//查出角色ID对应角色,只取第一个角色
			$arr[$k]['role'] = M('Role')->where(['id'=>['in',$roleIds]])->getField('name');
		}
		return $arr;
	}
}