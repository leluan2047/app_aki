<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */

namespace Plugin\MdlPaygent\Controller\Util;

/**
 * 決済モジュール基本クラス
 */
class PaygentHash
{
    /**
     * ハッシュ生成（ﾘﾝｸﾀｲﾌﾟ決済ﾊｯｼｭ区分：EC-CUBE用）
     *
     * ハッシュ値生成のための連結対象となるパラメータと連結順序は以下の通りです。
     * "マーチャント取引ID"+"決済種別"+"固定項目"+"請求金額"+"入金通知URL"+"マーチャントID"+"支払期間（日指定）"
     * +"支払期間（分指定）"+"支払区分"+"カード確認番号利用フラグ"+"顧客ID"+"3Dセキュア不要区分"+"ハッシュ値生成キー"
     */
    function setPaygentHash($arrSend, $hash_key) {
        // create hash hex string
        $default = array(
            'payment_class'=>'',
            'hash_key'=>$hash_key,
            'paygent_mark'=>'paygent2006',
            'trading_id'=>'',
            'id'=>'',
            'payment_type'=>'',
            'seq_merchant_id'=>'',
            'payment_term_day'=>'',
            'use_card_conf_number'=>'',
            'fix_params'=>'',
            'inform_url'=>'',
            'payment_term_min'=>'',
            'customer_id'=>'',
            'threedsecure_ryaku'=>'',
        );
    	$org_str = '';
        foreach ($default as $key=>$value) {
        	$org_str .= isset($arrSend[$key]) ? $arrSend[$key]:$value;
        }
        if (function_exists("hash")) {
            $hash_str = hash("sha256", $org_str);
        } elseif (function_exists("mhash")) {
            $hash_str = bin2hex(mhash(MHASH_SHA256, $org_str));
        } else {
            return;
        }

        // create random string
        $rand_char = array('a','b','c','d','e','f','A','B','C','D','E','F','0','1','2','3','4','5','6','7','8','9');
        for ($i = 0; ($i < 20 && rand(1,10) != 10); $i++) {
            $rand_str .= $rand_char[rand(0, count($rand_char)-1)];
        }

        return $hash_str. $rand_str;
    }
}    
?>
