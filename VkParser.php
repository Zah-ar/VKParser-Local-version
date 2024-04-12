<?php

namespace common\components\VkParser;

class VkParser extends VkParserApi
{
    private $albums;
    public $vkAlbums;
    public $vkAlbumsData;
    public $categoryes;
    public $goodCategoryes;
    public $useCategoryes;
    public $usePromocategoryes;
    public $useNotes;
    public $existGoods;
    public $existGoodsHash;
    public $existGoodsItemids;
    public $Router;
    public $VkGoodFormater;
    public $promoPosts;
    public $userClass;
    public $endpoint;
    public $description;
    public $utm;
    public $albumCovers;

    const TIMEOUT = 5;
    const MAX_ALBUMS = 99;

    public function Init()
    {
        ini_set('max_execution_time', 0);
        $this->sended            = false;
        $this->Loger             = new Loger;
        $this->Router            = new Router;
        $this->VkGoodFormater    = new VkGoodFormater;
        $this->albums            = false; 
        $this->categoryes        = false; 
        $this->goodCategoryes    = false;
        $this->useCategoryes     = false;
        $this->usePromocategoryes = false;
        $this->existGoodsHash    = false; 
        $this->existGoodsItemids = false;
        $this->useNotes          = false; 
        $this->albumCovers       = false;
        $this->promoPosts        = [];
        $this->Router->init('https://api.vk.com/method/', $this->ACCESS_TOKEN, $this->GROUP_ID, $this->OWNER_ID, $this->utm);
        return;
    }
    private function setExistGoods($goods)
    {
        $this->existGoods = $goods;
        return;
    }
    
