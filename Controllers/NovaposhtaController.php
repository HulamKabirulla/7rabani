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
use Carbon\Carbon;
use DateTime;

class NovaposhtaController extends Controller
{
    public function normJsonStr($str){
    $str = preg_replace_callback('/\\\\u([a-f0-9]{4})/i', function($m){return chr(hexdec($m[1])-1072+224);}, $str);
    return iconv('cp1251', 'utf-8', $str);
    }  
    
    public function updateTTNstatus()
    {
        //DatePayedKeeping - дата начала платного хранения
        $DocumentList=[];
            
            
        $DocumentList3=array();
        $allOrders=Order::where('action',"!=","0")->where('action',"!=","3")->whereNotNull('IntDocNumber')->get();
        for($i=0;$i<count($allOrders);$i++)
        {
            $DocumentList[$i]=["DocumentNumber" => $allOrders[$i]['IntDocNumber'],
                "Phone" => ""];
            array_push($DocumentList3, $DocumentList[$i]);
            //do something
        }
        
        /*array_push($DocumentList3, $DocumentList);
        array_push($DocumentList3, $DocumentList2);*/
        header('Content-Type: application/json; charset=utf-8');
        $data = ["apiKey" => "9911c50e77acd5aeafaeba1af3a719d3", "modelName" => "TrackingDocument", "calledMethod" => "getStatusDocuments",
    "methodProperties" => [
        "Documents" => 
           $DocumentList3
        
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
        //Номер ТТН
        
        /*$orderUpdate=Order::where('DocRef',$DocRef)->first();
        
        $orderUpdate->IntDocNumber=NULL;
        $orderUpdate->DocRef=NULL;
        $orderUpdate->save();*/
        
        $updateResultArray=json_decode($result, true);
        for($i=0;$i<count($updateResultArray["data"]);$i++)
        {
            $curNumber=$updateResultArray["data"][$i]['Number'];
            $curStatus=$updateResultArray["data"][$i]['Status'];
            
            //if(strcmp($curStatus,"Прибув у відділення")== 0)
            //{
                if($orderUpdate=Order::where('IntDocNumber',$curNumber)->first())
                {
                    if (array_key_exists('DatePayedKeeping', $updateResultArray["data"][$i])) {
                    $curExpireDate=$updateResultArray["data"][$i]['DatePayedKeeping'];
                    if($curExpireDate!=null) {
                        $now = new DateTime(); // текущее время на сервере
                        $date = DateTime::createFromFormat("Y-m-d", explode(" ",$curExpireDate)[0]); // задаем дату в любом формате
                        $interval = $now->diff($date); // получаем разницу в виде объекта DateInterval
                        //echo $interval->d, "\n"; // кол-во дней
                        
                        $orderUpdate->novapoch_expire=$curExpireDate;
                        $orderUpdate->novapoch_expiredays=$interval->d;
                        $orderUpdate->action=11-$interval->d;
                    }
                    }
                        if(strpos($curStatus, "отримано")!==false)
                        {
                            
                                $orderUpdate->action=2;
                        }
                        else if(strpos($curStatus, "Прибув у відділення")!==false&&$curExpireDate==null)
                        {//Здесь я убрал: &&$curExpireDate==null Если что добавить обратно
                            $orderUpdate->action=4;
                        }
                        else if(strpos($curStatus, "Відмова")!==false)
                        {
                            $orderUpdate->action=25;
                        }
                    
                        $orderUpdate->novapoch_stat=$curStatus;
                        $orderUpdate->save();
                }
            //}
        }
        
        curl_close($curl);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }


    //Удаление ТТН Новой почты
    public function deleteTTN(Request $request)
    {
        
        $DocRef=$request->DocRef;
        $data = ["apiKey" => "9911c50e77acd5aeafaeba1af3a719d3", "modelName" => "InternetDocument", "calledMethod" => "delete",
    "methodProperties" => [
        "DocumentRefs" => $DocRef
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
        //Номер ТТН
        
        $orderUpdate=Order::where('DocRef',$DocRef)->first();
        
        $orderUpdate->IntDocNumber=NULL;
        $orderUpdate->DocRef=NULL;
        $orderUpdate->save();
        
        curl_close($curl);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    //Создание ТТН Новой почты
    public function createTTN(Request $request)
    {
        $OptionsSeat=$request->OptionsSeat;
        $orderId=$request->orderId;
        $paymentType=$request->paymentType;
        $cost=$request->cost;
        $SeatsAmount=$request->SeatsAmount;
        $description=$request->orderDescription;
        
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
        
        $mytime = Carbon::now();
        $curYear=explode("-",$mytime->toDateTimeString())[0];
        $curMonth=explode("-",$mytime->toDateTimeString())[1];
        $curDay=explode("-",explode(" ",$mytime->toDateTimeString())[0])[2];
        $curDate=$curDay.".".$curMonth.".".$curYear;
        
        $OptionsSeat = str_replace("{", "[", $OptionsSeat);
        $OptionsSeat = str_replace("}", "]", $OptionsSeat);
        $OptionsSeat = json_decode($OptionsSeat);
        $data = ["apiKey" => "9911c50e77acd5aeafaeba1af3a719d3", "modelName" => "InternetDocument", "calledMethod" => "save",
    "methodProperties" => [
        "NewAddress" => "1",
        "PayerType" => "Recipient",
        "PaymentMethod" => "Cash",
        "CargoType" => "Cargo",
        "BackwardDeliveryData" => $BackwardDeliveryData,
        "Weight" => "1",
        "ServiceType" => "WarehouseWarehouse",
        "Description" => $description,
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
        "DateTime" => $curDate,
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
        //return json_encode($result, JSON_UNESCAPED_UNICODE);
        $IntDocNumber=explode("\"",explode("IntDocNumber\":\"",$result)[1])[0];
        $DocRef=explode("\"",explode("Ref\":\"",$result)[1])[0];
        
        $orderUpdate->IntDocNumber=$IntDocNumber;
        $orderUpdate->DocRef=$DocRef;
        $orderUpdate->save();
        
        curl_close($curl);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

	public function getCities(Request $request)
    {
        $searchCity=$request->searchCity;
        header('Content-Type: application/json; charset=utf-8');
        //$data = ["apiKey" => "9911c50e77acd5aeafaeba1af3a719d3", "modelName" => "InternetDocument", "calledMethod" => "getDocumentList", "methodProperties" => ["DateTimeFrom" => "21.04.2021", "DateTimeTo" => "28.04.2021", "Page" => "1", "GetFullList" => "0"]];
        //1 - Идентификатор города КИЕВ
        $data = ["apiKey" => "9911c50e77acd5aeafaeba1af3a719d3", "modelName" => "Address", "calledMethod" => "getCities",
    "methodProperties" => [
        "Page" => "1",
        "FindByString" => $searchCity
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
        curl_close($curl);
        
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
    
    public function getWarehouses(Request $request)
    {
        /*{
    "modelName": "AddressGeneral",
    "calledMethod": "getWarehouses",
    "methodProperties": {
        "Language": "ru",
        "CityRef": "db5c88d0-391c-11dd-90d9-001a92567626"
    },
    "apiKey": "9911c50e77acd5aeafaeba1af3a719d3"
}*/
        $cityRef=$request->cityRef;
        $warehouseNumber=$request->warehouseNumber;
        header('Content-Type: application/json; charset=utf-8');
        //$data = ["apiKey" => "9911c50e77acd5aeafaeba1af3a719d3", "modelName" => "InternetDocument", "calledMethod" => "getDocumentList", "methodProperties" => ["DateTimeFrom" => "21.04.2021", "DateTimeTo" => "28.04.2021", "Page" => "1", "GetFullList" => "0"]];
        //1 - Идентификатор города КИЕВ
        $data = ["apiKey" => "9911c50e77acd5aeafaeba1af3a719d3", "modelName" => "AddressGeneral", "calledMethod" => "getWarehouses",
    "methodProperties" => [
        "Language" => "ru",
        "CityRef" => $cityRef,
        "Number" => $warehouseNumber
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
        curl_close($curl);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
