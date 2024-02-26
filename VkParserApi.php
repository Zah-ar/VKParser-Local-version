<?php

namespace common\components\VkParser;

abstract class VkParserApi extends VkDbAPI
{

    public $ACCESS_TOKEN;
    public $GROUP_ID;
    public $OWNER_ID;
    private $VkGoodFormater;
    private $Router;
    
    public function setGoods()
    {
        $existGoods = $this->getExistGoods();
            if(!is_array($this->goods)) return false;
            if(count($this->goods) == 0) return false;
            $this->insertGoods($this->goods, $existGoods);
            $this->setDeleteGoods($this->goods, $existGoods);
    }
   public function sendGoods()
   {
      $this->VkGoodFormater =  new VkGoodFormater;
      $this->Router = new Router;
      $this->Router->init('https://api.vk.com/method/', $this->ACCESS_TOKEN, $this->GROUP_ID, $this->OWNER_ID);
      //Удаление товаров
      $goods = $this->getGoods('DELETE_GOODS', 0);  
        if($goods)
        {
            do{
                $lastID = $goods['last_id'];
                    foreach($goods['data'] as $good)
                    { 
                        $this->Router->deleteGood($good);
                        $this->deleteGood($good['good_id']);
                        sleep(\common\components\VkParser\VkParser::TIMEOUT);
                    }
                $goods = $this->getGoods('DELETE_GOODS', $lastID);  
            }while($goods);
         
        }
        //обновление товаров
      $goods = $this->getGoods('UPDATE_GOODS', 0);  
      if($goods)
      {
          do{
              $lastID = $goods['last_id'];
                  foreach($goods['data'] as $good)
                  { 
                      $goodData = $this->VkGoodFormater->getGoodAnsw($this->Router, $good, $this->GROUP_ID, $this->OWNER_ID, $this->ACCESS_TOKEN, 'UPDATE_GOODS');
                      $this->Router->sendGood($goodData, 'UPDATE_GOODS');
                      $this->unsetUpdate($good['good_id']);
                      sleep(\common\components\VkParser\VkParser::TIMEOUT);
                  }
              $goods = $this->getGoods('UPDATE_GOODS', $lastID);  
          }while($goods);
      }
      //созданние товаров
      $goods = $this->getGoods('CREATE_GOODS', 0);  
        if($goods)
        {
            do{
                $lastID = $goods['last_id'];
                    foreach($goods['data'] as $good)
                    { 
                        $goodData = $this->VkGoodFormater->getGoodAnsw($this->Router, $good, $this->GROUP_ID, $this->OWNER_ID, $this->ACCESS_TOKEN, 'CREATE_GOODS');
                        $this->setDebug($goodData);
                        $item_id = $this->Router->sendGood($goodData, 'CREATE_GOODS');
                        if(is_int($item_id))
                        {
                            $this->setItemid($good['good_id'], $item_id);
                        }
                        sleep(\common\components\VkParser\VkParser::TIMEOUT);
                    }
                $goods = $this->getGoods('CREATE_GOODS', $lastID);  
            }while($goods);
        }
   }
}