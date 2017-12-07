<?php
namespace Home\Model;
use Think\Model;
/**
 * 处理分类数据
 */
class TypeModel extends Model{
	//存放商品分类
	protected $types = [];

	/**
	 * [获取指定商品的分类]
	 * @param  [int] $id [商品id]
	 * @return [array]     [所有的分类信息]
	 */
	public function getData($id)
	{
		$data = $this->where(['id' => $id])->find();
		$this->types[] = $data;
		$res = $this->where(['id' => $data['pid']])->find();
		if($res) $this->getData($res['id']); 
		return $this->types;
	}
}