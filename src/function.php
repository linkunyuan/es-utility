<?php
namespace Linkunyuan\EsUtility;

use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\HttpClient\HttpClient;
use EasySwoole\I18N\I18N;
use EasySwoole\Utility\File;
use Linkunyuan\EsUtility\Classes\LamJwt;
use Linkunyuan\EsUtility\Classes\WeChatManager;

if ( ! function_exists('Linkunyuan\EsUtility\model')) {
	/**
	 * 实例化Model
	 * @param string    $name Model名称
	 * @return \App\Model\$model
	 */
	function model($name = '', $data = [])
	{
		//static $instance = [];
		$guid = $name = parse_name($name, 1);

//		if (isset($instance[$guid])) {
//			return $instance[$guid];
//		}

		$gameid = '';
		// 实例化XXX_gid模型
		if(strpos($name, ':'))
		{
			list($name, $gameid) = explode(':', $name);
		}

		$class = "\\App\\Model\\$name";
		$model = null;

		if (class_exists($class)) {
			try{
				$model = new $class($data, $gameid != '' ? parse_name($name,0, false) . "_$gameid" : '', $gameid);
			}
			catch (\Exception $e)
			{
				// TODO
				var_dump($e->getMessage());
			}
		} else {
			// TODO
			//throw new \Exception("模型不存在:$class");
		}
		//$instance[$guid] = $model;
		return $model;
	}
}

if ( ! function_exists('Linkunyuan\EsUtility\config')) {
	/**
	 * 获取和设置配置参数
	 * @param string|array  $name 参数名
	 * @param mixed         $value 参数值
	 * @return mixed
	 */
	function config($name = '', $value = null)
	{
		$Config = Config::getInstance();
		if (is_null($value) && is_string($name))
		{
			return $Config->getConf($name);
		} else {
			return $Config->setConf($name, $value);
		}
	}
}

if ( ! function_exists('Linkunyuan\EsUtility\trace')) {
	/**
	 * 记录日志信息
	 * @param string|array $log log信息 支持字符串和数组
	 * @param string $level 日志级别
	 * @param string $category 日志类型
	 * @return void|bool
	 */
	function trace($log = '', $level = 'info', $category = 'debug')
	{
		is_scalar($log) or $log = json_encode($log, JSON_UNESCAPED_UNICODE);
		return Logger::getInstance()->$level($log, $category);
	}
}

if ( ! function_exists('Linkunyuan\EsUtility\defer_redis')) {
	/**
	 * 返回redis句柄资源
	 * @param string $poolname 标识
	 * @param number $db 数据库编号
	 * @return \EasySwoole\Redis\Redis
	 */
	function defer_redis($poolname = '', $db = null)
	{
		$poolname = $poolname ? : config('REDIS.poolname');
		$db = is_numeric($db) ? $db : config('REDIS.db');
		// defer方式获取连接
		$Redis  = \EasySwoole\RedisPool\RedisPool::defer($poolname);
		$Redis->select($db); // 切换到指定序号
		return $Redis;
	}
}

if ( ! function_exists('Linkunyuan\EsUtility\parse_name')) {
	/**
	 * 字符串命名风格转换
	 * @param string $name 字符串
	 * @param integer $type 转换类型  0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
	 * @param bool $ucfirst 首字母是否大写（驼峰规则）
	 * @return string
	 */
	function parse_name($name, $type = 0, $ucfirst = true)
	{
		if ($type) {
			$name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
				return strtoupper($match[1]);
			}, $name);
			return $ucfirst ? ucfirst($name) : lcfirst($name);
		} else {
			return strtolower(trim(preg_replace('/[A-Z]/', '_\\0', $name), '_'));
		}
	}
}


if ( ! function_exists('Linkunyuan\EsUtility\array_merge_multi')) {
	/**
	 * 多维数组合并（支持多数组）
	 * @return array
	 */
	function array_merge_multi (...$args)
	{
		$array = [];
		foreach ( $args as $arg )
		{
			if ( is_array($arg) )
			{
				foreach ( $arg as $k => $v )
				{
					if ( is_array($v) )
					{
						$array[$k] = isset($array[$k]) ? $array[$k] : [];
						$array[$k] = array_merge_multi($array[$k], $v);
					} else {
						$array[$k] = $v;
					}
				}
			}
		}
		return $array;
	}
}

