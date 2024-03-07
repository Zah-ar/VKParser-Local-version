<?php
namespace common\components\VkParser;

class NoteRouter extends Loger
{

    private $marketUploadServer;
    private $VK_URL;
    private $ACCESS_TOKEN;
    private $GROUP_ID;
    private $OWNER_ID;
    public  $sended;
    public $albumID;
    
    public function init($VK_URL, $ACCESS_TOKEN, $GROUP_ID, $OWNER_ID)
    {
        
        $this->VK_URL = $VK_URL;
        $this->ACCESS_TOKEN = $ACCESS_TOKEN;
        $this->GROUP_ID = $GROUP_ID;
        $this->OWNER_ID = -$OWNER_ID;
        $this->sended   = false;
        $this->albumID = $this->setAlbum();
        $url = $VK_URL.'photos.getUploadServer?access_token='.$ACCESS_TOKEN.'&v=5.131&album_id='.$this->albumID.'&group_id='.$this->GROUP_ID;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $json = json_decode($json_html, true);
        $this->marketUploadServer = $json['response']['upload_url'];
        return;
    }
    private function setAlbum()
    {
        $url = $this->VK_URL.'photos.getAlbums?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $json = json_decode($json_html);
        return $json->response->items[0]->id;
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
        $postData['album_id'] = $this->albumID;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        sleep(\common\components\VkParser\VkParser::TIMEOUT);
        $json_html = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($json_html, true);
        $img = $this->saveImg($json['server'], $json['photo'], $json['hash'], $json['photos_list']);
        if(array_key_exists('error', $img['response']))
        {
            sleep(\common\components\VkParser\VkParser::TIMEOUT);
            return false;
        }
        if(count($img['response'][0]) == 0) 
        {
            sleep(\common\components\VkParser\VkParser::TIMEOUT);
            return false;
        }

        if(!array_key_exists('id', $img['response'][0])) 
        {
            sleep(\common\components\VkParser\VkParser::TIMEOUT);
            return false;
        }
        return $img['response'][0]['id'];
    }
    
    private function saveImg($sever, $photo, $hash, $photos_list)
    {
        
        $url = $this->VK_URL.'photos.save';
        $ch = curl_init($url);
        $postData = [];
        $postData['server'] = $sever;
        $postData['photo']  = $photo;
        $postData['hash']   = $hash;
        $postData['v'] = '5.131';
        $postData['access_token'] = $this->ACCESS_TOKEN;
        $postData['group_id'] = $this->GROUP_ID;
        $postData['album_id'] = $this->albumID;
        $postData['photos_list'] = $photos_list;
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
                print_r($json['error']);
                return false;
            }
        return $json;
    }

}