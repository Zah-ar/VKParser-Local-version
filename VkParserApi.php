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
   public function getHash($good, $description, $utm)
   {
        $discount = false;
        if(array_key_exists('discount', $good)) $discount = $good['discount'];
        $good = $good['available'].$good['url'].$good['price'].$good['old_price'].$good['categoryId'].$good['picture'].$good['store'].$good['pickup'].$good['name'].$good['vendor'].$good['color'].$good['size'].$good['categoryes'];
        if($utm) $good .= $utm;
        //if($this->promoAlbums && $discount) $good .= $discount;
        return md5($good.$description);
   }
   public function sendGoods($action, $goods)
   {
      $this->VkGoodFormater =  new VkGoodFormater;
      $this->Router = new Router;
      $this->Router->init('https://api.vk.com/method/', $this->ACCESS_TOKEN, $this->GROUP_ID, $this->OWNER_ID);
   }
   public function deleteGood($VKParser, $good_id)
   {
    return;
        $this->Router = new Router;
        $this->Router->init('https://api.vk.com/method/', $this->ACCESS_TOKEN, $this->GROUP_ID, $this->OWNER_ID);  
        if($this->existGoodsItemids)
        {
            if(array_key_exists($good_id ,$this->existGoodsItemids))
            {
                $goodData = $this->Router->deleteGood($VKParser, $this->existGoodsItemids[$good_id]);
                return $this->existGoodsItemids[$good_id];
            }
        }
      return false;
   }
}