<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 前台个人收藏控制器
 */
class CollectController extends CommonController {

    /**
     * 显示个人收藏页面
     * @return void
     */
    public function collect(){
        // 操作收藏商品类
        $collect = M('UserCollect');
        // 取出当前用户的收藏数据
        $data = $collect->where(['uid'=>$_SESSION['HomeUser']['id']])->select();
        $gid = array_column($data,'gid');

        $goods = D('Goods');
        // 判断有无id
        if(!empty($gid)){

        $map['id'] =['in',$gid];
        // 查询用户收藏商品总数
        $count = $goods->where($map)->count();
        // 分页类
        $Page = new \Think\Page($count,8);
        //显示分页按钮
        $page = $Page->show();
        // 获取收藏商品数据
        $arr = $goods->where($map)->limit($Page->firstRow,$Page->listRows)->getData();
        }
        if(IS_AJAX){
            $data = $arr;
            $data['page'] = $page;
            $this->ajaxReturn($data);
            exit;
        }
        //将收藏时间放进商品数据中
        foreach ($arr as $k => $v) {
            $arr[$k]['collectTime'] = $data[$k]['addtime'];
        }
        if(isset($count)){

            $this->assign('count',$count);
        }
        if(isset($page)){

            $this->assign('page',$page);
        }
        if(isset($arr)){

            $this->assign('data',$arr);
        }

        $this->display();

    }

    /**
     * ajax取消收藏商品
     * @param init $id 要删除的id
     * @return void
     */
    public function delCollect($id){
        if (IS_AJAX) {

            if (M('UserCollect')->delete($id)) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }

    }

    /**
     * ajax 添加收藏商品
     * @param init $boolean 
     * @return void
     */
    public function addCollect($uid,$gid){
        if (IS_AJAX) {
            $collect = M('UserCollect');
            $data['uid'] = $uid;
            $data['gid'] = $gid;
            $data['addtime'] = time();
            $res = $collect->add($data);
            if($res) {
                $info['id'] = $collect->where(['uid'=>$uid,'gid'=>$gid])->find()['id'];
                $info['status'] = '1';
                $info['msg'] = '设置成功';
                $this->ajaxReturn($info);
            } else {
                $info['status'] = '0';
                $info['msg'] = '收藏失败';
                $this->ajaxReturn($info);
            }
        }
    }

    /**
     * ajax删除收藏
     * @param  [type] $uid 用户id
     * @param  [type] $gid 商品id
     * @return boolean      
     */
    public function delCollect2($uid,$gid){

        if (IS_AJAX) {
            $collect = M('UserCollect');
            $id = $collect->where(['uid'=>$uid,'gid'=>$gid])->find()['id'];
            if (M('UserCollect')->delete($id)) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
}