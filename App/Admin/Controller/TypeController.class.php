<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 商品分类控制器
 */
class TypeController extends CommonController{

	/**
	 * 显示分类列表页
	 */
	public function index()
	{
		$name = I('get.name');
		if($name) $where['name'] = ['like', '%'.$name.'%'];
		$type = D('Type');
		$total = $type->where($where)->count();
		$page = new \Think\Page($total,7);
		$btns = $page->show();
		$data = $type->order('concat(path,id)')->where($where)->limit($page->firstRow, $page->listRows)->getData();
		if(IS_AJAX)
		{
			$data = $data;
			$data['btns'] = $btns;
			$this->ajaxReturn($data);
			exit;
		}
		$this->assign(['data' => $data, 'btns' => $btns]);
		$this->display();

	}

	/**
	 * 显示和处理添加分类
	 */
	public function add()
	{
		$type = D('Type');
		$data = $type->create();
		if(IS_POST)
		{
			if(I('post.pid'))
			{
				$res = $type->field('id,path')->find(I('post.pid'));
				$data['pid'] = $res['id'];
				$data['path'] = $res['path'].$res['id'].',';
			} else {
				$data['pid'] = 0;
			}

			if($resid = $type->add($data))
			{
				// 增加索引
		    	if($resid){
			    	vendor('xunsearch.lib.XS');
			    	$xs = new \XS('type');
			    	$xs->index->add(new \XSDocument(['id'=>$resid,'name'=>$_POST['name'],'pid'=>$data['pid']]));
			    	$xs->index->flushIndex();	
		    	}
				$this->success('添加成功');
			} else {
				$this->error($type->getError());
			}
			
		} else {
			//如果传入了id就是往子类添加子类
			if(I('get.id'))
			{
				//加入第三级分类的时候，找到二级分类，再找顶级分类
				$tid = M('Type')->find($_GET['id'])['pid'];
				//最后将顶级分类的缓存删除
				S('types'.$tid,null);
				//分配变量
				$res = $type->where(['id' => I('get.id')])->getData();
				$this->assign('data', $res[0]);
			}
			$this->display();
		}
	}

	/**
	 * 删除操作
	 * @param  [int] $id [要删除的id]
	 */
	public function del($id)
	{
		$id = I('get.id') + 0;
		//操纵分类表
		$type = M('Type');
		//查询要删除的分类是否有子类
		$son = $type->where(['pid' => $id])->select();
		//操作商品表
		$goods = M('goods');
		//判断要删除的分类下是否有商品
		$goodsInfo = $goods->where(['tid' => $id])->select();
		//操作品牌分类关联表
		$brandType = M('brandType');
		$del = $brandType->where(['tid' => $id])->delete();
		//删除3级分类的同时删除顶级分类的缓存
		$second = M('Type')->find($id)['pid'];
		$first = M('Type')->find($second)['pid'];
		//最后将顶级分类的缓存删除
		S('types'.$first,null);
		//当没有子类，没有商品时，删除成功
		if(!$son && !$goodsInfo && $type->delete($id))
		{
			// 删除索引			
			vendor('xunsearch.lib.XS');
		    $xs = new \XS('type');
		    $xs->index->del($id);
		    $xs->index->flushIndex();
			$this->redirect('index');
		} else {
			$this->error('删除失败');
		}
	}

	/**
	 * 显示并处理修改
	 * @param  [int] $id [要修改的id]
	 */
	public function edit($id)
	{
		$id = I('get.id') + 0;
		$type = M('Type');
		if(IS_POST)
		{
			//修改子分类的时候，找到顶级分类，并删除缓存
			$son = $type->find($_POST['id'])['pid'];
			if($type->find($son)['pid'] === '0'){
				S('types'.$son,null);
			} else {
				S('types'.$type->find($son)['pid'],null);
			}

			$res = $type->field('name')->where(['id' => $id])->save($_POST);
			if($res) 
			{
				// 修改索引
			    vendor('xunsearch.lib.XS');
			    $xs = new \XS('type');
			    $xs->index->update(new \XSDocument(['id'=>$id,'name'=>$_POST['name'],'pid'=>$son]));
			    $xs->index->flushIndex();

				$this->success('修改成功', U('index'));
			} else {
				echo $type->_sql();
				$this->error('修改失败');
			}
		} else {
			//获取格式化后的数据
			$res = $type->where(['id' => $id])->select();
			//分配数据
			$this->assign('data', $res[0]);
			$this->display();
		}
	}
}