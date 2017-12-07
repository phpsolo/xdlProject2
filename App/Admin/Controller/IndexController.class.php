<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 首页控制器
 */
class IndexController extends CommonController {
    /**
     * 显示主框架页面
     */
    public function index(){
        $this->display(); 
    }

    /**
     * 显示后台首页
     */
    public function x_index()
    {
        //操作订单表
        $order = M('Order');
        //操作商品留言表
        $assess = M('GoodsAssess');
        //操作商品表
        $goods = M('Goods');
        //操作用户表
        $loginInfo = M('LoginInfo');
        //得到订单总条数
        $orderTotal = $order->count();
        //得到留言总条数
        $assessTotal = $assess->count();
        //得到成交总金额
        $total = $order->where(['status' => 5])->sum('total');
        //得到待付款订单数
        $payOrderTotal = $order->where(['status' => 1])->count();
        //得到待发货订单数
        $sendOrderTotal = $order->where(['status' => 2])->count();
        //得到待收货订单数
        $getOrderTotal = $order->where(['status' => 3])->count();
        //得到已完成订单数
        $overOrderTotal = $order->where(['status' => 4])->count();
        //得到无效订单数
        $endOrderTotal = $order->where(['status' => 5])->count();
        //得到商品销售量前十得商品
        $topGoods = $goods->field('id,name,buynum')
                          ->order('buynum desc')
                          ->limit(10)
                          ->select();
        //得到用户登陆数据
        $loginInfo = $loginInfo->order('login_time desc')
                               ->limit(5)
                               ->field('username, login_time, role')
                               ->where(['role' => ['egt', 4]])
                               ->join('shop_user ON shop_login_info.uid = shop_user.id')
                               ->select();
        
        $this->assign([
            'orderTotal' => $orderTotal, 
            'assessTotal' => $assessTotal, 
            'total' => $total,
            'payOrderTotal' => $payOrderTotal,
            'sendOrderTotal' => $sendOrderTotal,
            'getOrderTotal' => $getOrderTotal,
            'overOrderTotal' => $overOrderTotal,
            'endOrderTotal' => $endOrderTotal,
            'topGoods' => $topGoods,
            'loginInfo' => $loginInfo,
            ]);
        $this->display();
    }
}