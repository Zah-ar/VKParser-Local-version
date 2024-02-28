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
   public function getHash($good)
   {
        $good = $good['available'].$good['url'].$good['price'].$good['old_price'].$good['categoryId'].$good['picture'].$good['store'].$good['pickup'].$good['name'].$good['vendor'].$good['color'].$good['size'];
        return md5($good.\common\components\VkParser\VkParser::DESCRIPTION);
   }
   public function sendGoods($action, $goods)
   {
      $this->VkGoodFormater =  new VkGoodFormater;
      $this->Router = new Router;
      $this->Router->init('https://api.vk.com/method/', $this->ACCESS_TOKEN, $this->GROUP_ID, $this->OWNER_ID);
   }
   public function deleteGood($good_id)
   {
        $this->Router = new Router;
        $this->Router->init('https://api.vk.com/method/', $this->ACCESS_TOKEN, $this->GROUP_ID, $this->OWNER_ID);  
        if($this->existGoodsItemids)
        {
            if(array_key_exists($good_id ,$this->existGoodsItemids))
            {
                $goodData = $this->Router->deleteGood($this->existGoodsItemids[$good_id]);
                return $this->existGoodsItemids[$good_id];
            }
        }
      return false;
   }
}