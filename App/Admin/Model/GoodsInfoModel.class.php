<?php
namespace Admin\Model;
use Think\Model;
/**
 * 商品信息model层
 */
class GoodsInfoModel extends Model{

	/**
	 * 格式化商品属性数据
	 * @return [array] [格式化后的数据]
	 */
	public function getData()
	{
		$data = $this->select();
		$tmp = [];
		$i = 0;
		//把属性和值拼接成 属性:值,属性:值 的格式
		foreach ($data as $k => $value) {
			$tmp['id'][$k] = $value['id'];
			$tmp['stock'][$k] = $value['stock'];
			$tmp['price'][$k] = $value['price'];
			$arr = explode(',', $value['attr']);
			foreach ($arr as $key => $val) {
				$array = explode(':', $val);
				$tmp['attr'][$i][] = $array[0];
				$tmp['val'][$i][] = $array[1];
				if($key % 2 == 1) $i++;
			}
		}
		$tmp['attribute'] = $data;
		return $tmp;
	}
}
