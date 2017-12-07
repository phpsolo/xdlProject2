<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 商品控制器
 */
class GoodsController extends CommonController{
	protected $redis;
	/**
	 *连接redis
	 */
	public function _initialize()
    {
    	parent::_initialize();
    	$this->redis = new \Redis();
    	$this->redis->connect('localhost', 6379);
    }
    
	/**
	 * 显示商品列表页
	 */
	public function index()
	{
		$name = I('get.name');
		$tid = I('get.tid');
		// dump($tid);
		if($name) $where['name'] = ['like', '%'.$name.'%'];
		if($tid) $where['tid'] = $tid;
		//分类表
		$type = D('Type')->order('concat(path,id)')->getData();
		//商品表
		$goods = D('Goods');
		//总条数
		$total = $goods->where($where)->count();
		//实例化分页类
		$page = new \Think\Page($total, 4);
		//获取分页按钮
		$btns = $page->show();
		//查询数据
		$data = $goods->field('id,name,bid,tid,price,stock,addtime,status')->where($where)->limit($page->firstRow, $page->listRows)->order('addtime desc')->getData();
		if(IS_AJAX)
		{
			$res = [
				'data' => $data,
				'btns' => $btns
			];
			$this->ajaxReturn(json_encode($res));
			exit;
		} else {
			$this->assign('type', $type);
			$this->assign('data', $data);
			$this->assign('btns', $btns);
			$this->display('Products');
		}
	}

	/**
	 * 显示添加商品页面
	 */
	public function add()
	{
		if(IS_POST)
		{
			$goods = D('Goods');
			//创建数据对象
			if ($goods->create())
			{  
				//1. 开启事务
		    	$goods->startTrans();
				//获取设置的商品属性
				$attr = I('post.attr');
				$price = $_POST['price'];
				$stock = $_POST['stock'];
				$_POST['name'] = trim($_POST['name'],' ');
				$_POST['addtime'] = $_POST['addtime'] ? strtotime($_POST['addtime']) : time();
				foreach($attr as $key => $val)
				{
					$_POST['attr'] = $val;
					$_POST['price'] = $price[$val];
					$_POST['stock'] = $stock[$val];
					//第一条条件为默认条件，插入到商品表作为冗余字段
					if($key === 0)
					{
						//判断是否上船图片
						if (empty($_FILES['pic']['name']))
						{
							$this->error('最少也给一张图片吧');
						} else {
							$config = array(    
								'maxSize'    =>    3145728,
								'rootPath'	 =>	   './Public/',
								'savePath'   =>    '/Uploads/',
							    'saveName'=> array('uniqid', mt_rand(1,999999).'_'.md5(uniqid())),
							    'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),   
							    'autoSub'    =>    false,
							);
							//实例化上传类
							$upload = new \Think\Upload($config);
							$pic = $upload->upload();
							if ($pic)
							{
								//获取上传后的文件名
								$pics = array_column($pic, 'savename');
								//把第一张图片作为默认图片
								$_POST['pic'] = $pics[0];
								$res = $goods->add($_POST);
								//把促销商品添加到redis								if($_POST['status'] == 4)
								{
									$tmp = $_POST;
									unset($tmp['attr']);
									if(!$tmp['givescore']) $tmp['givescore'] = 0;
									$this->redis->hMSet('hash:goods:'.$res, $tmp);
								}
								//把gid塞入数组
								$_POST['gid'] = $res;
								if(!$res)
								{
									//回滚事务
							    	$goods->rollback();
							    	$this->error('商品表插入失败');
								}
								$pic = M('GoodsPic');
								foreach ($pics as $v) {
									$picId = $pic->add(['gid' => $res, 'pic' => $v]);
									if($_POST['status'] == 4)
									{
										$this->redis->hSet('hash:goodspic:gid:'.$_POST['gid'], $picId, $v);
									}
								}
							} else {
								$this->error($upload->getError());
							}
						}
					}
					//商品属性插入到商品信息表
					$goodsInfo = M('GoodsInfo');
					$infores = $goodsInfo->add($_POST);
					if($_POST['status'] == 4)
					{
						$data = $goodsInfo->create();
						$data['id'] = $infores;
						$this->redis->hMSet('hash:goodsinfo:gid:'.$data['gid'].':attr:'.$data['attr'], $data);
					}
					if(!$infores) 
					{
						//回滚事务
				    	$goods->rollback();
				    	$this->error('商品信息表插入失败');
					}
				}
				//2. 提交事务
				//若执行到该地方，则说明插入商品成功，则要删除该商品所属分类下的缓存
				S('a'.I('tid'),null);
		    	$goods->commit();
		    	// 增加索引
		    	if($res){
			    	vendor('xunsearch.lib.XS');
			    	$xs = new \XS('goods');
			    	$xs->index->add(new \XSDocument(['id'=>$res,'name'=>$_POST['name'],'des'=>$_POST['des'],'tid'=>$_POST['tid'],'bid'=>$_POST['bid']]));
			    	$xs->index->flushIndex();
		    	}
		    	$this->success('添加成功', U('index'));
		    	exit;
			} else {     
				// 如果创建失败 表示验证没有通过 输出错误提示信息     
				$this->error($goods->getError());
			}
		} else if (IS_AJAX) {
			//获取提交的tid
			$tid = I('get.tid');
			//操作品牌分类中间表
			$brandType = M('BrandType');
			//获取该分类下的bid
			$bids = array_column($brandType->where(['tid' => $tid])->select(), 'bid');
			//操作品牌表
			$brand = M('Brand');
			//查询该分类下所有的品牌数据
			foreach ($bids as $value) {
				$data[] = $brand->field('id, brandname')->where(['id' => $value])->find();
			}
			$this->ajaxReturn($data);
			exit;
		} else {
			$type = D('Type');
			$data = $type->order('concat(path, id)')->getData();
			$this->assign('type', $data);
			$this->display();
		}
	}