if ( ! function_exists('Linkunyuan\EsUtility\listdate')) {
	/**
	 * 返回两个日期之间的具体日期或月份
	 *
	 * @param string|int $beginday 开始日期，格式为Ymd或者Y-m-d
	 * @param string|int $endday 结束日期，格式为Ymd或者Y-m-d
	 * @param int $type 类型 1：日； 2：月； 3：季； 4：年
	 * @return array
	 */
	function listdate($beginday, $endday, $type = 2)
	{
		$dif = difdate($beginday, $endday, $type != 2);

		// 季
		if($type == 3)
		{
			// 开始的年份, 结束的年份
			$arry = [date('Y', strtotime($beginday)), date('Y', strtotime($endday))];
			// 开始的月份, 结束的月份
			$arrm = [date('m', strtotime($beginday)), date('m', strtotime($endday))];
			$arrym = [];

			$quarter = ['04','07',10,'01'];
			$come = false; // 入栈的标识
			$by = $arry[0]; // 开始的年份
			do
			{
				foreach($quarter as $k => $v)
				{
					if($arrm[0] < $v || $k == 3)
					{
						$come = true;
					}

					$key = substr($by, 2) . str_pad($k+1, 2, '0', STR_PAD_LEFT);

					// 下一年度
					if($k == 3)
					{
						++$by;
					}

					if($come)
					{
						$arr[$key] = $by . $v . '01'; // p1803=>strtotime(20181001)
					}
				}
			}while($by<=$arry[1]);
		}
		// 年
		elseif($type == 4)
		{
			$begintime = substr($beginday, 0, 4);
			for($i = 0; $i<=$dif; ++$i)
			{
				$arr[$begintime-1] = $begintime . '0101'; // p2018=>strtotime(20190101)
				++$begintime;
			}
		}
		else
		{
			// 日期 p180302=>strtotime(20180304)
			if($type === true || $type == 1)
			{
				$format = 'Y-m-d';
				$unit = 'day';
				$d = '';
			}
			// 月份 p1803=>strtotime(20180401)
			elseif($type === false || $type == 2)
			{
				$format = 'Y-m';
				$unit = 'month';
				$d = '01';
			}

			$begintime = strtotime(date($format, strtotime($beginday)));
			for($i = 0; $i<=$dif; ++$i)
			{
				$key = strtotime("+$i $unit" , $begintime);
				$format = str_replace('-','', $format);
				$arr[date(strtolower($format), $key-3600*24)] = date(ucfirst($format), $key) . $d;
			}
		}
		return $arr;
	}
}


if ( ! function_exists('Linkunyuan\EsUtility\difdate')) {
	/**
	 * 计算两个日期相差多少天或多少月
	 */
	function difdate($beginday, $endday, $d = false)
	{
		$beginstamp = strtotime($beginday);
		$endstamp = strtotime($endday);

		// 相差多少个月
		if( ! $d)
		{
			list($date_1['y'], $date_1['m']) = explode('-', date('Y-m', $beginstamp));
			list($date_2['y'], $date_2['m']) = explode('-', date('Y-m', $endstamp));
			return ($date_2['y'] - $date_1['y']) * 12 + $date_2['m'] - $date_1['m'];
		}

		// 相差多少天
		return ceil( ($endstamp-$beginstamp) / (3600*24) );
	}
}


if ( ! function_exists('Linkunyuan\EsUtility\verify_token')) {
	/**
	 * 验证jwt并读取用户信息
	 */
	function verify_token($orgs = [], $header = [], $key = 'uid')
	{
		$token = $header['HTTP_TOKEN'] ?? ($header['token'][0] ??'');
		if( ! $token)
		{
			// invalid_verify_token
			return ['INVERTOKEN'=>1, 'code'=>401, 'msg'=>'缺少token'];
		}
		// 验证JWT
		$jwt = LamJwt::verifyToken($token);
		if($jwt['status'] != 1 || ! isset($jwt['data'][$key]) ||  ! isset($orgs[$key]) ||  $jwt['data'][$key] != $orgs[$key])
		{
			return ['INVERTOKEN'=>1, 'code'=>400, 'msg'=>'jwt有误'];
		}

		$jwt['data']['token'] = $token;

		return $jwt['data'];
	}
}


