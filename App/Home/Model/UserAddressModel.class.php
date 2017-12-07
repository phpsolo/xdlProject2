<?php 
namespace Home\Model;
use Think\Model;
/**
 * 用于处理用户收货地址数据
 */
class UserAddressModel extends Model
{
	//自动验证收货地址
	protected $_validate = [
		//检测用户名是否为空
		['linkman','require','收件人不能为空'],
		['linkman','/^[\w\x{4e00}-\x{9fa5}]{2,6}$/u','请输入合法姓名'],

		//验证手机号
		['phone','require','手机号不能为空'],
		['phone','/^(((13|14|15|18|17)\d{9}))$/','请输入合法手机号'],
		//验证收货地址
		['address','require','收货地址不能为空'],
		['address','/^[\w -\x{4e00}-\x{9fa5}]{10,200}$/u','请输入10位以上的详细地址']
	];
}