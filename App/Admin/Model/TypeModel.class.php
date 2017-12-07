<?php
namespace Admin\Model;
use Think\Model;
/**
 * 用于处理分类model层数据
 */
class TypeModel extends Model{
	//自动验证规则
	protected $_validate = [
		['name', 'require', '分类名必须有'],
		['name', '/^[\w\/\x{4e00}-\x{9fa5}]{2,18}$/ui', '分类名必须为2-50位数字字母下划线'],
	];

	/**
	 * 格式化数据
	 * @return [array] [格式化后的数据]
	 */
	public function getData()
	{
		$res = $this->select();
		foreach($res as $k => $v)
		{
			//统计path中','的数量
			$num = substr_count($v['path'], ',');
			$str = '';
			for($i = 0; $i < $num; $i++)
			{
				//根据数量拼接空格
				$str .= '　　　';
			}
			//格式化数据
			if($v['pid'] == 0)
			{
				$res[$k]['pid'] = '顶级分类';
			} else {
				$res[$k]['pid'] = $this->field('name')->find($v['pid'])['name'];
				$res[$k]['name'] = $str.'┗─'.$v['name'];
			}
			//判断是否有子类，有的话塞一个attr = disabled
			//品牌模块遍历用到
			$son = $this->where(['pid' => $v['id']])->select();
			if($son) $res[$k]['attr'] = 'disabled';

		}
		return $res;
	}
}