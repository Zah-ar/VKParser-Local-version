<?php
namespace common\components\VkParser;

class GoodTemplate
{
    private function setUrl(&$result, $good)
    {
        $result = str_replace('%url%', $good['url'], $result);
        return;
    }
    private function setParams(&$result, $good)
    {
        if($good['size'] !=  'no defined' && mb_strlen($good['size']) != 0)
        {
            $result = str_replace('%size%', $good['size'], $result);
        }else{
            $result = str_replace('%size%', '', $result);
            $result = str_replace('  ', '', $result);
        }
        if($good['color'] !=  'no defined' && mb_strlen($good['color']) != 0)
        {
            $result = str_replace('%color%', $good['color'], $result);
        }else{
            $result = str_replace('%color%', '', $result);
            $result = str_replace('  ', '', $result);
        }
        return;
    }
    public function getDescription($good)
    {
        $result = \common\components\VkParser\VkParser::DESCRIPTION;
        self::setUrl($result, $good);
        self::setParams($result, $good);
        return $result;
    }
}
    