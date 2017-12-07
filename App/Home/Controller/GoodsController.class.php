<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 前台商品控制器
 */
class GoodsController extends Controller {
    protected $redis;
    public function _initialize(){
        $this->redis = new \Redis();
        $this->redis->connect('localhost', 6379);
        // 实例化类
        $seo = M('IndexSeo');
        // 获取seo数据
        $seoarr = $seo->select();
        // 分配数据
        $this->assign('seo',$seoarr);

    }
    /**
     * 显示商品列表页
     */
    public function index()
    {   

        // 获取id
        $id = I('get.id');
        // 获取搜索条件并做处理
        $search = trim(trim(I('get.search')),'+');
        // 搜索框提示
        // 导入第三方类库
        vendor('xunsearch.lib.XS');
        $xs = new \XS('goods');
        $obj = $xs->search;
        if(IS_POST){
            $words = I('words');
            $result = $obj->getExpandedQuery($words,7);
            $this->success($result);
            exit;  
            
        }
        // 没有商品id和搜索条件不能进入
        if(!$id&&!$search) $this->redirect('Index/index');
        // 获取热搜词
        $hotwords = $obj->getHotQuery(50);
        arsort($hotwords);
        $hotwords = array_slice($hotwords,0,6,true);

        // 分配热搜词和搜索框数据
        $this->assign('hotwords',$hotwords);
        $this->assign('search',$search);
        // 获取id和分配数据
        @$ida = $_SESSION['HomeUser']['id'];     
        $this->assign('ida',$ida);
        // 通过搜索进入
        if(strlen(trim($search)) > 0){
            // 实例化类
            $xtype = new \XS('type');
            $typeobj = $xtype->search;
            // var_dump($typeobj);
            // 判断是否搜索到分类
            if($typeinfo = $typeobj->setQuery($search)->setSort('pid', $asc = true)->search()){
                foreach ($typeinfo as $k=> $v) {
                    foreach ($v as $key => $value) {
                        $info[$key] = $value;
                    }
                    $type[$k] = $info; 
                }
                // 搜索到顶级分类
                if($type[0]['pid']==='0'){
                    // echo '顶级分类';
                    $id = $type[0]['id'];
                    $this->assign('search',$search);
                    $parent = null;
                } else {                
                    // echo '父类';
                    $id = $type[0]['id'];
                    $page = '';
                    $this->assign('search',$search);
                }

            } else { 
                // 判断提交的搜索是否包含空格 加号
                if (preg_match("/\s/", $search)) {
                    $res = [];
                    $sarr = explode(' ',$search);
                    // 去除空值
                    $sarr = array_filter($sarr);
                    foreach($sarr as $k=>$v){
                        $arr = $obj->setQuery($v)->search();
                        $res = array_merge($res, $arr);
                    }

                } else if(preg_match("/\+/", $search)){
                    $res = [];
                    foreach(explode('+',$search) as $v){
                        $arr = $obj->setQuery($v)->search();
                        $res = array_merge($res, $arr);
                    }  
                    
                } else {
                    $res = $obj->setQuery($search)->search();
                }
                $corrected = $obj->getCorrectedQuery();
                if (count($corrected) !== 0)
                {
                    // echo '你要找的是不是:'.$corrected[0];
                    $this->assign('corrected',$corrected[0]);
                }        

                // 获取id
                if($res){
                    $ids = [];
                    $bids = [];      
                    foreach ($res as $k=> $v) {
                        $ids[] = $v->id;
                        $bids[] = $v->bid;
                    }
                    //操作品牌表
                    $brand = M('Brand');
                    //查询该分类下所有的品牌信息
                    $brand = $brand->where(['id' => ['in', join(',', $bids)],'status' => 2])->select();
                    if($brand){
                        $this->assign('brand',$brand);
                    }
                    // dump($brand);
                    $where['id'] = ['in',$ids];
                    $where['status'] = ['in',[2,4]]; 
                } else {
                    $where['id'] = 'id=null';
                }
                $goods = M('Goods');
                //是否带有品牌条件
                if(I('get.brand')) $where['bid'] = I('get.brand');
                $count = $goods->where($where)->count();
                //实例化分页类
                $Page = new \Think\Page($count, 2);
                //是否带有排序条件
                $order = I('get.order') ? urldecode(I('get.order')) : 'buynum desc';
                //是否带有商品名模糊搜索
                if(I('get.name')) $where['name'] = ['like', '%'.I('get.name').'%'];
                //是否带有价格区间条件
                $minprice = I('get.minprice') ? I('get.minprice') : 0;
                $maxprice = I('get.maxprice') ? I('get.maxprice') : pow(10, 9);
                $where['price'] = ['BETWEEN', [$minprice, $maxprice]];
                // 得到搜索数据
                $res = $goods->order($order)->where($where)->limit($Page->firstRow, $Page->listRows)->select();
                $idsarr = array_column($res,'id');
                $collect = M('UserCollect');
                if(!empty($_SESSION['HomeUser'])){

                    $collectinfo = $collect->where(['gid' =>['in', join(',', $idsarr)],'uid'=>$_SESSION['HomeUser']['id']])->select();
                } else {
                    $collectinfo = [];
                }
                // 判断是否收藏商品
                foreach($res as $k=>$v){
                    $res[$k]['collect'] = 0;
                    foreach ($collectinfo as $key=>$value){
                        if($v['id'] == $value['gid']){
                            $res[$k]['collect'] = 1;
                        }
                    }
                }

                // 总页面数
                $btns = $Page->show();
                // AJAX请求
                if(IS_AJAX)
                {   
                    // 判断有无登录
                    if(isset($_SESSION['HomeUser']['id'])){
                    $tmp['session'] = $_SESSION['HomeUser']['id'];
                    } else {
                        $tmp['session']=null;
                    }
                    $tmp['data'] = $res;
                    $tmp['btns'] = $btns;
                    $this->ajaxReturn($tmp);
                    exit;  
                }
                $this->assign('data',$res);
                $this->assign('btns',$btns);
                $this->assign('search',$search);
            }
            
        }
        // 通过首页点击进入
        if(!$id==''){
            // dump($id);
            //操作分类表
            $type = M('Type');
            $me = $type->where(['id' => $id])->find();
            //操作商品表
            $goods = M('Goods');

            $tmp = $type->where(['pid' => $id])->select();
            if($tmp)
            {
                $ids = array_column($tmp, 'id');
                $tmp1 = $type->where(['pid' => ['in', join(',', $ids)]])->select();
                // dump($tmp1);
                if($tmp1)
                {
                    $tmp1Total = $type->where(['pid' => ['in', join(',', $ids)], 'status' => 2])->count();
                    $page = new \Think\Page($tmp1Total, 5);
                    //滚动加载
                    $tmpScroll = $type->where(['pid' => ['in', join(',', $ids)], 'status' => 2])->limit($page->firstRow, $page->listRows)->select();
                    $parent =null;
                    //如果还有子类就是点击了一级分类
                    $ids = array_column($tmp1, 'id');
                    // dump($tmp);
                    $type = $tmp1;
                    foreach ($ids as $value) {
                        $data[$value] = $goods->where(['tid' => $value, 'status' => 2])->limit(6)->select();
                    }
                } else {
                    //没有子类就是点击了二级分类
                    $parent[] = $type->where(['id' => $me['pid']])->find();
                    $tmpTotal = $type->where(['id' => ['in', join(',', $ids)], 'status' => 2])->count();
                    $page = new \Think\Page($tmpTotal, 5);
                    //滚动加载
                    $tmpScroll = $type->where(['id' => ['in', join(',', $ids)], 'status' => 2])->limit($page->firstRow, $page->listRows)->select();
                    $type = $tmp;
                    foreach ($ids as $value) {
                        $data[$value] = $goods->where(['tid' => $value, 'status' => 2])->limit(6)->select();
                    }

                }
                if(IS_AJAX)
                {
                    $data['data'] = $data;
                    $data['page'] = ($page->firstRow/$page->listRows)+2;
                    $data['type'] = $tmpScroll;
                    $this->ajaxReturn($data);
                    exit;
                }
                // var_dump($parent);
                // dump($tmpScroll);
                $this->assign(['type' => $type, 'parent' => $parent,'data' => $data, 'goods' => $goods, 'tmpScroll' => $tmpScroll, 'id' => $id]);
                $this->display('x_index');
                exit;
            }
            //查询父类用来做面包屑
            $parent[] = $type->where(['id' => $me['pid']])->find();
            $parent[] = $type->where(['id' => $parent[0]['pid']])->find();
            //操作品牌分类关联表
            $brandType = M('BrandType');
            $brandIds = array_column($brandType->field('bid')->where(['tid' => $me['id']])->select(), 'bid');
            //操作品牌表
            $brand = M('Brand');
            //查询该分类下所有的品牌信息
            $brand = $brand->where(['id' => ['in', join(',', $brandIds)]])->select();
            $where = ['tid' => $id, 'status' => 2];
            // dump(I('get.'));
            //是否带有品牌条件
            if(I('get.brand')) $where['bid'] = I('get.brand');
            //是否带有排序条件
            // dump(I('get.order'));
            $order = I('get.order') ? urldecode(I('get.order')) : 'buynum desc';
            //是否带有商品名模糊搜索
            if(I('get.name')) $where['name'] = ['like', '%'.I('get.name').'%'];
            //是否带有价格区间条件
            $minprice = I('get.minprice') ? I('get.minprice') : 0;
            $maxprice = I('get.maxprice') ? I('get.maxprice') : pow(10, 9);
            $where['price'] = ['BETWEEN', [$minprice, $maxprice]];
            //统计数据总条数
            $dataTotal = $goods->where($where)->count();
            //实例化分页类
            $page = new \Think\Page($dataTotal, 2);
            $btns = $page->show();
            //没有子类就是点击了三级分类，查商品
            $data = $goods->order($order)->limit($page->firstRow, $page->listRows)->where($where)->select();
            // 获取id
            $idsarr = array_column($data,'id');
            $collect = M('UserCollect');
            if(!empty($_SESSION['HomeUser'])){

                $collectinfo = $collect->where(['gid' =>['in', join(',', $idsarr)],'uid'=>$_SESSION['HomeUser']['id']])->select();
            } else {
                $collectinfo = [];
            }

            // 判断是否收藏商品
            foreach($data as $k=>$v){
                $data[$k]['collect'] = 0;
                foreach ($collectinfo as $key=>$value){
                    if($v['id'] == $value['gid']){
                        $data[$k]['collect'] = 1;
                    }
                }
            }
            if(IS_AJAX)
            {
                $tmp['data'] = $data;
                $tmp['btns'] = $btns;
                if(isset($_SESSION['HomeUser']['id'])){

                $tmp['session'] = $_SESSION['HomeUser']['id'];
                } else {
                    $tmp['session']=null;
                }
                // echo $goods->_sql();
                $this->ajaxReturn($tmp);  
            }
            $this->assign([
                'data' => $data, 
                'parent' => $parent,
                'brand' => $brand,
                'btns' => $btns,
                // 'totalPages' => $totalPages,
                'me' => $me
            ]);           
        }
        // dump($me);
        $this->display();
    }
    /**
     * 显示商品详情页
     */
    public function detail($id){
        //操作商品品论表
        $goodsAssess = D('GoodsAssess');
        //得到全部商品评论
        $assessTotal = $goodsAssess->where(['gid' => $id])->count();
        //总评论
        $allAssess = $goodsAssess->order('addtime desc')->where(['gid' => $id])->limit(5)->getData();

        if(IS_AJAX)
        {
            $assess = I('get.assess');
            
            if($assess)
            {
                $gid = I('get.id');
                //带有这个属性就是滚动或者点击选项卡
                if($assess == 4)
                {
                    //实例化分页类
                    $page = new \Think\Page($assessTotal, 5);
                    if(I('get.p'))
                    {
                        //带有这个属性就是滚动触发的ajax
                        $data['data'] = $goodsAssess->order('addtime desc')->limit($page->firstRow, $page->listRows)->where(['gid' => $gid])->getData();
                        if(!$data) exit;
                        $data['page'] = ($page->firstRow/$page->listRows)+2;
                    } else {
                        //点击选项卡触发的ajax
                        $data = $goodsAssess->order('addtime desc')->limit($page->firstRow, $page->listRows)->where(['gid' => $gid])->getData();
                    }
                } else {
                    //根据点击的选项查询数据
                    $data = $goodsAssess->order('addtime desc')->where(['gid' => $gid])->where(['feel' => $assess])->getData();
                }
                
                $this->ajaxReturn($data);
                exit;
            } else {
                //这是点击商品属性的ajax请求
                $goodsInfo = M('GoodsInfo');
				$goods = M('Goods')->where(['id' => $id])->find();
                $data = $goodsInfo->where(['gid' => $id, 'attr' => I('get.attr')])->find();
				if($goods['status'] == 4 && $goods['addtime'] > time()) $data['disabled'] = 'disabled';                if(!$data['stock']) $data['disabled'] = 'disabled';
                $this->ajaxReturn($data);
                exit;
            }
        }

        //设置缓存参数
        S(array(
            'host'=>'localhost',    
            'port'=>'11211',
            'expire'=> 10    
        ));
        //操作分类表
        $type = D('Type');
        //操作商品表
        $goods = M('Goods');
         //获取客户端ip
        $clientIp = $_SERVER['REMOTE_ADDR'];
        if(!S('str:client:ip:'.$clientIp.':goods:'.$id))
        {
            //10秒内从相同ip访问同一个商品详情页，不重复增加点击量
            S('str:client:ip:'.$clientIp.':goods:'.$id, 1);   
            $goods->where(['id' => $id])->setInc('clicknum');
        }
        //操作商品信息表
        $goodsInfo = D('GoodsInfo');
        //操作商品图片表
        $goodsPic = M('GoodsPic');
        $cache = S($id);
        // $redis = $this->redis->hGetAll('hash:goods:'.$id);
        if($cache)
        {
            // echo '缓存中获取';
            $data = $cache['data'];
            $pics = $cache['pics'];
            $attr = $cache['attr'];
            $disabled = $cache['disabled'];
        } else if($redis) {
            $data = $redis;
            $pics = $this->redis->hGetAll('hash:goodspic:gid:'.$id);
            sort($pics);
            $attr = $this->redis->hGetAll('hash:goodsinfo:gid:'.$id);
            $attr = $goodsInfo->changeJson($attr);
        } else {
            // echo '请求数据库';
            //得到商品数据
            $data = $goods->where(['id' => $id + 0])->find();
            //得到商品信息数据
            $attr = $goodsInfo->where(['gid' => $id])->getData(); 
            //得到商品图片
            $pics = array_column($goodsPic->where(['gid' => $id])->select(), 'pic');
            if($data['status'] == 4 && $data['addtime'] > time()) $disabled = 'disabled';
            if($data['clicknum'] > 3/* && $data['status'] != 4*/)
            {
                $cache['data'] = $data;
                $cache['attr'] = $attr;
                $cache['pics'] = $pics;
                $cache['disabled'] = $data['disabled'];
                S($id, $cache);
            }
        }
        //好评总条数
        $goodAssessTotal = $goodsAssess->where(['gid' => $id, 'feel' => 1])->count();
        //中评总条数
        $normalAssessTotal = $goodsAssess->where(['gid' => $id, 'feel' => 2])->count();
        //差评总条数
        $badAssessTotal = $goodsAssess->where(['gid' => $id, 'feel' => 3])->count();
        //得到商品的分类   
        $type = $type->getData($data['tid']);
        //操作收藏商品表
        $collect = M('UserCollect'); 
        $res = $collect->where(['gid'=>$data['id'],'uid'=>$_SESSION['HomeUser']['id']])->select();

        //分配数据
        $this->assign([
            'data' => $data, 
            'attr' => $attr['attr'], 
            'pics' => $pics, 
            'allAssess' => $allAssess,
            'default' => $attr['default'],
            'type' => $type,
            'goodAssessTotal' => $goodAssessTotal,
            'normalAssessTotal' => $normalAssessTotal,
            'badAssessTotal' => $badAssessTotal,
            'assessTotal' => $assessTotal,
            'res' => $res,
            'disabled' => $disabled
        ]);
        $this->display();
    }

