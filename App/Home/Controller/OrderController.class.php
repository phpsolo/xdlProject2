<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 前台会员中心个人订单控制器
 */
class OrderController extends CommonController {
    /**
     * 显示会员中心个人订单页面
     */
    public function order(){
        // 判断是否登录
        if(empty($_SESSION)){
            $this->redirect('请您先登录','User/login');
        }
        // 获取id
        $id= $_SESSION['HomeUser']['id'];
        // 分页类
        $count = M('Order')->where(['uid'=>$id])->count();
        $Page = new \Think\Page($count,2);
        //显示分页按钮
        $page = $Page->show();
        $arr = D('Order')->limit($Page->firstRow,$Page->listRows)->where(['uid'=>$id])->getData();
        foreach ($arr as $k => $v) {
            $detail[] = M('Detail')->where(['oid'=>$v['oid']])->select();
        }
        if(IS_AJAX){
            $data = $arr;
            $data['page'] = $page;
            $this->ajaxReturn([$data,$detail]);
            exit;
        }
        //待付款个数
        $one = M('Order')->where(['uid'=>$id,'status'=>1])->count();
        //待发货个数
        $two = M('Order')->where(['uid'=>$id,'status'=>2])->count();
        //待收货个数
        $three = M('Order')->where(['uid'=>$id,'status'=>3])->count();
        //交易完成个数
        $four = M('Order')->where(['uid'=>$id,'status'=>5])->count();
        //分配订单详情数据
        if(isset($detail)){
            $this->assign('detail',$detail);
        }
        // 分配数据
        $this->assign('list', $arr);
        $this->assign('count',$count);
        $this->assign('page',$page);
        $this->assign(['one'=>$one,'two'=>$two,'three'=>$three,'four'=>$four]);
        //显示页面
        $this->display();
    }

    /**
     * 显示和处理上传页面
     */
    public function upload(){
        
        if(!empty($_POST)){
            // 实例化上传类 
            $upload = new \Think\Upload();
             
            // 设置附件上传大小 
            $upload->maxSize   =     314572833 ;   
            // 设置附件上传类型 
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg'); 
            // 设置附件上传目录
            $upload->rootPath  =      './Public/'; 
            $upload->savePath  =      'Uploads/';
            $upload->autoSub = false;
            // 上传文件      
            
            $info = $upload->upload();
            // var_dump($upload);exit;
            if(!$info) {
                // 上传错误提示错误信息        
                $this->error($upload->getError());    
            } else {
                // 上传成功 
                foreach($info as $file){
                    $data['photo']=$file['savename'];
                }
                // 获取id
                $id=$_SESSION['HomeUser']['id'];
                //实例化类 
                $user=M('User');
                //获取数据
                $user->where("`id`={$id}")->save($data);
                $info = M('User')->where("`id`={$id}")->select();
                // 更新SESSION数据
                $_SESSION['HomeUser'] = $info[0];
                // var_dump($_SESSION);exit;
                $this->success('修改成功',U('Order/order'));    
            }
        } else {
            $this->display();
        }
    }

    /**
     * 该方法用于处理发送过来的商品信息
     * @param string $ids 订单中所有商品id
     */
    public function expla($ids){
        //分割每一样商品
        $set = explode(',',$ids);
        //分别将每一样商品的，商品和信息分割
        //通过商品属性和id查找属性
        //从购物车中查找购买数量
        //当前用户id
        $uid = $_SESSION['HomeUser']['id'];
        $json = M('Shopcar')->field('shopcar')->where(['uid'=>$uid])->find()['shopcar'];
        $json = json_decode($json,true);
        foreach ($set as $k => $v) {   
            if(!array_key_exists($v,$json)){
                $this->redirect('Index/index');
            }
        }
        //处理数据
        foreach($json as $k => $v){
            $num = explode('_',$k)[0];
            $nums[$num] = $json[$k]; 
        }
        foreach ($set as $k => $v) {
            $goodinfo[] = explode('_',$v);
            $arr[] = M('GoodsInfo')->where(['id'=>$goodinfo[$k][1]])->find();
            $arr[$k]['name'] = M('Goods')->where(['id'=>$goodinfo[$k][0]])->find()['name'];
            $arr[$k]['pic'] = M('Goods')->where(['id'=>$goodinfo[$k][0]])->find()['pic'];
        }
        foreach($nums as $key=>$value){
            foreach ($arr as $k => $v) {
                if($key == $v['gid']){
                    $arr[$k]['num'] = $value['buyNum'];
                }
            }
        }
        return $arr;
    }

