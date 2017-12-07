<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 前台购物车控制器
 */
class ShopCarController extends Controller {

	/**
	 * 设置连接redis的属性
	 */
	public function _initialize()
    {
        //如果成员属性没有声明，默认就是公有属性
        $this->redis = new \Redis;
        $this->redis->connect('127.0.0.1', 6379);
        // 设置session的最大生命周期
        ini_set("session.gc_maxlifetime", 3600*24*7);
        setcookie(session_name(), session_id(), time() + 3600*24*7);
    }

	/**
	 * 显示购物车页面
	 */
    public function shopcar()
    {
    	$goods = M('Goods');
    	$goodsInfo = M('GoodsInfo');
    	if(!session('?HomeUser')){
			//根据session_id得到缓存在redis数据的键
			$key = 'cart:ids:set:'.session_id();
			//根据键取集合中的商品id
			$idArr = $this->redis->zRange($key, 0, -1);
			//根据商品id和session_id得到redis的数据
			for ($i = 0; $i<count($idArr); $i++) {
				$k = 'cart:datas:'.session_id().':'.$idArr[$i];
				//获取redis中集合的拼接id
				$list = $this->redis->hGetAll($k);
				//切割id
				$ids = explode('_',$idArr[$i]);
				//根据id查询商品信息
				$goods_data = $goods->field('name,givescore,pic,status')->where(['id'=>$ids[0]])->find();
				//判断是否在售中
				if($goods_data['status'] == 1 || $goods_data['status'] == 3) continue;
				//根据属性id查询商品属性信息
				$goodsInfo_data = $goodsInfo->field('id,gid,attr,price')->where(['id'=>$ids[1]])->find();
				//合并商品信息
				$list = array_merge($list,$goods_data,$goodsInfo_data);
				$data[] =$list;
 			}
			//倒转数组，然后加入购物车的商品遍历到最前面
			$data = array_reverse($data,true);
 			$role = 0;
		} else {
			$shopcar = M('shopcar');
			//获取用户数据库购物车信息
			$info = $shopcar->field('shopcar')->where(['uid'=>session('HomeUser')['id']])->find();
			//将json格式转为数组
			$info = json_decode($info['shopcar'],true);
			foreach ($info as $k=>$v) {
				//切割id
				$ids = explode('_',$k);
				//根据id查询商品信息
				$goods_data = $goods->field('name,givescore,pic,status')->where(['id'=>$ids[0]])->find();
				//判断是否在售中
				if($goods_data['status'] == 1 || $goods_data['status'] == 3) continue;
				//根据属性id查询商品属性信息
				$goodsInfo_data = $goodsInfo->field('id,gid,attr,price')->where(['id'=>$ids[1]])->find();
				//合并商品信息
				$list = array_merge($v,$goods_data,$goodsInfo_data);
				$data[] =$list;
			}
			//倒转数组，然后加入购物车的商品遍历到最前面
			$data = array_reverse($data,true);
			//查询用户的会员角色
			$user = M('user');
			$role = $user->field('role')->where(['id' => session('HomeUser')['id']])->find()['role'];
		}
		//分配数据
		if(IS_AJAX) {
			$this->success($data);
		}
		$this->assign('list',$data);
		$this->assign('role',$role);
        $this->display();
    }


