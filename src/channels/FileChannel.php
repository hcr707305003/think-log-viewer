<?php

namespace Shiroi\ThinkLogViewer\channels;

use Shiroi\ThinkLogViewer\interfaces\DefaultLogMethodInterface;

/**
 * User: Shiroi
 * EMail: 707305003@qq.com
 */
class FileChannel extends BaseChannel implements DefaultLogMethodInterface
{
    protected static ?FileChannel $file = null;

    private array $config;

    protected string $log_path;

    protected array $param = [];

    protected array $all_log = [];

    protected array $all_log_files = [];

    protected string $choose_file = "全部文件";

    protected int $fileSize = 0;

    protected int $total = 0;

    protected int $sliceSize = 300;

    protected int $defaultReadSize = 10000;

    //字节流处理日志信息不全问题
    protected string $startLog = "";

    //RFC 5424 规范中的所有日志级别
    protected array $level = [
        'emergency',
        'alert',
        'critical',
        'sql',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    public static function getInstance($channel): ?FileChannel
    {
        if(self::$file == null) {
            self::$file = new FileChannel($channel);
        }
        return self::$file;
    }


    public function __construct($channel)
    {
        $this->config = $channel;
        $this->log_path = public_path($this->config['path']);
        $this->all_log = $this->getDirs($this->log_path);
        foreach ($this->all_log as $app_path => $logs) {
            foreach ($logs as $log) {
                if($log_file = input('file')) {
                    if("{$app_path}/$log" == $log_file) {
                        $this->choose_file = $app_path . DIRECTORY_SEPARATOR . $log;
                        $this->all_log_files[$app_path . pathinfo($log)['filename']] = $this->log_path . $app_path . DIRECTORY_SEPARATOR . $log;
                    }
                } else {
                    $this->all_log_files[$app_path . pathinfo($log)['filename']] = $this->log_path . $app_path . DIRECTORY_SEPARATOR . $log;
                }
            }
        }
        $this->loadParam();
    }


    public function view()
    {
        if(mb_strlen($search = request()->param('search', '')) <= 100) {
            $all_data = $this
                ->setPage(input('page', 1))
                ->setLimit(input('limit', 20))
                ->setOrder([
                    'create_time' => 'desc'
                ])
                ->index(preg_quote($search, '/'));
        }
        include_once __DIR__."/../view/index.php";
    }

    public function index($search = ''): array
    {
        //设置默认内容
        $content = '';
        //获取所有log文件
        $logs = [];
        //分页获取的数据
        $arr = [];
        //第一层排序
        foreach ($this->all_log_files as $key => $log) {
            $content = file_get_contents($log);
            //存在则加入
            $logs[$key] = $log;
            //计算文件大小
//            $this->fileSize += filesize($log);
            $this->total += substr_count($content, "\n");
        }

        //排序
        if((isset($this->getOrder()['create_time'])) && ($this->getOrder()['create_time'] == 'desc')) {
            //排序定义
            $order = 'desc';
            //日志排序
            krsort($logs);
        } else {
            //排序定义
            $order = 'asc';
            //日志排序
            ksort($logs);
        }

        //生成where语句
        $whereString = $this->buildWhere($search);
        //跳过的条数
        $skipLimit = $this->getPage() == 1? 0: (($this->getPage() - 1) * $this->getLimit());
        //展示的条数
        $showLimit = $this->getLimit();
        //遍历所有log
        foreach ($logs as $log) {
            //显示页数为0则跳出循环
            if ($showLimit <= 0) break;
            if($order == 'desc') {
                $this->tailCustom($log, $whereString, $skipLimit, $showLimit, $arr);
            } else {
                $this->headCustom($log, $whereString, $skipLimit, $showLimit, $arr);
            }
        }

        return [
            'data' => $this->generateLogData($arr),
            'total' => $this->total,
            'page'=> $this->getPage(),
            'limit' => $this->getLimit(),
            'last_page' => intval(ceil($this->total / $this->getLimit()))
        ];
    }

    public function getLevel($data): string
    {
        return in_array(($level = $data['level']??'info'),$this->level)?$level:'info';
    }

    protected function headCustom($filepath, $whereString, &$skipLimit, &$showLimit, &$arr) {
        $fp = fopen($filepath, "r");
        while (!feof($fp)) {
            //正则匹配
            preg_match_all($whereString, $this->jointLog(stream_get_line($fp, $this->defaultReadSize, "")), $match_content);
            //没有则返回空
            $logList = reset($match_content);
            //空则直接跳出循环
            if(!$logList) continue;
            //日志条数
            $logLimit = count($logList);
//                dump("展示的条数：{$showLimit},跳过的条数：{$skipLimit},日志的条数：{$logLimit}");
            //跳过条数
            if(($skipLimit - $logLimit) >= 0) {
                //大于等于0则改变值
                $skipLimit -= $logLimit;
            } else {
                //小于0则获取条数
                if(($showLimit - ($logLimit - $skipLimit)) <= 0) {
                    //展示条数
                    $arr = array_merge($arr,array_slice($logList, $skipLimit, $showLimit));
                    //设置显示条数为0
                    $showLimit = 0;
                    //跳出循环
                    break;
                } else {
                    //减去显示条数
                    $showLimit -= ($getLimit = ($logLimit - $skipLimit));
                    //获取条数
                    $arr = array_merge($arr, array_slice($logList, $skipLimit, $getLimit));
                }
                //设置跳过的条数为0
                $skipLimit = 0;
            }
        }
    }

    protected function tailCustom($filepath, $whereString, &$skipLimit, &$showLimit, &$arr) {
        //打开文件
        $f = @fopen($filepath, "r");
        //设置文件流为尾部读起
        fseek($f, -1, SEEK_END);
        //获取文件尺寸
        $fileSize = ftell($f);
        //设置开始初始值
        $start = 1;
        //设置log为空
        $this->startLog = "";
        while ($fileSize > 0) {
            //切片读取
            fseek($f, -min($fileSize, $this->defaultReadSize), SEEK_CUR);
            $chunk = fread($f, min($fileSize, $this->defaultReadSize));
            //正则匹配
            preg_match_all($whereString, $this->jointLog($chunk . (($start == 1) ? "\n": ""), 'end'), $match_content);
            //没有则返回空
            $logList = reset($match_content);
            //空则直接跳出循环
            if($logList) {
                //设置倒叙
                $logList = array_reverse($logList);
                //日志条数
                $logLimit = count($logList);
                //跳过条数
                if(($skipLimit - $logLimit) >= 0) {
                    //大于等于0则改变值
                    $skipLimit -= $logLimit;
                } else {
                    //小于0则获取条数
                    if(($showLimit - ($logLimit - $skipLimit)) <= 0) {
                        //展示条数
                        $arr = array_merge($arr,array_slice($logList, $skipLimit, $showLimit));
                        //设置显示条数为0
                        $showLimit = 0;
                        //跳出循环
                        break;
                    } else {
                        //减去显示条数
                        $showLimit -= ($getLimit = ($logLimit - $skipLimit));
                        //获取条数
                        $arr = array_merge($arr, array_slice($logList, $skipLimit, $getLimit));
                    }
                    //设置跳过的条数为0
                    $skipLimit = 0;
                }
            }
            //自增
            $start++;
            //减去长度
            $fileSize = $fileSize - $this->defaultReadSize;
            //跳转
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
        }
    }

    protected function generateLogData($data): array
    {
        $newLogData = [];
        foreach ($data as $k => $v) {
            if(preg_match("/\[([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})\]\[debug\] (.*?)$/i",$v,$match)) {
                $newLogData[$k]['level'] = 'debug';
            } elseif (preg_match("/\[([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})\]\[info\] (.*?)$/i",$v,$match)) {
                $newLogData[$k]['level'] = 'info';
            } elseif (preg_match("/\[([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})\]\[warning\] (.*?)$/i",$v,$match)) {
                $newLogData[$k]['level'] = 'warning';
            } elseif (preg_match("/\[([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})\]\[error\] (.*?)$/i",$v,$match)) {
                $newLogData[$k]['level'] = 'error';
            } elseif (preg_match("/\[([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})\]\[sql\] (.*?)$/i",$v,$match)) {
                $newLogData[$k]['level'] = 'sql';
            } elseif (preg_match("/\[([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})\]\[notice\] (.*?)$/i",$v,$match)) {
                $newLogData[$k]['level'] = 'notice';
            } else {
                $newLogData[$k]['level'] = 'all';
            }
            $newLogData[$k]['date'] = $match[1] ?? '时间获取失败';
            $newLogData[$k]['content'] = $match[2] ?? $v;
        }
        return $newLogData;
    }

    protected function jointLog($log, $type = 'start')
    {
        if($this->startLog) {
            $log = $type == 'start' ? $this->startLog . $log: $log . $this->startLog;
        }
        //前置日志
        if($type == 'start') {
            $this->startLog = (string)substr($log, strripos($log,"\n") + 1);
        } else {
            $this->startLog = (string)substr($log, 0, stripos($log,"\n") + 1);
        }
        return $log;
    }

    protected function buildWhere($queryString): string
    {
        //get level
        $level = input('level', '', 'strval');
        //generate match statement
        return "/\[[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\]\[{$level}.*\].*?{$queryString}.*?\n/i";
    }

    protected function getDirs(string $dir): array
    {
        $files = array();
        if(file_exists($dir)) if ( $handle = opendir($dir) ) {
            while ( ($file = readdir($handle)) !== false ) {
                if ( $file != ".." && $file != "." ) {
                    if ( is_dir($dir . "/" . $file) ) {
                        $files[$file] = $this->getDirs($dir . "/" . $file);
                    } else {
                        $files[] = $file;
                    }
                }
            }
            closedir($handle);
        }
        return $files;
    }

    protected function loadParam() {
        $this->param = array_merge(request()->get(),['page' => $this->getPage()]);
    }
}