	/**
	 * 删除商品操作
	 * @param  [int] $id [要删除的id]
	 */
	public function del($id)
	{
		//操作商品表
		$goods = M('Goods');
		//操作商品属性表
		$goodsInfo = M('GoodsInfo');
		//操作商品图片表
		$goodsPic = M('GoodsPic');
		$goods->startTrans();
		$arr = $goods->find($id);
		if($arr['status'] == 4)
		{
			//如果是促销商品，删除redis中的数据
			$this->redis->del('hash:goods:'.$arr['id'], 'hash:goodsinfo:gid:'.$arr['id'], 'hash:goodspic:gid:'.$arr['id']);
		}
		$falg = true;
		//删除该商品属性
		if(!$goodsInfo->where(['gid' => $arr['id']])->delete()) $falg = false;
		//删除该商品图片
		if(!$goodsPic->where(['gid' => $arr['id']])->delete()) $falg = false;
		//查出图片路径
		$path = './Public/Uploads/'.$arr['pic'];
		//删除商品的同时删除图片和缓存
		if($path) {
			unlink($path);
		}
		S('a'.$arr['tid'],null);
		if(!$res = $goods->delete($id)) $falg = false;
		if($falg)
		{
			$goods->commit();
		} else {
			$goods->rollback();
		}
		// 删除索引
		if($res){
		    vendor('xunsearch.lib.XS');
		    $xs = new \XS('goods');
		    // var_dump($_POST);exit;
		    $xs->index->del($id);
		    $xs->index->flushIndex();
		}
		if(IS_AJAX)
		{
			echo $res ? 1 : 0;		
		} else {
			$this->redirect('index');
		}
	}  

	/**
	 * 批量删除
	 */
	public function delAll()
	{
		$goods = M('Goods');
		$falg = true;
		//操作商品属性表
		$goodsInfo = M('GoodsInfo');
		//操作商品图片表
		$goodsPic = M('GoodsPic');
		$goods->startTrans();
		//找出批量删除商品下的所有不同的分类,并删除缓存
		foreach ($_GET['ids'] as  $v) {
			//删除该商品属性
			if(!$goodsInfo->where(['gid' => $v])->delete()) $falg = false;
			//删除该商品图片
			if(!$goodsPic->where(['gid' => $v])->delete()) $falg = false;
			$tmp = M('Goods')->find($v);
			//如果是促销商品，删除redis中的数据
			if($tmp['status'] == 4)
			{
				$this->redis->del('hash:goods:'.$tmp['id'], 'hash:goodsinfo:gid:'.$tmp['id'], 'hash:goodspic:gid:'.$tmp['id']);
			}
			//分类id
			$tid = $tmp['tid'];
			S('a'.$tid,null);
			//并删除图片
			$path = './Public/Uploads/'.M('Goods')->find($v)['pic'];
			unlink($path);
		}
		if(!$res = $goods->where(['id' => [ 'in', I('get.ids')]])->delete()) $falg = false;
		if($falg)
		{
			$goods->commit();
		} else {
			$goods->rollback();
		}
		// 删除索引
		if($res){
			vendor('xunsearch.lib.XS');
		    $xs = new \XS('goods');
		    // var_dump($_POST);exit;
		    $xs->index->del(I('get.ids'));
		    $xs->index->flushIndex();
		}
		echo $res ? 1 : 0;
	}

