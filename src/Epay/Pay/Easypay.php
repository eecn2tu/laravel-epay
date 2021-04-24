<?php
namespace Sinta\LaravelEpay\Pay;


use Sinta\LaravelEpay\Util;

class Easypay extends AEpay
{
    // 快捷支付API地址，测试环境地址可根据需要修改
    const GATE_API = "https://pay.cib.com.cn/acquire/easypay.do";
    const DEV_GATE_API = "https://3gtest.cib.com.cn:37031/acquire/easypay.do";

    public function getGate()
    {
        return $this->debug ? self::DEV_GATE_API : self::GATE_API;
    }


    /**
     * 快捷支付交易接口
     * @param string $order_no		订单号
     * @param string $order_amount	金额，单位元，两位小数，例：8.00
     * @param string $order_title	订单标题
     * @param string $order_desc	订单描述
     * @param string $card_no		支付卡号
     * @return string				json格式结果，返回结果包含字段请参看收付直通车代收接口文档
     */
    public function pay($order_no, $order_amount, $order_title, $order_desc, $card_no)
    {
        $param_array = [];

        $param_array['order_no']	= $order_no;
        $param_array['order_amount']= $order_amount;
        $param_array['order_title']	= $order_title;
        $param_array['order_desc']	= $order_desc;
        $param_array['card_no']		= $card_no;


        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.acquire.easypay';
        $param_array['ver']			= '01';
        $param_array['sub_mrch']	= $this->config['sub_mrch'];
        $param_array['cur']			= 'CNY';
        $param_array['order_time']	= self::getDateTime();
        $param_array['order_ip']	= self::getLocalIp();
        $param_array['timestamp']	= self::getDateTime();

        $this->postService($this->getGate(),$param_array,null);
    }


    /**
     * 付交易查询接口
     *
     * @param $order_no
     * @param null $order_date
     */
    public function query($order_no,$order_date = null)
    {
        $param_array = [];
        $param_array['order_no']	= $order_no;
        $param_array['order_date']	= $order_date ? $order_date : self::getDate();

        $param_array['service']		= 'cib.epay.acquire.easypay.query';
        $param_array['ver']			= '02';
        $param_array['timestamp']	= self::getDateTime();

        $this->postService($this->getGate(),$param_array,null);
    }


    /**
     * 支付退款交易接口
     *
     * @param string $order_no  待退款订单号
     * @param string $order_date 订单下单日期，格式yyyyMMdd
     * @param string $order_amount 退款金额（不能大于原订单金额）
     *
     * @return string				json格式结果，返回结果包含字段请参看收付直通车代收接口文档
     */
    public function refund($order_no,$order_date,$order_amount)
    {
        $param_array = [];

        $param_array['order_no']	= $order_no;
        $param_array['order_date']	= $order_date;
        $param_array['order_amount']= $order_amount;

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.acquire.easypay.refund';
        $param_array['ver']			= '02';
        $param_array['timestamp']	= self::getDateTime();

        $this->postService($this->getGate(),$param_array,null);
    }

    /**
     * 支付退款交易结果查询接口
     *
     * @param string $order_no          退款的订单号
     * @param string null $order_date   订单日期，格式yyyyMMdd，为null时使用当前日期
     * @return string				json格式结果，返回结果包含字段请参看收付直通车代收接口文档
     */
    public function refundQuery($order_no,$order_date = null)
    {
        $param_array = [];

        $param_array['order_no']	= $order_no;
        $param_array['order_date']	= $order_date ? $order_date : Util::getDate();

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.acquire.easypay.refund.query';
        $param_array['ver']			= '01';
        $param_array['timestamp']	= self::getDateTime();

        $this->postService($this->getGate(),$param_array,null);
    }

