## Laravel Debugbar

这个扩展包将 [PHP Debug Bar](http://phpdebugbar.com/) 与  [Phalcon FrameWork](http://phalconphp.com) 集成在了一起.
 
要感谢 laravel-debugbar, 我从中得到了启发, 使用了其中的一些代码, 经过几天夜以继日的工作, PhpDebugbar 终于可以用在Phalcon项目上了!

我在 Mac/PHP5.6/Phalcon 1.3.4 之下开发, 时间关系, 还为其他环境下的全面测试, 如果有问题, 欢迎提Issue或者Pull Reqeust. 

![Screenshot](https://cloud.githubusercontent.com/assets/973269/4270452/740c8c8c-3ccb-11e4-8d9a-5a9e64f19351.png)

注意: 这是一个开发辅助扩展, 切勿部署生产环境. 

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
 - SwiftMailCollector 收集邮件发送信息

## 安装

### composer 安装

```
php composer.phar require-dev snowair/phalcon-debugbar
```


### 手动安装

在你的loader注册代码区域, 将debugbar的命名空间注册

```
$loader = new \Phalcon\Loader();
$loader->registerNamespaces(array(
	'Snowair\Debugbar' => 'Path-To-PhalconDebugbar',  
));
$loader->register();
```

### 设置

1. 将应用实例保存为app服务

    ```
    $di = new Phalcon\DI\FactoryDefault();
    $application = new Phalcon\Mvc\Application($di); // 将$di作为构造参数传入
    $di['app'] = $application; // 将应用实例保存到$di的app服务中
    ```

2. 在合适的位置插入下面的代码, 通常应该在所有服务都注册完以后的位置. 对于多模块应用, 应该放在所有公共服务都注册以后的位置.

    ```
    $provider = new Snowair\Debugbar\ServiceProvider();
    $provider->register();
    $provider->boot();
    ```
    
3. 使用自己的debugbar配置文件,将包内`config/debugbar.php`文件复制到你的项目配置目录下, 修改后使用:

    ```
    $provider = new Snowair\Debugbar\ServiceProvider('your-config-file-path');
    ```

4. 对于多模块应, 如果每个模块都定义了自己独立的 db服务和 view 服务. 则需要在模块服务的注册区域参考以下代码将模块的
db 和 view 服务添加到debugbar中.

    ```
    if ( $di->has('debugbar') ) {
        /** @var PhalconDebugbar $debugbar */
        $debugbar = $di['debugbar'];
        $debugbar->attachDb($di['db']);
        $debugbar->attachView($di['view']);
    }
    ```

5. 如果你的应用用到多个数据库连接, 例如读写分离. 则可以将每个数据库服务都添加到debugbar:

    ```
    $debugbar->attachDb($di['dbRead']);
    $debugbar->attachDb($di['dbWrite']);
    $debugbar->attachDb($di['anyOtherDb']);
    ```

6. 你也可以选择在运行时关闭或开启debugbar

    ```
    $debugbar->enable();
    $debugbar->disable();
    ```