	/**
	 * 修改操作
	 * @param  [int] $id [要修改的id]
	 */
	public function edit($id)
	{
		// dump($_COOKIE);
		if(IS_POST)
		{
			$goods = D('Goods');
			$oldData = $goods->where(['id' => I('post.id')])->find();
			if($_POST['status'] != 4) 
			{
				$this->redis->del('hash:goods:'.$oldData, 'hash:goodspic:gid:'.$oldData, 'hash:goodsinfo:gid:'.$oldData);
			}
			//创建数据对象
			if ($goods->create())
			{  
				//1. 开启事务
		    	$goods->startTrans();
				$price = $_POST['price'];
				$stock = $_POST['stock'];
				$attr = $_POST['attr'];
				$attr_id = $_POST['attr_id'];
				$sid = $_POST['id'];
				foreach($attr as $key => $val)
				{
					if($_POST['addtime'])
					{
						$_POST['addtime'] = strtotime($_POST['addtime']);
					}
					$_POST['attr'] = $attr[$key];
					$_POST['price'] = $price[$val];
					$_POST['stock'] = $stock[$val];
					//第一条条件为默认条件，插入到商品表作为冗余字段
					if($key === 0)
					{
						// dump($_FILES);exit;
						//判断是否上船图片
						if ($_FILES['pic']['error'][0] != 4)
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
							$pic = $upload->upload();
							if ($pic)
							{
								//获取上传后的文件名
								$pics = array_column($pic, 'savename');
								//把第一张图片作为默认图片
								$_POST['pic'] = $pics[0];
								$pic = M('GoodsPic');
								$pic->where(['gid' => I('post.id')])->delete();
								foreach ($pics as $v) {
									$pic->add(['gid' =>I('post.id'), 'pic' => $v]);
								}
							} else {
								$this->error($upload->getError());
							}
						}
						//修改商品表
						$product = $goods->save($_POST);
					}
					//商品属性插入到商品信息表
					$goodsInfo = M('GoodsInfo');
					unset($_POST['id']);
					if($goodsInfo->where(['id' => $attr_id[$val]])->save($_POST)) $res = true;
				}
				//如果既没有改商品属性，也没有改商品
				if(!$res && !$product) 
				{
					//回滚事务
			    	$goods->rollback();
			    	$this->error('商品信息表或商品表修改失败');
				}
				//2. 提交事务
				//若执行到该地方，则说明插入商品成功，则要删除该商品所属分类下的缓存
				S('a'.I('tid'),null);
		    	$goods->commit();
		    	// 修改索引
		    	vendor('xunsearch.lib.XS');
		    	$xs = new \XS('goods');
		    	// var_dump($_POST);exit;
		    	$xs->index->update(new \XSDocument(['id'=>$sid,'name'=>$_POST['name'],'des'=>$_POST['des'],'tid'=>$_POST['tid'],'bid'=>$_POST['bid']]));
		    	$xs->index->flushIndex();
		    	$this->success('修改成功', U('index'));
		    	exit;
			} else {     
				// 如果创建失败 表示验证没有通过 输出错误提示信息     
				$this->error($goods->getError());
			}
		} else {
			//操作商品表
			$goods = M('Goods');
			//查询商品数据
			$data = $goods->where(['id' => $id + 0])->find();
			//操作分类表
			$type = D('Type');
			$type = $type->order('concat(path, id)')->getData();
			//操作品牌分类关联表表
			$brandType = M('BrandType');
			$brandType = array_column($brandType->field('bid')->where(['tid' => $data['tid']])->select(), 'bid');
			//操作品牌表
			$brand = M('Brand');
			$brand = $brand->where(['id' => ['in', join(',', $brandType)]])->select();
			//操作商品详情表
			$goodsInfo = D('GoodsInfo');
			$goodsInfo = $goodsInfo->where(['gid' => $data['id']])->getData();
			// dump($goodsInfo);exit;
			$this->assign(['data' => $data, 'type' => $type, 'brand' => $brand, 'goodsInfo' => $goodsInfo]);
			$this->display();
		}
	}
}