<?php
namespace Admin\Controller;
use Think\Controller;

/**
 *	后台登录控制器
 */
class LoginController extends Controller {
	/**
	 * 显示后台登录页面
	 */
	public function login(){
		if(IS_POST){
			//判断用户名是否为空
			if(I('username')==''){
				$this->error('请输入用户名');
			}
			 //再判断用户是否输入了密码
            if(I('password')==''){
                $this->error('请输入密码');
            }

            // 验证验证码
            $verify = new \Think\Verify();
            if(!$verify->check(I('validate'))){
                $this->error('验证码错误');
            }

            //查询用户信息，邮箱或者用户名登陆都可以查询出
            $user = M('User');
            $arr = $user->query("select * from shop_user where username='".I('username')."' or email = '".I('username')."'");
            $arr = $arr[0];
            //判断用户是否存在
            if(!$arr){
                $this->error('该用户不存在，请注册');
            }
            //再查询该用户是否被禁用
            if($arr['status'] === '2'){
                //该用户被禁用
                 $this->error('该用户被禁用，请付费解禁');
            }
            //查询该用户是否是管理员,角色号小于4则为普通用户不能进入后台
            if($arr['role'] < 4){
            	$this->error('只有管理员才能进入后台');
            }
            //进行密码验证
            $password = I('password');
            $hash = $arr['password'];
            if($arr){
            	//哈希验证
            	if(password_verify($password,$hash)){
            		//将用户信息存入session
            		session('AdminUser',$arr);
            		//1.根据当前用户ID获取当前用户的角色ID
            		$roleIds = M('User')->where(['id'=>$arr['id']])->getField('role',true);
            		//2.根据角色id查询节点ID
            		$nodeIds = M('RoleNode')->where(['r_id'=>['in',$roleIds]])->getField('n_id',true);
            		//3.根据节点ID查询出所有的节点
            		$nodeList = M('Node')->where(['id'=>['in',$nodeIds]])->getField('node',true);
            		//追加一个首页的权限
            		$nodeList[] = 'Index/index';
                    $nodeList[] = 'Index/x_index';
                    $nodeList[] = 'User/personal';
                    
            		//将角色权限信息存入session
            		session('nodeList',$nodeList);

            		//记录登录信息
            		recordCorrectTime($arr['id']);
                    //判断后台是否勾选了免登录
                    if(I('remember')!==''){
                        setcookie('user',I('username'),time()+7*24*3600,'/');
                        setcookie('pass',I('password'),time()+7*24*3600,'/');
                        setcookie(session_name(),session_id(),time()+7*24*3600,'/');
                    } else {
                        cookie('user',null);
                        cookie('pass',null);
                    }
            		$this->success('登录成功',__MODULE__);
            	} else {
            		$this->error('用户名或密码错误');
            	}
            }
		} else {
			$this->display();
		}
	}

	/**
	 * 该用户用于后台注销用户
	 */
	public function logout(){
		//清空session
        session('AdminUser',null);
        session('nodeList',null);
        $this->redirect('Login/login');
	}

	 /**
     * 该方法用于前台获取验证码
     */
    public function getVerify(){
        $config = array(
                'fontSize' => 15,
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
}