    /**
     * 显示订单确认页
     */
    public function orderConfirm(){
        $arr = $this->expla(I('ids'));

        $uid = $_SESSION['HomeUser']['id'];
        //当前用户状态
        $role = M('User')->where(['id'=>$uid])->find()['role'];
        //查出当前用户的收货地址
        $add = M('UserAddress');
        $address = $add->where(['uid'=>$uid,'status'=>0])->select();
        $default = $add->where(['uid'=>$uid,'status'=>1])->select();

        //分配默认地址
        $this->assign('default',$default);
        $this->assign('address',$address);
        //分配数据
        $this->assign('arr',$arr);
        $this->assign('role',$role);
        $this->display();
    }

    /**
     * 显示订单完成页
     */
    public function orderSuccess(){

        //判断购物车传递过来的商品是否已下架
        $arr = $this->expla($_POST['ids']);
        foreach ($arr as $k => $v) {
            $status = M('Goods')->where(['id'=>$v['gid']])->find()['status'];
            if($status == 3){
                $this->error('您的购物车中有商品已下架,请重新选购',U('Index/index'));
            }
        }
        $arrs = $arr;
        //准备当前时间
        $_POST['addtime'] = time();
        //当前用户
        $_POST['uid'] = $_SESSION['HomeUser']['id'];

        //查询当前用户角色
        $role = M('User')->where(['id'=>$_POST['uid']])->find()['role'];
        //判断用户是否是会员
        if($role == '2'){
            //VIP会员
            $_POST['total'] = $_POST['total']*0.95;
        } else if($role == '3'){
            //钻石会员
            $_POST['total'] = $_POST['total']*0.9;
        }
        //生成唯一订单流水号
        $_POST['oid'] = orderNum();

        //订单过期时间
        $_POST['exptime'] = time()+60*24*60;
        //得到订单详情的所有信息
        $new['ids'] = $_POST['ids'];
        //将订单信息插入数据库的同时
        if($_POST){
            //赋值给data用于拼接
            $data = $_POST;
            // 拼接数据，用于传入数据库
            foreach ($arr as $k => $v) {
                $arr[$k]['oid'] = $data['oid'];
                unset($arr[$k]['id']);
                //先判断库存，当两个人同时买一样商品，总量超出库存，但另一个人没有下单
                if($v['stock'] < $v['num']){
                    $this->error('库存不足，请重新选购');
                }
            }
            
            //存进redis
            $redis = new \Redis();
            $redis->connect('localhost', 6379);
            $flag = true;
            if(!$redis->hMSet('hash:order:uid:'.session('HomeUser')['id'].':oid:'.$_POST['oid'], $_POST)) $flag = false;
            if(!$redis->lPush('list:addtomysql', 'hash:order:uid:'.session('HomeUser')['id'].':oid:'.$_POST['oid'])) $flag = false;
            foreach ($arr as $value) {
                $id = $redis->incr('string:detail:id');
                if($redis->hMSet('hash:detail:id:'.$id, $value) && $flag)
                {
                    $tmp[] = 'hash:detail:id:'.$id;
                    M('GoodsInfo')->where(['gid' => $value['gid'], 'attr'=>$value['attr']])->setDec('stock',$value['num']);
                } else {
                    $flag = false;
                }
            }
            $redis->hMSet('hash:oid:'.$_POST['oid'].':did', $tmp);

            $orderName = $redis->rPop('list:addtomysql');
            $arr = $redis->hGetAll($orderName);
            $orderInfoId = $redis->hGetAll('hash:oid:'.$arr['oid'].':did');
            foreach ($orderInfoId as $value) {
                $tmp_detail[] = $redis->hGetAll($value);
            }

            $order = D('Order');
            //开启事务
            $order->startTrans();
            if($order->add($arr) && $flag){
                //准备标记
                $flag = true;
                // 当插入订单表成功时，插入到订单详情表中
                foreach ($tmp_detail as $v) {
                    if(!M('Detail')->add($v)){
                        $flag = false;
                    }
                }
                //当标记为true时，说明插入订单详情表成功,进行更新购物车信息操作
                if($flag == true){
                    $ids = $arr['ids'];
                    $ids = explode(',',$ids);
                    $news = json_decode(M('Shopcar')->where(['uid'=>$arr['uid']])->find()['shopcar'],true);
                    foreach ($ids as $v) {
                        //更新数据库中购物车信息
                        unset($news[$v]);
                    }
                    if(empty($news)){
                        //当更新后的购物车数据为空时，删除该整条数据
                        $res = M('Shopcar')->where(['uid'=>$arr['uid']])->delete();
                    } else {
                        //当购物车数据还有数据时，重新存入数据库
                        $news = json_encode($news);
                        $res = M('Shopcar')->where(['uid'=>$arr['uid']])->save(['shopcar'=>$news]);
                    }
                    if(!$res){
                        $flag = false;
                    }
                }
                //如果标记为false时,说明插入订单详情页有错误，则事务回滚
                if($flag == false){
                    $redis->rPush('list:addtomysql', $orderName);
                    $order->rollback();
                    $this->error('提交订单失败');
                } else {
                    $redis->del($orderName, 'hash:oid:'.$arr['oid'].':did');
                    foreach ($orderInfoId as $value) {
                        $redis->del($value);
                    }
                    //成功则提交事务
                    $order->commit();
                    //当提交订单成功时,给相应的商品增加购买量
                    foreach ($arrs as $k => $v) {
                        $num = M('Goods')->where(['id'=>$v['gid']])->find()['buynum'];
                        $add['buynum'] = $num + $v['num'];
                        M('Goods')->where(['id'=>$v['gid']])->save($add);
                    }
 
                    $this->assign('info',$_POST);
                    $this->display(); 
                }
            } else {
                $order->rollback();
                $this->error('提交订单失败');
            }

            // $order = D('Order');
            // //开启事务
            // $order->startTrans();
            // if($order->add($_POST)){
            //     //准备标记
            //     $flag = true;
            //     // 当插入订单表成功时，插入到订单详情表中
            //     foreach ($arr as $k => $v) {
            //         //插入订单表成功时，减少响应的库存
            //         $id = M('GoodsInfo')->where(['gid'=>$v['gid'],'attr'=>$v['attr']])->find()['id'];

            //         if(!M('Detail')->add($v)){
            //             $flag = false;
            //         }

            //         if($flag == true){
            //             //减库存
            //             M('GoodsInfo')->where(['id'=>$id])->setDec('stock',$v['num']);
            //         }
            //     }
            //     //当标记为true时，说明插入订单详情表成功,进行更新购物车信息操作
            //     if($flag == true){
            //         $ids = $new['ids'];
            //         $ids = explode(',',$ids);
            //         $news = json_decode(M('Shopcar')->where(['uid'=>$_POST['uid']])->find()['shopcar'],true);
            //         foreach ($ids as $v) {
            //             //更新数据库中购物车信息
            //             unset($news[$v]);
            //         }
            //         if(empty($news)){
            //             //当更新后的购物车数据为空时，删除该整条数据
            //             $res = M('Shopcar')->where(['uid'=>$_POST['uid']])->delete();
            //         } else {
            //             //当购物车数据还有数据时，重新存入数据库
            //             $news = json_encode($news);
            //             $res = M('Shopcar')->where(['uid'=>$_POST['uid']])->save(['shopcar'=>$news]);
            //         }
            //         if(!$res){
            //             $flag = false;
            //         }
            //     }
            //     //如果标记为false时,说明插入订单详情页有错误，则事务回滚
            //     if($flag == false){
            //         $order->rollback();
            //     } else {
            //         //成功则提交事务
            //         $order->commit();
            //     }
            //     $this->assign('info',$_POST);
            //     $this->display();
            // } else {
            //     $order->rollback();
            //     $this->error('提交订单失败');
            // }
        }
    }