if ( ! function_exists('Linkunyuan\EsUtility\ip')) {
	/**
	 * 验证jwt并读取用户信息
	 */
	function ip($request = null)
	{
		if($request)
		{
			$arr = $request->getHeaders();
			// IP1,IP2
			$iparr = ! empty($arr['x-forwarded-for'][0]) ? $arr['x-forwarded-for'][0] : (
				! empty($arr['x-real-ip'][0]) ? $arr['x-real-ip'][0] : (
					$request->getServerParams()['remote_addr'] ?? ''
				)
			);
		}
		else
		{
			// IP1,IP2
			$iparr = ! empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (
				! empty($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : (
					$_SERVER['REMOTE_ADDR'] ?? ''
				)
			);
		}

		return explode(',', $iparr)[0];

		/*
		 *
		$request->getHeaders()如下
		Array
		(
			[connection] => Array ([0] => keep-alive)
			[x-real-ip] => Array ([0] => 172.28.48.1)
		    [x-forwarded-for] => Array ([0] => 172.28.48.1)
			[host] => Array	([0] => 127.0.0.1:8503)
			[content-length] => Array ([0] => 148)
			[accept] => Array ([0] => application/json)
			[cross-request-open-sign] => Array([0] => 1)
			[user-agent] => Array ([0] => Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36)
		    [token] => Array([0] => ZX2KaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SmxlSEFpT2pFNU1UazBPVGN4T0RNc0luTjFZaUk2SWlJc0ltNWlaaUk2TVRZd016STNNekU0TXl3aVlYVmtJam9pSWl3aWFXRjBJam94TmpBek1qY3pNVGd6TENKcWRHa2lPaUkxWmprd01ERmtaalpsTWprNElpd2lhWE56SWpvaUlpd2ljM1JoZEhWeklqb3hMQ0prWVhSaElqcDdJblZwWkNJNklqRWlMQ0oxYzJWeWJtRnRaU0k2SW14aGJYTnZiaUlzSW1kaGJXVnBaQ0k2SWpBaWZYMC5FcE5fN0gwNWtlX2RQWTNJWmxFMmxUQUtoSi1vdmVHRVgxQ2duMGNQdzNJ)
			[content-type] => Array([0] => application/x-www-form-urlencoded)
			[origin] => Array	([0] => chrome-extension://bbddljjploajjcembfomccpkbnmfapcj)
			[accept-encoding] => Array	([0] => gzip, deflate)
			[accept-language] => Array (	[0] => zh-CN,zh;q=0.9)
		)

		$_SERVER 如下
		Array
		(
			[HTTP_CONNECTION] => keep-alive
			[HTTP_X_REAL_IP] => 172.19.160.1
			[HTTP_X_FORWARDED_FOR] => 172.19.160.1
			[HTTP_HOST] => api-pay.hk.com
			[HTTP_CONTENT_LENGTH] => 148
			[HTTP_ACCEPT] => application/json
			[HTTP_CROSS_REQUEST_OPEN_SIGN] => 1
			[HTTP_USER_AGENT] => Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4302.0 Safari/537.36
			[HTTP_TOKEN] => ZXlKaGJHX2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SmxlSEFpT2pFNU1UazBPVGN4T0RNc0luTjFZaUk2SWlJc0ltNWlaaUk2TVRZd016STNNekU0TXl3aVlYVmtJam9pSWl3aWFXRjBJam94TmpBek1qY3pNVGd6TENKcWRHa2lPaUkxWmprd01ERmtaalpsTWprNElpd2lhWE56SWpvaUlpd2ljM1JoZEhWeklqb3hMQ0prWVhSaElqcDdJblZwWkNJNklqRWlMQ0oxYzJWeWJtRnRaU0k2SW14aGJYTnZiaUlzSW1kaGJXVnBaQ0k2SWpBaWZYMC5FcE5fN0gwNWtlX2RQWTNJWmxFMmxUQUtoSi1vdmVHRVgxQ2duMGNQdzNJ
			[HTTP_CONTENT_TYPE] => application/x-www-form-urlencoded
			[HTTP_ORIGIN] => chrome-extension://jlclmgonaaejmkhcknfafaflodkfjcdd
			[HTTP_ACCEPT_ENCODING] => gzip, deflate
			[HTTP_ACCEPT_LANGUAGE] => zh-CN,zh;q=0.9
			[REQUEST_METHOD] => POST
			[REQUEST_URI] => /v1/orders/create
			[PATH_INFO] => /v1/orders/create
			[REQUEST_TIME] => 1609377238
			[REQUEST_TIME_FLOAT] => 1609377238.751
			[SERVER_PROTOCOL] => HTTP/1.1
			[SERVER_PORT] => 8503
			[REMOTE_PORT] => 37982
			[REMOTE_ADDR] => 127.0.0.1
			[MASTER_TIME] => 1609377238
		)

		*/
	}
}

if ( ! function_exists('Linkunyuan\EsUtility\lang')) {
	function lang($const = '')
	{
		return I18N::getInstance()->translate($const);
	}
}


if ( ! function_exists('Linkunyuan\EsUtility\wechatWarning')) {
    /**
     * 发送微信报警
     * @param string $msg
     * @param string $file
     * @param int $line
     */
    function wechatWarning($msg, $file = '', $line = 0)
    {
        $time = time();
        $strId = md5($file);
        $chkFile = config('LOG_DIR') . '/wechat/checktime.log';
        File::touchFile($chkFile, false);
        $content = file_get_contents($chkFile);
        $arr = json_decode($content, true);
        if ($arr) {
            $last = $arr[$strId]['time'] ?? '';
            $limit = config('wechat.err_limit_time') * 60;
            if ($last && $limit && $last > $time - $limit) {
                return;
            }
        }
        $arr[$strId]['time'] = $time;
        file_put_contents($chkFile, json_encode($arr));

        // 为避免绝对路径太长，最多取3级
        $path = implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $file), -3));

        $tempData = [
            'first' => [
                'value' => APP_NAME . "程序发生错误：第{$line}行",
                'color' => '#FF0000'
            ],
            'keyword1' => [
                'value' => "相关文件： {$path}",
                'color' => '#FF0000'
            ],
            'keyword2' => [
                'value' => "相关内容：{$msg}",
                'color' => '#FF0000'
            ],
            'keyword3' => date('Y年m月d日 H:i:s'),
            'remark' => '查看详情'
        ];;
        WeChatManager::getInstance()->sendTemplateMessage($tempData, false, config('wechat.template.warning'));
    }
}

