<?php

namespace Shiroi\ThinkLogViewer;

use Shiroi\ThinkLogViewer\channels\FileChannel;

class LogServer
{
    //配置信息
    protected array $config = [];

    //是否开启日志记录
    private bool $isOpen = true;

    //是否开启上锁
    private bool $isLock = false;

    //上锁的cookies密码
    private string $lock_cookies_name = 'log_view_name';

    //cookies失效时间(秒)
    private int $cookies_expires = 30 * 24 * 60 * 60;

    //上锁密码(默认上锁密码是123456)
    private string $lock_password = '123456';

    //上锁提示
    private string $lock_hint_content = "请输入验证码";

    //查看默认日志记录通道
    private string $default = 'file';

    //默认记录数组
    protected array $default_channel = [];

    //日志记录级别
    private array $level = [];

    //日志类型记录的通道
    private array $type_channel = [];

    //关闭全局日志写入
    private bool $close = false;

    //全局日志处理 支持闭包
    private ?object $processor = null;

    private int $total = 0;

    //日志通道列表
    private array $channels = [];

    public function __construct() {
        $this->loadConfig();
        $this->init();
        $this->loadParam();
        $this->isLock = env('log_view.is_lock', $this->isLock);
        $this->lock_password = env('log_view.lock_password', $this->lock_password);
    }

    public function index()
    {
        $this->lockView();
        if($this->isOpen) {
            switch ($this->default) {
                case 'file':
                    FileChannel::getInstance($this->default_channel)->view();
                    break;
                default:
            }
        }
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return bool
     */
    public function getIsOpen(): bool
    {
        return $this->isOpen;
    }

    /**
     * @return string
     */
    public function getDefault(): string
    {
        return $this->config['default']??$this->default;
    }

    /**
     * @return array
     */
    public function getDefaultChannel(): array
    {
        return $this->default_channel;
    }

    /**
     * @return array
     */
    public function getLevel(): array
    {
        return $this->level;
    }

    /**
     * @return array
     */
    public function getTypeChannel(): array
    {
        return $this->type_channel;
    }

    /**
     * @return bool
     */
    public function isClose(): bool
    {
        return $this->close;
    }

    /**
     * @return object|null
     */
    public function getProcessor(): ?object
    {
        return $this->processor;
    }

    /**
     * @return array
     */
    public function getChannels(): array
    {
        return $this->config['channels']??$this->channels;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param int $total
     */
    private function setTotal(int $total): void
    {
        $this->total = $total;
    }

    /**
     * @return void
     */
    private function loadConfig(): void
    {
        $this->config = config('log');
    }

    /**
     * @return void
     */
    private function init(): void
    {
        foreach ($this->config as $key => $value) {
            if(property_exists($this,$key)) $this->$key = $value;
        }
    }

    /**
     * @return void
     */
    private function loadParam(): void
    {
        $this->default_channel = $this->config['channels'][$this->getDefault()]??[];
        $this->isOpen = (bool)$this->default_channel['path'];
    }

    /**
     * lock view
     * @return void
     */
    private function lockView()
    {
        $lock_hint_content = env('log_view.lock_hint_content', $this->lock_hint_content);

        if($this->isLock && (cookie($this->lock_cookies_name) != base64_encode($this->lock_password))) {
            echo <<<EOF
            <script>
            function prom() {
                var input = prompt("{$lock_hint_content}", ""); //将输入的内容赋给变量 name ， 
                if (input) {
                    let exp = new Date();
                    let _keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";let output = "";let chr1, chr2, chr3, enc1, enc2, enc3, enc4;let i = 0;
                    while (i < input.length) {
                      chr1 = input.charCodeAt(i++);chr2 = input.charCodeAt(i++);chr3 = input.charCodeAt(i++);enc1 = chr1 >> 2;enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);enc4 = chr3 & 63;if (isNaN(chr2)) enc3 = enc4 = 64;else if (isNaN(chr3)) enc4 = 64; output = output + _keyStr.charAt(enc1) + _keyStr.charAt(enc2) + _keyStr.charAt(enc3) + _keyStr.charAt(enc4);
                    }
                    exp.setTime(exp.getTime() + parseInt({$this->cookies_expires})*1000);
                    document.cookie =  "{$this->lock_cookies_name}="+ escape (output) + ";expires=" + exp.toGMTString();
                    window.location.href = window.location.href.split('?')[0];
                }  
            }  
             prom();
            </script>
            EOF;exit();
        }
    }
}