    /**
     * ajax获取前台订单数据
     */
    public function getOrder(){
        $order = D('Order');
        $uid = $_SESSION['HomeUser']['id'];
        //获得订单信息
        //默认获取全部订单信息
        if(I('status')=='all'){

            $count = $order->where(['uid'=>$uid])->count();

            $Page = new \Think\Page($count,2);
            //显示分页按钮
            $page = $Page->show();
            $arr = $order->limit($Page->firstRow,$Page->listRows)->where(['uid'=>$uid])->getData();
            foreach ($arr as $k => $v) {
                $detail[] = M('Detail')->where(['oid'=>$v['oid']])->select();
            }
            $arr['page'] = $page;
            $obj = [$arr,$detail];
            $this->ajaxReturn($obj);
        }

        $count = $order->where(['uid'=>$uid,'status'=>I('status')])->count();
        $Page = new \Think\Page($count,2);
        //显示分页按钮
        $page = $Page->show();
        //选项卡获取单一状态
        $arr = $order->limit($Page->firstRow,$Page->listRows)->where(['uid'=>$uid,'status'=>I('status')])->getData();
        //获得订单详情信息
        foreach ($arr as $k => $v) {
            $detail[] = M('Detail')->where(['oid'=>$v['oid']])->select();
        }
        $arr['page'] = $page;
        $obj = [$arr,$detail];
        $this->ajaxReturn($obj);
        
    }

