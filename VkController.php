<?php

namespace console\controllers;

use yii\console\Controller;

class VkController extends Controller
{
    public function actionRun()
    {
        set_time_limit(0);
        $discounts = [];
        $discounts[] = 1499;
        $goodsModels = \common\models\Shop\Good\Good::find()->select('good.*')->joinWith('page')->with(['page','vendor','images','cover', 'categories'])->where(['page.is_published' => 1])->andWhere(['or', ['>','stock',0] , ['>','stock_msk',0]]);
        $goodsModels->byDiscountsgoods($discounts);
        $goodsModels->groupBy('good.code');
        $goodsModels->limit(5);
        //$goodsModels->orderBy(new \yii\db\Expression('rand()'));
        $goodsModels = $goodsModels->all();
        $VKParser = new \common\components\VkParser\VkParser;
        $VKParser->ACCESS_TOKEN = 'vk1.a.OFzMAlQgGV5r11inRWeKJseBO4GoxbZ0wUoHXCdVsE9cds5UfCPf763arYQqyzR2IjvGrZVczmyX71uwREhb9__RdXzLu80DT1fV3iFO8vHNibXkeAwg9ZxNN-SzADf09WNo-e6PdtXfv_yOU7PEqBVxWgGp_3_AMcUd1x_E1nu71aEhWyVIk0t3PH1PTuYXzgvEUelAmQ_IK3JBIsRGTw';
        $VKParser->GROUP_ID     = 223876149;
        $VKParser->OWNER_ID     = -223876149;
        $VKParser->Init();
        $VKParser->usePromocategoryes  = true;
        $VKParser->useCategoryes = true;
        $VKParser->useNotes = true;
        $promoPosts = [];
        $promoPosts[0] = [];
        $promoPosts[0]['album'] = '-40% на компрессионную одежду*';
        $promoPosts[0]['text']  = 'Текст промопоста консоль';
        $promoPosts[0]['url']   = 'https://4mma.ru/catalog/promo-1498/';
        $promoPosts[0]['image']   = \Yii::getAlias('@frontend') . '/web/media/images/5acc425c241cec23a1ad55059d8b527f.jpg';
        $VKParser->promoPosts = $promoPosts;
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
                        $goods[$i]['discount']  = $goodPrice['discount_title'];
                    }else{
                        $goods[$i]['old_price'] = 0;
                        $goods[$i]['price'] = $goodItem->price;
                    }
                    $goods[$i]['categoryId'] = $goodItem->category_ids[0];
                    $goods[$i]['picture']    =  \Yii::getAlias('@frontend') . '/web/media/images/'.$goodItem->images[0]->filename;
                    $goods[$i]['store']       = 1;
                    $goods[$i]['pickup']      = 1;
                    $goods[$i]['name']        = $goodItem->title;
                    $goods[$i]['vendor']      = $goodItem->vendor->title;
                    $goods[$i]['color']       = $goodItem->color;
                    $goods[$i]['size']        = $goodItem->size;            
                    $goods[$i]['categoryes']  = $goodItem->categoryes; 
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
            $VKParser->Router->sendNotes($VKParser);
        
                $updateGoods = $VKParser->getGoodsUpdate($goods);
                if(count($updateGoods) > 0)
                {
                    foreach ($updateGoods as $good)
                    {
                        $goodData = $VKParser->VkGoodFormater->getGoodAnsw($VKParser->existGoodsItemids, $VKParser->Router, $good, $VKParser->GROUP_ID, $VKParser->OWNER_ID, $VKParser->ACCESS_TOKEN, 'UPDATE_GOODS');
                            if(!$goodData)
                            {
                                continue;   
                            }
                        $VKParser->Router->sendGood($VKParser, $good, $goodData,  'UPDATE_GOODS');
                        $hash = $VKParser->getHash($good);
                        $answ = "UPDATE vk_goods SET hash = '".$hash."' WHERE (good_id = '".$good['good_id']."' AND shop_id = ".$VKParser->GROUP_ID.")";
                        \Yii::$app->getDb()->createCommand($answ)->execute();
                            /*if($albums)
                            {   
                                $VKParser->Router->addToAlbum($VKParser->useCategoryes, $albums, $good, 'UPDATE_GOODS');                
                            }*/
                        sleep(\common\components\VkParser\VKParser::TIMEOUT);
                    }
                }
                $createGoods = $VKParser->getGoodsCreate($goods);
                if(count($createGoods) > 0)    
                {
                    foreach ($createGoods as $good)
                    {
                        $goodData = $VKParser->VkGoodFormater->getGoodAnsw($VKParser->existGoodsItemids, $VKParser->Router, $good, $VKParser->GROUP_ID, $VKParser->OWNER_ID, $VKParser->ACCESS_TOKEN,  'CREATE_GOODS');
                        //print_r($goodData);
                        $itemID = $VKParser->Router->sendGood($VKParser, $good, $goodData,'CREATE_GOODS');
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
                        $VKParser->deleteGood($VKParser, $good);
                        $answ = "DELETE FROM vk_goods WHERE (good_id = '".$good."' AND shop_id = ".$VKParser->GROUP_ID.")";
                        \Yii::$app->getDb()->createCommand($answ)->execute();                  
                        sleep(\common\components\VkParser\VKParser::TIMEOUT);
                    }
                }
                //$VKParser->Router->sendNotes($VKParser);      
             //   $VKParser->finish();

         echo 'All Done!';
        return; 
    }
}