if ( ! function_exists('Linkunyuan\EsUtility\wechatNotice')) {
    /**
     * 发送微信通知
     * @param string $title
     * @param string $content
     */
    function wechatNotice($title = '', $content = '')
    {
        $tempData = [
            'first' => [
                'value' => APP_NAME . $title,
                'color' => '#32CD32'
            ],
            'keyword1' => [
                'value' => $content,
                'color' => '#32CD32'
            ],
            'keyword3' => date('Y年m月d日 H:i:s'),
            'remark' => '查看详情'
        ];;
        WeChatManager::getInstance()->sendTemplateMessage($tempData, false, config('wechat.template.notice'));
    }
}


if ( ! function_exists('Linkunyuan\EsUtility\sendDingTalk')) {
    /**
     * 发送钉钉机器人消息
     * @doc https://developers.dingtalk.com/document/app/custom-robot-access?spm=ding_open_doc.document.0.0.6d9d28e1eEU9Jd#topic-2026027
     */
    function sendDingTalk($content = '')
    {
        $url = config('dingtalk.url');
        $secret = config('dingtalk.sign_key');

        // 签名 &timestamp=XXX&sign=XXX
        $timestamp = time() * 1000;

        $data = json_encode([
            'msgtype' => 'text',
            'text' => [
                'content' => $content
            ],
            'at' => [
                'isAtAll' => true // @所有人
            ]
        ]);

        $sign = utf8_encode(urlencode(base64_encode(hash_hmac('sha256', $timestamp . "\n" . $secret, $secret, true))));

        $url .= "&timestamp={$timestamp}&sign={$sign}";

        $client = new HttpClient($url);

        // 支持文本 (text)、链接 (link)、markdown(markdown)、ActionCard、FeedCard消息类型

        $response = $client->postJson($data);
        $json = json_decode($response->getBody(), true);

        if ($json['errcode'] !== 0)
        {
            trace("钉钉消息发送失败：data={$data},response={$response}", 'error');
        }
    }
}
