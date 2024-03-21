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
        //$goodsModels->byDiscountsgoods($discounts);
        $goodsModels->groupBy('good.code');
        $goodsModels->limit(3);
        //$goodsModels->orderBy('good.id desc');
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
        $VKParser->userClass = '\console\controllers\VkController';
        $VKParser->endpoint = 'endPoint';
        $promoPosts = [];
        $promoPosts[0] = [];
        //$promoPosts[0]['album'] = '-40% на компрессионную одежду*';
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
                    $goods[$i]['name']        = $goodItem->title.rand(0, 5);
                    $goods[$i]['vendor']      = $goodItem->vendor->title;
                    $goods[$i]['color']       = $goodItem->color;
                    $goods[$i]['size']        = $goodItem->size;            
                    $categoryes = explode('|', $goodItem->categoryes);
                    $goodCats = [];
                    $goodCats[] = 'Каталог';
                        foreach($categoryes as $category)
                        {
                            if($category == 'Одежджа' || $category == 'Экипировка' || $category == 'Футболки' || $category == 'Рашгарды' || mb_stripos($category, 'перчатки') !== false)
                            {

                                if(!in_array($category, $goodCats))
                                {
                                    $goodCats[] = $category;
                                }
                            }
                        }
                    $goods[$i]['categoryes']  = implode('|', $goodCats); 
                    $i++;
            }
            $VKParser->goods = $goods;

            $goodIDs = new \yii\db\Query();
            $goodIDs->select(['good_id', 'hash', 'item_id'])
                    ->from('vk_goods')
                    ->where(['shop_id' => $VKParser->GROUP_ID])
                    ->groupBy('good_id');
            $goodIDs = $goodIDs->all();    
            $VKParser->startParsing($goodIDs);
         echo 'All Done!';
        return; 
    }
    public function endPoint($data)
    {
        $db = \Yii::$app->getDb();
            if($data['action'] == 'UPDATE_GOODS')
            {
                $answ = "UPDATE `vk_goods` SET hash = '".$data['hash']."' WHERE (`good_id` = '".$data['good_id']."' AND `shop_id` = ".$data['shop_id'].")";
                $db->createCommand($answ)->execute();
                return;
            }
            if($data['action'] == 'CREATE_GOODS')
            {
                $answ = "INSERT INTO vk_goods (id, good_id, hash, shop_id, item_id) VALUES('NULL', '".$data['good_id']."', '".$data['hash']."', ".$data['shop_id'].", ".$data['item_id'].")";
                $db->createCommand($answ)->execute();
                return;
            }
            if($data['action'] == 'DELETE_GOODS')
            {
                $answ = "DELETE FROM `vk_goods` WHERE (good_id = '".$data['good_id']."' AND `shop_id` = ".$data['shop_id'].")";
                $db->createCommand($answ)->execute();
                return;
            }

    }
}