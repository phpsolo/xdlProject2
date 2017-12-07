<?php
return array(
	//'配置项'=>'配置值'
	/*配置邮件发送服务器*/
    'MAIL_HOST'     => 'smtp.qq.com',          /*smtp服务器的名称、smtp.126.com*/
    'MAIL_SMTPAUTH' => TRUE,                    /*启用smtp认证*/
    'MAIL_DEBUG'    => TRUE,                    /*是否开启调试模式*/
    'MAIL_USERNAME' => '369244579@qq.com',      /*邮箱名称*/
    'MAIL_FROM'     => '369244579@qq.com',      /*发件人邮箱*/
    'MAIL_FROMNAME' => 'xxx',                 /*发件人昵称*/
    'MAIL_PASSWORD' => 'ytqcfdnyqykbbifd',      /*发件人邮箱的密码*/
    'MAIL_CHARSET'  => 'utf-8',                 /*字符集*/
    'MAIL_ISHTML'   => TRUE,                    /*是否HTML格式邮件*/
    'MAIL_PORT'     => 465,                     /*邮箱服务器端口*/
    'MAIL_SECURE'   => 'ssl',                   /*smtp服务器的验证方式，注意：要开启PHP中的openssl扩展*/

    // /* 数据库设置 */
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  '123456',          // 密码
    'DB_DSN'                => 'mysql:host=localhost;dbname=shop;charset=UTF8',
    'DB_PREFIX'             =>  'shop_',    // 数据库表前缀

    // /* 数据缓存设置 */
    // 'DATA_CACHE_TIME'       =>  0,      // 数据缓存有效期 0表示永久缓存
    // 'DATA_CACHE_TYPE'       =>  'memcache',
    // 'MEMCACHE_HOST' => '127.0.0.1',
    // 'MEMCACHE_PORT' => 11211,

    // 'SHOW_PAGE_TRACE'=>TRUE,
    'DATA_CACHE_TYPE' => 'Memcache',
    'DATA_CACHE_TIME' => 20,
    'DATA_CACHE_PREFIX' => 'goods_',
);