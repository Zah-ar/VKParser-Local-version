<?php

namespace common\components\VkParser;

class Router extends Loger
{
    private $marketUploadServer;
    private $VK_URL;
    private $ACCESS_TOKEN;
    private $GROUP_ID;
    private $OWNER_ID;
    private $albumCovers;
    public $utm;
    public  $sended;
    public $promoAlbums;

    public function init($VK_URL, $ACCESS_TOKEN, $GROUP_ID, $OWNER_ID, $utm)
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
        $this->marketUploadServer = $json['response']['upload_url'];
        $this->VK_URL = $VK_URL;
        $this->ACCESS_TOKEN = $ACCESS_TOKEN;
        $this->GROUP_ID = $GROUP_ID;
        $this->OWNER_ID = $OWNER_ID;
        $this->utm      = $utm;
        $this->sended   = false;
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
        if(!is_array($json)) return;
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
                $this->setLog('[Error] '.print_r($json['error'],true));
                return false;
            }
        return $json;
    }
    private function getGoodCategoryes($VKParser, $good)
    {
        if(!array_key_exists('categoryes', $good) && !array_key_exists('discount', $good)) return false;
        //file_get_contents(__DIR__.'/vkAlbums.txt',);
            if(array_key_exists('categoryes', $good))
            {
                if(is_string($good['categoryes']))
                {
                    $categoryes = explode('|', $good['categoryes']);
                    $albums = [];
                        foreach($categoryes as $category)
                        {
                            if(array_key_exists(md5($category), $VKParser->vkAlbums))
                            {
                                $albums[] = $VKParser->vkAlbums[md5($category)];
                            }
                        }
                   if(count($albums) == 0) return false;
                   return $albums;     
                }
            }
        return false;
    }
    private function getUnsetAlbums($VKParser, $albums)
    {
        if(!is_array($VKParser->vkAlbums)) return false;
        $unsetAlbums = [];
            foreach($VKParser->vkAlbums as $vkAlbum)
            {
                if(!in_array($vkAlbum, $albums)) $unsetAlbums[] = $vkAlbum;
            }     
         if(count($unsetAlbums) == 0) return false;
         return $unsetAlbums;       
    }
    private function unsetCategoryes($VKParser, $albums, $item_id = false)
    {
        if(!$item_id)
        {
            if(is_array($good_id)) return;
            $arr = explode('&', $good_id);  
            $item_id = $arr[9];
            $item_idArr  = explode('=', $item_id);
            $item_id = $item_idArr[count($item_idArr) - 1];
            $existGoodsItemidsFlip = array_flip($VKParser->existGoodsItemids);
            if(!array_key_exists($item_id, $existGoodsItemidsFlip)) return false;
            $good_id = $existGoodsItemidsFlip[$item_id];
        }
        $unsetAlbums = $this->getUnsetAlbums($VKParser, $albums);
        if(!$unsetAlbums) return;
        $this->removeFromAlbum($unsetAlbums, $item_id);
     return;
    }
    
    
    private function setCategoryes($VKParser, $albums, $item_id = false)
    {
        if(!$item_id)
        {
            if(is_array($good_id)) return;
            $arr = explode('&', $good_id);  
            $item_id = $arr[9];
            $item_idArr  = explode('=', $item_id);
            $item_id = $item_idArr[count($item_idArr) - 1];
            $existGoodsItemidsFlip = array_flip($VKParser->existGoodsItemids);
            if(!array_key_exists($item_id, $existGoodsItemidsFlip)) return false;
            $good_id = $existGoodsItemidsFlip[$item_id];
        }
               if(count($albums) == 0) return;
                $this->addToAlbum($albums, $item_id);
            return;
    }
    
    public function sendGood($VKParser, $good, $goodData, $action)
    {
        $albums = $this->getGoodCategoryes($VKParser, $good);
        //file_put_contents(__DIR__.'/log/good_'.$good['good_id'].'.txt', print_r($albums, true));
        $this->sended   = true;
        $sumbarket = 'add';
            if($action == 'UPDATE_GOODS')
            {
                $sumbarket = 'edit';
            }
        $cnt = 0;   

            $url = $this->VK_URL.'market.'.$sumbarket.'/?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID.'&'. $goodData; 
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
                    $this->setLog('[Error] '.print_r($json['error'],true));
                    return false;
                }
                if(array_key_exists('error', $json))
                {
                    $this->setLog('[Error] '.print_r($json['error'],true));
                    sleep(\common\components\VkParser\VkParser::TIMEOUT);
                    $this->setLog('Goods received...');
                }   
                if($action  == 'CREATE_GOODS')
                {
                    if(array_key_exists('market_item_id', $json['response']))
                    {
                        $item_id = $json['response']['market_item_id'];
                            if(array_key_exists('good_id', $good))
                            {
                                $VKParser->addToArray($good['good_id'], $item_id);
                                sleep(\common\components\VkParser\VkParser::TIMEOUT);
                                if($albums) $this->setCategoryes($VKParser, $albums, $item_id);
                            }         
                        return $item_id;
                    }
                }
                if($action == 'UPDATE_GOODS')
                {
                    if($albums)
                    {
                        $this->unsetCategoryes($VKParser, $albums, $good['item_id']);
                        $this->setCategoryes($VKParser, $albums, $good['item_id']);
                    } 
                    return;
                }
                    
    }
    public function deleteGood($VKParser,$good)
    {
        if(!array_key_exists($good, $VKParser->existGoodsItemids)) return;
        $item_id =  $VKParser->existGoodsItemids[$good];
        $VKParser->deleteFromArray($good);
        $url = $this->VK_URL.'market.delete?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID.'&item_id='.$item_id;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        print_r(json_decode($json_html));
        return;
    }
    private function getAlbum($name)
    {
        sleep(\common\components\VkParser\VkParser::TIMEOUT);
        $url = $this->VK_URL.'market.getAlbums?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $json_arr = json_decode($json_html);
        if($json_arr->response->count == 0) return false;
            foreach($json_arr->response->items as $item)
            {
                if(trim($name) ==  $item->title) return $item->id;
            }
        
        return false;
    }
    public function initAlbums($albumCovers)
    {
        $this->albumCovers = $albumCovers;
        $url = $this->VK_URL.'market.getAlbums?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID.'&limit=50&offset=0';
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $json_arr = json_decode($json_html);
        if($json_arr->response->count == 0) return false;
        $albums = [];
        $data = [];
            foreach($json_arr->response->items as $item)
            {

                $albums[md5($item->title)] = $item->id;
                $data[$item->id] = $item->title;                
            }
            $result = [];
            $result['albums'] = $albums;
            $result['data']   = $data;
            if($json_arr->response->count <= 50) return $result;
            sleep(\common\components\VkParser\VkParser::TIMEOUT);
            $url = $this->VK_URL.'market.getAlbums?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID.'&limit=50&offset=50';
            $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
            $json_arr = json_decode($json_html);
            if($json_arr->response->count == 0) return $result;
            $albums = [];
            $data = [];
                foreach($json_arr->response->items as $item)
                {
    
                    $albums[md5($item->title)] = $item->id;
                    $data[$item->id] = $item->title;                
                }
            $result['albums'] = array_merge($result['albums'], $albums);
            $result['data'] = array_merge($result['data'], $data);    
            return $result;
    }
    
    public function getAlbums()
    {
        $url = $this->VK_URL.'market.getAlbums?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $json_arr = json_decode($json_html);
        
        if($json_arr->response->count == 0) return false;
            foreach($json_arr->response->items as $item)
            {
                $albumIds[] = $item->id;
            }
        
        return $json_arr->response->items;
    }
    public function deleteAlbums() 
    {
        $albums = $this->getAlbums();
        if(!$albums) return false;
            foreach($albums as $album)
            {
                if(!is_int($album)) $album = $album->id;
                $url = $this->VK_URL.'market.deleteAlbum?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID.'&album_id='.$album;
                $arrContextOptions = array(
                    "ssl" => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ),
                );
                $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
                $json = json_decode($json_html);
            }
        
        return;
    }   
    public function deleteAlbum($name) 
    {
        $albumExist = $this->getAlbum($name);
        if(!$albumExist) return false;
        $url = $this->VK_URL.'market.deleteAlbum?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID.'&album_id='.$albumExist;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $json = json_decode($json_html);
        return;
    }   
    public function deleteAlbumById($albumID) 
    {
        if(!is_int($albumID)) return;
        $url = $this->VK_URL.'market.deleteAlbum?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID.'&album_id='.$albumID;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $json = json_decode($json_html);
        return;
    }   
    private function loadAlbumcover($cover)
    {
        $url = $this->VK_URL.'photos.getMarketAlbumUploadServer?access_token='.$this->ACCESS_TOKEN.'&v=5.131&group_id='.$this->GROUP_ID;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $json = json_decode($json_html, true);
        //file_put_contents(__DIR__.'/'.__METHOD__.'.txt', print_r($json,true));
        $marketAlbumUploadServer = $json['response']['upload_url'];
        sleep(\common\components\VkParser\VkParser::TIMEOUT);
        if(!file_exists($cover))
            {
                echo 'File '.basename($cover).' not found!';
                $this->setLog('[Error] '.print_r($json['error'],true));
                return false; 
            }     
        $cFile = curl_file_create($cover);
        $ch = curl_init($marketAlbumUploadServer); // создаем подключение
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
        if(!is_array($json)) return;
            if(!array_key_exists('photo', $json))
            {
                sleep(\common\components\VkParser\VkParser::TIMEOUT);
                return false;        
            }
            if(array_key_exists('error', $json))
            {
                $this->setLog('[Error] '.print_r($json['error'],true));
                return false;
            }
        sleep(\common\components\VkParser\VkParser::TIMEOUT);
        $saveCoverUrl = $this->VK_URL.'photos.saveMarketAlbumPhoto?access_token='.$this->ACCESS_TOKEN.'&v=5.131&group_id='.$this->GROUP_ID.'&photo='.$json['photo'].'&server='.$json['server'].'&hash='.$json['hash']; 
        $json_html = file_get_contents($saveCoverUrl, false, stream_context_create($arrContextOptions));
        $json = json_decode($json_html, true);
        if(array_key_exists('error', $json))
        {
            $this->setLog('[Error] '.$json['error']['error_msg']);
            $this->setLog('[Error] '.print_r($json['error'],true));
            return false;
        }
        sleep(\common\components\VkParser\VkParser::TIMEOUT);
        return $json['response'][0]['id'];

    }
    public function craeateAlbum($name, $iteration = 0) 
    {
        $albumExist = $this->getAlbum($name);
        if($albumExist) return $albumExist;
        $albumCover = false;
            if($this->albumCovers)
            {
                 if(array_key_exists(md5($name), $this->albumCovers))
                 {
                    $albumCover = $this->albumCovers[md5($name)];
                    $photo_id = $this->loadAlbumcover($albumCover);
                 } 
            }   
        $url = $this->VK_URL.'market.addAlbum?access_token='.$this->ACCESS_TOKEN.'&v=5.131&owner_id='.$this->OWNER_ID.'&title='.urlencode($name);
            if($photo_id) $url .= '&photo_id='.$photo_id;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $json = json_decode($json_html);
        
            if(array_key_exists('error', $json))
            {
                if(/*!get_object_vars($json)['error']['error_code'] == 6 ||*/ $iteration > 1) return false;
                sleep(5 * \common\components\VkParser\VkParser::TIMEOUT);
                return $this->craeateAlbum($name, 1);
            }
            sleep(\common\components\VkParser\VkParser::TIMEOUT);
            if(array_key_exists('error', $json->response))
            {
                echo 'Слишком много категорий';
                return false;
            }
        return $json->response->market_album_id;
    }

    public function removeFromAlbum($albums, $item_id)
    {
        
        $url = $this->VK_URL.'market.removeFromAlbum?access_token='.$this->ACCESS_TOKEN.'&v=5.13&owner_id='.$this->OWNER_ID.'&item_id='.$item_id.'&v=5.131&album_ids='.implode(',', $albums);
         $arrContextOptions = array(
                        "ssl" => array(
                            "verify_peer" => false,
                            "verify_peer_name" => false,
                        ),
                    );
         $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
         $json = json_decode($json_html);
         sleep(\common\components\VkParser\VkParser::TIMEOUT);
        return;
    }
    
    public function addToAlbum($albums, $item_id)
    {
        $url = $this->VK_URL.'market.addToAlbum?access_token='.$this->ACCESS_TOKEN.'&v=5.13&owner_id='.$this->OWNER_ID.'&item_ids='.$item_id.'&v=5.131&album_ids='.implode(',', $albums);
         $arrContextOptions = array(
                        "ssl" => array(
                            "verify_peer" => false,
                            "verify_peer_name" => false,
                        ),
                    );
         $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
         $json = json_decode($json_html);
         sleep(\common\components\VkParser\VkParser::TIMEOUT);
        return;
    }
    private function getNotesByText($text)
    {
        $url = $this->VK_URL.'wall.search?access_token='.$this->ACCESS_TOKEN.'&owner_id='.$this->OWNER_ID.'&v=5.131&query='.urlencode($text).'&count=1';
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $json = json_decode($json_html);
        if($json->response->count != 0) return true;
        return false;
    }
    private function sendNote($data)
    {
        if(array_key_exists('text', $data))
        {
           //if($this->getNotesByText($data['text'])) return;
        }
        $url = $this->VK_URL.'wall.post?access_token='.$this->ACCESS_TOKEN.'&owner_id='.$this->OWNER_ID.'&v=5.131';
        $attachmentsArr = [];
        if(array_key_exists('text', $data))
        {
            $url .= '&message='.urlencode($data['text']);
            $attachmentsArr[] = 'message='.urlencode($data['text']);
        }
        if(array_key_exists('albumID', $data))
        {
            $attachments = 'market_album'.$this->OWNER_ID.'_'.$data['albumID'];
            $attachmentsArr[] = 'market_album'.$this->OWNER_ID.'_'.$data['albumID'];
            //$url .= '&attachments='.$attachments;
        }
        if(array_key_exists('img', $data))
        {
            $attachments = 'photo'.$this->OWNER_ID.'_'.$data['img'];
            $attachmentsArr[] = 'photo'.$this->OWNER_ID.'_'.$data['img'];
           // $url .= '&attachments='.$attachments;
        }
        if(array_key_exists('url', $data))
        {
            $attachments = $data['url']['protocol'].'://'.$data['url']['url'];
            $attachmentsArr[] =  $data['url']['protocol'].'://'.$data['url']['url'];
         //   $url .= ','.$attachments;
        }
        if(count($attachmentsArr) > 0)
        {
            $url.= '&attachments='.implode(',', $attachmentsArr);
        }
        $url .= '&mark_as_ads=1';
        $arrContextOptions = array(
                       "ssl" => array(
                           "verify_peer" => false,
                           "verify_peer_name" => false,
                       ),
                   );          
        $json_html = file_get_contents($url, false, stream_context_create($arrContextOptions));
        $json = json_decode($json_html);
        sleep(\common\components\VkParser\VkParser::TIMEOUT);
        return;
    }
    public function sendNotes($VKParser)
    {
        if(count($VKParser->promoPosts) == 0) return;
            $NoteRouter =  new NoteRouter;
            $NoteRouter->init('https://api.vk.com/method/', $this->ACCESS_TOKEN, -$this->OWNER_ID, $this->GROUP_ID);
            
            foreach($VKParser->promoPosts as $item)
            {
                $data = [];
                if(array_key_exists('album', $item))
                {
                    if(array_key_exists(md5($item['album']), $VKParser->vkAlbums))
                    {
                        $albumID = $VKParser->vkAlbums[md5($item['album'])];
                        $data['albumID']  = $albumID;
                    }
                }
                if(array_key_exists('text', $item))
                {
                    $data['text'] = $item['text'];
                }
                if(array_key_exists('image', $item))
                {
                    $img = $NoteRouter->sendImg($item['image']);
                    if(is_int($img)) 
                    {
                        $data['img'] = $img;
                    }
                }
                if(array_key_exists('url', $item))
                {

                    $urlArr = explode('://', $item['url']);
                    $data['url']['protocol'] = $urlArr[0];
                    $data['url']['url']      = $urlArr[1];
                }
                if(count($data) > 0)
                {
                   $this->sendNote($data);
                   sleep(\common\components\VkParser\VkParser::TIMEOUT);
                }
            }
        
        return;
    }
}
