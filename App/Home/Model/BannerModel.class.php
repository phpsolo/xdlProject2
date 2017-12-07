<?php
namespace Home\Model;
use Think\Model;

class BannerModel extends Model{
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