<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 品牌控制器
 */
class BrandController extends CommonController{
	/**
	 * 品牌列表页
	 */
	public function index()
	{
		if(I('get.name')) $where['brandname'] = ['like', '%'.I('get.name').'%'];
		//操作品牌表
		$brand = M('Brand');
		//统计数据总条数
		$total = $brand->where($where)->count();
		//实例化分页类
		$page = new \Think\Page($total,3);
		//获取分页按钮
		$btns = $page->show();
		//查询数据
		$list = $brand->where($where)->limit($page->firstRow, $page->listRows)->select();
		if(IS_AJAX){
			$data = $list;
			$data['btns'] = $btns;
			$this->ajaxReturn($data);
			exit;
		}
		$this->assign(['data' => $list, 'btns' => $btns]);
		$this->display();
	}

	/**
	 * 显示并处理添加品牌
	 */
	public function add()
	{
		if(IS_POST)
		{
			$brand = D('Brand');
			$data = $brand->create();
			//判断是否创建数组对象成功
			if(!$data) $this->error($brand->getError());
			//开启事务
			$brand->startTrans();
			//判断是否上船图片
			if (empty($_FILES['pic']['name']))
			{
				$this->error('最少也给一张图片吧');
			} else {
				$config = array(    
					'maxSize'    =>    3145728,
					'rootPath'	 =>	   './Public/',
					'savePath'   =>    '/Uploads/',
				    'saveName'   =>    array('uniqid',''),
				    'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),   
				    'autoSub'    =>    false,
				);
				//实例化
				$upload = new \Think\Upload($config);
				//上传图片
				$info = $upload->uploadOne($_FILES['pic']);;
				if ($info)
				{
					$pic = $info['savename'];
				} else {
					$brand->rollback();
					$this->error($upload->getError());
				}
			}
			$data['pic'] = $pic;
			if($brand->add($data))
			{
				$brand->commit();
				$this->success('添加成功', U('index'));
				exit;
			} else {
				$brand->rollback();
				$this->error('添加失败');
			}
		} else {
			$this->display();
		}
	}

	/**
	 * 显示并处理修改
	 * @param  [int] $id [要修改的id]
	 */
	public function edit($id)
	{
		$brand = D('Brand');
		if(IS_POST)
		{
			//创建数组对象
			$data = $brand->create();
			if(!$data) $this->error($brand->getError());
			//判断是否上船图片
			if (!empty($_FILES['pic']['name']))
			{
				$config = array(    
					'maxSize'    =>    3145728,
					'rootPath'	 =>	   './Public/',
					'savePath'   =>    '/Uploads/',
				    'saveName'   =>    array('uniqid',''),
				    'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),   
				    'autoSub'    =>    false,
				);
				//实例化上传类
				$upload = new \Think\Upload($config);
				$info = $upload->uploadOne($_FILES['pic']);;
				if ($info)
				{
					//把上传后的文件名塞到data数组
					$data['pic'] = $info['savename'];
				} else {
					$this->error($upload->getError());
				}
			}
			//修改品牌表
			$res = $brand->save($data);
			if($res)
			{
				$this->success('修改成功', U('index'));
			} else if($res === 0) {
				$this->error('你什么都没改');
			} else {
				$this->error('修改失败');
			}
		} else {
			$data = $brand->find(I('get.id') + 0);
			$this->assign('data', $data);
			$this->display();
		}
	}

	/**
	 * 删除操作
	 * @param  [int] $id [要删除的id]
	 * @return [type]     [description]
	 */
	public function del($id)
	{
		//操作品牌表
		$brand = M('Brand');
		//开启事务
		$brand->startTrans();
		//操作品牌分类关联表
		$brandType = M('BrandType');
		//判断关联分类表有几条数据
		$total = count($brandType->where(['bid' => I('get.id') + 0])->select());
		$goods = M('Goods');
		//判断该品牌下有无商品
		$product = $goods->where(['bid' => I('get.id') + 0])->select();
		if($product) $this->error('该品牌下有商品，不能删除');
		if($total == 0)
		{
			//如果分类表,商品表都没有数据则直接删除
			if($brand->delete(I('get.id') + 0))
			{
				$brand->commit();
				$this->redirect('index');
			} else {
				$brand->rollback();
				$this->error('删除失败');
			}
		} else {
			//如果有则删除关联数据，然后再删除
			$del = $brandType->where(['bid' => I('get.id') + 0])->delete();
			if($del === $total && $brand->where(['id' => I('get.id') + 0])->delete())
			{
				$brand->commit();
				$this->redirect('index');
			} else {
				$brand->rollback();
				$this->error('删除失败');
			}
		}
	}

	/**
	 * 为品牌添加分类
	 * @param [id] $id [品牌id]
	 */
	public function addType($id)
	{
		//操作品牌表
		$brand = M('Brand');
		//查找品牌信息
		$tmp = $brand->find(I('get.id') + 0);
		//操作分类表
		$type = D('Type');
		//查找分类信息
		//操作品牌分类中间表
		$brandType = D('BrandType');
		$data = $type->order('concat(path, id)')->getData();
		if(IS_POST)
		{
			//操作品牌分类中间表
			$brandType = D('BrandType');
			//开启事务
			$brandType->startTrans();
			//获取格式化后的数据
			$ids = $brandType->getPostData();
			// var_dump($ids);exit;
			//定义一个开始为true的条件
			$tmp = true;
			foreach ($ids['insertIds'] as $v) {
				$res = $brandType->add(['tid' => $v, 'bid' => I('post.bid') + 0]);
				//如果插入失败，把条件改为false
				if(!$res) $tmp = false;
			}
			//要删除的tid数组
			if(empty($ids['deleteIds']))
			{
				//为空则没有要删除的，把条件设为true
				$tmp1 = true;
			} else {
				//根据tid数组删除关联表中的数据
				$tmp1 = $brandType->where(['tid' => ['in', join(',', $ids['deleteIds'])], 'bid' => I('post.bid') + 0])->delete();
			}

			if($tmp && $tmp1)
			{
				//如果插入与删除同时成功则提交事务
				$brandType->commit();
				//修改成功删除缓存
				foreach ($ids['deleteIds'] as  $v) {
					S("a{$v}",null);
				}
				foreach ($ids['insertIds'] as  $v) {
					S("a{$v}",null);
				}
				$this->success('修改成功', U('index'));
			} else {
				//否则回滚事务
				$brandType->rollback();
				$this->error('修改失败');
			}
		} else {
			//查找品牌分类关联表
			$res = $brandType->where(['bid' => I('get.id') + 0])->select();
			$tids = array_column($res, 'tid');
			$this->assign('type', $tids);
			$this->assign('data', $data);
			$this->assign('brand', $tmp);
			$this->display();
		}
	}
}