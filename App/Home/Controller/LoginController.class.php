<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 前台登陆注册控制器
 */
class LoginController extends Controller {
    /**
     * 该页面用来显示登录页面并处理
     * @return void
     */
    public function login(){
    	if(IS_POST){
            $user = M('User');
            //判断用户是否输入了用户名
            if(I('username')==''){
                $this->error('请输入用户名');
            }
            
            //再判断用户是否输入了密码
            if(I('password')==''){
                $this->error('请输入密码');
            }
            //如果错误次数达到2次以上，就要验证验证码
            if($_SESSION['vali_wrong_time'] >=2){
                $verify = new \Think\Verify();
                //判断验证码
                if(!$verify->check(I('validate'))){
                    $this->error('验证码错误');
                }
            }


            //查询用户信息，邮箱或者用户名登陆都可以查询出
            $arr = $user->query("select * from shop_user where username='".I('username')."' or email = '".I('username')."'");

            //判断用户是否存在
            if(!$arr){
                $this->error('该用户不存在，请注册');
            }
            $arr = $arr[0];
            //判断该用户是否已经通过邮箱激活
            if($arr['email_status'] == 0){
                $this->error('请通过邮箱验证激活您的账号');
            }

            //再查询该用户是否被禁用
            if($arr['status'] === '2'){
                //该用户被禁用
                 $this->error('该用户被禁用，请付费解禁');
            }

            //进行密码验证
            $password = I('password');//用户输入的密码
            $hash = $arr['password'];//查询出的哈希密码
            
            //检测用户最近30分钟密码错误次数
            $res = checkPassWrongTime($arr['id']);
            //若错误次数超过限制次数，则锁定30分钟
            if($res === false) {
                //记录密码错误次数
                recordPassWrongTime($arr['id']);
                $this->error('错误次数过多，为了保护账户安全,系统已经将您账户锁定30min');
            }

            if($arr){
                //哈希验证
                if(password_verify($password,$hash)){
                    $_SESSION['HomeUser'] = $arr;
                    session('vali_wrong_time',null);
                    //判断后台是否勾选了免登录
                    if(I('remember')!==''){
                        setcookie('username',I('username'),time()+7*24*3600,'/');
                        setcookie('password',I('password'),time()+7*24*3600,'/');
                        setcookie(session_name(),session_id(),time()+7*24*3600,'/');
                    } else {
                        cookie('username',null);
                        cookie('password',null);
                    }
                    recordTime($arr['id']);

                    //合并购物车redis的缓存到数据库
                    $redis = new \Redis;
                    $redis->connect('127.0.0.1', 6379);
                    //根据session_id得到缓存在redis数据的键
                    $key = 'cart:ids:set:'.session_id();
                    //根据键取集合中的商品id
                    $idArr = $redis->zRange($key, 0, -1);
                    $redis->delete($key);
                    //判断redis是否有购物车的缓存数据
                    if($idArr) {
                        //根据商品id和session_id得到redis的数据
                        for ($i = 0; $i<count($idArr); $i++) {
                            $k = 'cart:datas:'.session_id().':'.$idArr[$i];
                            //获取商品和属性的拼接字符串
                            $gid_infoId = $idArr[$i];
                            //将商品对应的购买量取出遍历存进数组
                            $list[$gid_infoId] = $redis->hGetAll($k);
                            $redis->delete($k);                           
                        }
                        //判断数据库有没有数据
                        $shopcar = M('shopcar');
                        //根据用户id查询购物车的数据
                        $info = $shopcar->where(['uid'=>session('HomeUser')['id']])->find();
                        if(!$info) {
                            //数据库中没有该用户的购物车信息，将redis的数据直接插入到数据库中
                            $list = json_encode($list);
                            $data = ['uid'=>session('HomeUser')['id'],'shopcar'=>$list];
                            $shopcar->add($data);
                        } else {
                            $data = json_decode($info['shopcar'],true);
                            $data = array_merge($data,$list);
                            $data = json_encode($data);
                            $shopcar->where(['uid'=>session('HomeUser')['id']])->save(['shopcar'=>$data]);                            
                        }
                    }
                    //登录后跳回原来的地址             
                    if(!I('url')==''){
                        $this->success('登陆成功',I('url'));
                    } else {
                        $this->success('登陆成功',U('Index/index'));
                    }
                } else {
                    cookie('username',null);
                    cookie('password',null);
                    //记录密码错误次数
                    recordPassWrongTime($arr['id']);
                    //记录错误次数，用于显示验证码
                    $_SESSION['vali_wrong_time'] += 1; 
                    $this->error('密码错误');
                }
            }
    	} else {
    		$this->display();
    	}
    }

     /**
     * 该页面用来显示注册页面并处理
     * @return void
     */
    public function register(){
        if(IS_POST){
            $user = D('User');
            if(!I('password')){
                $this->error('密码不能为空');
            }
            //创建用于邮箱激活的32位激活码
            $_POST['token'] = str_shuffle(md5(I('password')));
            //设定该激活码的过期时间
            $_POST['token_exptime'] = time() + 60*30;
            $vali = $user->create();
            if($vali){
                //如果验证成功，就将数据插入数据库
                if($user->add($vail)){
                        $res = sendMail(I('email'),'傻逼','感谢您在我站注册了新帐号。<br/>请在30min内点击链接激活您的账号。<br/><a href="http://192.168.32.10/D2/Home/User/verify/verify/'.$_POST['token'].'">http://192.168.32.10/D2/Home/User/verify.html?verify="'.$_POST['token'].'"</a>');
                        if($res){
                            $this->success('恭喜你,注册成功<br/>请登录到您的邮箱及时激活您的账号',U('Index/index'));
                        } else {
                            $this->error('注册失败');
                        }
                } else {
                    $this->error('注册失败');
                }
            } else {
                $this->error($user->getError());
            }
        } else {
            $this->display();
        }
    }

    /**
     * 该方法用于前台退出登录账号
     * @return void
     */
    public function logout(){
        //清空session
        session('HomeUser',null);
        $this->redirect('login');
    }



}