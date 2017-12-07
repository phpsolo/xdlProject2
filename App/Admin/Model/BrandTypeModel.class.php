<?php
namespace Admin\Model;
use Think\Model;
/**
 * 品牌分类model层处理数据
 */
class BrandTypeModel extends Model{

	/**
	 * 给提交上来的数据去掉空并去重
	 * @return [array] [处理完成的数组]
	 */
	public function getPostData()
	{
		//查询该品牌id关联的所有分类id
		$dbtids = array_column($this->where(['bid' => I('post.bid')])->select(), 'tid');
		//去重
		$tids = array_unique(I('post.tid'));
		foreach ($tids as $key => $value) {
			//去掉数组中空的键
			if(empty($value)) unset($tids[$key]);
		}
		//关联表需要插入的数据
		$insertIds = array_diff($tids, $dbtids);
		//关联表需要删除的数据
		$deleteIds = array_diff($dbtids, $tids);
		$arr['insertIds'] = $insertIds;
		$arr['deleteIds'] = $deleteIds;
		return $arr;
	}
}