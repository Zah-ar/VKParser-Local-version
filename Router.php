<?php

namespace common\components\VkParser;

class Router extends Loger
{
    private $marketUploadServer;
    private $VK_URL;
    private $ACCESS_TOKEN;
    private $GROUP_ID;
    private $OWNER_ID;
    public function init($VK_URL, $ACCESS_TOKEN, $GROUP_ID, $OWNER_ID)
    {
        $url = $VK_URL.'photos.getMarketUploadServer?access_token='.$ACCESS_TOKEN.'&v=5.131&group_id='.$GROUP_ID;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $json = json_decode($json_html, true);
        //echo '<pre>';print_r($json);echo'</pre>';
        $this->marketUploadServer = $json['response']['upload_url'];
        $this->VK_URL = $VK_URL;
        $this->ACCESS_TOKEN = $ACCESS_TOKEN;
        $this->GROUP_ID = $GROUP_ID;
        $this->OWNER_ID = $OWNER_ID;
        return;
    }
    
    public function sendImg($imgUrl)
    {
            if(!file_exists($imgUrl))
            {
                echo 'File '.basename($imgUrl).' not found!';
                return false; 
            }
               
        $cFile = curl_file_create($imgUrl);
        $ch = curl_init($this->marketUploadServer); // создаем подключение
        $postData = [];
        $postData['file'] = $cFile;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        sleep(\common\components\VkParser\VkParser::TIMEOUT);
        $json_html = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($json_html, true);
            if(!array_key_exists('photo', $json))
            {
                sleep(\common\components\VkParser\VkParser::TIMEOUT);
                return false;        
            }
        $img = $this->saveImg($json['server'], $json['photo'], $json['hash']);
        sleep(\common\components\VkParser\VkParser::TIMEOUT);
            if($img == false)
            {
                return false;
            }   
        return $img['response'][0]['id'];
    }
    
    private function saveImg($sever, $photo, $hash)
    {
        $url = $this->VK_URL.'photos.saveMarketPhoto';
        $ch = curl_init($url);
        $postData = [];
        $postData['server'] = $sever;
        $postData['photo']  = $photo;
        $postData['hash']   = $hash;
        $postData['v'] = '5.131';
        $postData['access_token'] = $this->ACCESS_TOKEN;
        $postData['group_id'] = $this->GROUP_ID;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
        $json_html = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($json_html, true);
            if(array_key_exists('error', $json))
            {
                $this->setLog('[Error] '.$json['error']['error_msg']);
                //echo $json['error']['error_msg'];
                return false;
            }
        return $json;
    }
    public function sendGood($good, $action)
    {
        $sumbarket = 'add';
            if($action == 'UPDATE_GOODS')
            {
                $sumbarket = 'edit';
            }
        $cnt = 0;   

            $url = $this->VK_URL.'market.'.$sumbarket.'/?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID.'&'. $good; 
                    
            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            );
            $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
            $json = json_decode($json_html, true);
                if(array_key_exists('error', $json))
                {
                    //$this->setDebug($json['error']);
                    $this->setLog('[Error] '.print_r($json['error'],true));
                    return false;
                }
            //echo '<pre>'; print_r($json); echo '<pre>';
                if(array_key_exists('error', $json))
                {
                    //print_r($json['error'], false);
                    $this->setLog('[Error] '.print_r($json['error'],true));
                    sleep(\common\components\VkParser\VkParser::TIMEOUT);
                    $this->setLog('Goods received...');
                }   
                if($action  == 'CREATE_GOODS')
                {
                    
                    if(array_key_exists('market_item_id', $json['response']))
                    {
                        $item_id = $json['response']['market_item_id'];
                        return $json['response']['market_item_id'];
                    }
                }
                if($action == 'UPDATE_GOODS')
                {
                    /*$goodArr = explode('&', $good);
                    $arr = explode('=', $goodArr[count($goodArr) - 1]);
                    return $arr[1];*/
                    return;
                }
                    
    }
    
    public function deleteGood($good)
    {
        $url = $this->VK_URL.'market.delete?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID.'&item_id='.$good;

        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        return;
    }

}
