<?php
namespace Admin\Model;
use Think\Model;
/**
 * 商品model层处理数据
 */
class GoodsModel extends Model{
	//自动验证规则
	protected $_validate = [
		['name', 'require', '商品名必须有'],
		['name', '/^[\w \/\-\x{4e00}-\x{9fa5}]{2,50}$/ui', '商品名必须为2-50位数字字母下划线'],
		['des', 'require', '商品描述必须有'],
		['tid', 'require', '分类名必须有'],
		['bid', 'require', '品牌名必须有'],
		['attr', 'checkAttr', '属性必须为数字字母下划线,值不能为空', 0, 'callback'],
		['stock', 'checkStock', '库存的值必须为整数大于等于0', 0, 'callback'],
		['price', 'checkPrice', '价格的值必须大于等于0', 0, 'callback'],
		['givescore', 'checkGivescore', '积分的值必须大于等于0', 0, 'callback'],
		['addtime', 'require', '促销品必须填写开售时间'],
		['addtime', '/\w{4}-\w{2}-\w{2} \w{2}:\w{2}(:\w){0,2}/', '请输入正确的时间']
	];

	/**
	 * 格式化数据
	 * @return [array] [格式化后的数据]
	 */
	public function getData()
	{
		//操作分类表
		$type = M('Type');
		//操作品牌表
		$brand = M('Brand');
		//查询商品数据
		$data = $this->select();
		//定义所有商品状态
		$status = [1 => '新添加', '在售中', '已下架', '促销品'];
		foreach ($data as $key => $value) {
			//格式化分类名
			$data[$key]['tid'] = $type->field('name')->where(['id' => $value['tid']])->find()['name'];
			//格式化状态
			$data[$key]['status'] = $status[$value['status']];
			//格式化品牌，不选为其他
			if($value['bid'] == 0)
			{
				$data[$key]['bid'] = '其他';
			} else {
				$data[$key]['bid'] = $brand->field('brandname')->where(['id' => $value['bid']])->find()['brandname'];
			}
			//格式化时间
			$data[$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
		}
		return $data;
	}

	/**
	 * 检查提交的表单项是否为空
	 * @return [bool] [空返回false]
	 */
	public function checkAttr()
	{	
		foreach(I('post.attr') as $val)
		{
			$arr = explode(',', $val);
			foreach ($arr as $value) {
				if(!preg_match('/^[\w\x{4e00}-\x{9fa5}]+:[\.\w\x{4e00}-\x{9fa5}]+$/ui', $value))
				{
					return false;
				}
			}
		}
	}

	/**
	 * 检查库存
	 * @return [boolean] [不合法返回false]
	 */
	public function checkStock()
	{
		$stocks = I('post.stock');
		$return = true;
		foreach ($stocks as $value)
		{			
			if(!preg_match('/^[0-9]+$/ui', $value)) return false;
			$value += 0;
			if(empty($value) || $value < 0)
			{
				$return = false;
			}
		}
		return $return;
	}

	/**
	 * 检查price是否合法
	 * @return [boolean] [不合法返回false]
	 */
	public function checkPrice()
	{
		$prices = I('post.price');
		$return = true;
		foreach ($prices as $value)
		{
			if(empty($value) || $value < 0)
			{
				$return = false;
			} else if (!is_numeric($value)){
				$return = false;
			}
		}
		return $return;
	}

	/**
	 * 检查积分是否合法
	 */
	public function checkGivescore()
	{
		$givescore = I('post.givescore');
		$return = true;
		if(!empty($givescore) && $givescore < 0)
		{
			$return = false;
		}
		return $return;
	}
}