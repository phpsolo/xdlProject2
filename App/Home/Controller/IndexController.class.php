<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 前台首页控制器
 */
class IndexController extends Controller {
    //初始化数据
     public function _initialize(){

        $seo = M('IndexSeo');
        // 获取seo数据
        $seoarr = $seo->select();
        // 分配数据
        $this->assign('seo',$seoarr);
    }
	/**
	 * 该操作用来显示商城首页
	 */
    public function index(){

        //只是前台首页遍历友情链接
        $friend = D('Admin/FriendShip');
        $list = $friend->where(['status'=> 1])->select();
        
        $Type = M('Type');
        $typeId = $Type->where(['name'=>'男装'])->find()['id'];
        //遍历男装下的子分类和品牌
        $sonId = $Type->where(['pid'=>$typeId])->select();
        //分配男装下的子分类数据
        $this->assign('son',$sonId);
         //操作轮播图表
        $banner = D('Banner');
        //查询轮播图数据
        $banner = $banner->where(['status' => 1])->limit(5)->getData();
        $tId = $Type->where(['name'=>'女装'])->find()['id'];
        //遍历女装下的子分类的品牌
        $girlId = $Type->where(['pid'=>$tId])->select();
        //分配女装下的子分类数据
        $this->assign('girl',$girlId);
        // 导入第三方类库
        vendor('xunsearch.lib.XS');
        $xs = new \XS('goods');
        $searchobj = $xs->search;
        // 获取热搜词
        $hotwords = $searchobj->getHotQuery(50);
        arsort($hotwords);
        $hotwords = array_slice($hotwords,0,6,true);
        // var_dump($hotwords);
        $search = '';
        // 分配热搜词和提交的搜索词的数据
        $this->assign('hotwords',$hotwords);
        $this->assign('search',$search);
        //查找所有的顶级分类
        $types = $Type->field('id,name')->where(['pid'=>0])->select();

        // 查询是否有缓存
        if(S('ads')){
            $adsarr = S('ads');
            $count = S('count');

        } else {
            //查找广告数据
            $ads = M('ads');
            // 获取广告图片
            $adsarr = $ads->limit(4)->where(['status'=>'1'])->order('id desc')->select();
            // 获取广告总条数
            $num = $ads->where(['status'=>'1'])->count();
            // 判断总条数
            if($num > '4'){
                $count = '4';
            } else {
                $count = $num;
            }
            // 写入缓存
            S('ads',$adsarr,300);
            S('count',$count,300);

        }

        //滚动加载促销商品
        if(IS_AJAX)
        {

            if(I('get.model') == 'hotsale')
            {
                //操作商品表
                $goods = D('Goods');
                //查看是否有缓存
                if(S('allhomepics'))
                {
                    // 有就获取缓存的
                    $pics = S('allhomepics'); 
                } else {
                    //没有则查询数据库
                    $pics = $goods->cache('allhomepics', 60)->field('id, pic')->select();
                }
                //随机取出4张图片
                foreach(array_rand($pics, 4) as $v)
                {
                    $data['pics'][] = $pics[$v];    
                }
                //统计商品总数
                $count = $goods->where(['status' => 2])->count();
                $page = new \Think\Page($count, 6);
                //取出按销量由大到小排序的商品数据
                $hotbuy = $goods->where(['status' => 2])->order('buynum')->limit($page->firstRow, $page->listRows)->getGoodsData();
                $data['hotbuy'] = $hotbuy;
                $data['bigpic'] = $pics[array_rand($pics)];
                $data['data'] = $goods->where(['status' => 4])->limit(5)->order('addtime')->getGoodsData();
                //判断是按那个按钮来更新数据
                if(I('get.demo') == 'hotbuy')
                {
                    $p = $page->firstRow / $page->listRows + 2;
                    if($p > $count / 6) $p = 1;
                    $data = ['hotbuy' => $data['hotbuy'], 'page' => $p];
                } else if(I('get.demo') == 'pics')
                {
                    unset($data['hotbuy']);
                    unset($data['data']);
                } else {
                    // dump($data['pics']);
                    $data = $data;
                }
                $this->ajaxReturn($data);
                exit;
            }
        }
        // 分配广告数据
        $this->assign('adsarr',$adsarr);
        $this->assign('count',$count);
        //分配分类数据
        $this->assign('type',$types);
        //分配轮播图数据
        $this->assign('banner',$banner);
        //分配首页数据
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 获取子分类下的品牌和商品
     */
    public function getBrand(){
        //ajax传过来的子分类id
        $tid = I('tid');
        //不存在则去查
        $brand = M('BrandType');
        $goods = M('Goods');
        $bras = $brand->where(['tid'=>I('tid')])->select();
        $arr = $goods->where(['tid'=>I('tid')])->select();
        foreach ($bras as $k => $v) {
            $bras[$k]['pic'] = M('Brand')->where(['id'=>$v['bid']])->find()['pic'];
        }

        $list['bras'] = $bras;
        $list['goods'] = $arr;
        $this->ajaxReturn($list);
    }

    /**
     * 用于前台ajax获得分类信息
     */
    public function getType(){
        //查询第二级分类ID
        $second = M('Type')->where(['pid'=>I('tid')])->select();
        foreach ($second as $k => $v) {
            $third[] = M('Type')->where(['pid'=>$v['id']])->select();
        }
        $obj = [$second,$third];
        $this->ajaxReturn($obj);
    }
}