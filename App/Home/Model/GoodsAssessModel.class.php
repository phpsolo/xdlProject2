<?php
namespace Home\Model;
use Think\Model;
/**
 * 处理商品评论数据
 */
class GoodsAssessModel extends Model{

	/**
	 * 获取商品评论数据
	 * @return [array] [格式化后的商品评论数据]
	 */
	public function getData()
	{
		$data = $this->select();
		$user = M('User');
		//所有评价
		$feels = [1 => '好评', '中评', '差评'];
		foreach ($data as $key => $value) {
			$userData = $user->field('username, photo')->where(['id' => $value['uid']])->find();
			//判断是否匿名评论
			if($data[$key]['status'] == 0)
			{
				//匿名评论的话对用户名进行替换
				$data[$key]['username'] = substr_replace($userData['username'], '***', 2);
			} else {
				$data[$key]['username'] = $userData['username'];
			}
			$data[$key]['photo'] = $userData['photo'];
			//把时间转化为为 xxxx-xx-xx xx:xx:xx 的格式
			$data[$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
			$data[$key]['feel'] = $feels[$value['feel']];
		}
		return $data;
	}
}