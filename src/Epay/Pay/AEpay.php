<?php
namespace Sinta\LaravelEpay\Pay;

/**
 * Class AEpay
 * @package Sinta\LaravelEpay\Pay
 */
abstract class AEpay
{
    //通讯失败时返回的报文
    const TXN_ERROR_RESULT ="{\"errcode\":\"EPAY_29001\",\"errmsg\":\"[EPAY_29001]通讯错误或超时，交易未决\"}";

    ////系统异常时返回的报文
    const SYS_ERROR_RESULT = "{\"errcode\":\"EPAY_29099\",\"errmsg\":\"[EPAY_29099]未知错误，请检查是否为最新版本SDK或是否配置错误\"}";

    //对账文件下载，写入文件异常返回报文
    const FILE_ERROR_RESULT = "{\"errcode\":\"EPAY_29002\",\"errmsg\":\"[EPAY_29002]写入对账文件失败\"}";

    //验签失败
    const SIGN_ERROR_RESULT = "{\"errcode\":\"EPAY_29098\",\"errmsg\":\"[EPAY_29098]应答消息验签失败，交易未决\"}";

    //对账文件下载，下载成功返回报文
    const SUCCESS_RESULT = "{\"errcode\":\"EPAY_00000\",\"errmsg\":\"[EPAY_00000]下载成功\"}";

    protected $debug = false;

    /**
     * 设置
     *
     * @var
     */
    protected $config;


    /**
     * 签名类型
     *
     * @var array
     */
    private static $sign_types = array(
        'cib.epay.acquire.easypay.acctAuth' => 'SHA1',
        'cib.epay.acquire.easypay.quickAuthSMS' => 'RSA',
        'cib.epay.acquire.checkSms' => 'RSA',
        'cib.epay.acquire.easypay.cancelAuth' => 'SHA1',
        'cib.epay.acquire.easypay.acctAuth.query' => 'SHA1',
        'cib.epay.acquire.easypay' => 'RSA',
        'cib.epay.acquire.easypay.query' => 'SHA1',
        'cib.epay.acquire.easypay.refund' => 'RSA',
        'cib.epay.acquire.easypay.refund.query' => 'SHA1',
        'cib.epay.acquire.authAndPay' => 'RSA',
        'cib.epay.acquire.easypay.quickAuth' => 'RSA',

        'cib.epay.acquire.cashier.netPay' => 'SHA1',
        'cib.epay.acquire.cashier.quickNetPay' => 'SHA1',
        'cib.epay.acquire.cashier.query' => 'SHA1',
        'cib.epay.acquire.cashier.refund' => 'RSA',
        'cib.epay.acquire.cashier.refund.query' => 'SHA1',

        'cib.epay.payment.getMrch' => 'RSA',
        'cib.epay.payment.pay' => 'RSA',
        'cib.epay.payment.get' => 'RSA',

        'cib.epay.acquire.settleFile' => 'SHA1',
        'cib.epay.payment.receiptFile' => 'SHA1'
    );


    /**
     * 获取网关地址
     *
     * @return mixed
     */
     abstract public function getGate();


    /**
     *
     * AEpay constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->config = $config;
        $this->debug = isset($config['debug']) && $config['debug'] ? true: false;
    }


    /**
     * POST通讯模式通讯
     *
     * @param string	$url			接口URL
     * @param array		$param_array	post参数列表
     * @param string	$save_file_name	保存至该参数命名的文件（覆盖），为null时直接返回结果
     * @return mixed					响应内容
     */
    protected function postService($url,$param_array,$save_file_name)
    {
        if(array_key_exists('service',$param_array) &&
            array_key_exists($param_array['service'],self::$sign_types)){
            $param_array['sing_type'] = self::$sign_types[$param_array['service']];
        }
        //签名设置
        $param_array['mac'] = $this->Signature($param_array,$this->config['commKey'],
                        $this->config['mrch_cert'],$this ->config['mrch_cert_pwd']);

        $response = null;
        $response = self::getHttpPostResponse($url,$param_array,
            $this->debug? true: false,$save_file_name,
            $this->config['proxy_ip'],$this->config['proxy_port']);
        if(!$response){
            return self::SUCCESS_RESULT;
        }else{
            if(self::TXN_ERROR_RESULT !== $response &&
                self::SYS_ERROR_RESULT !== $response &&
                self::FILE_ERROR_RESULT !== $response &&
                self::SUCCESS_RESULT !== $response){
                if($this->config['needChkSign'] &&
                    !$this->verifyMac(json_decode($response, true),$this->config['commKey'],
                        $this->config['debug'] ? $this->config['epay_cert_test']:$this->config['cert_prod'])){//签名检测
                    return self::SIGN_ERROR_RESULT;
                }
            }
            return $response;
        }
    }


    /**
     * 生成跳转HTML页面方法
     * @param string $url				接口URL
     * @param array $param_array		参数列表
     * @return string					跳转页面html源代码
     */
    protected function redirectService($url, $param_array) {

        $param_array['mac'] = $this -> Signature($param_array, $this->config['commKey']);

        $html = '';
        $html .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">";
        $html .= "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><title>收付直通车跳转接口</title></head>";
        $html .= "<form id=\"epayredirect\" name=\"epayredirect\" action=\"{$url}\" method=\"post\">";

        foreach ($param_array as $k => $v) {
            $html .= "<input type=\"hidden\" name=\"{$k}\" value=\"{$v}\"/>";
        }

        $html .= "<input type=\"submit\" value=\"submit\" style=\"display:none;\"></form>";
        $html .= "<script>document.forms[\"epayredirect\"].submit();</script>";
        $html .= "<body></body></html>";

        return $html;
    }



