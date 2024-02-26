<?php

namespace common\components\VkParser;

class VkParser extends VkParserApi
{
    const TIMEOUT = 5;
    const DESCRIPTION = '%size%
    %color%

    
    Для заказа пишите https://vk.com/club223876149 или звоните:
    8-800-333-47-04 (бесплатно по России)';

    public function Init($ACCESS_TOKEN = false, $GROUP_ID = false, $goods)
    {
        ini_set('max_execution_time', 0);
        $this->Loger = new Loger;
        $this->ACCESS_TOKEN = $ACCESS_TOKEN;
        $this->GROUP_ID     = $GROUP_ID;
        $this->OWNER_ID     = -$GROUP_ID;
        $this->goods        = $goods;
        $this->setGoods();
        $this->setLog('[Info] Goods received...');
        $newGoods = $this->sendGoods();
        $this->setLog('[Info]Goods sended...');
        return;
    }
   
}