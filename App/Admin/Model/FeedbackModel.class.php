<?php
namespace Admin\Model;
use Think\Model;
 
/**
 *FeedbackModel  用于处理后台意见反馈
 */
class FeedbackModel extends Model {
	/**
	 * 处理后台意见反馈数据
	 * @return [array] [处理完成的数组]
	 */
	public function getData(){
		$arr = $this->select();
		foreach ($arr as $k => $v) {
			$arr[$k]['uid'] = M('User')->find($v['uid'])['username'];
			$arr[$k]['addtime'] = date('Y-m-d H:i:s',$v['addtime']);

			if( strlen($arr[$k]['pic']) > 17){
				$arr[$k]['pic'] = substr($v['pic'],0,17);
			}
		}
		return $arr;
	}
}