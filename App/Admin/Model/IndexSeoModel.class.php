<?php
namespace Admin\Model;
use Think\Model;
 
/**
 * LoginInfoModel  用于处理后台友情数据
 */
class IndexSeoModel extends Model {
	/**
	 * 处理网站logo上传
	 * @return [type] [description]
	 */
	public function getPic($file){
		//先判断是否有文件需要处理,如果错误号为0，则成功
		if($file['error'] == '0'){
			$config = [
				//保存根路径
				'rootPath'=>'./Public/',
				// 设置附件上传类型
				'exts'     =>     array('jpg', 'gif', 'png', 'jpeg'),
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
	 * 处理友情链接数据
	 * @return [array] [处理后的数据]
	 */
	public function getData(){
		$arr = $this->select();
		
		return $arr;
	}
}