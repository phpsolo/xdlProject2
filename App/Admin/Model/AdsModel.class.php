<?php
namespace Admin\Model;
use Think\Model;
 
/**
 * LoginInfoModel  用于处理后台广告数据
 */
class AdsModel extends Model {
	/**
	 * $_validate  自动验证添加广告的信息
	 */
	protected $_validate = [
		// 检测友链名称是否存在
		['name','','该友链已存在',0,'unique'],
		// 必须输入友链名
		['name','require','请输入广告名'],
		['url','require','必须输入网址'],
		['url','url','网址格式不正确']
	];

	/**
	 * 处理广告logo上传
	 * @return [type] [description]
	 */
	public function getPic($file){
		//先判断是否有文件需要处理,如果错误号为0，则成功
		if($file['error'] == '0'){
			$config = [
				//保存根路径
				'rootPath'=>'./Public/',
				'exts'    =>  array('jpg','jpeg','png','gif'),
				//保存路径
				'savePath'=>'Upload/',
				'autoSub'       =>  false,
				'saveName'=> array('uniqid', mt_rand(1,999999).'_'.md5(uniqid())),
				  //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
			];
			$upload = new \Think\Upload($config);
			//开始上传
			$info = $upload->uploadOne($file);
			if(!$info){
				return ['false',$upload->getError()];
			} else{
				return $info['savename'];
			}
		}
	}

	/**
	 * 处理广告数据
	 * @return [type] [description]
	 */
	public function getData(){
		$arr = $this->select();
		// var_dump($arr);
		$list = ['禁用','启用中'];
		foreach ($arr as $k => $v) {
			$arr[$k]['status'] = $list[$v['status']];
		}
		return $arr;
	}
}