    /**
     * 生成签名MAC字符串（包含SHA1算法和RSA算法）
     *
     * @param array $param_array    参数列表（若包含mac参数名，则忽略该项）
     * @param string $commkey         商户秘钥（加密算法为SHA1时使用，否则置null）
     * @param string $cert            商户证书（加密算法为RSA时使用，否则置null）
     * @param string $cert_pwd      商户证书密码（加密算法为RSA时使用）
     * @return string               MAC字符串
     */
    public static function Signature($param_array, $commkey = null, $cert = null, $cert_pwd = '123456')
    {

        ksort($param_array);
        reset($param_array);
        $signstr = '';
        foreach ($param_array as $k => $v) {

            if(strcasecmp($k, 'mac') == 0) continue;
            $signstr .= "{$k}={$v}&";
        }

        if(array_key_exists('sign_type', $param_array) && $param_array['sign_type'] === 'RSA') {
            $signstr = substr($signstr, 0, strlen($signstr) - 1);
            if (false !== ($keystore = file_get_contents($cert)) &&
                openssl_pkcs12_read($keystore, $cert_info, $cert_pwd) &&
                openssl_sign($signstr, $sign, $cert_info['pkey'], 'sha1WithRSAEncryption')) {
                return base64_encode($sign);
            } else {
                return 'SIGNATURE_RSA_CERT_ERROR';
            }
        } else {/* 默认SHA1方式 */
            $signstr .= $commkey;
            return strtoupper(sha1($signstr));
        }
    }


    /**
     * 验证服务器返回的信息中签名的正确性
     *
     * @param array		$param_array	参数列表（必须包含mac参数）
     * @param string	$commkey		商户秘钥
     * @param string	$cert			商户证书路径
     * @return boolean					true-验签通过，false-验签失败
     */
    public static function verifyMac($param_array, $commkey = null, $cert = null) {

        if(!array_key_exists('mac', $param_array) || !$param_array['mac'])
            return false;
        if(array_key_exists('sign_type', $param_array) && $param_array['sign_type'] === 'RSA') {
            ksort($param_array);
            reset($param_array);
            $signstr = '';
            foreach ($param_array as $k => $v) {

                if(strcasecmp($k, 'mac') == 0) continue;
                $signstr .= "{$k}={$v}&";
            }
            $signstr = substr($signstr, 0, strlen($signstr) - 1);

            $pubKey = openssl_pkey_get_public(file_get_contents($cert));
            $result = openssl_verify($signstr, base64_decode($param_array['mac']), $pubKey, 'sha1WithRSAEncryption');
            openssl_free_key($pubKey);
            return (1 === $result ? true : false);
        } else {		/* 默认SHA1方式 */
            $mac = self::Signature($param_array, $commkey);
            if(strcasecmp($mac, $param_array['mac']) == 0)
                return true;
            else
                return false;
        }
    }





    /**
     * 行号文件下载接口
     *
     * @param string $download_type		文件类型：01-行号文件
     * @param string $save_file_name	保存下载内容至以该变更为名的文件
     * @return string					当下载成功时，返回SUCCESS_RESULT常量值；当下载失败时，返回失败信息json字符串
     */
    public function dlFile($download_type, $save_file_name) {

        $param_array = array();

        $param_array['download_type']	= $download_type;

        $param_array['appid']		= $this -> epay_config['appid'];
        $param_array['service']		= 'cib.epay.acquire.download';
        $param_array['ver']			= '01';
        $param_array['timestamp']	= self::getDateTime();


        if($this->epay_config['isDevEnv'])
            $response = $this -> postService(EPay::GP_DEV_API, $param_array, $save_file_name);
        else
            $response = $this -> postService(EPay::GP_PROD_API, $param_array, $save_file_name);

        return $response;
    }

    /**
     * HTTP请求通讯器
     *
     * @param $url
     * @param $param_array
     * @param bool $skip_ssl_verify
     * @param null $save_file_name
     * @param null $proxy_ip
     * @param null $proxy_port
     * @return mixed
     */
    public static function getHttpPostResponse($url,$param_array,$skip_ssl_verify = false,
                                 $save_file_name = null, $proxy_ip = null, $proxy_port = null)
    {
        $curl = curl_init($url);
        if(!$skip_ssl_verify) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__).'/ca-bundle.crt');	// 信任CA证书相对路径，如果不是在这里，需要修改该变更
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        }

        if($proxy_ip) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy_ip);
            curl_setopt($curl, CURLOPT_PROXYPORT, $proxy_port);
        }

        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($param_array));
        $response = curl_exec($curl);
        $header = curl_getinfo($curl);
        curl_close($curl);

        if($header['http_code'] >= 300 || $header['http_code'] < 100){
            return self::TXN_ERROR_RESULT;
        }


        //文件下载，且成功
        if($save_file_name && $header['content_type'] === 'application/octet-stream') {
            if(false === file_put_contents($save_file_name, $response))
                return self::FILE_ERROR_RESULT;
            else
                return self::SUCCESS_RESULT;
        } else {		//返回json或下载失败
            return $response;
        }
    }


    /**
     * 获取当前系统日期时间
     *
     * @return string 当前日期时间，20150801010203格式
     */
    public static function getDateTime()
    {
        return date('YmdHis');
    }

    /**
     * 获取当前系统日期
     *
     * @return string
     */
    public static function getDate() {

        return date('Ymd');
    }


    /**
     * 获取服务器IP地址
     *
     * @return string	服务器IP地址
     */
    public static function getLocalIp()
    {
        if(isset($_ENV["HOSTNAME"]))
            $machineName = $_ENV["HOSTNAME"];
        else if(isset($_ENV["COMPUTERNAME"]))
            $machineName = $_ENV["COMPUTERNAME"];
        else $machineName = '';
        return gethostbyname($machineName);
    }

}