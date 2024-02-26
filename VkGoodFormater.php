<?php

namespace common\components\VkParser;

class VkGoodFormater
{
  public function getGoodAnsw($Rotuter, $good, $GROUP_ID, $OWNER_ID, $ACCESS_TOKEN, $action)
  {
        if($action == 'CREATE_GOODS')
        {
            //структура товара для добавления
            $img = $Rotuter->sendImg($good['picture']);
            if(!is_int($img)) return false;
            $result = '';   
            $result  = 'owner_id='.$OWNER_ID;    
            $result .= '&name='.urlencode($good['name']);
            $result .= '&description='.urlencode(\common\components\VkParser\GoodTemplate::getDescription($good));
            $result .= '&category_id='.$good['categoryId'];
            $result .= '&price='.$good['price'];
            $result .= '&url='.$good['url'];
                if($good['old_price'] != 0)
                  {
                       $result .= '&old_price='.$good['old_price'];
                  }
                   $result .= '&main_photo_id='.$img;
                   $result .= '&id='.$good['good_id'];
            return $result;
        }

        if($action == 'UPDATE_GOODS')
        {
            //структура товара для обновления
            $img = $Rotuter->sendImg($good['picture']);
            if(!is_int($img)) return false;
            $result = '';   
            $result  = 'owner_id='.$OWNER_ID;    
            $result .= '&name='.urlencode($good['name']);
            $result .= '&description='.urlencode(\common\components\VkParser\GoodTemplate::getDescription($good));
            $result .= '&category_id='.$good['categoryId'];
            $result .= '&price='.$good['price'];
            $result .= '&url='.$good['url'];
                if($good['old_price'] != 0)
                  {
                       $result .= '&old_price='.$good['old_price'];
                  }
                   $result .= '&main_photo_id='.$img;
                   $result .= '&id='.$good['good_id'];
                   $result .= '&item_id='.$good['item_id'];
            return $result;
        }
  }

}