    /**
     * 商品评论处理
     */
    public function assess()
    {
        //判断是否登录
        if(!session('HomeUser')['id']) $this->error('请先登录');
        $uid = session('HomeUser')['id'];
        //操作商品评论表
        $order = M('Order')->field('oid, status')->where(['uid' => $uid])->select();
        //操作商品详情表
        $detail = M('Detail');
        $flag = false;
        foreach ($order as $value) {
            //判断订单详情中是否有该商品，并且订单状态为已收货
            $tmp = $detail->field('gid')->where(['oid' => $value['oid']])->select();
            foreach ($tmp as $v) {
                if($v['gid'] == $_POST['gid'] && $value['status'] == 5) $flag = true;
            }
        }
        if(!$flag) $this->error('必须购买并且已收到货，才能评论该商品');
        $_POST['addtime'] = time();
        $_POST['uid'] = $uid;
        //操作商品评论表
        $goodsAssess = M('GoodsAssess');
        $goodsAssess->add($_POST);
        $this->redirect('Home/Goods/detail/id/'.$_POST['gid']);
    }

    /**
     * 商品促销模块
     */
    public function sale()
    {
        $goods = D('Goods');
        $data = $goods->where(['status' => 4])->select();
        foreach ($data as $key => $value) {
            $data[$key]['addtime'] -= time();
        }
        $this->assign(['data' => $data]);
        $this->display();
    }

