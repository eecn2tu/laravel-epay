<?php
namespace Sinta\LaravelEpay\Pay;


use Sinta\LaravelEpay\Util;

class Gatepay extends AEpay
{
    // 网关支付API地址，测试环境地址可根据需要修改
    const GATE_API = "https://pay.cib.com.cn/acquire/cashier.do";
    const DEV_GATE_API = "https://3gtest.cib.com.cn:37031/acquire/cashier.do";


    public function getGate()
    {
        return $this->debug ? self::DEV_GATE_API : self::GATE_API;
    }


    /**
     * 网关支付交易跳转页面生成接口<br />
     * 该方法将生成跳转页面的全部HTML代码，商户直接输出该HTML代码至某个URL所对应的页面中，即可实现跳转，可以参考epay_redirect.php中示例<br />
     * [重要]各传入参数SDK都不作任何检查、过滤，请务必在传入前进行安全检查或过滤，保证传入参数的安全性，否则会导致安全问题。
     * @param string $order_no		订单号
     * @param string $order_amount	金额，单位元，两位小数，例：8.00
     * @param string $order_title	订单标题
     * @param string $order_desc	订单描述
     * @param string $remote_ip		客户端IP地址
     * @return string				跳转页面HTML代码
     */
    public function pay($order_no, $order_amount, $order_title, $order_desc, $remote_ip)
    {

        $param_array = array();

        $param_array['order_no']	= $order_no;
        $param_array['order_amount']= $order_amount;
        $param_array['order_title']	= $order_title;
        $param_array['order_desc']	= $order_desc;
        $param_array['order_ip']	= $remote_ip;

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.acquire.cashier.netPay';
        $param_array['ver']			= '01';
        $param_array['sub_mrch']	= $this->config['sub_mrch'];
        $param_array['cur']			= 'CNY';
        $param_array['order_time']	= Util::getDateTime();
        $param_array['timestamp']	= Util::getDateTime();

        $this->redirectService($this->getGate(),$param_array);
    }



    /**
     * 网关支付交易查询接口
     * @param string $order_no		订单号
     * @param string $order_date	订单日期，格式yyyyMMdd，为null时使用当前日期
     * @return string				json格式结果，返回结果包含字段请参看收付直通车代收接口文档
     */
    public function gpQuery($order_no, $order_date = null) {

        $param_array = array();

        $param_array['order_no']	= $order_no;
        $param_array['order_date']	= $order_date ? $order_date : Util::getDate();

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.acquire.cashier.query';
        $param_array['ver']			= '02';
        $param_array['timestamp']	= Util::getDateTime();

        return $this->postService($this->getGate(),$param_array,null);
    }


    /**
     * 网关支付退款交易接口
     * @param string $order_no		待退款订单号
     * @param string $order_date	订单下单日期，格式yyyyMMdd
     * @param string $order_amount	退款金额（不能大于原订单金额）
     * @return string				json格式结果，返回结果包含字段请参看收付直通车代收接口文档
     */
    public function refund($order_no, $order_date, $order_amount) {

        $param_array = array();

        $param_array['order_no']	= $order_no;
        $param_array['order_date']	= $order_date;
        $param_array['order_amount']= $order_amount;

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.acquire.cashier.refund';
        $param_array['ver']			= '02';
        $param_array['timestamp']	= Util::getDateTime();

        return $this->postService($this->getGate(),$param_array,null);
    }


    /**
     * 网关支付退款交易结果查询接口
     * @param string $order_no		退款的订单号
     * @param string $order_date	订单日期，格式yyyyMMdd，为null时使用当前日期
     * @return string				json格式结果，返回结果包含字段请参看收付直通车代收接口文档
     */
    public function refundQuery($order_no, $order_date = null) {

        $param_array = array();

        $param_array['order_no']	= $order_no;
        $param_array['order_date']	= $order_date ? $order_date : Util::getDate();

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.acquire.cashier.refund.query';
        $param_array['ver']			= '01';
        $param_array['timestamp']	= Util::getDateTime();

        return $this->postService($this->getGate(),$param_array,null);
    }

}