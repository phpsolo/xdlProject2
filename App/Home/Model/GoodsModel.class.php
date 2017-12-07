<?php
namespace Home\Model;
use Think\Model;

/**
 * 处理收藏商品数据
 */
class GoodsModel extends Model{
	
	/**
	 * 获取收藏商品数据
	 * @return [array] [格式化后的收藏商品数据]
	 */
	public function getData()
	{
		$arr = $this->select();
		foreach ($arr as $key => $value) {
			// 转换时间格式
			$arr[$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
		}
		// 返回数据
		return $arr;		
	}

	/**
	 * 格式化促销商品数据
	 * @return [array] [返回格式化后的商品数据]
	 */
	public function getGoodsData()
	{
		$data = $this->select();
		foreach ($data as $key => $value) {
			//开售时间
			$data[$key]['addtime'] -= time();
			//千分位格式化价格
			$data[$key]['price'] = number_format($data[$key]['price']);
			$data[$key]['normalprice'] = number_format($value['price'] / 0.3);
		}
		return $data;
	}
}