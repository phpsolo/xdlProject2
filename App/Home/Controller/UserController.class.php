<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 前台用户控制器
 */
class UserController extends Controller {
    /**
     * 该方法用于前台获取验证码
     * @return void
     */
    public function getVerify(){
        $config = array(
                'fontSize' => 16,
                'useCurve' => false,
                'useNoise' => false,
                'fontttf'   =>  '4.ttf', 
                'length' => 4
        );
        //实例化验证码类
        $verify = new \Think\Verify($config);
        //输出验证码,底层自动存入session中
        $verify->entry();
    }

    /**
     * *该方法用于验证激活邮箱/
     * @return void
     */
    public function verify(){
        $verify = I('verify');
        $nowtime = time();
        $user = M('User');
        $arr = $user->where(['email_status'=>0,'token'=>$verify])->find();
        if($arr){
            //判断激活有效期是否过期
            if($nowtime > $arr['token_exptime']){
                //如果激活有效果过期了，就删除该用户名，需要重新填写用户信息
                $user->delete($arr['id']);
                $this->error('你的激活码有效期已过,请重新注册发送激活邮件',U('Login/register'));
            } else {
                //给激活码激活
                $date['email_status'] = 1; 
                $user->where(['id'=>$arr['id']])->save($date);
                $this->success('恭喜你激活成功,现在您可以登录了',U('Index/index'));
            }
        } else {
            $this->error('该链接已失效',U('Login/register'));
        }
    }

    /**
     * *该方法用于发送找回密码的邮件/
     * @return void
     */
    public function back(){
        if(IS_POST){
            $user = M('User');
            $arr = $user->where(['username'=>I('username'),'email'=>I('email')])->find();
            //创建用于找回密码的标识码
            $mark['mark'] = str_shuffle(md5(uniqid()));
            //设置该标识的过期时间
            $mark['mark_exptime'] = time() + 60*30;
            //如果查出数据就发邮件给用户，点击url确认后重新设定密码
            if($user->where(['id'=>$arr['id']])->save($mark)){
                $res = sendMail($arr['email'],'傻逼','重新设定密码。<br/>请在30min内点击链接重新您账号的密码。<br/><a href="http://192.168.32.10/D2/Home/User/backPass/mark/'.$mark['mark'].'">http://192.168.32.10/D2/Home/User/backPass/mark/'.$mark['mark'].'</a>');
                if($res){
                    $this->success('邮箱已发送，请及时查看您的邮箱，点击地址重新设置密码',U('Login/login'));
                }
            } else {
                $this->error('该用户不存在');
            }

        } else {
            $this->display();
        }
    }

    /*
     * *该方法用于找回密码
     * @return void
    */
    public function backPass(){
        $mark = I('mark');
        $nowtime = time();
        $user = M('User');
        $arr = $user->where(['mark'=>$mark])->find();
        if($arr){
            //判断该用户的修改密码标识码是否过期
            if($nowtime > $arr['mark_exptime']){
                $this->error('你的修改密码有效期已过,请重新发送邮件',U('User/back'));
            } else {
                $this->success('跳转页面后，设置新的密码',U('Home/User/newPass/mark/'.$arr['mark']));
            }
        }
    }

    /**
     * 该方法用于重新设定邮箱
     * @return void
     */
    public function newEmail(){
        $nowtime = time();
        $res = M('User')->where(['mark'=>I('mark')])->find();
        //查找用户
        if($res){
            //判断该用户的修改邮箱标识码是否过期
            if($nowtime > $res['mark_exptime']){
                 $this->error('你的修改邮箱有效期已过,请重新发送邮件',U('Safe/safe'));
            } else {
                //修改邮箱激活状态
                $data['email_status'] = 1;
                
                M('User')->where(['id'=>$res['id']])->save($data);
                $this->success('修改邮箱成功',U('Safe/safe'));
            }
        } else {
            $this->error('无效链接');
        }
    }

    /**
     * 该方法用于重新设定密码
     * @return void
     */
    public function newPass(){
        $user = D('User');
        if(IS_POST){
            //自动验证密码
            $vali = $user->create();
            if($vali){
                $res = $user->where(['mark'=>I('mark')])->save($vali);
                if($res){
                    $this->success('设置新密码成功',U('Login/login'));
                } else {
                    $this->error('设置密码失败');
                }
            } else {
                $this->error($user->getError());
            }
        } else if(!$user->where(['mark'=>I('mark')])->find()){
            //防止用户非法访问    
            $this->error('非法访问,不存在该用户');
        } else {
            $mark = I('mark');
            //数据分配，将区分用户的标识码分配给模板，通过模板POST提交上去
            $this->assign('mark',$mark);
            $this->display();
        }
    }

    /**
     * 该方法用于ajax检验前台用户名是否存在
     * @return void
     */
    public function checkName(){
        if(!preg_match('/^\w{6,12}$/',I('username'))){
            $data['status'] = 2;
            $data['msg'] = '用户名请输入6-12位的数字字母下划线';
            $this->ajaxReturn($data);
        } 
        $user = M('User');
        
        $arr = $user->where(['username'=>I('username')])->find();
        //如果查询出数据，则用户名存在，不可以注册
        if($arr){
            $data['status'] = 0;
            $data['msg'] = '用户名已经存在';
        } else {
            $data['status'] = 1;
            $data['msg'] = '该用户名可以注册';
        }
        //用于ajax返回
        $this->ajaxReturn($data);
    }

    /**
     * 该方法用于ajax检验前台密码是否一致
     * @return void
     */
    public function checkPass(){
        //正则判断密码是否是6-18位
        if(!preg_match('/^\S{6,18}$/',I('password2'))){
            $data['status'] = 0;
            $data['msg'] = '密码请输入6-18位';
            $this->ajaxReturn($data);
        } else if(I('password')!==I('password2')){
            $data['status'] = 1;
            $data['msg'] = '密码不一致,请重新输入';
            $this->ajaxReturn($data);
        } else {
            $data['status'] = 2;
            $data['msg'] = '验证通过';
            $this->ajaxReturn($data);
        }
    }

    /**
     * 该方法用于ajax验证前台邮箱
     * @return void
     */
    public function checkEmail(){

        //检验邮箱是否存在
        $email = M('User');
        $arr = $email->where(['email'=>I('email')])->find();
        //正则判断邮箱格式
        if(!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',I('email'))){
            $data['status'] = 0;
            $data['msg'] = '请输入正确的邮箱格式';
            $this->ajaxReturn($data);
        } else if($arr){
            $data['status'] = 2;
            $data['msg'] = '该邮箱已存在';
            $this->ajaxReturn($data);
        } else {
            $data['status'] = 1;
            $data['msg'] = '格式正确';
            $this->ajaxReturn($data);
        }

    }
}