    /**
     * 添加商品数据进购物车
     * @param string $gid     商品ID
     * @param string $info    商品属性
     * @param number $buyNum  商品的数量
     */
	public function addToCart($gid,$info, $buyNum)
	{
		if(IS_AJAX){
			//判断提交的购买量是否是数值
			if(!is_numeric($buyNum)) {
				$this->error('添加失败');
			}
			if($buyNum < 1) {
				$this->error('请添加商品的数量等于或大于1件');
			}
			$GoodsInfo = M('GoodsInfo');
			$shopcarInfo = $GoodsInfo->field('id,stock')->where(['gid'=>$gid,'attr'=>urldecode($_GET['info'])])->find();
			//判断是否有该属性
			if(!$shopcarInfo) {
				$this->error('添加失败');
			}
			$info_id = $shopcarInfo['id'];
			//判断添加购物车的商品数量是否小于或者等于商品库存
			if ($buyNum > $shopcarInfo['stock']) {
				$this->error("添加数量大于商品库存");
			}
			//判断用户是否已登录
			if(!session('?HomeUser')){
				//用户未登录
				$cartKey = 'cart:datas:'.session_id().':'.$gid.'_'.$info_id;
				//判断购物车中有无对应商品数据
				if(!$this->redis->exists($cartKey)) {
					//购物车没有对应商品
					$product['buyNum'] = $buyNum;
					//将信息存进redis的hash类型中
					$this->redis->hMSet($cartKey, $product);
					//准备购物车的商品ID
					$setKey = 'cart:ids:set:'.session_id();
					//获取集合的数据个数，准备插入有序集合数据的下标
					$xiabiao = $this->redis->zSize($setKey) + 1;
					//将已经放入到购物车的商品ID存放到集合中
					$message = $this->redis->zAdd($setKey, $xiabiao ,$gid.'_'.$info_id);
				} else {
					//购物车已经有对应的商品，修改对应购物车中商品的数量
					//判断购物车商品数量是否大于该商品库存
					$idArr = $this->redis->hGetAll($cartKey);
					$total = $idArr['buyNum'] + $buyNum;
					if ($total > $shopcarInfo['stock']) {
						$this->error("该商品在你购物车的数量已接近或等于库存，你只能再添加".($buyNum - ($total - $shopcarInfo['stock']))."件");
					}
					$message = $this->redis->hIncrBy($cartKey, 'buyNum', $buyNum);
				}
			} else {
				//用户已登录,在登录的时候已经把redis购物车信息放到数据库
				//判断该用户数据库中有没有购物车信息
				$uid = session('HomeUser')['id'];
				$shopcar = M('shopcar');
				//查询用户ID对应的购物车数据
				$data = $shopcar->where(['uid'=>$uid])->find();
				if(!$data) {
					//当该用户的购物车为空时
					$insert['shopcar'] = json_encode([$gid.'_'.$info_id =>['buyNum'=>$buyNum]]);
					$insert['uid'] = $uid;
					$message = $shopcar->add($insert);
				} else {
					//该用户数据库有购物车信息
					$info = json_decode($data['shopcar'],true);
					$info[$gid.'_'.$info_id]['buyNum'] = $info[$gid.'_'.$info_id]['buyNum'] + $buyNum;
					if ($info[$gid.'_'.$info_id]['buyNum'] > $shopcarInfo['stock']) {
						$this->error("该商品在你购物车的数量已接近或等于库存，你只能再添加".($buyNum - ($info[$gid.'_'.$info_id]['buyNum'] - $shopcarInfo['stock']))."件");
					}
					$data = json_encode($info);
					$message = $shopcar->where(['uid'=>session('HomeUser')['id']])->save(['shopcar'=>$data]);
				}
			}
			//判断添加状态，返回信息
			if($message) {
				$this->success('添加成功');
			} else {
				$this->error('添加失败');
			}
		} else {
			$this->redirect('Index/index');
		}
	}

	/**
	 * 购物车商品的删除
	 * @param  string $idInfo 商品id与商品属性id的拼接字符串
	 * @return string         商品是否删除成功的状态信息
	 */
	public function del($idInfo)
	{
		//判断用户是否登录
		if(!session('?HomeUser')){
			//用户未登录，删除redis中对应的键
			$cartKey = 'cart:datas:'.session_id().':'.$idInfo;
			//开启redis事务
			$this->redis->multi();
			$info = $this->redis->delete($cartKey);
			if($info) {
				//准备购物车的商品对应标识
				$setKey = 'cart:ids:set:'.session_id();
				//删除集合中的商品对应标识
				$data = $this->redis->zDelete($setKey,$idInfo);
				if($data) {
					//集合删除标识成功，提交redis事务
					$this->redis->exec();
					//返回信息
					$this->success('删除成功');					
				} else {
					//集合删除标识失败，回滚redis事务
					$this->redis->discard();
					$this->error('删除失败');
				}
			} else {
				//对应键的数据删失败，回滚redis事务
				$this->redis->discard();
				$this->error('删除失败');

			}	
		} else {
			//用户已登录，删除数据库中购物车对应的商品
			$shopcar = M('shopcar');
			//取出用户数据库中的购物车信息
			$info = $shopcar->field('shopcar')->where(['uid'=>session('HomeUser')['id']])->find();
			//转换json为数组格式
			$info = json_decode($info['shopcar'],true);
            //删除对应下标的数据
            unset($info[$idInfo]);
            //判断购物车商品信息是否为空
            if(empty($info)){
            	//商品信息为空
            	$data = $shopcar->where(['uid'=>session('HomeUser')['id']])->delete();
            } else {
            	//商品信息不为空
            	$info = json_encode($info);
            	$data = $shopcar->where(['uid'=>session('HomeUser')['id']])->save(['shopcar'=>$info]);
            }
			if($data){
				$this->success('删除成功');
			} else {
				$this->error('删除失败');
			}
		}
	}


