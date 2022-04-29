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

class PromController extends Controller
{
    public function normJsonStr($str){
    $str = preg_replace_callback('/\\\\u([a-f0-9]{4})/i', function($m){return chr(hexdec($m[1])-1072+224);}, $str);
    return iconv('cp1251', 'utf-8', $str);
    }  

    //Создание ТТН Новой почты
    public function createTTN(Request $request)
    {
        $OptionsSeat=$request->OptionsSeat;
        $orderId=$request->orderId;
        $paymentType=$request->paymentType;
        $cost=$request->cost;
        $SeatsAmount=$request->SeatsAmount;
        
        $orderUpdate=Order::where('id',$orderId)->first();
        
        if($orderUpdate->IntDocNumber!=NULL)
        {
            return "ТТН уже создано! Удалите сначала прошлую ТТН";
        }
        //Наложеный платеж
        if($paymentType==0)
        {
            $BackwardDeliveryData = [
                [
                    "PayerType" => "Recipient",
                    "CargoType" => "Money",
                    "RedeliveryString" => $cost
                ]
            ];
        }
        else
        {
            $BackwardDeliveryData = "";
        }
        $OptionsSeat = str_replace("{", "[", $OptionsSeat);
        $OptionsSeat = str_replace("}", "]", $OptionsSeat);
        $OptionsSeat = json_decode($OptionsSeat);
        $data = ["apiKey" => "9911c50e77acd5aeafaeba1af3a719d3", "modelName" => "InternetDocument", "calledMethod" => "save",
    "methodProperties" => [
        "NewAddress" => "1",
        "PayerType" => "Sender",
        "PaymentMethod" => "Cash",
        "CargoType" => "Cargo",
        "BackwardDeliveryData" => $BackwardDeliveryData,
        "Weight" => "1",
        "ServiceType" => "WarehouseWarehouse",
        "Description" => "Массажер",
        "Cost" => $cost,
        "CitySender" => "e71c2a15-4b33-11e4-ab6d-005056801329",
        "Sender" => "f9652379-3dd7-11ea-9937-005056881c6b",
        "SenderAddress" => "5a6e2101-5629-11e5-8d8d-005056887b8d",
        "ContactSender" => "f96c9c4f-3dd7-11ea-9937-005056881c6b",
        "SendersPhone" => "380975169114",
        "RecipientCityName" => $request->RecipientCityName,
        "RecipientArea" => "",
        "RecipientAreaRegions" => "",
        "RecipientAddressName" => $request->RecipientAddressName,
        "RecipientHouse" => "",
        "RecipientFlat" => "",
        "RecipientName" => $request->reciepterName,
        "RecipientType" => "PrivatePerson",
        "RecipientsPhone" => $request->reciepterNumber,
        "DateTime" => "09.09.2021",
        "SeatsAmount" => $SeatsAmount,
        "OptionsSeat" => $OptionsSeat
        /*"OptionsSeat" => [
                    [
                        "volumetricVolume" => "1",
                        "volumetricWidth" => "30",
                        "volumetricLength" => "30",
                        "volumetricHeight" => "30",
                        "weight" => "2"
                    ],
                    [
                        "volumetricVolume" => "1",
                        "volumetricWidth" => "10",
                        "volumetricLength" => "10",
                        "volumetricHeight" => "10",
                        "weight" => "1"
                    ]
                ]*/
    ]];
        $data_string = json_encode ($data, JSON_UNESCAPED_UNICODE);
        $curl = curl_init('https://api.novaposhta.ua/v2.0/json/');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        // Принимаем в виде массива. (false - в виде объекта)
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
           'Content-Type: application/json',
           'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($curl);
        $result=self::normJsonStr($result);
        //Номер ТТН
        $IntDocNumber=explode("\"",explode("IntDocNumber\":\"",$result)[1])[0];
        $DocRef=explode("\"",explode("Ref\":\"",$result)[1])[0];
        
        $orderUpdate->IntDocNumber=$IntDocNumber;
        $orderUpdate->DocRef=$DocRef;
        $orderUpdate->save();
        
        curl_close($curl);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

	public function getProductsPriceFromCatalog(Request $request)
    {
        $searchProduct=$request->searchProduct;
        
        /*$handle = curl_init();
    curl_setopt($handle, CURLOPT_URL,'https://prom.ua/search?search_term='.$searchProduct);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $homepage = curl_exec($handle);
    $homepage=self::normJsonStr($homepage);
    curl_close($handle);*/
    $html = file_get_contents('https://prom.ua/search?search_term=%D0%94%D0%BE%D1%81%D0%BA%D0%B0%20%D0%B4%D0%BB%D1%8F%20%D0%BE%D1%82%D0%B6%D0%B8%D0%BC%D0%B0%D0%BD%D0%B8%D0%B9%20Foldable%20Push%20Up%20Board%2014%20%D0%B2%201%20%7C%20%D0%A3%D0%BF%D0%BE%D1%80%20%D0%BF%D0%BE%D0%B4%D1%81%D1%82%D0%B0%D0%B2%D0%BA%D0%B0%20%D0%B4%D0%BB%D1%8F%20%D0%BE%D1%82%D0%B6%D0%B8%D0%BC%D0%B0%D0%BD%D0%B8%D0%B9%20%7C%20%D0%9F%D0%BB%D0%B0%D1%82%D1%84%D0%BE%D1%80%D0%BC%D0%B0'); 
    
    
        return json_encode($html, JSON_UNESCAPED_UNICODE);
    }
}
