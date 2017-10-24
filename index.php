<?php
/**
 * Created by PhpStorm.
 * User: wumin
 * Date: 2017/10/19
 * Time: 上午 12:29
 */
//require_once(dirname(__FILE__) . '/vendor/autoload.php');

// parameters
$hubVerifyToken = 'AccessToken';
$accessToken = "EAABunNudZBCEBAGzcIWOwY2qZApEYZBKJjZAiJHy5Ll2O9Bd2RiSDRE0ZBZCz0ZA29oihFpZBXP7AbadrwxUZC5yZCBi8GyiVKcFW4GYm0rRe0rZAvkKuomZBO52VXq6OzDpAbAEvhrjXqWEmHScW7kpCmvdH7NTDK7BarbFZAwfE5wnKPw3tsiFBjJbg";
$witToken = "6WHTFDIESQE4WKBJI5QKRVWS7TEOKP7I";

// 建立 mysql 連線
$server='localhost';
$id='wuming';
$pwd='22451010';
$dbname='god';
$link = mysqli_connect($server, $id, $pwd, $dbname) or die("無法開啟MySQL資料庫連結!");

mysqli_query($link, 'SET CHARACTER SET utf8');
mysqli_query($link, "SET collation_connection = 'utf8_general_ci'");

// check token at setup
if (!empty($_REQUEST['hub_mode']) && $_REQUEST['hub_mode'] == 'subscribe') {
    if($_REQUEST['hub_verify_token'] == $hubVerifyToken) {
        echo $_REQUEST['hu_challenge'];
        exit;
    }
}

// handle bot's anwser
$input = json_decode(file_get_contents('php://input'), true);

$senderId = $input['entry'][0]['messaging'][0]['sender']['id'];
$messageText = $input['entry'][0]['messaging'][0]['message']['text'];
$wit_url = "https://api.wit.ai/message?v=20171020&q=".$messageText;
$wit_auth = array("Authorization: Bearer ". $witToken);

$ch = curl_init();
curl_setopt($ch,CURLOPT_URL, $wit_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $wit_auth);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$fp = fopen("./response", "w+");
fwrite($fp, "url:".$wit_url."\n");
fwrite($fp, "auth:\n");
fwrite($fp, "response:".$response."\n");
$response = json_decode($response,true);
foreach ($wit_auth as $key => $value) {
    fwrite($fp, $key.":".$value);
}

$intent = $response['entities']['intent'][0]['value'];
$poem_num = $response['entities']['poem_number'][0]['value'];
fwrite($fp, "\nresponse:\n");
fwrite($fp, "intent:". $intent ."\n");
fwrite($fp, "poem_number".":".$poem_num."\n");
if ($intent == "Welcome") {
    $answer = "您好，我是神算子，有什麼需要我幫忙的嗎？";
} else if ($intent == "poem") {
    if (is_numeric($poem_num) ) {
        $poem_num = (int)$poem_num;
        if ($poem_num > 0 && $poem_num <= 60) {
            // 有取得籤號, 我們回覆籤詩及解釋
            $sql = "SELECT content, explanation, name FROM data WHERE id=". $poem_num;
            fwrite($fp, $sql."\n");
            $result = mysqli_query($link, $sql);
            while( $row = mysqli_fetch_row($result) ){
                $answer = $row[2]."\n籤詩：\n".$row[0]."\n"."解釋：\n".$row[1];
                fwrite($fp, "籤詩:". $row[0]."\n");
            }
            mysqli_free_result($result);
        } else {
            $answer = "很抱歉, 我們的籤詩只有一~六十籤!";
        }
    } else {
        $answer = "您是要問籤詩內容嗎？請告訴我您要問那支籤!";
    }
} else {
    if (is_numeric($poem_num) ) {
        $poem_num = (int)$poem_num;
        if ($poem_num > 0 && $poem_num <= 60) {
            // 有取得籤號, 我們回覆籤詩及解釋
            $sql = "SELECT content, explanation, name FROM data WHERE id=". $poem_num;
            fwrite($fp, $sql."\n");
            $result = mysqli_query($link, $sql);
            while( $row = mysqli_fetch_row($result) ){
                $answer = $row[2]."\n籤詩：\n".$row[0]."\n"."解釋：\n".$row[1];
                fwrite($fp, "籤詩:". $row[0]."\n");
            }
            mysqli_free_result($result);
        } else {
            $answer = "很抱歉, 我們的籤詩只有一~六十籤!";
        }
    } else {
        $answer = "很抱歉, 我不懂您的意思";
    }
}
fclose($fp);
$response = [
    'recipient' => [ 'id' => $senderId ],
    'message' => [ 'text' => $answer ]
];
$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token='.$accessToken);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_exec($ch);
curl_close($ch);
mysqli_close($link);