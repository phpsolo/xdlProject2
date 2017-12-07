<?php
namespace Admin\Model;
use Think\Model;
/**
 * 轮播图model层处理数据
 */
class BannerModel extends Model{
	//自动验证规则
	protected $_validate = [
		['gid', 'require', '商品ID必须有'],
		['gid', 'number', '商品ID必须为数字'],
	];

	/**
	 * 格式化轮播图数据
	 * @return [array] [格式化后的轮播图数据]
	 */
	public function getData()
	{
		$data = $this->select();
		$status = [1 => '显示', '隐藏'];
		foreach ($data as $key => $value) {
			$data[$key]['status'] = $status[$value['status']];
		}
		return $data;
	}
}