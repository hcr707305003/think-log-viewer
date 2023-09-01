# think-log-viewer
thinkphp6 log日志的视图扩展包

### 页面展示
![image](https://s3.bmp.ovh/imgs/2022/06/26/3385b1106dbd178f.png)

### 1.配置路由
~~~
// config\log.php 文件
return [
    // 默认日志记录通道
    'default'      => env('log.channel', 'file'),
    // 日志记录级别
    'level'        => ['error','notice','info','debug'],
    // 日志类型记录的通道 ['error'=>'email',...]
    'type_channel' => [],
    // 关闭全局日志写入
    'close'        => false,
    // 全局日志处理 支持闭包
    'processor'    => null,

    // 日志通道列表
    'channels'     => [
        'file' => [
            // 日志记录方式
            'type'           => 'File',
            // 日志保存目录
            'path'           => 'log',
            // 单文件日志写入
            'single'         => false,
            // 独立日志级别
            'apart_level'    => [],
            // 最大日志文件数量
            'max_files'      => 0,
            // 使用JSON格式记录
            'json'           => false,
            // 日志处理
            'processor'      => null,
            // 关闭通道日志写入
            'close'          => false,
            // 日志输出格式化
            'format'         => '[%s][%s] %s',
            // 日志输出的时间格式
            'time_format'    =>  'Y-m-d H:i:s',
            // 是否实时写入
            'realtime_write' => false,
        ],
        // 其它日志通道配置
    ],

];


// route\app.php 文件
Route::get('log_view', "\Shiroi\ThinkLogViewer\LogServer@index");
~~~

### 2.运行thinkphp服务
~~~ 
php think run
~~~


### 3.访问浏览器 `http://127.0.0.1:8000/log_view`即可