    /**
     * 增加讯搜的所有商品索引
     * @return null
     */
    // public function createIndex()
    // {
    //     // 导入第三方类库
    //     vendor('xunsearch.lib.XS');
    //     $xs = new \XS('goods');
    //     $index = $xs->index;
    //     // var_dump($index);
        
    //     $arr = M('goods')->field('id, name , des, tid ,bid')->select();
    //     // var_dump($arr);
    //     foreach ($arr as $v) {
    //         $doc = new \XSDocument($v);
    //         // var_dump($doc);
    //         // 创建索引
    //         $index->update($doc);
    //     }
    //     $type = new \XS('type');
    //     $index = $type->index;
    //     $data = M('type')->field('id, name , pid')->select();
    //     // var_dump($data);
    //     foreach ($data as $v) {
    //         $doc = new \XSDocument($v);
    //         var_dump($doc);
    //         // 创建索引
    //         $index->add($doc);
    //     }
    // }

    /**
     * 删除讯搜所有索引
     * @return [type] [description]
     */
    // public function delIndex()
    // {
    //     vendor('xunsearch.lib.XS');
    //     $xs = new \XS('goods');
    //     $index = $xs->index;
    //     // var_dump($index);
    //     $index->clean();
    //     $xstype = new \XS('type');
    //     $index = $xstype->index;
    //     // var_dump($index);
    //     $index->clean();
    // }
}