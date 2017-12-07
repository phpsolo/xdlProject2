<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 前台账户安全控制器
 */
class SafeController extends CommonController {
    
    /**
     * 显示个人信息页面
     * @return void
     */
    public function safe(){
        // 查询当前登陆IP和上次登陆IP
        $user = M('LoginInfo');
        // 上次登陆
        $pre = $user->limit(1,1)->order('login_time desc')->where(['pass_wrong_status'=>1])->select();
        $pre = $pre[0];
        //分配数据
        $this->assign('pre',$pre);
        //显示页面
        $this->display();
    }

    /**
     * 显示修改和处理密码页面
     * @return void
     */
    public function ModifyPass(){
        // 判断是否POST提交
        if(IS_POST){
            if(empty($_POST['password2'])){
                $this->error('密码不能为空');
            }
            // 正则判断密码
            if(!preg_match('/^\S{6,18}$/',I('password2'))){
                $this->error('密码请输入6-18位');

            }
            //获取id
            $id = $_SESSION['HomeUser']['id'];
            $user = M('User');
            // 获取数据
            $arr = $user->where("`id` = {$id}")->select(); 
            $password = I('password'); //用户输入的旧密码
            $hash = $arr[0]['password']; //查询出的哈希密码  
            // 比较用户输入的密码是否正确
            if(!password_verify($password,$hash)){
                $this->error('密码错误');
            } else {
                $pass = M('User');
                //对新密码进行加密
                $data['password'] = password_hash($_POST['password2'], PASSWORD_DEFAULT);
                //修改数据库数据
                $pass->where("`id`={$id}")->save($data);
                $this->success('修改成功!!');
            }
        } else {
            //显示页面
            $this->display();
        }
        
    }

    /**
     * 修改邮箱
     * @return void
     */
    public function securityMail(){
        // 判断是否POST提交
        if(IS_POST){
            // 正则判断邮箱
            if(!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',I('email'))){
                $this->error('请输入正确的邮箱格式');
            }
            // 实例化类
            $user = M('User');
            // 获取邮箱数据
            $email=I('email');
            // 查数据库用户输入的邮箱是否存在
            $res = $user->where("`email`='{$email}'")->select();
            if($res){
                $this->error('该邮箱已存在');
            }
            // 判断密码是否为空
            if(empty($_POST['password'])){
                $this->error('密码不能为空');
            }
            //获取id
            $id = $_SESSION['HomeUser']['id'];
            //获取数据
            $arr = $user->where("`id` = {$id}")->select(); 
            $password = I('password');//用户输入的密码
            $hash = $arr[0]['password'];//查询出的哈希密码
            // 验证用户输入的密码  
            if(!password_verify($password,$hash)){
                $this->error('密码错误');
            } else {
                $data['email'] = $email;
                // 创建用于改邮箱的标识码
                $data['mark'] = str_shuffle(md5(uniqid()));
                //设置该标识的过期时间
                $data['mark_exptime'] = time() + 60*30;
                //并且把新邮箱的激活状态换成未激活
                $data['email_status'] = 0;
                // 修改user表数据,如果成功则发邮件给用户的新邮箱，点击url确认后修改成功
                if($user->where(['id'=>$id])->save($data)){
                    $res = sendMail($data['email'],'傻逼','重新设定邮箱。<br/>请在30min内点击该链接。<br/><a href="http://192.168.32.10/D2/Home/User/newEmail/mark/'.$data['mark'].'">点击激活</a>');
                    if($res){
                        $this->success('修改成功！！！请在30分钟内进入新邮箱重新激活',U('Safe/safe'));
                    }
                } else {
                    $this->error('修改失败');
                }

            }
        } else {
            // 获取id
            $id = $_SESSION['HomeUser']['id'];
            $mail = M('User');
            // 获取数据在前台遍历
            $arr = $mail->where("`id`={$id}")->select();
            // 分配数据
            $this->assign('list', $arr);
            //显示页面
            $this->display();
        }
    }

    /**
     * 修改个人信息
     * @return void
     */
    public function personalMessage(){
        // 获取id
        $id = $_SESSION['HomeUser']['id'];
        $user = M('User');
        // 获取数据在前台遍历
        $arr = $user->where("`id`={$id}")->select();
        // 分配数据
        $this->assign('list', $arr);
        // 判断是否post提交
        if(IS_POST){
            if (!empty($_POST['name'])){
                // 判断用是否为空和正则验证
                if(!preg_match('/^[\w\x{4e00}-\x{9fa5}]{2,6}$/u', $_POST['name'])){
                    $this->error('请合法姓名');
                }
                $data['realname'] = $_POST['name'];
            }
            // 判断年龄是否为空和正则验证
            if (!empty($_POST['age'])){
                if($_POST['age']<1||$_POST['age']>150){
                    $this->error('请输入正确年龄');
                }
                $data['age']=$_POST['age'];
            }
            // 判断手机是否为空和正则验证
            if (!empty($_POST['phone'])){
                if(!preg_match('/^(((13|14|15|18|17)\d{9}))$/', $_POST['phone'])){
                    $this->error('请合法手机号');
                }
                $data['phone']=$_POST['phone'];
            }
            $data['sex']=$_POST['sex'];
            // 修改user表数据
            $user->where("`id`={$id}")->save($data);
            $this->success('修改成功！！！');
        
        } else {
            // 显示页面
           $this->display(); 
        }
        
    }

    /**
     * 用户查询当前用户的积分
     * @return void
     */
    public function MyScore(){
        //查询当前用户的积分
        $uid = $_SESSION['HomeUser']['id'];
        $score = M('User')->where(['id'=>$uid])->find()['score'];
        $this->assign('score',$score);
        $this->display();
    }
}