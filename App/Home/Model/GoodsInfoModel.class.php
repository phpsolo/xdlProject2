<?php
namespace Home\Model;
use Think\Model;

/**
 * 格式化数据
 */
class GoodsInfoModel extends Model{
	
	/**
	 * 格式化商品属性
	 * @return [array] [格式化后的数据]
	 */
	public function getData()
	{
		$data = $this->select();
		foreach ($data as $key => $value) {
			$tmp = explode(',', $value['attr']);

			foreach ($tmp as $k => $v) {
				$tmp1 = explode(':', $v);
				$res['attr'][$tmp1[0]][] = $tmp1[1];
			}
		}
		//去掉重复的属性值
		foreach ($res['attr'] as $key => $value) {
			$res['attr'][$key] = array_unique($res['attr'][$key]);
		}
		foreach ($res['attr'] as $key => $value) {
			$res['default'][] = $key.':'.$value[0]; 
		}

		$res['default'] = join(',', $res['default']);
		return $res;	
	}
}