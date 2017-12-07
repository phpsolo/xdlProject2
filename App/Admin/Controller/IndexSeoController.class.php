<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 主页seo控制器
 */
class IndexSeoController extends CommonController{
	/**
	 * 显示主页seo列表页
	 */
	public function index()
	{	
		// 实例化类
		$seo = D('IndexSeo');
		// 获取数据
		$list = $seo->getData();
		// 分配数据
		$this->assign('list',$list);
		// 显示页面
		$this->display('IndexSeo');
	}
	public function editSeo(){

		if(IS_POST){
		// var_dump($_POST);
			// 实例化类
			$seo = D('IndexSeo');
			// 获取数据
			// var_dump($arr);
			$res = $seo->getPic($_FILES['pic']);
			// //如果返回的是数组，且下标为0，则出错
			if($res[0] === 'false'){
				$this->error($res[1]);
			}
			//判断标题是否为空
			if(!empty($_POST['title'])){
				$arr['title'] = $_POST['title'];
			}
			// 判断描述是否为空
			if(!empty($_POST['description'])){
				$arr['description'] = $_POST['description'];
			}
			// 判断关键词是否为空
			if(!empty($_POST['keywords'])){
				$arr['keywords'] = $_POST['keywords'];
			}
			if(!empty($res)){
				$arr['pic'] = $res;
			}
			// 获取id
			$arr['id'] = $_POST['id'];
			// 修改数据库数据
			$info = $seo->where(['id' => $arr['id']])->save($arr);
			if($info){
				$this->success('修改成功',U('Index'));
			} else {
				$this->error('修改失败');
			}
		}else{
			// 获取id
			$id = I('id');
			$seo = M('IndexSeo');
			// 获取seo数据
			$seoarr = $seo->where(['id'=>$id])->select();
			// 分配数据
			$this->assign('seoarr',$seoarr);
			$this->assign('id',$id);
			$this->display();
		}
	}
}