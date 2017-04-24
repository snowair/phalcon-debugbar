[![Latest Stable Version](https://poser.pugx.org/snowair/phalcon-debugbar/v/stable)](https://packagist.org/packages/snowair/phalcon-debugbar) 
[![Total Downloads](https://poser.pugx.org/snowair/phalcon-debugbar/downloads)](https://packagist.org/packages/snowair/phalcon-debugbar) 
[![Latest Unstable Version](https://poser.pugx.org/snowair/phalcon-debugbar/v/unstable)](https://packagist.org/packages/snowair/phalcon-debugbar) 
[![License](https://poser.pugx.org/snowair/phalcon-debugbar/license)](https://packagist.org/packages/snowair/phalcon-debugbar)

## Phalcon Debugbar

一个无侵入的[Phalcon Framework](https://github.com/phalcon/cphalcon)应用调试/分析工具条

## 功能特性

1. 常规请求调试信息收集
2. Ajax请求调试信息收集
3. Redirect请求调试信息
4. 调试信息持久化支持:本地file,MongoDB,ElasticSearch
5. 支持 多模块,单模块,微应用.
6. 数据按 sessionid 存储, 多人共用测试环境协作开发时调试数据互不影响.
7. debugbar工具条可不注入正常页面, 访问 `/_debugbar/open` 独立查看调试数据.
8. 集成 Whoops, 即使发生异常, 仍可正常收集到异常发生之前的所有调试数据.
9. 支持palcon 1.3.x,2.x,3.x, 支持 PHP5.5~7.1
 
### 支持的数据收集器

- **MessagesCollector**: 手动收集应用专门抛出的调试数据
- **TimeDataCollector**: 手动测量区间代码执行耗时信息
- **ExceptionsCollector**: 手动显示捕捉的异常信息
- **MemoryCollector**: 自动内存销消耗信息收集
- **QueryCollector**: 自动收集所有SQL查询信息, 每条SQL的执行时间, SELECT语句的EXPLAIN信息
    * 信息收集自 `db` 服务. 仅支持Phalcon自身的ORM系统
- **DoctrineCollector**: 自动收集所有SQL查询信息,每条SQL的执行时间
    * 信息收集自 `entityManager` 服务. 仅支持 Doctrine ORM.
- **RouteCollector**: 自动收集当前请求的路由信息: 路由设置, 即路由分析结果,以及路由触发执行的action代码体
    * 信息收集自 `router` 服务.
- **ViewCollector**:  自动收集视图渲染信息, 包括渲染的所有模板及渲染耗时,引擎类型及模板变量.
    * 信息收集自 `view` 服务.
- **PhalconRequestCollector**: 自动收集请求相关的全局数据: request headers, cookies, server variables, response headers, querys, post data,raw body
    * 信息收集自 `request` 服务.
- **ConfigCollector**: 自动显示配置信息
    * 信息收集自 `config` 服务.
- **SessionCollectior**: 收集session数据
    * 信息收集自 `session` 服务.
- **LogsCollectors**: 自动收集log信息, 支持 Phalcon 内置的log组件及Monolog组件
    * 信息收集自 `log` 服务.
- **CacheCollectors**: 自动收集缓存操作详情: 包括 saved,gets,incs,decs,failds 五种类型信息, 以及操作前后的数据详情.
    * 信息收集自 `cache` 服务.
- **SwiftMailCollector**: 邮件信息收集
    * 信息收集自 `mail` 服务.

## 快速开始

### composer

* 安装

    ```
    php composer.phar require --dev snowair/phalcon-debugbar
    ```
* 更新

    ```
    php composer.phar update snowair/phalcon-debugbar
    ```

### 修改 index.php

1. 将应用实例保存为app服务:

    ```
    // 先创建 $di实例
    $application = new Phalcon\Mvc\Application($di); // 将$di作为构造参数传入 Micro应用也一样: new Phalcon\Mvc\Micro($di);
    $di['app'] = $application; // 将应用实例保存到$di的app服务中
    ```

2. 在handle()方法前面的位置启动debugbar即可, 例如:

    ```
    (new Snowair\Debugbar\ServiceProvider())->start();
    // 在启动debugbar之后,立即handle应用.
    echo $application->handle()->getContent();
    ```
3. **可选**  启用Whoops, 修改index.php, 在启动debugbar之后,加入下面代码:
    ```
    (new \Snowair\Debugbar\Whoops\WhoopsServiceProvider($di));
    ```

### 修改权限控制代码

下面的acl控制代码适用于 INVO:

```
public function beforeDispatch(Event $event, Dispatcher $dispatcher)
    {
        $auth = $this->session->get('auth');
        if (!$auth){
            $role = 'Guests';
        } else {
            $role = 'Users';
        }

        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

        /* Debugbar start */
        $ns = $dispatcher->getNamespaceName();
        if ($ns=='Snowair\Debugbar\Controllers') {
            return true;
        }
        /* Debugbar end */

        $acl = $this->getAcl();
        $allowed = $acl->isAllowed($role, $controller, $action);
        if ($allowed != Acl::ALLOW) {
            $dispatcher->forward(array(
                'controller' => 'errors',
                'action'     => 'show401'
            ));
                        $this->session->destroy();
            return false;
        }
    }
```

### 数据持久化

每次请求的调试数据都可以被保存下了, 供你进行系统分析.

* 对于 **file** 驱动, 调试数据默认保存在 `Runtime/phalcon` 目录. 如果该目录不存在会自动创建. 你也可以在配置文件中指定其他目录.

* 对于 **mongodb** 驱动, 需要安装 **mongodb** 扩展, 以及mongodb phplib: `composer require mongodb/mongodb`

* 对于 **elastic** 驱动, 需要安装 phplib: `composer require elasticsearch/elasticsearch:some-version`

### 关于 baseUri

当心 baseUri 设置, 你的uri服务必须有正确的 baseUri设置. 然后:

* 如果你使用apache, 只需要按官方文档在baseUri的目录下增加相应的`.htaccess` 文件即可.

* 如果你使用的是nginx, 则需要正确配置location区块，例如:

    ```
        location @rewrite {
            # 把 'baseuri' 字符替换成你项目实际的 baseuri
            rewrite ^/baseuri/(.*)$ /baseuri/index.php?_url=/$1;
        }
    ```


## 技巧
    
### 使用外部的配置文件, 以便于composer更新

将包内`config/debugbar.php`文件复制到你的项目配置目录下, 修改后使用:

```
(new Snowair\Debugbar\ServiceProvider('your-debugbar-config-file-path'))->start();
```

### 手动发送消息到调试条

```
\PhalconDebug::startMeasure('start-1','how long');        // startMeasure($internal_sign_use_to_stop_measure, $label)
\PhalconDebug::addMeasurePoint('start');                  // measure the spent time from latest measurepoint to now.
\PhalconDebug::addMessage('this is a message', 'label');  // add a message using a custom label.
\PhalconDebug::info($var1,$var2, $var3, ...);  // add many messages once a time. See PSR-3 for other methods name.(debug,notice,warning,error,...)
\PhalconDebug::addMessageIfTrue('1 == "1"', 1=='1','custom_label'); // add message only when the second parameter is true
\PhalconDebug::addMessageIfTrue('will not show', 1=='0');
\PhalconDebug::addMessageIfFalse('1 != "0" ', 1=='0');       // add message only when the second parameter is false
\PhalconDebug::addMessageIfNull('condition is null', Null ); // add message only when the second parameter is NULL
\PhalconDebug::addMessageIfEmpty('condition is emtpy', $condtion ); // add message only when the second parameter is empty
\PhalconDebug::addMessageIfNotEmpty('condition is not emtpy', $condtion=[1] ); // add message only when the second parameter is not empty
\PhalconDebug::addException(new \Exception('oh , error'));
\PhalconDebug::addMeasurePoint('stop');
\PhalconDebug::stopMeasure('start-1');                    // stopMeasure($internal_sign_use_to_stop_measure)
```

### Volt 模板函数

```
addMessage
addMessageIfTrue
addMessageIfFalse
addMessageIfNull
addMessageIfEmpty
addMessageIfNotEmpty
addException
addMeasurePoint
startMeasure
stopMeasure
debug/info/notice/warning/error/emergency/critical
```

#### volt模板中发送消息示例

```
{{ debug( var1, var2 )}}
{{ info( var1, var2 )}}
{{ addMessageIfTrue('$var === true', var ) }}
```

### 多模块应用相关

我们认为以下习惯是良好的:

1. 缓存服务的命名一定含有`cache`
2. 数据库服务的命名一定含有`db`并且是以`db`开头或结尾
3. 多模块应用,可以使用 `/_debugbar/open?m=modulename` 打开模块的独立调试窗口

debugbar无需任何特殊设置即可支持符合以上习惯的多模块应用. 

假如你的服务命名习惯与众不同,则需要手动将缓存或数据库服务绑定到debugbar中, 手动绑定示例代码如下:

```
// service.php
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
