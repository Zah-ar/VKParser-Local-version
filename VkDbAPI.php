<?php

namespace common\components\VkParser;

abstract class VkDbAPI extends Loger
{

    private $db;
    
    public function __construct()
    {
        $this->db = \Yii::$app->getDb();
      
    }
    public function getExistGoods()
    {
        $sql = "
                    SELECT 
                            `vk_goods`.`good_id`   as `good_id`
                     FROM
                            `vk_goods`             as `vk_goods`
                     WHERE
                            `vk_goods`.`shop_id` = ". $this->GROUP_ID  ."  
                      GROUP BY       
                            `vk_goods`.`good_id`   
                ";
        $query = $this->db->createCommand($sql);
        $queryResult = $query->queryAll();
        if (count($queryResult) == 0) return false;
        $result = [];
                foreach($queryResult as $item)
                {
                    $result[] = $item['good_id'];
                }
        return $result;
    }   
    public function insertGoods($goods, $existGoods)
    {
        $batchInsertArray = [];
        $updateGoods = [];
            foreach($goods as $good)
            {
                if($existGoods)
                {
                    if(in_array($good['good_id'], $existGoods))
                    {
                        $updateGoods[] = $good;
                        continue;
                    }
                }
                $batchInsertArray[] = array(
                    'id'             =>         NULL, 
                    'good_id'        =>        $good['good_id'],
                    'available'      =>        $good['available'],
                    'url'            =>        $good['url'],
                    'price'          =>        $good['price'],
                    'old_price'      =>        $good['old_price'],
                    'categoryId'     =>        $good['categoryId'],
                    'picture'        =>        $good['picture'],
                    'store'          =>        $good['store'],
                    'pickup'         =>        $good['pickup'],
                    'name'           =>        str_replace("'", "\'", $good['name']),
                    'vendor'         =>        str_replace("'", "\'", $good['vendor']),
                    'color'          =>        $good['color'],
                    'size'           =>        $good['size'],
                    'need_update'    =>        0,
                    'need_delete'    =>        0,
                    'shop_id'        =>        $this->GROUP_ID,
                    'item_id'        =>        NULL,
                    'error'          =>        0,
                );
            }
            if(count($updateGoods) > 0) $this->setUpdateGoods($updateGoods);
            if(count($batchInsertArray) == 0) return;
            $this->db->createCommand()->batchInsert('vk_goods', ['id','good_id','available','url', 'price', 'old_price', 'categoryId', 'picture', 'store', 'pickup', 'name', 'vendor', 'color', 'size', 'need_update', 'need_delete', 'shop_id', 'item_id', 'error'], $batchInsertArray)->execute();
        return;
    }
    public function setUpdateGoods($goods)
    {
            foreach($goods as $good)
            {
                $answ = "
                            UPDATE 
                                    vk_goods
                            SET
                                    available       = ".$good['available'].",
                                    url             = '".$good['url']."', 
                                    price           = ".$good['price'].", 
                                    old_price       = ".$good['old_price'].", 
                                    categoryId      = ".$good['categoryId'].", 
                                    picture         = '".$good['picture']."', 
                                    store           =  ".$good['store'].", 
                                    pickup          =  ".$good['pickup'].", 
                                    name            =  '".str_replace("'", "\'", $good['name'])."', 
                                    vendor          =  '".str_replace("'", "\'", $good['vendor'])."', 
                                    color           =  '".$good['color']."', 
                                    size            =  '".$good['size']."', 
                                    need_update   = 1

                            WHERE (good_id = '".$good['good_id']."' AND shop_id = ".$this->GROUP_ID.")";
                            $this->db->createCommand($answ)->execute();
            }
        return;
    }
    public function setDeleteGoods($goods, $existGoods)
    {
        if(!$existGoods) return;
        if(!$goods)
        {
            return $this->setDeleteAll();
        }
        $deleteGoodIds = [];
        $currentGoodIds = [];
            foreach($goods as $good)
            {
                //id текущей выгркзи
                $currentGoodIds[] = $good['good_id']; 
            }
            if(count($currentGoodIds) == 0)
            {
                return $this->setDeleteAll();
            }
            foreach($existGoods as $existGood)
            {
                if(!in_array($existGood, $currentGoodIds))
                {
                    //если товар отсутствует в новой выгрузкe
                    $answ = "UPDATE vk_goods SET need_delete = 1 WHERE good_id = '".$existGood."' AND shop_id = ".$this->GROUP_ID;
                    $this->db->createCommand($answ)->execute();
                }
            }

        return;
    }
    private function setDeleteAll()
    {
        $answ = "UPDATE vk_goods SET need_delete = 1 WHERE shop_id = ".$this->GROUP_ID;
        $this->db->createCommand($answ)->execute();
        return;

    }
    public function getGoods($action, $lastID)
    {
        $answ = "SELECT 
                        id          as id,
                        good_id     as good_id,
                        available   as available,
                        picture     as picture,
                        url         as url,
                        price       as price,
                        old_price   as old_price,
                        categoryId  as categoryId,
                        store       as store,
                        pickup      as pickup,
                        name        as name,
                        vendor      as vendor,
                        size        as size,
                        color       as color,
                        item_id     as item_id,
                        error       as error
                ";
                if($action == 'CREATE_GOODS')
                {
                    $answ .=  " FROM vk_goods WHERE shop_id = ".$this->GROUP_ID ." AND available = 1 AND item_id IS NULL"; 
                }else if($action == 'UPDATE_GOODS'){
                    $answ .=  " FROM vk_goods WHERE need_update = 1 AND shop_id = ".$this->GROUP_ID ." AND item_id IS NOT NULL"; 
                }else{
                    $answ .=  " FROM vk_goods WHERE need_delete = 1 AND shop_id  = ".$this->GROUP_ID ." AND item_id IS NOT NULL"; 
                }
            $answ .= " AND error = 0 ";
            $answ .= " AND id >  ".$lastID;
            $answ .= " GROUP BY good_id ";
            $answ .= " ORDER BY id ASC ";
            $answ .= " LIMIT 10 ";
            $query = $this->db->createCommand($answ);
            $queryResult = $query->queryAll();
            if (count($queryResult) == 0) return false;
                       
            $data = [];
                foreach($queryResult as $good)
                {
                    $result['last_id'] = $good['id'];
                    $data[]    = $good;
           
                }
           $result['data'] = $data;     
           return $result;     
    }
    public function setItemid($goodID, $item_id)
    {
      $answ = "UPDATE vk_goods SET item_id = ".$item_id." WHERE(good_id = '".$goodID."' AND shop_id = ".$this->GROUP_ID.")";
      $this->db->createCommand($answ)->execute();
      return;
    }
    public function deleteGood($good_id)
    {
      $answ = "DELETE FROM vk_goods WHERE (good_id = '".$good_id."' AND shop_id = ".$this->GROUP_ID.")";
      $this->db->createCommand($answ)->execute();
      return;
    }
    public function unsetUpdate($goodID)
    {
      $answ = "UPDATE vk_goods SET need_update = 0 WHERE(good_id = '".$goodID."' AND shop_id = ".$this->GROUP_ID.")";
      $this->db->createCommand($answ)->execute();
      return;
    }
}