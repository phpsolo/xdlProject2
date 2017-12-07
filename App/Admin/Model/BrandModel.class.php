<?php
namespace Admin\Model;
use Think\Model;
/**
 * 品牌model层处理数据
 */
class BrandModel extends Model{
	//自动验证规则
	protected $_validate = [
		['brandname', 'require', '品牌名必须有'],
		['brandname', '/^[\w\x{4e00}-\x{9fa5}]{2,18}$/ui', '品牌名必须为2-50位数字字母下划线'],
		['brandname', 'checkBrandname', '品牌名必须唯一，不能冒牌', 0, 'callback'],
	];

	/**
	 * 检查商品名是否唯一
	 * @return [boolean] [重名返回false]
	 */
	public function checkBrandname()
	{
		$id = I('post.id');
		if($id === '')
		{
			$res = $this->where(['brandname' => I('post.brandname')])->find();
			return $res ? false : true;
		}
		$res = $this->where(['brandname' => I('post.brandname')])->find();
		return (!$res || $res['id'] == I('post.id')) ? true : false;
	}
}