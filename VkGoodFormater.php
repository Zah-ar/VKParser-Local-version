<?php

namespace common\components\VkParser;

class VkGoodFormater extends VkParser
{
  public function getGoodAnsw($existGoodsItemids, $Rotuter, $good, $GROUP_ID, $OWNER_ID, $ACCESS_TOKEN, $description, $action)
  {
        if($action == 'CREATE_GOODS')
        {
            //структура товара для добавления
            $img = $Rotuter->sendImg($good['picture']); 
            $result = '';   
            $result  = 'owner_id='.$OWNER_ID;    
            $result .= '&name='.urlencode($good['name']);
            $result .= '&description='.urlencode(\common\components\VkParser\GoodTemplate::getDescription($good, $description));
            $result .= '&category_id='.$good['categoryId'];
            $result .= '&price='.$good['price'];
            $result .= '&url='.$good['url'];
            if($Rotuter->utm)
              {
                $result .= $Rotuter->utm;
              }
                if($good['old_price'] != 0)
                  {
                       $result .= '&old_price='.$good['old_price'];
                  }
                   $result .= '&main_photo_id='.$img;
                   $result .= '&id='.$good['good_id'];
                   $result .= '&sku='.$good['good_id'];
            return $result;
        }

        if($action == 'UPDATE_GOODS')
        {
          if(!is_array($existGoodsItemids)) return false;
          if(!array_key_exists($good['good_id'], $existGoodsItemids)) return false;
          $item_id = $existGoodsItemids[$good['good_id']];
            //структура товара для обновления
            $img = $Rotuter->sendImg($good['picture']);
            if(!is_int($img)) return false;
            $result = '';   
            $result  = 'owner_id='.$OWNER_ID;    
            $result .= '&name='.urlencode($good['name']);
            $result .= '&description='.urlencode(\common\components\VkParser\GoodTemplate::getDescription($good, $description));
            $result .= '&category_id='.$good['categoryId'];
            $result .= '&price='.$good['price'];
            $result .= '&url='.$good['url'];
            if($Rotuter->utm)
              {
                $result .= $Rotuter->utm;
              }
                if($good['old_price'] != 0)
                  {
                       $result .= '&old_price='.$good['old_price'];
                  }
                   $result .= '&main_photo_id='.$img;
                   $result .= '&id='.$good['good_id'];
                   $result .= '&item_id='.$item_id;
            return $result;
        }
        if($action == 'DELETE_GOODS')
        {
            //структура товара для удаления
            $result = '';   
            $result  = 'owner_id='.$OWNER_ID;    
            $result .= '&item_id='.$good;
            return $result;
        }

  }

}