<?php
namespace controllers;

use Yansongda\Pay\Pay;

class WxpayController
{
    protected $config = [
        'app_id' => 'wx426b3015555a46be', // 公众号 APPID
        'mch_id' => '1900009851',
        'key' => '8934e7d15453e97507ef794cf7b0519d',
        // 通知的地址
        'notify_url' => 'http://6b0846e0.ngrok.io/wxpay/notify',
    ];

    // 调用微信接口进行支付
    public function pay()
    {
        // 接收订单消息
        $sn = $_POST['sn'];

        // 取出订单消息
        $order = new \models\Order;
        // 根据订单消息取出订单信息
        $data = $order->findBySn($sn);

        if($data['is_paid']==0)
        {
            $ret = Pay::wechat($this->config)->scan([
                'out_trade_no' => $data['sn'],
                'total_fee' => $data['money'] * 100, // 单位：分
                'body' => '智聊系统用户充值 ：'.$data['money'].'元',
            ]);
            if($ret->return_code == 'SUCCESS' && $ret->result_code == 'SUCCESS')
            {
                view('user.wxpay',[
                    'code'=>$ret->code_url,
                    'sn'=>$sn,
                ]);
            }
            
        }
        else
        {
            die('订单状态不允许支付');
        }
    }
   

    public function notify()
    {
        $log = new  \libs\log('wxpay');
        
        $log->log('已经接收到微信消息');

        $pay = Pay::wechat($this->config);

        try{
            $data = $pay->verify(); // 是的，验签就这么简单！

            // $log->log('验证成功，接收的数据是：' . file_get_contents('php://input'));

            if($data->result_code == 'SUCCESS' && $data->return_code == 'SUCCESS')
            {
                
                $log->log('支付成功！');
                // 更新订单状态
                $order = new \models\Order;
                // 获取订单信息
                $orderInfo = $order->findBySn($data->out_trade_no);
                
                if($orderInfo['is_paid']==0)
                {
                    // 开启事务
                    $order->startTrans();

                    // 设置订单为已支付状态
                    $ret1 = $order->setPaid($data->out_trade_no);
                    // 更新用户余额
                    $user = new \models\User;
                    $ret2 = $order->addMoney($orderInfo['money'],$orderInfo['user_id']);

                    if($ret1 && $ret2)
                    {
                        $order->commit();
                    }
                    else
                    {
                        $order->rollback();
                    }
                }
            }

        }
        catch (Exception $e) 
        {
            $log->log('验证签名失败,'.$e->getMessage());
            var_dump( $e->getMessage() );
        }
        
        $pay->success()->send();
    }

}