    /**
     * 用于处理成功支付的订单
     */
    public function paySuccess(){
        if(I('mark') != true){
            $this->error('支付失败');
        }
        //将该订单的状态变为待发货
        $data['status'] = 2;
        M('Order')->where(['oid'=>I('oid')])->save($data);
        //获取该用户id
        $uid = $_SESSION['HomeUser']['id'];
        //原积分
        $score = M('User')->where(['id'=>$uid])->find()['score'];
        //将该订单的用户积分加上
        $user['score'] = M('Order')->where(['oid'=>I('oid')])->find()['getscore'] + $score;
        M('User')->where(['id'=>$uid])->save($user);
        //统计用户积分，判断用户是否能够升级,管理员不能升级
        $role = $_SESSION['HomeUser']['role'];
        if($role < 4){
            //用户才能用积分来升级
            //判断当前用户积分总数
            $score = M('User')->where(['id'=>$uid])->find()['score'];
            if($score < 50000 && $score > 10000){
                $data['role'] = 2;
                M('User')->where(['id'=>$uid])->save($data);
            } elseif($score > 50000){
                $data['role'] = 3;
                M('User')->where(['id'=>$uid])->save($data);
            }
        }
        $this->success('恭喜您,支付成功',U('Order/order'));
    }

    /**
     * 该方法用于前台个人中心支付
     */
    public function payHome(){
        if(IS_AJAX){
            if(I('mark') != true){
                $this->error('支付失败');
            }
            //将该订单的状态变为待发货
            if(I('status') == 2){
                $data['status'] = 2;
                M('Order')->where(['oid'=>I('oid')])->save($data);
                //将该订单的用户积分加上
                
                //获取该用户id
                $uid = $_SESSION['HomeUser']['id'];
                //原积分
                $score = M('User')->where(['id'=>$uid])->find()['score'];
                $user['score'] = M('Order')->where(['oid'=>I('oid')])->find()['getscore'] + $score;
                M('User')->where(['id'=>$uid])->save($user);

                //统计用户积分，判断用户是否能够升级,管理员不能升级
                $role = $_SESSION['HomeUser']['role'];
                if($role < 4){
                    //用户才能用积分来升级
                    //判断当前用户积分总数
                    $score = M('User')->where(['id'=>$uid])->find()['score'];
                    if($score < 50000 && $score > 10000){
                        $data['role'] = 2;
                        M('User')->where(['id'=>$uid])->save($data);
                    } elseif($score > 50000){
                        $data['role'] = 3;
                        M('User')->where(['id'=>$uid])->save($data);
                    }
                }
                $this->success('支付成功');
            } else {
                $data['status'] = 5;
                M('Order')->where(['oid'=>I('oid')])->save($data);
                $this->success('确认成功');
            }
        }
    }

    /**
     * 该方法用于订单确认页添加用户地址
     */
    public function newAddress(){
        //查询该用户
        $id = $_SESSION['HomeUser']['id'];
        //查询该用户下的地址，如果没有地址，则默认第一条地址为默认地址
        $add = M('UserAddress');
        $count = $add->where(['uid'=>$id])->count();
        if($count){
            $_GET['uid'] = $id;
            //进入这区间这说明已存在默认地址
            if($add->add($_GET)){
                $this->success('添加地址成功');
            } else {
                $this->error('添加地址失败');
            }
                
        } else {
            //进入这区间这说明第一次添加地址
            $_GET['status'] = 1;
            $_GET['uid'] = $id;
            if($add->add($_GET)){
                $this->success('添加地址成功');
            } else {
                $this->error('添加地址失败');
            }
        }
        
    }

}