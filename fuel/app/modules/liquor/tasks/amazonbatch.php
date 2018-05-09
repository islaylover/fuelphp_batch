<?php
namespace Fuel\Tasks;
use Fuel\Core\Cli;
use Fuel\Core\DB;
use Fuel\Core\DBUtil;
use Curl\CurlUtil;
use \Model\Util;

class Amazonbatch
{
    public function run()
    {
        \Liquor\Model_Amazon::getInstance()->getLiquorAffiliate();
    }


}
?>
