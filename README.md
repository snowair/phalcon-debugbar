## Phalcon Debugbar

[README(English)](https://github.com/snowair/phalcon-debugbar/blob/master/README.EN.md)

这个扩展包将 [PHP Debug Bar](http://phpdebugbar.com/) 与  [Phalcon FrameWork](http://phalconphp.com) 集成在了一起.
 
要感谢 laravel-debugbar, 我从中得到了启发, 并使用了其中的一些代码.

我在 Mac/PHP5.6/Phalcon 1.3.4 之下开发, 时间关系, 只在PHP5.4/Linux下测试通过, 其他环境尚未测试, 如果有问题, 欢迎提 Issue或者 Pull Reqeust. 

注意: 这是一个开发辅助扩展, 谨慎用于生产环境, 以免泄漏应用信息或影响应用性能.

## 功能特性

1. 常规请求调试信息收集
2. Ajax请求调试信息收集
3. Redirect请求调试信息
4. 调试信息本地持久化支持
5. 支持 多模块,单模块,微应用.
 
### 支持收集的调试数据

 - MessagesCollector : 收集自己发送的调试数据
 - TimeDataCollector : 收集时间计算信息
 - MemoryCollector : 请求的内存占用
 - ExceptionsCollector : 异常信息收集
 - QueryCollector: 收集所有SQL查询, 每条SQL的执行时间, SELECT语句的EXPLAIN信息
 - RouteCollector: 收集当前路由的相关信息
 - ViewCollector:  收集当前请求渲染的所有模板, 每个模板的渲染耗时, 赋值到视图的视图变量
 - PhalconRequestCollector: 收集请求头信息, 请求数据, 解密后的cookie, RAW BODY, 以及响应头信息
 - ConfigCollector: 收集 config service中的数据.
 - SessionCollectior 收集session数据
 - SwiftMailCollector: 收集邮件发送信息
 - LogsCollector: 当前请求产生的日志
 - CacheCollector: 缓存操作统计/详情

## 快速开始

### composer 安装

```
php composer.phar require --dev snowair/phalcon-debugbar
```

### 创建目录

为了支持ajax调试和重定向调试, debugbar默认开启了调试数据持久化功能, 它会将收集到的调试信息以json文件保存在`Runtime/phalcon`目录下.

如果该目录不存在, 会试图创建, 这需要你的项目目录**可写**, 否则将抛出warning错误. 建议手动创建`Runtime`目录并设置可写. 你也可以修改配置文件,使用其他目录进行持久化.

### 修改 index.php

1. 将应用实例保存为app服务

    ```
    $application = new Phalcon\Mvc\Application($di); // 将$di作为构造参数传入  Micro应用也一样: new Phalcon\Mvc\Micro($di);
    $di['app'] = $application; // 将应用实例保存到$di的app服务中
    ```

2. 在handle()方法前面的位置插入下面的代码.

    ```
    (new Snowair\Debugbar\ServiceProvider())->start();
    // 在启动debugbar之后,立即handle应用.
    echo $application->handle()->getContent();
    ```
    
## 技巧
    
### 使用外部的配置文件,以便于composer更新

将包内`config/debugbar.php`文件复制到你的项目配置目录下, 修改后使用:

```
$provider = new Snowair\Debugbar\ServiceProvider('your-config-file-path');
```

### 多模块应用相关

我们认为以下习惯是良好的:

1. 缓存服务的命名一定含有`cache`
2. 数据库服务的命名一定含有`db`并且是以`db`开头或结尾

debugbar无需任何特殊设置即可支持符合以上习惯的多模块应用. 

假如你的服务命名习惯与众不同,则需要手动将缓存或数据库服务绑定到debugbar中, 手动绑定示例代码如下:

```
$di->set('my-db-2',function(...));
$di->set('huan-cun',function(...));

if ( $di->has('debugbar') ) {
    $debugbar = $di['debugbar'];
    $debugbar->attachDb('my-db-2');
    $debugbar->attachCache('huan-cun');
}
```

### 出现问题怎么办

1. 依次将配置文件中 `collectors`中的各项关闭, 直到问题不再出现, 从而确定是哪个collector的问题, 然后在git@osc 提 issue 反馈

2. 直接提 issue 反馈

### 截图


* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/message.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/timeline.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/exception.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/route.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/database.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/views.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/caches.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/config.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/session.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/request.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/stackdata.png)
