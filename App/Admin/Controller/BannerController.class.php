<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 轮播图控制器
 */
class BannerController extends Controller{
	/**
	 * 显示轮播列表页
	 * @return void
	 */
	public function index()
	{
		$banner = D('Banner');
		$total = $banner->count();
		$page = new \Think\Page($total, C('MY_ADMIN_PAGE'));
		$banner = $banner->limit($page->firstRow, $page->listRows)->getData();
		$btns = $page->show();
		// dump($banner);
		$this->assign(['data' => $banner, 'btns' => $btns]);
		$this->display('banner');
	}

	/**
	 * 显示并处理轮播图添加
	 * @return void
	 */
	public function add()
	{
		if(IS_POST)
		{
			$banner = D('Banner');
			$data = $banner->create();
			if(!$data) $this->error($banner->getError());
			//判断是否上传图片
			if (empty($_FILES['pic']['name']))
			{
				$this->error('给张图片吧');
			} else {
				$config = array(    
					'maxSize'    =>    3145728,
					'rootPath'	 =>	   './Public/',
					'savePath'   =>    '/Uploads/',
				    'saveName'   =>    array('uniqid',''),
				    'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),   
				    'autoSub'    =>    false,
				);
				//实例化
				$upload = new \Think\Upload($config);
				//上传图片
				$info = $upload->uploadOne($_FILES['pic']);;
				if ($info)
				{
					$pic = $info['savename'];
				} else {
					$this->error($upload->getError());
				}
			}
			$data['pic'] = $pic;
			if($banner->add($data))
			{
				$this->success('添加成功', U('index'));
				exit;
			} else {
				$this->error('添加失败');
				exit;
			}
		}
		$this->display();
	}

	/**
	 * 显示并处理修改
	 * @param  [int] $id [需要修改的轮播图id]
	 */
	public function edit($id)
	{
		$banner = D('Banner');
		if(IS_POST)
		{
			$data = $banner->create();
			if(!$data) $this->error($banner->getError());
			//判断是否上传图片
			if (!empty($_FILES['pic']['name']))
			{
				$config = array(    
					'maxSize'    =>    3145728,
					'rootPath'	 =>	   './Public/',
					'savePath'   =>    '/Uploads/',
				    'saveName'   =>    array('uniqid',''),
				    'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),   
				    'autoSub'    =>    false,
				);
				//实例化
				$upload = new \Think\Upload($config);
				//上传图片
				$info = $upload->uploadOne($_FILES['pic']);;
				if ($info)
				{
					$data['pic'] = $info['savename'];
				} else {
					$this->error($upload->getError());
				}
			}
			//修改表数据
			if($banner->save($data)){
				$this->success('修改成功', U('index'));	
				exit;
			} else {
				$this->error('修改失败');
				exit;
			}
		}
		$data = $banner->where(['id' => $id])->find();
		$this->assign(['data' => $data, 'id' => $data['id']]);
		$this->display();
	}
	
	/**
	 * 删除轮播图
	 * @param  [type] $id [轮播图id]
	 * @return void
	 */
	public function del($id)
	{
		$banner = M('Banner');
		$banner->where(['id' => $id])->delete();
		$this->redirect('index');
	}
}