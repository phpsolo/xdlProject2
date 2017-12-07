<?php
namespace Admin\Model;
use Think\Model;
 
/**
 * 后台订单模型层
 */
class OrderModel extends Model{

	/**
	 * 处理后台订单数据
	 * @return [array] [格式化后的数据]
	 */
	public function getData(){
		$arr = $this->select();
		//遍历数据
		$status = ['','待付款','已支付待发货','待收货','','交易完成'];
		foreach ($arr as $k => $v) {
			$arr[$k]['status'] = $status[$v['status']];
			$arr[$k]['addtime'] = date('Y-m-d H:i:s',$v['addtime']);
		}
		return $arr;
	}
}