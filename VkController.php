<?php

namespace console\controllers;

use yii\console\Controller;

class VkController extends Controller
{
    public function actionRun()
    {
        set_time_limit(0);
        $goodsModels = \common\models\Shop\Good\Good::find()->select('good.*')->joinWith('page')->with(['page','vendor','images','cover', 'categories'])->where(['page.is_published' => 1])->andWhere(['or', ['>','stock',0] , ['>','stock_msk',0]]);
        $goodsModels->groupBy('good.code');
        $goodsModels->limit(1);
        //$goodsModels->orderBy(new \yii\db\Expression('rand()'));
        $goodsModels = $goodsModels->all();
        $VKParser = new VkParser;
        $VKParser->ACCESS_TOKEN = '****************';
        $VKParser->GROUP_ID     = ******;
        $VKParser->OWNER_ID     = -*****;
        $VKParser->Init();
        $goods = [];
        $i = 0;
            foreach ($goodsModels as $goodItem)
            {
                if(count($goodItem->images) == 0) 
                {
                    continue;
                }
                if(!file_exists( \Yii::getAlias('@frontend') . '/web/media/images/'.$goodItem->images[0]->filename))
                {
                    continue;
                }
                $goods[$i] = [];
                $goods[$i]['good_id']    = $goodItem->code;
                $goods[$i]['available']  = 1;
                $goods[$i]['url']        = 'https://4mma.ru/good/'.$goodItem->id.'/';
                $goodPrice = $goodItem->getGoodricePublicDiscounts($goodItem->id, false, true);//Обновить метод
                    if($goodPrice != false)
                    {
                        $goods[$i]['price'] = $goodPrice['good_discount_discount_price'];
                        $goods[$i]['old_price'] = $goodItem->price;
                    }else{
                        $goods[$i]['old_price'] = 0;
                        $goods[$i]['price'] = $goodItem->price;
                    }
                    $goods[$i]['categoryId'] = $goodItem->category_ids[0];
                    $goods[$i]['picture']    =  \Yii::getAlias('@frontend') . '/web/media/images/'.$goodItem->images[0]->filename;
                    $goods[$i]['store']       = 1;
                    $goods[$i]['pickup']      = 1;
                    $goods[$i]['name']        = 'собаколёт '.$goodItem->title;
                    $goods[$i]['vendor']      = $goodItem->vendor->title;
                    $goods[$i]['color']       = $goodItem->color;
                    $goods[$i]['size']       = $goodItem->size;
                $i++;
            }
            $VKParser->goods = $goods;

            $goodIDs = new \yii\db\Query();
            $goodIDs->select(['good_id', 'hash', 'item_id'])
                    ->from('vk_goods')
                    ->where(['shop_id' => $VKParser->GROUP_ID])
                    ->groupBy('good_id');
            $goodIDs = $goodIDs->all();                             
            $VKParser->initGoods($goodIDs);
            $updateGoods = $VKParser->getGoodsUpdate($goods);
            //echo count($updateGoods);
                if(count($updateGoods) > 0)
                {
                    foreach ($updateGoods as $good)
                    {
                        $goodData = $VKParser->VkGoodFormater->getGoodAnsw($VKParser->existGoodsItemids, $VKParser->Router, $good, $VKParser->GROUP_ID, $VKParser->OWNER_ID, $VKParser->ACCESS_TOKEN, 'UPDATE_GOODS');
                            if(!$goodData)
                            {
                                continue;   
                            }
                        $VKParser->Router->sendGood($goodData, 'UPDATE_GOODS');
                        $hash = $VKParser->getHash($good);
                        $answ = "UPDATE vk_goods SET hash = '".$hash."' WHERE (good_id = '".$good['good_id']."' AND shop_id = ".$VKParser->GROUP_ID.")";
                        \Yii::$app->getDb()->createCommand($answ)->execute();                  
                        sleep(\common\components\VkParser\VKParser::TIMEOUT);
                    }
                }
                $createGoods = $VKParser->getGoodsCreate($goods);
                if(count($createGoods) > 0)    
                {
                    foreach ($createGoods as $good)
                    {
                        $goodData = $VKParser->VkGoodFormater->getGoodAnsw($VKParser->existGoodsItemids, $VKParser->Router, $good, $VKParser->GROUP_ID, $VKParser->OWNER_ID, $VKParser->ACCESS_TOKEN,  'CREATE_GOODS');
                        $itemID = $VKParser->Router->sendGood($goodData, 'CREATE_GOODS');
                            if(is_int($itemID))
                            {
                                $hash = $VKParser->getHash($good);
                                $answ = "INSERT INTO vk_goods (id, good_id, hash, shop_id, item_id) VALUES('NULL', '".$good['good_id']."', '".$hash."', ".$VKParser->GROUP_ID.", ".$itemID.")";
                               \Yii::$app->getDb()->createCommand($answ)->execute();                  
                            }
                        sleep(\common\components\VkParser\VKParser::TIMEOUT);
                    }

                }
                $deleteGoods = $VKParser->getGoodsDelete($goods);
                if(count($deleteGoods) > 0 && $deleteGoods)
                {
                    foreach ($deleteGoods as $good)
                    {
                        $VKParser->deleteGood($good);
                        $answ = "DELETE FROM vk_goods WHERE (good_id = '".$good."' AND shop_id = ".$VKParser->GROUP_ID.")";
                        \Yii::$app->getDb()->createCommand($answ)->execute();                  
                        sleep(\common\components\VkParser\VKParser::TIMEOUT);
                    }
                }

         echo 'All Done!';
        return;
    }
}