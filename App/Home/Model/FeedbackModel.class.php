<?php
namespace Home\Model;
use Think\Model;
 
/**
 * FeedbackModel  用于处理前台意见截图上传数据
 */
class FeedbackModel extends Model {

	/**
	 * $_validate  自动验证意见反馈的信息
	 */
	protected $_validate = [
		// 必须输入意见描述
		['des','require','请输入意见'],
	];

	/**
	 * 处理意见反馈截图上传
	 * * @return [string] [图片名字]
	 */
	public function getPic($file){
		//先判断是否有文件需要处理,如果错误号为0，则成功
		if($file){
			$config = [
				//保存根路径
				'rootPath'=>'./Public/',
				'exts'    =>  array('jpg','jpeg','png','gif'),
				//保存路径
				'savePath'=>'FeedbackUpload/',
				'autoSub'       =>  false,
				  //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
			];
			$upload = new \Think\Upload($config);
			//开始上传
			$info = $upload->upload();
			if(!$info){
				return ['false',$upload->getError()];
			} else{
				return join(',',array_column($info,'savename'));
			}
		}
	}
}