    /**
     * 无绑定账户快捷支付跳转页面生成接口<br />
     *
     * @param $order_no
     * @param $order_amount
     * @param $order_title
     * @param $order_desc
     * @param $remote_ip
     * @param null $bank_no
     * @param null $acct_type
     * @param null $card_no
     * @param null $user_name
     * @param null $cert_no
     * @param null $card_phone
     * @param null $expireDate
     * @param null $cvn
     * @return mixed
     */
    public function authPay($order_no, $order_amount, $order_title, $order_desc, $remote_ip,
                              $bank_no = null, $acct_type = null, $card_no = null, $user_name = null,
                              $cert_no = null, $card_phone = null, $expireDate = null, $cvn = null)
    {

        $param_array = array();

        $param_array['order_no']	= $order_no;
        $param_array['order_amount']= $order_amount;
        $param_array['order_title']	= $order_title;
        $param_array['order_desc']	= $order_desc;
        $param_array['order_ip']	= $remote_ip;

        if($bank_no !== null) $param_array['bank_no'] = $bank_no;
        if($acct_type !== null) $param_array['acct_type'] = $acct_type;
        if($card_no !== null) $param_array['card_no'] = $card_no;
        if($user_name !== null) $param_array['user_name'] = $user_name;
        if($cert_no !== null) {
            $param_array['cert_no'] = $cert_no;
            $param_array['cert_type'] = '0';
        }
        if($card_phone !== null) $param_array['card_phone'] = $card_phone;
        if($expireDate !== null) $param_array['expireDate'] = $expireDate;
        if($cvn !== null) $param_array['cvn'] = $cvn;

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.acquire.authAndPay';
        $param_array['ver']			= '01';
        $param_array['sub_mrch']	= $this->config['sub_mrch'];
        $param_array['cur']			= 'CNY';
        $param_array['order_time']	= self::getDateTime();
        $param_array['timestamp']	= self::getDateTime();

        return $this->redirectService($this->getGate(),$param_array);
    }


    /**
     * 快捷支付账户解绑接口
     * @param string $card_no		账号
     * @return string				json格式结果，返回结果包含字段请参看收付直通车代收接口文档
     */
    public function authCancel($card_no) {

        $param_array = array();

        $param_array['card_no']		= $card_no;

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.acquire.easypay.cancelAuth';
        $param_array['ver']			= '01';
        $param_array['timestamp']	= self::getDateTime();

        $this->postService($this->getGate(),$param_array,null);
    }


    /**
     * 快捷认证短信验证码确认接口
     * @param string $trac_no		发起同步认证时的商户跟踪号
     * @param string $sms_code		6位数字短信验证码
     * @return string				json格式结果，返回结果包含字段请参看收付直通车代收接口文档
     */
    public function authCheckSms($trac_no, $sms_code) {

        $param_array = array();

        $param_array['trac_no']		= $trac_no;
        $param_array['sms_code']	= $sms_code;

        $param_array['appid']		= $this-> config['appid'];
        $param_array['service']		= 'cib.epay.acquire.checkSms';
        $param_array['ver']			= '01';
        $param_array['timestamp']	= self::getDateTime();

        $this->postService($this->getGate(),$param_array,null);
    }


    /**
     * 快捷支付认证接口（同步接口，需短信确认）
     * @param string $trac_no		商户跟踪号
     * @param string $acct_type		卡类型：0-储蓄卡,1-信用卡
     * @param string $bank_no		人行联网行号
     * @param string $card_no		账号
     * @param string $user_name		姓名
     * @param string $cert_no		证件号码
     * @param string $card_phone	联系电话
     * @param string $expireDate	信用卡有效期（仅信用卡时必输，格式MMYY）
     * @param string $cvn			信用卡CVN（仅信用卡时必输）
     * @return	string				json格式结果，返回结果包含字段请参看收付直通车代收接口文档
     */
    public function authSyncWithSms($trac_no, $acct_type, $bank_no, $card_no, $user_name,
                                      $cert_no, $card_phone, $expireDate = null, $cvn = null)
    {

        $param_array = array();

        $param_array['trac_no']		= $trac_no;
        $param_array['acct_type']	= $acct_type;
        $param_array['bank_no']		= $bank_no;
        $param_array['card_no']		= $card_no;
        $param_array['user_name']	= $user_name;
        $param_array['cert_no']		= $cert_no;
        $param_array['card_phone']	= $card_phone;

        if($expireDate !== null)
            $param_array['expireDate']	= $expireDate;
        if($cvn !== null)
            $param_array['cvn']			= $cvn;

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.acquire.easypay.quickAuthSMS';
        $param_array['ver']			= '01';
        $param_array['cert_type']	= '0';
        $param_array['timestamp']	= self::getDateTime();

        $this->postService($this->getGate(),$param_array,null);
    }
}