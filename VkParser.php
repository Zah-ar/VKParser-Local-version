<?php

namespace common\components\VkParser;

class VkParser extends VkParserApi
{
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
        $this->Loger             = new Loger;
        $this->Router            = new Router;
        $this->VkGoodFormater    = new VkGoodFormater;
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
    public function initGoods($goods)
    {
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
}