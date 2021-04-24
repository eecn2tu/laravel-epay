<?php
namespace Sinta\LaravelEpay\Pay;



/**
 * 智能代付
 *
 * Class Daipay
 * @package Sinta\LaravelEpay\Pay
 */
class Daipay extends AEpay
{
    // 智能代付API地址，测试环境地址可根据需要修改
    const GATE_API		= "https://pay.cib.com.cn/payment/api";
    const DEV_GATE_API		= "https://3gtest.cib.com.cn:37031/payment/api";


    public function getGate()
    {
        return $this->debug ? self::DEV_GATE_API : self::GATE_API;
    }

    /**
     * 智能代付单笔付款接口
     *
     * @param string $order_no		订单号
     * @param string $to_bank_no	收款行行号
     * @param string $to_acct_no	收款人账户
     * @param string $to_acct_name	收款人户名
     * @param string $acct_type		账户类型：0-储蓄卡,1-信用卡,2-对公账户
     * @param string $trans_amt		付款金额
     * @param string $trans_usage	用途
     * @return string				json格式结果，返回结果包含字段请参看收付直通车代收接口文档
     */
    public function pay($order_no, $to_bank_no, $to_acct_no,
                          $to_acct_name, $acct_type, $trans_amt, $trans_usage) {

        $param_array = array();

        $param_array['order_no'] = $order_no;
        $param_array['to_bank_no'] = $to_bank_no;
        $param_array['to_acct_no'] = $to_acct_no;
        $param_array['to_acct_name'] = $to_acct_name;
        $param_array['acct_type'] = $acct_type;
        $param_array['trans_amt'] = $trans_amt;
        $param_array['trans_usage'] = $trans_usage;

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.payment.pay';
        $param_array['ver']			= '02';
        $param_array['sub_mrch']	= $this->config['sub_mrch'];
        $param_array['cur']			= 'CNY';
        $param_array['timestamp']	= self::getDateTime();

        return $this->postService($this->getGate(),$param_array,null);
    }

    /**
     * 智能代付单笔订单查询接口
     *
     * @param $order_no              订单号
     * @return string               json格式结果，返回结果包含字段请参看收付直通车代收接口文档
     */
    public function query($order_no)
    {
        $param_array = array();

        $param_array['order_no'] = $order_no;

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.payment.get';
        $param_array['ver']			= '02';
        $param_array['timestamp']	= self::getDateTime();

        return $this->postService($this->getGate(),$param_array,null);
    }

    /**
     * 智能代付商户信息查询接口
     *
     * @return mixed
     */
    public function mrch()
    {
        $param_array = array();

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.payment.getMrch';
        $param_array['ver']			= '02';
        $param_array['timestamp']	= self::getDateTime();

        return $this->postService($this->getGate(),$param_array,null);
    }


    /**
     * 对账文件下载接口
     * @param string $rcpt_type			回单类型：0-快捷入账回单；1-快捷出账回单；2-快捷手续费回单；3-网关支付入账回单；4-网关支付出账回单；
     *                                  5-网关支付手续费回单；6-代付入账回单；7-代付出账回单；8-代付手续费回单
     * @param string $trans_date		交易日期，格式yyyyMMdd
     * @param string $save_file_name	保存下载内容至以该变量为名的文件
     * @return string					当下载成功时，返回SUCCESS_RESULT常量值；当下载失败时，返回失败信息json字符串
     */
    public function dlSettleFile($rcpt_type, $trans_date, $save_file_name) {

        $param_array = [];

        $param_array['appid']		= $this->config['appid'];
        $param_array['ver']			= '01';
        $param_array['trans_date']	= $trans_date;
        $param_array['timestamp']	= self::getDateTime();

        if($rcpt_type === '6' || $rcpt_type === '7' || $rcpt_type === '8') {
            if($rcpt_type === '6') $param_array['rcpt_type'] = '0';
            else if($rcpt_type === '7') $param_array['rcpt_type'] = '1';
            else $param_array['rcpt_type'] = '2';

            $param_array['service']		= 'cib.epay.payment.receiptFile';
            return $this->postService($this->getGate(),$param_array,$save_file_name);
        } else {
            $param_array['rcpt_type']	= $rcpt_type;
            $param_array['service']		= 'cib.epay.acquire.settleFile';

            return $this->postService($this->getGate(),$param_array,$save_file_name);
        }
    }


    /**
     * 行号文件下载接口
     * @param string $download_type		文件类型：01-行号文件
     * @param string $save_file_name	保存下载内容至以该变更为名的文件
     * @return string					当下载成功时，返回SUCCESS_RESULT常量值；当下载失败时，返回失败信息json字符串
     */
    public function dlFile($download_type, $save_file_name) {

        $param_array = array();

        $param_array['download_type']	= $download_type;

        $param_array['appid']		= $this->config['appid'];
        $param_array['service']		= 'cib.epay.acquire.download';
        $param_array['ver']			= '01';
        $param_array['timestamp']	= self::getDateTime();

        return $this->postService($this->getGate(),$param_array,$save_file_name);
    }

}