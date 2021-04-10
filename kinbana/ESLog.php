<?php

namespace KinBa;


class ESLog
{
    const IP_ADDRESS = "172.19.235.52";
    const PORT = 9092;

    private static $data;
    public static $DEFAULT_TOPIC = "test";

    /**
     * 日志保存
     *
     * @param $message         需要记录的数据信息
     * @param string $topic    队列主题名称
     * @param $isAsyn          是否同步 false：否|true：是
     */
    public static function Save($message, $topic = 'test', $isAsyn = false)
    {
        try {
            $kafkaIp = self::getIp() .':'. self::PORT;
            $config = \Kafka\ProducerConfig::getInstance();
            $config->setMetadataRefreshIntervalMs(10000);

            $config->setMetadataBrokerList($kafkaIp);
            $config->setRequiredAck(1);
            $config->setIsAsyn($isAsyn);
            $config->setProduceInterval(500);
            self::$data = [[
                    'topic' => $topic,
                    'value' => $message,
                ]
            ];
            $producer = new \Kafka\Producer(function () {
                return self::$data;
            });
            $producer->success(function ($result) {});
            $producer->error(function ($errorCode) {});
            $producer->send(true);

        } catch (\Exception $e) {

        }
    }

    /**
     * 保存接口的访问日志
     *
     * @param $infoTitle infotitle说明
     * @param $userId 用户ID
     *
     */
    public static function SaveRequestLog($infoTitle, $userId = '匿名用户')
    {
        try {
            $message = [
                'INFO'     => $infoTitle,
                'URL'      => self::getUrl(),
                'USER_ID'  => $userId,
                'POST'     => $_POST,
                'GET'      => $_GET,
                'HEADER'   => self::getHeaderInfo(),
                'RESPONSE' => self::getResponseData(),
                'USER_IP'  => self::getRemoteIP(),
            ];

            ESLog::Save(
                json_encode($message,JSON_UNESCAPED_UNICODE),
                self::$DEFAULT_TOPIC
            );
        } catch (\Exception $e) {
            ESLog::Save(
                "调用SaveRequestLog()方法发生错误：" . $e->getMessage(),
                self::$DEFAULT_TOPIC
            );
        }
    }

    /**
     * 获取IP地址
     */
    private static function getIp()
    {
        if (isset(\Yii::$app->params['kafka']['ip']) && !empty(\Yii::$app->params['kafka']['ip'])) {
            return \Yii::$app->params['kafka']['ip'];
        }

        return self::IP_ADDRESS;
    }

    /**
     * 获取客户端IP
     */
    private static function getRemoteIP()
    {
        try {
            return \Yii::$app->request->getRemoteIP();
        } catch (\Exception $e) {
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        }
    }

    /**
     * 获取header信息
     *
     * @return string
     */
    private static function getHeaderInfo()
    {
        try {
            return \Yii::$app->request->headers->toArray();
        } catch (\Exception $e) {
            return "获取header信息失败：".$e->getMessage() . "|" . $e->getLine() . "|" . $e->getFile();
        }
    }

    /**
     * 获取url
     *
     * @return string
     */
    private static function getUrl()
    {
       return isset($_GET['r']) ? $_GET['r'] : "没有路径";
    }

    /**
     * 获取响应结果数据
     *
     * @return string
     */
    private static function getResponseData()
    {
        try {
            return \Yii::$app->response->data;
        } catch (\Exception $e) {
            return "获取响应数据失败：" . $e->getMessage() . "|" . $e->getLine() . "|" . $e->getFile();
        }
    }
}