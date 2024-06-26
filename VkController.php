<?php

namespace console\controllers;

use yii\console\Controller;

class VkController extends Controller
{
    public function actionRun()
    {
        /*
        $goodsModels = \common\models\Shop\Good\Good::find()->select('good.*')->joinWith('page')->with(['page','vendor','images','cover', 'categories'])->where(['page.is_published' => 1])->andWhere(['or', ['>','stock',0] , ['>','stock_msk',0]]);
        $goodsModels->andWhere(['good.new' =>1]);
        $goodsModels = $goodsModels->all();
            $codes = [];
            foreach($goodsModels as $good)
            {
                $codes[] = "'".$good->code."'";
            }
            echo count($codes);
            $codes = implode(',', $codes);
            $answ = "UPDATE `vk_goods` SET `hash` = 'NO_HASH' WHERE (`good_id` IN( ".$codes."))";
            $db = \Yii::$app->getDb();
            $db->createCommand($answ)->execute();
            
        return;     
        */
        /*
        $goods = \common\models\Shop\Good\Good::find()->byCategory(6)->all();
        $codes = [];
            foreach($goods as $good)
            {
                $codes[] = "'".$good->code."'";
            }
            echo count($codes);
            $codes = implode(',', $codes);
            $answ = "DELETE FROM `vk_goods` WHERE (`good_id` IN( ".$codes."))";
            $db = \Yii::$app->getDb();
            $db->createCommand($answ)->execute();
        return;*/
        $fileRunning = \Yii::getAlias('@frontend/runtime/logs/VKParser/').'vkParser_19766478.txt';
        if(file_exists($fileRunning)) 
        {
            
            echo 'Выгрузка уже выполняется.';
            unlink($fileRunning);
            return;
            
        }
        file_put_contents($fileRunning, 'Run');
        $feedSetting = \common\models\Shop\Ymfeed\Ymfeed::findOne(12);
        set_time_limit(0);
        $discounts = [];
        $discounts[] = 1499;
        $catsForAlbums = array(5, 6, 20, 21, 22, 8);
        $goodsModels = \common\models\Shop\Good\Good::find()->select('good.*')->joinWith('page')->with(['page','vendor','images','cover', 'categories'])->where(['page.is_published' => 1])->andWhere(['or', ['>','stock',0] , ['>','stock_msk',0]]);
        if (count($feedSetting->vendson_ids) > 0)
        {
            $goodsModels->andWhere(['IN', 'good.vendor_id', $feedSetting->vendson_ids]);
        }
        if (count($feedSetting->vendsoff_ids) > 0)
        {
            $goodsModels->andWhere(['NOT IN', 'good.vendor_id', $feedSetting->vendsoff_ids]);
        }
        if (count($feedSetting->catson_ids) > 0)
        {
            foreach ($feedSetting->catson_ids as $catson_id)
            {
                $categoryModel =  \common\models\Shop\Category\Category::findOne($catson_id);
                    if ($categoryModel != null)
                    {
                        $goodsModels->andFilterWhere(['LIKE', 'good.categoryes', '%|'.$categoryModel->title.'|%', false]);
                    }
            }
        }
        if (count($feedSetting->catsoff_ids) > 0)
        {
            foreach ($feedSetting->catsoff_ids as $catsoff_id)
            {
                $categoryModel =  \common\models\Shop\Category\Category::findOne($catsoff_id);
                if ($categoryModel != null)
                {
                    $goodsModels->andFilterWhere(['NOT LIKE', 'good.categoryes', '%'.$categoryModel->title.'%', false]);
                }
            }
        }
        if ($feedSetting->excluded_titles != '')
        {
            $excluded_titlesArr  = explode(",", $feedSetting->excluded_titles);
            if (count($excluded_titlesArr))
            {
                foreach ($excluded_titlesArr as $excluded_titlesArrItem)
                {
                    $goodsModels->andWhere(['AND',['NOT LIKE','good.title', trim($excluded_titlesArrItem)], ['NOT LIKE','good.code', trim($excluded_titlesArrItem)]]);
                }
            }
        }
    
        //$goodsModels->byDiscountsgoods($discounts);
        //$goodsModels->byCategory($catsForAlbums);
        $goodsModels->orderBy('good.id ASC');
        $goodsModels->addOrderBy('good.size ASC');
        $goodsModels->groupBy('good.code');
        //$goodsModels->offset(0);
        //$iteration = 5;
        //$goodsModels->limit(200 * $iteration);      
        $goodsModels->orderBy('good.id asc');
        //$goodsModels->orderBy(new \yii\db\Expression('rand()'));
        $goodsModels = $goodsModels->all();
        $VKParser = new \common\components\VkParser\VkParser;
        $VKParser->ACCESS_TOKEN = 'vk1.a.OFzMAlQgGV5r11inRWeKJseBO4GoxbZ0wUoHXCdVsE9cds5UfCPf763arYQqyzR2IjvGrZVczmyX71uwREhb9__RdXzLu80DT1fV3iFO8vHNibXkeAwg9ZxNN-SzADf09WNo-e6PdtXfv_yOU7PEqBVxWgGp_3_AMcUd1x_E1nu71aEhWyVIk0t3PH1PTuYXzgvEUelAmQ_IK3JBIsRGTw';
        $VKParser->utm = '%3Futm_source=vk.com%26utm_medium=VKontakte%26utm_campaign=vk_market';
        /* Test */
        /*$VKParser->GROUP_ID     = 223876149;
        $VKParser->OWNER_ID     = -223876149;*/
        /* Production */
        $VKParser->GROUP_ID     = 19766478;
        $VKParser->OWNER_ID     = -19766478;
        $VKParser->Init();
        $VKParser->usePromocategoryes  = false;
        $VKParser->useCategoryes = true;
        $VKParser->useNotes = false;
        $VKParser->userClass = '\console\controllers\VkController';
        $VKParser->endpoint = 'endPoint';
        $VKParser->description = 
        '
        - Размер: %size%
        - Цвет: %color%
        
        Для заказа пишите https://vk.com/club223876149 или звоните:
        8-800-333-47-04 (бесплатно по России)';
/*        $promoPosts = [];
        $promoPosts[0] = [];
        $promoPosts[0]['album'] = '-30% на кофты, спортивные штаны, бейсболки и шапки*';
        $promoPosts[0]['text']  = 'Текст промопоста консоль';
        $promoPosts[0]['url']   = 'https://4mma.ru/catalog/promo-1504/';
        $promoPosts[0]['image']   = \Yii::getAlias('@frontend') . '/web/media/images/5acc425c241cec23a1ad55059d8b527f.jpg';
        $VKParser->promoPosts = $promoPosts;*/
        $goods = [];
        $allCategoryes = [];
        $catsOff = [];
        $sports = \common\models\Shop\Category\Category::findOne(127);
        $sportsArr = $sports->getAllChildrenIds();
        $sportsArr[] = $sports->id;
        $promoCats = \common\models\Shop\Category\Category::findOne(216);
        $promoCatsArr = $promoCats->getAllChildrenIds();
        $promoCatsArr[]  = $promoCats->id;
        $promoCatsArr[] = 116;
        $promoCatsArr[] = 113;
        $catsOff = array_merge($sportsArr, $promoCatsArr);
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
                    $goods[$i]['categoryId']  =  $goodItem->category_ids[0];
                    $goods[$i]['picture']     =  \Yii::getAlias('@frontend') . '/web/media/images/'.$goodItem->images[0]->filename;
                    $goods[$i]['store']       = 1;
                    $goods[$i]['pickup']      = 1;
                        if($goodItem->code == 'testUpdateCode')
                        {
                            $goods[$i]['name']        = rand(0, 9).' '.$goodItem->title;
                        }else{
                            $goods[$i]['name']        = $goodItem->title;
                        }
                    $goods[$i]['vendor']      = $goodItem->vendor->title;
                    $color = str_replace('|', ', ', $goodItem->color);
                    $color = str_replace(', разноцветный', '', $color);
                    $color = str_replace(', Разноцветный', '', $color);
                    $color = str_replace('разноцветный, ', '', $color);
                    $color = str_replace('Разноцветный, ', '', $color);
                    
                    $goods[$i]['color']       = $color;
                   
                    $goods[$i]['size']        = $goodItem->size;            
                    $allGoodCategoryes = [];
                    $CategoryGoods = \common\models\Shop\CategoryGood::find()->where(['and',['good_id' => $goodItem->id], ['is_dynamic' => 0], ['NOT IN','category_id', $catsOff]])->all();
                        if($CategoryGoods != null)
                        {
                           
                            foreach($CategoryGoods as $CategoryGood)
                            {
                                
                                $lastCategory = \common\models\Shop\Category\Category::findOne($CategoryGood->category_id);
                                if($lastCategory == null) continue;
                                $goodCategoryes = \common\service\FeedcategoryService::getAllCategoryes($lastCategory);
                                if($goodCategoryes) $allGoodCategoryes = array_merge($allGoodCategoryes, $goodCategoryes);
                            }
                        }  
                        if($goodItem->new == 1)
                        {
                            $allGoodCategoryes[] = 'Новинки';
                        }
                        if($goodPrice != false)
                        {
                            $allGoodCategoryes[] = 'Акционные товары';
                        }         
                        if($goodItem->code == 'testCode')
                        {
                            print_r($allGoodCategoryes);
                        }
                    $goods[$i]['categoryes']  = implode('|', $allGoodCategoryes);
                    $i++;
            }
            //print_r($goods);
            $allCategoryes = array_unique($allCategoryes);
            $VKParser->goods = $goods;
            $goodIDs = new \yii\db\Query();
            $goodIDs->select(['good_id', 'hash', 'item_id'])
                    ->from('vk_goods')
                    ->where(['shop_id' => $VKParser->GROUP_ID])
                    ->groupBy('good_id');
            $goodIDs = $goodIDs->all();    
            $VKParser->startParsing($goodIDs);
                if(file_exists($fileRunning)) 
                {
                    unlink($fileRunning);
                }
         echo 'All Done!';
        return; 
    }
    public function actionUpdatebycategory()
    { 
        $catsOff = [];
        $sports = \common\models\Shop\Category\Category::findOne(127);
        $sportsArr = $sports->getAllChildrenIds();
        $sportsArr[] = $sports->id;
        $promoCats = \common\models\Shop\Category\Category::findOne(216);
        $promoCatsArr = $promoCats->getAllChildrenIds();
        $promoCatsArr[]  = $promoCats->id;
        $promoCatsArr[] = 116;
        $promoCatsArr[] = 113;
        $catsOff = array_merge($sportsArr, $promoCatsArr);
       
        $feedSetting = \common\models\Shop\Ymfeed\Ymfeed::findOne(12);
        set_time_limit(0);
        $VKParser = new \common\components\VkParser\VkParser;
        $VKParser->ACCESS_TOKEN = 'vk1.a.OFzMAlQgGV5r11inRWeKJseBO4GoxbZ0wUoHXCdVsE9cds5UfCPf763arYQqyzR2IjvGrZVczmyX71uwREhb9__RdXzLu80DT1fV3iFO8vHNibXkeAwg9ZxNN-SzADf09WNo-e6PdtXfv_yOU7PEqBVxWgGp_3_AMcUd1x_E1nu71aEhWyVIk0t3PH1PTuYXzgvEUelAmQ_IK3JBIsRGTw';
        $VKParser->utm = '%3Futm_source=vk.com%26utm_medium=VKontakte%26utm_campaign=vk_market';
     
        $VKParser->GROUP_ID     = 19766478;
        $VKParser->OWNER_ID     = -19766478;
        $VKParser->Init();
        $VKParser->usePromocategoryes  = false;
        $VKParser->useCategoryes = true;
        $VKParser->useNotes = false;
        $VKParser->userClass = '\console\controllers\VkController';
        $VKParser->endpoint = 'endPoint';
        $VKParser->updatCategory  = true;
        $VKParser->description = 
        '
        - Размер: %size%
        - Цвет: %color%
        
        Для заказа пишите https://vk.com/club223876149 или звоните:
        8-800-333-47-04 (бесплатно по России)';

        $updateCategory = \common\models\Shop\Category\Category::findOne(453);
        $updateCategoryes = $updateCategory->getAllChildrenIds();
        $updateCategoryes[] = $updateCategory->id;
        $goodsModels = \common\models\Shop\Good\Good::find()->select('good.*')->joinWith('page')->with(['page','vendor','images','cover', 'categories'])->where(['page.is_published' => 1])->andWhere(['or', ['>','stock',0] , ['>','stock_msk',0]]);
        if (count($feedSetting->vendson_ids) > 0)
        {
            $goodsModels->andWhere(['IN', 'good.vendor_id', $feedSetting->vendson_ids]);
        }
        if (count($feedSetting->vendsoff_ids) > 0)
        {
            $goodsModels->andWhere(['NOT IN', 'good.vendor_id', $feedSetting->vendsoff_ids]);
        }
        if (count($feedSetting->catson_ids) > 0)
        {
            foreach ($feedSetting->catson_ids as $catson_id)
            {
                $categoryModel =  \common\models\Shop\Category\Category::findOne($catson_id);
                    if ($categoryModel != null)
                    {
                        $goodsModels->andFilterWhere(['LIKE', 'good.categoryes', '%|'.$categoryModel->title.'|%', false]);
                    }
            }
        }
        if (count($feedSetting->catsoff_ids) > 0)
        {
            foreach ($feedSetting->catsoff_ids as $catsoff_id)
            {
                $categoryModel =  \common\models\Shop\Category\Category::findOne($catsoff_id);
                if ($categoryModel != null)
                {
                    $goodsModels->andFilterWhere(['NOT LIKE', 'good.categoryes', '%'.$categoryModel->title.'%', false]);
                }
            }
        }
        if ($feedSetting->excluded_titles != '')
        {
            $excluded_titlesArr  = explode(",", $feedSetting->excluded_titles);
            if (count($excluded_titlesArr))
            {
                foreach ($excluded_titlesArr as $excluded_titlesArrItem)
                {
                    $goodsModels->andWhere(['AND',['NOT LIKE','good.title', trim($excluded_titlesArrItem)], ['NOT LIKE','good.code', trim($excluded_titlesArrItem)]]);
                }
            }
        }
        $goodsModels->byCategory($updateCategoryes);
        $goodsModels->orderBy('good.id ASC');
        $goodsModels->addOrderBy('good.size ASC');
        $goodsModels->groupBy('good.code');
        $goodsModels->orderBy('good.id asc');
        echo $goodsModels->count();
        $goodsModels = $goodsModels->all();
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
                    $goods[$i]['categoryId']  =  $goodItem->category_ids[0];
                    $goods[$i]['picture']     =  \Yii::getAlias('@frontend') . '/web/media/images/'.$goodItem->images[0]->filename;
                    $goods[$i]['store']       = 1;
                    $goods[$i]['pickup']      = 1;
                        if($goodItem->code == 'testUpdateCode')
                        {
                            $goods[$i]['name']        = rand(0, 9).' '.$goodItem->title;
                        }else{
                            $goods[$i]['name']        = $goodItem->title;
                        }
                    $goods[$i]['vendor']      = $goodItem->vendor->title;
                    $color = str_replace('|', ', ', $goodItem->color);
                    $color = str_replace(', разноцветный', '', $color);
                    $color = str_replace(', Разноцветный', '', $color);
                    $color = str_replace('разноцветный, ', '', $color);
                    $color = str_replace('Разноцветный, ', '', $color);
                    
                    $goods[$i]['color']       = $color;
                   
                    $goods[$i]['size']        = $goodItem->size;            
                    $allGoodCategoryes = [];
                    $CategoryGoods = \common\models\Shop\CategoryGood::find()->where(['and',['good_id' => $goodItem->id], ['is_dynamic' => 0], ['NOT IN','category_id', $catsOff]])->all();
                        if($CategoryGoods != null)
                        {
                           
                            foreach($CategoryGoods as $CategoryGood)
                            {
                                
                                $lastCategory = \common\models\Shop\Category\Category::findOne($CategoryGood->category_id);
                                if($lastCategory == null) continue;
                                $goodCategoryes = \common\service\FeedcategoryService::getAllCategoryes($lastCategory);
                                if($goodCategoryes) $allGoodCategoryes = array_merge($allGoodCategoryes, $goodCategoryes);
                            }
                        }  
                        if($goodItem->new == 1)
                        {
                            $allGoodCategoryes[] = 'Новинки';
                        }
                        if($goodPrice != false)
                        {
                            $allGoodCategoryes[] = 'Акционные товары';
                        }         
                        if($goodItem->code == 'testCode')
                        {
                            print_r($allGoodCategoryes);
                        }
                    $goods[$i]['categoryes']  = implode('|', $allGoodCategoryes);
                    $i++;
            }
            //print_r($goods);
            //$allCategoryes = array_unique($allCategoryes);
            $VKParser->goods = $goods;
         
        $codes = [];
        $codesStr = [];
            foreach($goodsModels as $goodModel)
            {
                $codes[] = $goodModel->code;
                $codesStr[] = "'".$goodModel->code."'";
            }
           $goodIDs = new \yii\db\Query();
            $goodIDs->select(['good_id', 'hash', 'item_id'])
                    ->from('vk_goods')
                    ->where(['shop_id' => $VKParser->GROUP_ID])
                    ->andWhere(['IN', 'good_id', $codes])
                    ->groupBy('good_id');
            $goodIDs = $goodIDs->all(); 
                if($goodIDs != null)
                {
                    $VKParser->deleteGoodsByCategory($goodIDs);
                }
                $VKParser->sendGoodByCategory($goodIDs);
                return;
                if(count($codesStr) > 0)
                {
                    $codesStr = implode(",", $codesStr);
                    \Yii::$app->getDb();  
                    $answ = "DELETE FROM `vk_goods` WHERE (good_id IN('".$codesStr."') AND `shop_id` = ". $VKParser->GROUP_ID.")";
                    //$db->createCommand($answ)->execute();
               
                }   
            if($goodIDs == null) return;
            $VKParser->deleteGoodsByCategory($goodIDs);
        return ;
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