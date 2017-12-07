<?php
return array(
	//'配置项'=>'配置值'
	 // /* 数据库设置 */
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  '123456',          // 密码
    'DB_DSN'                => 'mysql:host=localhost;dbname=shop;charset=UTF8',
    'DB_PREFIX'             =>  'shop_',    // 数据库表前缀
    // 'SHOW_PAGE_TRACE'		=> true,
    'URL_MODEL'				=> 2,
    /* 数据缓存设置 */
    'DATA_CACHE_TIME'       =>  0,      // 数据缓存有效期 0表示永久缓存
    'DATA_CACHE_TYPE'       =>  'memcache',
    'MEMCACHE_HOST' => '127.0.0.1',
    'MEMCACHE_PORT' => 11211,
);