	/**
	 * 减少购物车商品的购买数量
	 * @param  string $idInfo 商品id与商品属性id的拼接字符串
	 * @return string         商品数量是否修改成功的状态信息
	 */
	public function reduce($idInfo) {
		//判断用户是否登录
		if(!session('?HomeUser')){
			//用户未登录,减少redis中用户对应商品数据的数量
			$cartKey = 'cart:datas:'.session_id().':'.$idInfo;
			//判断商品数量是否为1
			$num = $this->redis->hget($cartKey,'buyNum');
			if($num == 1) {
				$this->error('商品数量最少为1');
			} else {
				$this->redis->hincrby($cartKey,'buyNum','-1');
				$this->success('商品数量修改成功');
			}
		} else {
			//用户已登录，修改数据库中的商品数量
			$shopcar = M('shopcar');
			$data = $shopcar->where(['uid' => session('HomeUser')['id']])->find();
			$data = json_decode($data['shopcar'],true);
			$num = $data[$idInfo]['buyNum'];
			//判断商品数量是否为1
			if($num == 1) {
				$this->error('商品数量最少为1');
			} else {
				$data[$idInfo]['buyNum'] = $data[$idInfo]['buyNum'] - 1;
				$data = json_encode($data);
				$info = $shopcar->where(['uid' => session('HomeUser')['id']])->save(['shopcar'=>$data]);
				if($info) {
					$this->success('商品数量修改成功');
				} else {
					$this->error('商品数量修改失败');
				}
			}
		}
	}


	/**
	 * 增加购物车商品的购买数量
	 * @param  string $idInfo 商品id与商品属性id的拼接字符串
	 * @return string         商品数量是否修改成功的状态信息
	 */
	public function add($idInfo) {
		//判断用户是否登录
		if(!session('?HomeUser')){
			//用户未登录,增加redis中用户对应商品数据的数量
			$ids = explode('_',$idInfo);
			//查询商品的库存
			$goodsInfo = M('GoodsInfo');
			$goods_num = $goodsInfo->where(['id'=>$ids[1]])->find()['stock'];
			$cartKey = 'cart:datas:'.session_id().':'.$idInfo; 
			//判断商品数量是否超过商品库存
			$num = $this->redis->hget($cartKey,'buyNum');
			if($num >= $goods_num) {
				$this->error('库存不足，目前该商品数量最多只能购买'.$goods_num.'件');
			} else {
				$this->redis->hincrby($cartKey,'buyNum','1');
				$this->success('商品数量修改成功');
			}
		} else {
			//用户已登录，修改数据库中的商品数量
			$ids = explode('_',$idInfo);
			//查询商品的库存
			$goodsInfo = M('GoodsInfo');
			$goods_num = $goodsInfo->where(['id'=>$ids[1]])->find()['stock'];
			$shopcar = M('shopcar');
			$data = $shopcar->where(['uid' => session('HomeUser')['id']])->find();
			$data = json_decode($data['shopcar'],true);
			$num = $data[$idInfo]['buyNum'];
			//判断商品数量是否超过商品库存
			if($num >= $goods_num) {
				$this->error('库存不足，目前该商品数量最多只能购买'.$goods_num.'件');
			} else {
				$data[$idInfo]['buyNum'] = $data[$idInfo]['buyNum'] + 1;
				$data = json_encode($data);
				$info = $shopcar->where(['uid' => session('HomeUser')['id']])->save(['shopcar'=>$data]);
				if($info) {
					$this->success('商品数量修改成功');
				} else {
					$this->error('商品数量修改失败');
				}
			}
		}
	}
}