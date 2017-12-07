<?php
namespace Home\Model;
use Think\Model;
 
/**
 * OrderModel  用于处理订单数据
 */
class OrderModel extends Model {

	/**
	 * 用于处理前台订单数据
	 * @return [array] [格式化后的订单数据]
	 */
	public function getData(){
		$arr = $this->select();
		$status = ['','待付款','待发货','待收货','确认收货','交易完成','过期订单'];
		foreach ($arr as $k => $v) {
			$arr[$k]['addtime'] = date('Y-m-d H:i:s',$v['addtime']);
			$arr[$k]['status'] = $status[$v['status']];
		}
		return $arr;
	}
}