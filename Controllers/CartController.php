<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Client;
use App\Cart;
use App\Product;
use App\Order;

/*
	В этом контроллере все, что связано с корзиной.
	Добавление в корзину, удаление, изменение, подтвердение заказа
*/
class CartController extends Controller
{
    function instagramBuy(Request $request)
	{
		$comment=$request->comment;
		if($comment=="Заказ с инстаграмма: Мольберт детский")
		{
		    //Вот здесь отправляй запросы к себе в cms
		    
		}

		$vowels = array("(", ")", " ", "-");
		$numberOfClient=$request->numberClient;
		//$numberOfClient="380".str_replace($vowels, "", $numberOfClient);
		
		$newOrder=new Order;
		$newOrder->id_client=1;
		$newOrder->name=$request->nameClient;
		$newOrder->adress=$request->adressClient;
		$newOrder->number=$numberOfClient;
		$newOrder->comment=$comment;
		$newOrder->paymentType=1;
		$newOrder->save();
		
		if (Order::where('number', '=', $numberOfClient)->exists()) {
		    
        }
        else
        {
            //self::sendMessage("Заказ прийнятий. Очікуйте дзвінок або смс від менеджера",$numberOfClient);
        }
		
		

		return json_encode($comment);
	}
	
	function sendMessage($textMessage,$orderNumber)
	{
        
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
	}
    
	function confirmOrder(Request $request)
	{
		if($getCart=self::getCartApi())
		{
			$newOrder=new Order;
			$newOrder->id_client=$getCart['cartList'][0]->clientId;
			$newOrder->name=$request->nameClient;
			$newOrder->adress=$request->adressClient;
			$newOrder->number=$request->numberClient;
			if(trim($request->commentClient))
			{
				$newOrder->comment=$request->commentClient;
			}
			$newOrder->paymentType=$request->paymentTypeClient;
			$newOrder->save();

			Cart::where("id_client",$getCart['cartList'][0]->clientId)->where("id_order","0")->update(["id_order" => $newOrder->id]);
			return json_encode(["newOrder" => $newOrder,"responseText" => "Заказ отправлен в обработку. Мы свяжемся с вами в ближайшее время"]);
		}		

		return json_encode("Непредвиденная ошибка! Обновите страницу");
	}

	//Получение корзины
	function getCart()
	{
		/*$uNumber=\Cookie::get('uNumber');
		$uPassword=\Cookie::get('uPassword');
		$client=Client::where('number',$uNumber)->where('password',$uPassword)->first();
		if($client)
		{
			$cartList=Cart::leftJoin('products', function($join) {
			      $join->on('products.id', '=', 'carts.id_product');
			    })->leftJoin('images', function($join) {
			      $join->on('products.id', '=', 'images.id_product');
			    })->where("images.is_main","1")->where("carts.id_client",$client['id'])->where("carts.id_order","0")->select("products.id AS productId","products.name AS productName","carts.count AS productCount","carts.price AS productPrice","images.src AS productImage")->get();
			    //->orderBy("carts.updated_at","DESC")
	    	//$newCart->id_pro*/
	    	return response()->json([view('cart')->with(self::getCartApi())->render()]);
		//}
	}

	function getCartApi()
	{
		$uNumber=\Cookie::get('uNumber');
		$uPassword=\Cookie::get('uPassword');
		$client=Client::where('number',$uNumber)->where('password',$uPassword)->first();
		if($client)
		{
			$cartList=Cart::leftJoin('products', function($join) {
			      $join->on('products.id', '=', 'carts.id_product');
			    })->leftJoin('images', function($join) {
			      $join->on('products.id', '=', 'images.id_product');
			    })->where("images.is_main","1")->where("carts.id_client",$client['id'])->where("carts.id_order","0")->select("carts.id AS cartId","carts.id_client AS clientId","products.id AS productId","products.name AS productName","carts.count AS productCount","carts.price AS productPrice","images.src AS productImage")->get();
			    //->orderBy("carts.updated_at","DESC")
	    	//$newCart->id_pro
	    	return ["cartList" => $cartList, "client" => $client];
		}
		return null;
	}

	//Добавление в корзину
    public function addToCart(Request $request)
    {
    	/*
	return response()->json([
        'product' => view('product')->with('product',$product)->render()
    ]);
    	*/
    	$uNumber=\Cookie::get('uNumber');
		$uPassword=\Cookie::get('uPassword');
		$client=Client::where('number',$uNumber)->where('password',$uPassword)->first();
		if($client)
		{
			$product=Product::where("id",$request->productId)->where("isset","1")->first();

			//Проверяем добавлен ли данный товар уже в корзину
			/*$checkCart=Cart::where("id_client",$client['id'])->where("id_product",$product['id'])->where("action","0")->get()->first();
			if($checkCart)
			{

			}
			else
			{

	    	$newCart=new Cart;

	    	$newCart->id_client=$client['id'];
	    	$newCart->id_product=$request->productId;
	    	$newCart->count=$request->productCount;
	 		$newCart->action=0;
	 		$newCart->price=$product['price'];
	 		$newCart->save();*/


    Cart::updateOrCreate(["id_client" => $client['id'],
            "id_product" => $product['id'],
            "id_order" => 0
            ],["count" => $request->productCount,"price" => $product['price']]);

	 		

	    	$cartList=self::getCartApi();
			    //->orderBy("carts.updated_at","DESC")
	    	//$newCart->id_pro
	    	return response()->json([view('cart')->with(self::getCartApi())->render()]);
    	}
    	return json_encode(null);
    }

    function deleteCartById(Request $request)
    {
    	$uNumber=\Cookie::get('uNumber');
		$uPassword=\Cookie::get('uPassword');
		$cartId=$request->cartId;
		$client=Client::where('number',$uNumber)->where('password',$uPassword)->first();
		if($client)
		{
			Cart::where("id_client",$client['id'])->where("id",$cartId)->where("id_order","0")->delete();

			$cartList=self::getCartApi();
			    //->orderBy("carts.updated_at","DESC")
	    	//$newCart->id_pro
	    	return response()->json([view('cart')->with(self::getCartApi())->render()]);
		}
    }
}