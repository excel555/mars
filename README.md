概况：
==================
* 1.基于CI框架
* 2.支持数据库主从结构
* 3.请求路由支持restful风格  
* 4.请求头需要传token[类似之前的connect_id]，通过token进行验证，请求体只传递业务相关的数据，后期根据业务也可以在请求头中传递签名、版本等


开发相关：
-------------------
* 1.在controller/api中添加一个类Test；
* 2.Test类继承 REST_Controller 类；
* 3.默认构造函数调用父类构造函数 parent::__construct();
* 4.定义一个新方法，比如 get_users_get()，其中get_users 是请求路由的方法名，后面四位 "_get" 是代表请求方式是GET，支持POST/PUT/DELETE/GET...
* 5.通过 $this->send_error_response("xxxx")返回错误， $this->send_ok_response(['status'=>'succ'])返回正确结果；
* 6.在浏览器中请求 http://xxx.com/test/get_user 就能返回请求结果；
* 7.请求参数的获取，GET : $this->get('key');POST : $this->post('key');
* 8.sign 签名方法： 所有参数ksort之后，两次md5加密，然后去掉最后一位，同时加上字符‘w’;
* 8.需要在配置文件rest.php中设置这些url的请求范围，比如wap_auth_uri、admin_auth_uri....

前端静态页面开发相关：
-------------------
* 1.需要在本地配置好npm、gulp
    * cd city_box #切换到当前工程目录
    * npm install --save-dev gulp #按照gulp
    * npm install --save-dev  #安装package.json 配置的插件,在项目的目录会增加 node_modules 文件夹
* 2.开发环境目录是public_dev，发布目录是public
* 3.因为我们会把通用的js合并main.js，所以修改了config.js/global.js/common.js，则需要合并一下，运行的命令是gulp watch来监听修改文件
* 4.在提交代码库前，最好也执行一下 gulp watch
* 5.如果需要发布代码，执行命令 gulp 即可，同时会看到压缩整理后的文件出现在 public 目录


部署相关：【千万不能使用root权限部署】
-------------------
* 1.新建数据库
* 2.修改配置文件:[application/config] 现在可以统一修改 env_config.php 文件
    * 基本配置文件：config.php:
        *  base_url 【服务器host】
        *  sess_save_path 【保存session路径】 如果设置的memcached保存session，记得更改session_save_path
        *  log_threshold 【日志等级】如果是生成环境，设置为1（只记录错误信息），搞活动大并发的时候设置0比较合适 日志路径在 application/logs/
    * 数据库配置文件：database.php
        * 设置 hostname、username、password、db_debug
    * 零售机请求接口：device.php
        * host_device【硬件API host】
        * secret【硬件接口秘钥】
        * app_key【硬件接口APP】
    * 支付宝相关配置文件：alipay.php
        * app_id【支付宝APPID】
    * 支付宝免密支付配文件：mapi.php
        * mapi_box_host【支付回调host】、
        * partner【免密支付parner】
        * safe_key【免密支付安全码】
        * seller_id【免密支付商家、涉及分账、分润】
        * 模板消息id【切换partner账号，tpl_id会变】、模板内容【产品经理确认】
    * 缓存配置文件memcached.php
        * hostname、port
    * API restful配置文件rest.php
        * allow_host【允许调用API的域名，简单防止跨域请求】
        * api_secret【不同入口平台的秘钥配置】
    
    * 设置配置日志文件 log.php
        * crit_log_email_rec 【告警邮件】
* 3.修改index.php文件
    * 57行 配置 ENVIRONMENT 为生产环境
* 4.配置 wap 相关的文件 /js/config.js
    * alipayAppId【支付宝APPID】
    * wechatAppId【微信APPID】
* 5.设置文件权限
    * 如果是文件存放session：chmod -R 777 'sess_save_path'----- 具体路径见 config.php 373行 sess_save_path
* 6.设置定时任务
    * crontab -e
    * 然后 插入
    * */1 *  *  *  *  php /mnt/www/dalangkji/index.php cron_event pay_order
    * */1 *  *  *  *  php /mnt/www/dalangkji/index.php cron_event fix_box_status
    * */5 *  *  *  *  php /mnt/www/dalangkji/index.php cron_event box_heart_check
    * service crond restart|start|stop

