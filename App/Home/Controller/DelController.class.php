<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 该方法用于计划任务删除过期订单
 */
class DelController extends Controller{

	/**
     * 处理过期订单
     * @return void
     */
	public function index(){
	    $order = M('Order');
	    //获得当时时间
	    $nowtime = time();
	    // 取得所有用户过期订单，即状态码为1，当前时间大于过期时间
	    $map['status'] = 1;
	    $map['exptime'] = ['lt',$nowtime];
	    $arr = $order->where($map)->getField('oid',true);
	    $ids = join(',',$arr);
	    //循环遍历出该订单下的订单详情表
	    foreach ($arr as $k => $v) {
	    	//得到每一个订单所有商品id
	    	$detailIds = M('Detail')->where(['oid'=>$v])->getField('gid',true);
	    	$num = M('Detail')->where(['oid'=>$v])->getField('num',true);
	    	$attr = M('Detail')->where(['oid'=>$v])->getField('attr',true);
	    	foreach ($detailIds as $key => $value) {
	    		$backNum = $num[$key];
	    		
	    		//将商品的购买量回档
	    		M('Goods')->where(['id'=>$value])->setDec('buynum',$backNum);

	    		$id = M('GoodsInfo')->where(['gid'=>$value,'attr'=>$attr[$key]])->find()['id'];
	    		//将库存补回去
	    		M('GoodsInfo')->where(['id'=>$id])->setInc('stock',$backNum);
	    	}
	    	//把过期订单号对应的订单详情表删除
	    	M('Detail')->where(['oid'=>$v])->delete();
	    }
	    //最后把过期订单删除
	    $condition['oid'] = ['in',$ids];
	    $order->where($condition)->delete();
	}
}