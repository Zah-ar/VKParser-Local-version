<?php

namespace common\components\VkParser;

class VkParser extends VkParserApi
{
    private $albums;
    public $vkAlbums;
    public $categoryes;
    public $goodCategoryes;
    public $useCategoryes;
    public $usePromocategoryes;
    public $existGoods;
    public $existGoodsHash;
    public $existGoodsItemids;
    public $Router;
    public $VkGoodFormater;

    const TIMEOUT = 5;
    const DESCRIPTION = '%size%
    %color%

    
    Для заказа пишите https://vk.com/club223876149 или звоните:
    8-800-333-47-04 (бесплатно по России)';

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
        $this->Router->init('https://api.vk.com/method/', $this->ACCESS_TOKEN, $this->GROUP_ID, $this->OWNER_ID);
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
        $existGoodsItemids    = $this->existGoodsItemids;
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
    private function setAlbums()
    {
        $this->vkAlbums = $this->Router->initAlbums();
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
                        $albums = $this->vkAlbums;
                    }
                    foreach($this->goodCategoryes as $category)
                    {
                        if(array_key_exists(md5($category), $albums))
                        {
                            continue;
                        }
                        $albumID = $this->Router->craeateAlbum($category);
                        $albums[md5($category)] = $albumID;
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
    public function initGoods($goods)
    {
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
        $categoryes = [];
        $this->categoryes = [];
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