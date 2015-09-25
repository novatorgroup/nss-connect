<?php

namespace mrssoft\nss_connect;

use Yii;
use yii\base\Exception;
use yii\base\Object;

class NssDirect extends Object
{
    /**
     * @var string
     */
    public $ip;

    /**
     * @var string|int
     */
    public $port;

    /**
     * @var NssResponse
     */
    private $answer;

    /**
     * Выполнить запрос
     * @param $command - команда
     * @param array $params - параметры команды
     * @return string
     * @throws Exception
     */
    public function request($command, $params = [])
    {
        if (empty($this->ip))
        {
            throw new Exception('Novator IP is empty');
        }

        if (empty($this->port))
        {
            throw new Exception('Novator Port is empty');
        }

        $ch = curl_init($this->ip);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_PROXY => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_PORT => $this->port,
            CURLOPT_POSTFIELDS => $this->createBody($command, $params)
        ]);
        $this->answer = curl_exec($ch);

        if (curl_errno($ch) || empty($this->answer))
        {
            $this->answer = new NssResponse();
            $this->answer->error = curl_error($ch);
        }
        else
        {
            $this->answer = @simplexml_load_string($this->answer);
            if ($this->answer === false)
            {
                $this->answer = new NssResponse();
                $this->answer->error = 'От сервера пришел неверный ответ.';
            }
        }
        curl_close($ch);

        return $this->answer;
    }

    /**
     * Сформировать XML сообщение
     * @param $command - команда
     * @param array $params - параметры команды
     * @return string
     */
    private function createBody($command, $params = [])
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('data');
        $root->appendChild($doc->createElement('command', $command));
        foreach ($params as $key => $value)
        {
            $param = $doc->createElement('param');
            $param->setAttribute('name', $key);
            $param->setAttribute('value', $value);
            $root->appendChild($param);
        }
        $doc->appendChild($root);
        return $doc->saveXML();
    }
}