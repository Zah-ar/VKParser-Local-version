<?php
namespace common\components\VkParser;

class VkAlbums
{
    private $albunms;

    public function Init($albums)
    {
        $this->albums = $albums;
    }
    public function getGoodAlbum($good)
    {
        $result = [];
        if(array_key_exists('categoryes',$good))
        {
            $categoryes = explode('|', $good['categoryes']);
                foreach($categoryes as $category)
                {
                    if(array_key_exists(md5($category), $this->albums)) 
                    {
                       if(is_int($this->albums[md5($category)])) $result[] = $this->albums[md5($category)];
                    } 
                }
        }
        if(array_key_exists('discount',$good))
        {
            $discounts = explode('|', $good['discount']);
                foreach($discounts as $discount)
                {
                    if(array_key_exists(md5($discount), $this->albums))
                    {
                      if(is_int($this->albums[md5($discount)])) $result[] = $this->albums[md5($discount)]; 
                    }
                }
        }
        if(count($result) == 0) return false;
     return $result;   
    }
}