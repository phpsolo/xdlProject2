<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 后台订单控制器
 */
class OrderController extends CommonController {
	/**
	 * 显示后台订单列表
	 */
	public function index(){
		$order = D('Order');
		//分页
		//处理搜索
		if( strlen(trim(I('search'))) > 0){
			$map['oid'] = I('search');
		}
		$count = $order->where($map)->count();	
		$Page = new \Think\Page($count,6);
		//显示分页按钮
		$page = $Page->show();
		$list = $order->where($map)->limit($Page->firstRow,$Page->listRows)->getData();
		//分配订单列表数据
		$this->assign('list',$list);
		//分配按钮数据
		$this->assign('page',$page);
		$this->display('order');
	}

	/**
	 * 查看后台订单详情页列表
	 */
	public function detail(){
		$detail = M('Detail');
		$list = $detail->where(['oid'=>I('oid')])->select();
		//遍历出商品名称
		$goods = M('Goods');
		foreach ($list as $k => $v) {
			$list[$k]['gid'] = $goods->where(['id'=>$v['gid']])->find()['name'];
			//查找订单信息
			$list[$k]['addtime'] = date('Y-m-d H:i:s',M('Order')->where(['oid'=>I('oid')])->find()['addtime']);
			$list[$k]['address']= M('Order')->where(['oid'=>I('oid')])->find()['address'];
			$list[$k]['phone']= M('Order')->where(['oid'=>I('oid')])->find()['phone'];
			$list[$k]['message']= M('Order')->where(['oid'=>I('oid')])->find()['message'];
			$list[$k]['status']= D('Order')->where(['oid'=>I('oid')])->getData()[0]['status'];
			$list[$k]['attr'] = explode(',',$v['attr']);
			//获取商品的属性值
			foreach ($list[$k]['attr'] as $value) {
				$only[$k] = explode(':',$value);
				$attrs[$k] .= $only[$k][1].'/';
			}
			//美观一下
			$list[$k]['attr'] = rtrim($attrs[$k],'/');
		}
		$this->assign('list',$list);
		$this->display();
	}

	/**
	 * 处理后台发货操作
	 */
	public function sendGoods(){
		if(IS_AJAX){
			$data['status'] = 3;
			$res = M('Order')->where(['oid'=>I('oid')])->save($data);
			if($res){
				$this->success('发货成功');
			} else {
				$this->error('发货失败');
			}
		}
	}
}
