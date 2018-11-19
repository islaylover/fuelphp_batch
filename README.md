# fuelphp_batch
Run batch on FulePHP/modules

## やること
fuelのモジュール[モジュールとは、独立した MVC 要素のグループのことですモジュールを使うと、コードを再利用しカプセル化することが可能に
なります。 モジュールは、通常、アプリケーションディレクトリの下の modules ディレクトリに置かれます。 結構な量のコードをも
つ大きなプロジェクトを動かしている場合、 モジュールを使ってみてはいかがでしょうか。 モジュールは、物事をきちんと秩序立て
て整理する助けになるでしょう。] http://fuelphp.jp/docs/1.8/general/modules.html　より
を使い・・・まあ個人のサイトではあまり必要ないだろうけど・・・敢えてモジュール内でamazonのapiをコールするバッチを実装してみる

## 機能詳細
Database[mysql]に保存されたお酒の名前を基にamazonのAmazon.co.jp Product Advertisingをコールし、
結果をmemcachedに数時間保存するバッチを作る
*Amazon.co.jp Product Advertising API ライセンス契約
https://affiliate.amazon.co.jp/help/operating/paapilicenseagreement

## 前提
# １：Amazon.co.jp Product Advertising APIを使えるようにユーザー登録済でかつ各種必要なキーを取得済であること

# ２：同一サーバにMysql5.5(以上)がインストールされ、以下のようなDB・テーブル構成であること
DataBase名:spirits　[user:osake passwd:daisuki1234]
紐づくTable名：product
```
=============TABLE構成================
CREATE TABLE `product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `kana_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
======================================
```
※ 各自の環境にあわせて変える事
※ fuel/app/config/db.phpにしか使うDB接続設定をしていないので必要な場合は
設定をわけること

# ３：同一サーバにmemcachedがインストールされている事

# ４：このプログラムが
```
/var/www/sample/fuelphp-1.8
                |-fuel/app/modules/liquor [ココ]
                |-public/
                |-docs/
                oil
```
のように設置されているとする

# ５：「fuel/app/modules/liquor/config/api_config.php」に１のアマゾンAPIを使うのに必要な
正しい値が記述されていること
※ 当然このサンプルのままでは動きません

□cronへ定時処理登録[プログラムのパスは各自の環境にあわせる事]
0 */2 * * * php /var/www/sample/fuelphp-1.8/oil refine liquor::amazonbatch
