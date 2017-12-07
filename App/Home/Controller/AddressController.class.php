<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 前台个人地址控制器
 */
class AddressController extends CommonController {

    /**
     * 显示个人地址管理页面
     */
    public function address(){
        //实例化自定义类
        $address = D('UserAddress');
        //获取session_id  
        $id = $_SESSION['HomeUser']['id'];
        // 查询用户有多少个地址
        $count = $address->where(['uid'=>$id])->count();
        // 分页类
        $Page = new \Think\Page($count,4);
        //显示分页按钮
        $page = $Page->show();
        // 查询用户的收货地址信息
        $arr = $address->where(['uid'=>$id])->limit($Page->firstRow,$Page->listRows)->select();
        if(IS_AJAX){
            $data = $arr;
            $data['page'] = $page;
            $this->ajaxReturn($data);
            exit;
        }

        //分配数据
        $this->assign('count',$count);
        $this->assign('page',$page);
        $this->assign('list', $arr);
        $this->display();
    }

    public function doAddress(){
        // 判断是否POST提交
        if(IS_POST){
            // 实例化类 
            $address = D('UserAddress');
            // 自动验证
            $add = [
                $_POST['province'],
                $_POST['city'],
                $_POST['area']
            ];
            if(!empty($_POST["address"])){
                if(@!preg_match('/^[\w -\x{4e00}-\x{9fa5}]{5,200}$/u',$_POST["address"])){
                    $this->error('请输入5位以上详细地址');
                }
            } else {
                $this->error('详细地址不能为空');
            }
            $add = join('-',$add);
            $_POST['address'] = trim($add.'-'.$_POST["address"],' ');
            unset($_POST['province']);
            unset($_POST['city']);
            unset($_POST['area']);
            @$vali = $address->create($_POST);
            if($vali){
                // 更新session
                $vali['uid'] = $_SESSION['HomeUser']['id'];
                // 添加数据
                $address->add($vali);
                //当用户第一次添加地址时，默认第一条为默认地址
                if($address->where(['uid'=>$vali['uid']])->count() == 1){
                    $id = $address->where(['uid'=>$vali['uid']])->find()['id'];
                    $save['status'] = 1;
                    $address->where(['id'=>$id])->save($save);
                }
                $this->success('添加成功','address');
            } else {
                // 如果添加失败 表示验证没有通过 输出错误提示信息     
                $this->error($address->getError());

            }
        } else {
            // 显示页面
            $this->display();
        }
    }

    /**
     * 显示和处理修改收货地址页面
     * @return [type] [description]
     */
    public function editAddress(){
        // 判断id是否为空
        if(empty(I('id'))){
            $this->error('非法访问');
        }
        // 获取id
        $id = I('id');
        // 实例化类
        $address = D('UserAddress');
        // 获取数据
        $arr = $address->where("`id`={$id}")->select();
        // 分配数据
        $this->assign('list', $arr);
        // 判断是否POSTtijiao 
        if(IS_POST){
            $address = D('UserAddress');
            if(!empty($_POST["address"])){
                if(@!preg_match('/^[\w -\x{4e00}-\x{9fa5}]{5,200}$/u',$_POST["address"])){
                    $this->error('请输入5位以上详细地址');
                }
            } else {
                $this->error('详细地址不能为空');
            }
            @$vali = $address->create($_POST,1);
            if($vali){
                // 接受POST数据
                $data=$_POST;
                $add = D('UserAddress');
                $res = $add->where("`id`={$id}")->save($data);
                // 判断用户是否有修改
                if(!$res){
                    $this->error('您什么都没改到');
                }
                $this->success('修改成功','address');
            } else {
                // 如果创建失败 表示验证没有通过 输出错误提示信息     
                $this->error($address->getError());
            }
        } else {
            // 显示页面
            $this->display();
        }
    }

    /**
     * ajax删除用户
     * @param init $id 要删除的id
     */
    public function ajaxDel($id){
        if (IS_AJAX) {
            //判断是否是默认地址
            $add = M('UserAddress');
            if($add->where(['id'=>$id])->find()['status'] == 1){
                $this->error('默认地址不能删除');
            }
            if ($add->delete($id)) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
    
    /**
     * ajax修改默认地址
     * @return array  data 
     */
    public function ajaxAddress(){
        if(IS_AJAX){
            $address = D('UserAddress');

            $arr = $address ->where(['uid'=>$_SESSION['HomeUser']['id'],'status'=>'1'])->select();

            $arr[0]['status'] = '0';

            $address->where(['id'=>$arr[0]['id']])->save($arr[0]);

            if($address->where(['id'=>$_POST['id']])->setField('status','1')){

                $data['status'] = '1';
                $data['msg'] = '设置成功';
                $this->ajaxReturn($data);
            } else{

                $data['status'] = '0';
                $data['msg'] = '设置失败';
                $this->ajaxReturn($data);
            }
        }
    }
}