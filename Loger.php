<?php
    namespace common\components\VKParser;
    
    class Loger
    {
        private $path = __DIR__.'/log//log.txt';
        private $debug = __DIR__.'/log//debugLog.txt';
        
        public function __construct()
        {
        }
        public function setLog($message)
        {
            $txt  = '['.date('d-m-Y H:i:s', time()).'] ';
            $txt .= $message . PHP_EOL;
           // file_put_contents($this->path, $txt, FILE_APPEND);
            return;
        }
        public function setDebug($message)
        {
            if(is_array($message))
            {
                file_put_contents($this->debug, print_r($message,true));
                return;    
            }
            file_put_contents($this->debug, $message, FILE_APPEND);
            return;
        }
    }