<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');
$config = array (
    //应用ID,您的APPID。
    'app_id' => $env_config['zmxy_app_id'],
    'zmxy_merchant_id' => $env_config['zmxy_merchant_id'],
    'type_id' => $env_config['zmxy_type_id'],

    //商户私钥，您的原始格式RSA私钥,一行字符串 *** 去掉换行
    'merchant_private_key' => "MIICXQIBAAKBgQDNYhLB5gvYHFJqIiVxn8sMieUE+jHB5dZJ2lmU7d2q2N2MCA+rW3XAv2M/eQCtZ+WKGtgdUuOu1EN/6g2tdd8mxgW1I4Bm7S60cWuGKtkc2myyD8N3Hssk9KDhA5gloc3q5IBgBf9wWqCcXAT7clitlK5M083roI1PJQHxQS5gxQIDAQABAoGBALTjkPO34mynnSqfAm2NuG9FsDDvDw3gmRiYuFeEHLzRnmcr3mkk95QYvJf1wdP4cuFs/TTugVvE1eJ+SSeibjOLFM57no2RPI2lDGTGpulYvgYbQiVvrrrlaIxpw8oINoLygIC6d4lunj1QrWdnIwLqhZRIMqURVu47UL1YR2ThAkEA7ChhmgB4idhj7ZBbE8/XpDmonA4HCNb9HeU9CKdhNjpt301KLosfk/5OvQI1GE3jDrea3lqso716H6HWbYNqnQJBAN6jvzqe7pIgkfYpmZLARKZlx6az5VDY2JGz4mGPQMW2l3iqhbRcyaHofUkrkZo0C+LGfrvrA/cGJYa+Z+5ywkkCQGLWn8rZqZlfxKr4APZwxbsJGsV9pXoQqM1rVTka/Le6iqOr8IE8XxIMnJ3En741UvOk6p9nadv6AHPewyUAnI0CQBU3+fO2TfpzTDXvxQktdd19+cczgflwkUNhp4OwyXWOb2U6qz+DUFwz8izVEC1oJHHahR2XymrylQUAhJs/KLECQQC0BiTAt6qhGP1/i+5IhfOVys3fmlLq6lEOYkky/pVBJQ4CfQBrgLq0lx1g9kU4xvnKhL5QjdW/aUd8Dq0QSb9c",


    //商户公钥，您的原始格式RSA私钥,一行字符串 *** 去掉换行
    'merchant_public_key' => "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDNYhLB5gvYHFJqIiVxn8sMieUE+jHB5dZJ2lmU7d2q2N2MCA+rW3XAv2M/eQCtZ+WKGtgdUuOu1EN/6g2tdd8mxgW1I4Bm7S60cWuGKtkc2myyD8N3Hssk9KDhA5gloc3q5IBgBf9wWqCcXAT7clitlK5M083roI1PJQHxQS5gxQIDAQAB",


    //芝麻信用公钥,查看地址：https://b.zmxy.com.cn/technology/myApps.htm 对应APPID下的芝麻信用宝公钥。 *** 去掉换行
    'zm_public_key' => $env_config['zm_public_key'],
    'charset' => "UTF-8",

    //支付宝网关
    'gatewayUrl' => "https://zmopenapi.zmxy.com.cn/openapi.do",

    'sign_type'=>"RSA"
);