    private function setExistGoodsHash($existGoodsHash)
    {
        $this->existGoodsHash = $existGoodsHash;
        return;
    }
    private function setExistGoodsItemids($existGoodsItemids)
    {
        $this->existGoodsItemids = $existGoodsItemids;
        return;
    }
    public function deleteFromArray($itemID)
    {
        return;
        $existGoodsItemids    = $this->existGoodsItemids;
        if(!is_array($existGoodsItemids)) return;
        $existGoodsItemidsFlip = array_flip($existGoodsItemids);
        if(array_key_exists($itemID, $existGoodsItemidsFlip)) unset($existGoodsItemidsFlip[$itemID]);
        $existGoodsItemids = array_flip($existGoodsItemidsFlip);
        $this->existGoodsItemids = $existGoodsItemids;   
        return;
    }
    public function addToArray($good_id, $itemID)
    {
        $existGoodsItemids = $this->existGoodsItemids;
        $good = [];
        $good['good_id'] = $good_id;
        $good['item_id'] = $itemID;
        $existGoodsItemids[count($existGoodsItemids)] = $good;
        $this->existGoodsItemids = $existGoodsItemids;
        return;
    }
    private function setAlbumCovers()
    {
        $albumCovers = [];
            foreach($this->goods as $good)
            {
                if(!array_key_exists('categoryes', $good)) continue;
                if(!is_string($good['categoryes'])) continue;
                $categoryes = explode('|', $good['categoryes']);
                    foreach($categoryes as $category)
                    {
                        if(!array_key_exists(md5($category), $albumCovers) && array_key_exists('picture', $good))
                        {
                            if(file_exists($good['picture']))
                            {
                                $albumCovers[md5($category)] = $good['picture'];
                            }
                        }
                    }
            }    
        $this->albumCovers = $albumCovers;
        return;
    }
    private function setAlbums()
    {
        $this->setAlbumCovers();
        $albums = $this->Router->initAlbums($this->albumCovers);
        file_put_contents(__DIR__.'/log/vkAlbums.txt', print_r($albums['data'],true));
       // echo count($albums['data']);
        $albums == false ? $albumsCnt = 0 : $albumsCnt = count($albums['data']);
        $debugData = [];    
        $i = 0; 
        if($albums)
        {
            $this->vkAlbums     = $albums['albums'];
            $this->vkAlbumsData = $albums['data'];
        }
            if(is_array($this->vkAlbums))
            {
                //Удаление старых альбомов
                if(!$this->goodCategoryes)
                {
                    $this->Router->deleteAlbums();
                    return;
                }                
                    if($this->vkAlbums)
                    {
                           $vkAlbumsFlip = array_flip($this->vkAlbums);
                           //получаем хэш
                           $goodCategoryesHash = [];
                            foreach ($this->goodCategoryes as $item)
                            {
                                $goodCategoryesHash[] = md5($item);
                            }
                             foreach($vkAlbumsFlip as $item)
                             {
                                if(!in_array($item, $goodCategoryesHash))
                                {
                                    if(array_key_exists($item, $this->vkAlbums))
                                    {
                                        $this->Router->deleteAlbumById($this->vkAlbums[$item]);
                                        unset($this->vkAlbums[$item]);
                                    }
                                }
                             }  
                    }
            }
            if($this->goodCategoryes)
            {
                //Создание новых альбомов
                $albums = [];
                    if($this->vkAlbums)
                    {
                        if($albumsCnt > self::MAX_ALBUMS)
                        {
                            return;
                        }
                    }
                    foreach($this->goodCategoryes as $category)
                    {
                        if(mb_strlen(trim($category)) == 0)
                        {
                            continue;
                        }
                        if(array_key_exists(md5($category), $albums))
                        {
                            continue;
                        }
                        $albumID = $this->Router->craeateAlbum($category);
                        if(!$albumID)
                        {
                            return false;
                        }
                        $albums[md5($category)] = $albumID;
                        $debugData[$i] = [];
                        $debugData[$i]['album_id']   = $albumID;
                        $debugData[$i]['album_name'] = $category;
                        //file_put_contents(__DIR__.'/createdAlbums.txt', print_r($debugData,true).PHP_EOL);
                        $i++;
                    }
                $this->vkAlbums = $albums;
            }
        
        return;
    }
    private function initCategoryes()
    {
        if($this->useCategoryes || $this->usePromocategoryes)
        {
            $this->goodCategoryes = [];
        }
            foreach($this->goods as $good)
            {
                if($this->useCategoryes)
                {
                    if(!array_key_exists('categoryes', $good))
                    {
                        continue;
                    }
                    if(!is_string($good['categoryes']))
                    {
                        continue;
                    }
                    $categoryes = explode('|', $good['categoryes']);
                    if(is_array($categoryes))
                    {
                        foreach ($categoryes as $category)
                        {
                           if(!in_array($category, $this->goodCategoryes)) $this->goodCategoryes[] = $category;
                        }
                    }
                }
                if($this->usePromocategoryes)
                {
                    if(!array_key_exists('discount', $good))
                    {
                        continue;
                    }
                    $categoryes = explode('|', $good['discount']);
                    if(is_array($categoryes))
                    {
                        foreach ($categoryes as $category)
                        {
                            if(!in_array($category, $this->goodCategoryes)) $this->goodCategoryes[] = $category;
                        }
                    }
                }
            }
            $this->setAlbums();
       return;
    }
    private function checkData()
    {
        if(!$this->useCategoryes && !$this->usePromocategoryes) return false;
        $albums = [];
        if(is_array($this->promoPosts))
        {
            if(count($this->promoPosts) > 0)
            {
                foreach($this->goods as $good)
                        {
                            if(array_key_exists('discount', $good))
                            {
                                $discounts = explode('|', $good['discount']);
                                foreach($discounts as $discount)
                                {
                                    if(!in_array($discount, $albums))
                                    {
                                        $albums[] = $discount;
                                    }
                                }   
                            }
                            if(array_key_exists('categoryes', $good))
                            {
                                $categoryes = explode('|', $good['categoryes']);
                                foreach($categoryes as $category)
                                {
                                    if(!in_array($category, $albums))
                                    {
                                        $albums[] = $category;
                                    }
                                }   
                            }
                        }
                foreach($this->promoPosts as $promoPost)
                {
                    if(array_key_exists('album', $promoPost))
                    {
                        if(!in_array($promoPost['album'], $albums)) return 'Ошибка! Альбом для поста "'.$promoPost['album'].'" не найден в категориях товаров. Добавьте его в элемет массива товаров  $goods[$i]["categoryes"] или "$goods[$i][discount]"' ;
                    }
                }
            }
          if(count($albums) > self::MAX_ALBUMS)
          {
            return 'Ошибка! Слишком много категорий и акций. Максимальное количество: '.self::MAX_ALBUMS;
          }  
        }
        return false;
    }
    public function initGoods($goods)
    {
        $chek = $this->checkData();
            if($chek)
            {
                echo $chek;
                exit(0);
            }
        $this->initCategoryes();        
        if($goods == null) return;
        $goodsHash = [];
        $goodsItemids = [];
        $goodIDs = [];
            foreach($goods as $good)
            {
               $goodsHash[$good['good_id']] = $good['hash'];
               $goodsItemids[$good['good_id']] = $good['item_id'];
               $goodIDs[] = $good['good_id'];
               
            }
        $this->setExistGoods($goodIDs);    
        $this->setExistGoodsHash($goodsHash); 
        $this->setExistGoodsItemids($goodsItemids); 
        return;    
    }
    private function setDiscounts()
    {
        $discounts = [];
        $this->albums = [];
            foreach($this->goods as $good)
            {
                if(array_key_exists('discount', $good))
                {
                        if(in_array( $good['discount'], $discounts))
                        {
                            continue;
                        }
                    $discounts[] = $good['discount'];
                }
            }
                foreach($discounts as $discount)
                {
                  $this->albums[md5($discount)] = $this->Router->craeateAlbum($discount);
                }
         return $this->albums;   
    }
    private function setCategoryes()
    {
        $this->categoryes = [];
        $covers = [];
            foreach($this->goods as $good)
            {
                if(array_key_exists('categoryes', $good))
                {
                   $categoryes = explode('|', $good['categoryes']);
                        foreach($categoryes as $category)   
                        {
                            if(in_array($category, $categoryes))
                            {
                                continue;
                            }
                            $categoryes[] = $category;
                            $covers[md5($category)] = $good['picture'];
                        }
                }
            }
                foreach($categoryes as $category)
                {
                  $this->categoryes[md5($category)] = $this->Router->craeateAlbum($category);
                }
                
         return $this->categoryes;
    } 
    public function setDiscountsAndCategoryes()
    {
        $result = [];
        $this->Router->deleteAlbums();
        $result['discounts']  = $this->setDiscounts();
        $result['categoryes'] = $this->setCategoryes();
        $res = array_merge($result['discounts'], $result['categoryes']);
        $this->albums = $res;
        return;
    }
    public function startParsing($goodIDs)
    { 
        $result =[];
        $this->initGoods($goodIDs);
        $updateGoods = $this->getGoodsUpdate($this->goods, $this->description, $this->utm, $this->existGoodsItemids);
                if(count($updateGoods) > 0)
                {
                    foreach ($updateGoods as $good)
                    {
                        if($good['good_id'] != 'hayrash0113')
                        {
                            continue;
                        }
                        $result = [];
                        $goodData = $this->VkGoodFormater->getGoodAnsw($this->existGoodsItemids, $this->Router, $good, $this->GROUP_ID, $this->OWNER_ID, $this->ACCESS_TOKEN, $this->description, 'UPDATE_GOODS');
                            if(!$goodData)
                            {
                                continue;   
                            }
                        $this->Router->sendGood($this, $good, $goodData,  'UPDATE_GOODS');
                        $hash = $this->getHash($good, $this->description, $this->utm);
                        $result['action'] = 'UPDATE_GOODS';
                        $result['good_id'] =  $good['good_id'];
                        $result['shop_id'] =  $this->GROUP_ID;
                        $result['hash']    =  $hash;
                        call_user_func($this->userClass.'::'.$this->endpoint, $result);
                        sleep(\common\components\VkParser\VKParser::TIMEOUT);
                    }
                } 
                return;
                $createGoods = $this->getGoodsCreate($this->goods);
                if(count($createGoods) > 0)    
                {
                    foreach ($createGoods as $good)
                    {
                        if(!array_key_exists('good_id', $good))
                        {
                            continue;
                        }
                        $goodData = $this->VkGoodFormater->getGoodAnsw($this->existGoodsItemids, $this->Router, $good, $this->GROUP_ID, $this->OWNER_ID, $this->ACCESS_TOKEN, $this->description, 'CREATE_GOODS');
                        $itemID = $this->Router->sendGood($this, $good, $goodData,'CREATE_GOODS');
                            if(is_int($itemID))
                            {
                                $result= [];
                                $hash = $this->getHash($good, $this->description, $this->utm);
                                $result['action'] = 'CREATE_GOODS';
                                $result['good_id'] =  $good['good_id'];
                                $result['hash']    =  $hash;
                                $result['shop_id'] =  $this->GROUP_ID;
                                $result['item_id'] =  $itemID;
                                call_user_func($this->userClass.'::'.$this->endpoint, $result);
                            }
                        sleep(\common\components\VkParser\VKParser::TIMEOUT);
                    }
                }
                $deleteGoods = $this->getGoodsDelete($this->goods);
                    if(count($deleteGoods) > 0 && $deleteGoods)
                    {
                        $result = [];
                        foreach($deleteGoods as $good)
                        {
                            $this->Router->deleteGood($this, $good);
                            $result['action'] = 'DELETE_GOODS';
                            $result['good_id'] = $good;
                            $result['shop_id'] =  $this->GROUP_ID;
                            call_user_func($this->userClass.'::'.$this->endpoint, $result);
                            sleep(\common\components\VkParser\VKParser::TIMEOUT);
                        }
                    }
                
       $this->Router->sendNotes($this);             
        return $result;
    }
    public function sendNotes()
    {
        if(!$this->useNotes) return;
        $this->Router->sendPost('Test  post');
        return;
    }
    public function finish()
    {
        if(!$this->Router->sended) return;
        if(!$this->useCategoryes) return;
        if(count($this->goods) == 0) return;
        return;
        echo 'finish()';
        $this->setDiscountsAndCategoryes();
        $VkAlbums = new VkAlbums;
        $VkAlbums->Init($this->albums);
            foreach($this->goods as $good)
            {
                $goodAbums = $VkAlbums->getGoodAlbum($good);
                if(!$goodAbums) continue;
                if(!array_key_exists($good['good_id'], $this->existGoodsItemids)) continue;
                $this->Router->addToAlbum($goodAbums, $this->existGoodsItemids[$good['good_id']]);
            }
        return;
                
    }
}