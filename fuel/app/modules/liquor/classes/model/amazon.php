<?php
namespace Liquor;

class Model_Amazon
{

    private $serviceName      = '';
    private $secretAccessKey  = '';
    private $associateTag     = '';
    private $accessKey        = '';
    private $usleepSec        = 1000;
    private $memcachedHost    = '';
    private $memcachedPort    = '';
    private $remainSec        = 1800;

    private static $instance;

    private $memcached;

    /**
     * constructor
     * amazon apiに必要な設定値をセット
     */
    private function __construct()
    {
        \Config::load('api_config', true);
        $this->serviceName      = \Config::get('api_config.api.amazon.shopping.service_name');
        $this->secretAccessKey  = \Config::get('api_config.api.amazon.shopping.secret_access_key');
        $this->associateTag     = \Config::get('api_config.api.amazon.shopping.associate_tag');
        $this->accessKey        = \Config::get('api_config.api.amazon.shopping.access_key');
        $this->usleepSec        = \Config::get('api_config.api.amazon.shopping.api_usleep_milsec');
        $this->remainSec        = \Config::get('api_config.api.amazon.shopping.cache_remain_sec');
        $this->memcached = new \Memcached();
        $this->memcached->addServer(\Config::get('api_config.api.amazon.shopping.memcached_host'), \Config::get('api_config.api.amazon.shopping.memcached_port'));
    }

    public static function getInstance()
    {
        if ( is_null( self::$instance ) )
        {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     *　amazonのproduct apiをコールして結果をmemcacedに格納
     */
    public function getLiquorAffiliate() {
        $liquorList = $this->getLiquorList();
        if ($liquorList === false) {
            \Log::error("---- No data Available ----");
            exit;
        }

        foreach ($liquorList as $eachLiquor) {
            try {
                $this->getAmazonProduct($eachLiquor['id'], $eachLiquor['wiki_name']);
            } catch(\Exception $ex) {
                \Log::error('Amazon API Error :: '. $ex->getMessage());
                //APIの結果がとれない場合は次の商品へ
                continue;
            }
            usleep($this->usleepSec);
        }
    }

    /**
     * アマゾン商品取得API呼び出し
     *
     * @param  int     $productId      商品ID
     * @param  string  $productKeyword 検索するキーワード
     * @return void
     */
    private function getAmazonProduct($productId, $productKeyword)
    {
        $keyword = mb_convert_encoding($productKeyword, "UTF-8", "UTF-8");
        $memcachedProductKey = 'getAmazonProduct_' .$productId;

        //memcachedに結果データがなければAPIコールして結果をmemcachedに保存[APIアクセス数をなるべく減らす]
        $apiData = $this->memcached->get($memcachedProductKey);
        if (empty($apiData) === true) {
            $apiData = $this->getAmazonProductByApi($keyword);
            //FuelPHPのCacheクラスのsetメソッドはキーをハッシュ化するので他のFWや言語から呼びやすくするために使ってない
            $this->memcached->set($memcachedProductKey, $apiData, $this->remainSec);
        }
    }


    /**
     * AmazonProductApiをコールして結果を返す
     *
     * @param  string  $keyword
     * @return xml     $xmlData
     * @throws \Exception
     */
    private function getAmazonProductByApi($keyword) {
        $baseurl = 'http://ecs.amazonaws.jp/onca/xml';
        $params = array();
        $params['Service']        = $this->serviceName;
        $params['AWSAccessKeyId'] = $this->accessKey;
        $params['AssociateTag']   = $this->associateTag;
        $params['SearchIndex']    = 'All';

        // リクエスト定義（任意）
        $params['Operation'] = 'ItemSearch';
        $params['ResponseGroup'] = 'Large';
        $params['Keywords'] = $keyword;

        // Timestamp パラメータを追加 - 時間の表記は ISO8601 形式、タイムゾーンは UTC(GMT)
        $params['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');

        // パラメータ並び替え - 昇順
        ksort($params);

        // canonical string 作成
        $canonicalString = '';
        foreach ($params as $k => $v) {
            $canonicalString .= '&'.self::urlencode_rfc3986($k).'='.self::urlencode_rfc3986($v);
        }
        $canonicalString = substr($canonicalString, 1);

        // 署名作成 - 規定の文字列フォーマットを作成 - HMAC-SHA256 を計算 - BASE64 エンコード
        $parsedUrl = parse_url($baseurl);
        $stringToSign = "GET\n{$parsedUrl['host']}\n{$parsedUrl['path']}\n{$canonicalString}";
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $this->secretAccessKey, true));

        // URL作成 - リクエストの末尾に署名を追加
        $url = $baseurl.'?'.$canonicalString.'&Signature='.self::urlencode_rfc3986($signature);

        //ステータスコードが400系や500系の場合でもレスポンスを取得するため「ignore_errors」をtrueにセット
        $options = [
            'http' => [
                'ignore_errors' => true,
            ],
        ];
        $context = stream_context_create($options);
        $xmlData = file_get_contents($url, false, $context);
        $checkStatus = strpos($http_response_header[0], '200');
        //APIの結果がとれない場合は次の商品へ
        //response codeが「HTTP/1.1 200 OK」以外のケース
        if ($checkStatus === false) {
            throw new \Exception('amazon api response code error');
        }

        if (empty($xmlData) === true) {
            throw new \Exception('amazon api response xml empty error');
        }
        return $xmlData;
    }


    /*
     * RFC3986 形式で URL エンコードする
     * @param  string $str
     * @return string
     */
    private static function urlencode_rfc3986($str){
        return str_replace('%7E', '~', rawurlencode($str));
    }

    /*
     * DBからお酒リスト取得して返す
     *
     */
    private function getLiquorList()
    {
        $sql = 'select product.id, name, wiki_name, title from product inner join impression on impression.product_id = product.id group by product.name';
        $liquorlList = \DB::query($sql)->execute('liquor');
        if (!$liquorlList) {
            return false;
        }
        return $liquorlList;
    }
}
