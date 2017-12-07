<?php
namespace Home\Model;
use Think\Model;

/**
 * 处理主页数据
 */
class IndexModel extends Model{
	/**
 	* 获取个人会员数据
 	* @return [array] [格式化后的收藏商品数据]
 	*/
	public function getData()
	{
		$arr = $this->select();

		$status = ['普通用户','VIP用户','钻石用户','管理员','超级管理员','上帝'];
			$arr[0]['status'] = $status[$v['status']];
		
		// 返回数据
		return $arr;		
	}
}