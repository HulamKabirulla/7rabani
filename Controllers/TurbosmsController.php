<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Blog;
use App\Category;
use App\SubCategory;
use App\SubSubCategory;
use App\SeoText;
use App\Order;
use App\Traits\Auth;

class TurbosmsController extends Controller
{
    public function normJsonStr($str){
    $str = preg_replace_callback('/\\\\u([a-f0-9]{4})/i', function($m){return chr(hexdec($m[1])-1072+224);}, $str);
    return iconv('cp1251', 'utf-8', $str);
    }  

    //Отправить СМС
    public function sendmessage(Request $request)
    {
        /*
        {
   "recipients":[
      "380678998668",
      "380503288668",
      "380638998668"
   ],
   "sms":{
      "sender": "TurboSMS",
      "text": "TurboSMS приветствует Вас!"
   }
}
        */
        $messageVal=$request->messageVal;
        $orderNumber=$request->orderNumber;
        $IntDocNumber=$request->IntDocNumber;
        
        if($messageVal==0)
        {
            $textMessage="Ваш товар відправлений. Номер ТТН: ".$IntDocNumber;
            //$textMessage="Ваш заказ прийнятий, очікуйте дзвінок від менеджера";
        }
        else if($messageVal==1)
        {
            $textMessage="Шановний клієнт. Вас очікує посилка: ".$IntDocNumber;
        }
        else if($messageVal==2)
        {
            $textMessage="Ваша посилка на пошті 2 дні. Дуже просимо забрати";
        }
        else if($messageVal==3)
        {
            $textMessage="Будь ласка, заберіть посилку. Сподіваємось на чесність";
        }
        else if($messageVal==4)
        {
            $textMessage="Встигніть забрати посилку, щоб уникнути нарахувать";
        }
        else if($messageVal==5)
        {
            $textMessage="Закінчується час зберігання, скоріше до пошти";
            //$textMessage="Вибачте за докучання, але відміна коштує нам збитків. Сподіваємось на вас";
        }
        else if($messageVal==7)
        {
            //$textMessage="Закінчується час зберігання, скоріше до пошти";
            $textMessage="Залишився 1 день до платного зберігання. Не підводьте нас";
        }
        else if($messageVal==6)
        {
            $textMessage="Ваш заказ прийнятий, очікуйте дзвінок від менеджера";
        }
        $data = ["token" => "a80952810db1e07ad1c013708b4052ac38aaa4b8",
        "recipients" => [
            $orderNumber
        ],
        "sms" => [
            "sender" => "7rabani",
            "text" => $textMessage
        ]
    ];
        $data_string = json_encode ($data, JSON_UNESCAPED_UNICODE);
        $curl = curl_init('https://api.turbosms.ua/message/send.json?token=a80952810db1e07ad1c013708b4052ac38aaa4b8');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        // Принимаем в виде массива. (false - в виде объекта)
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
           'Content-Type: application/json',
           'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($curl);
        //Номер ТТН
        
        
        curl_close($curl);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
