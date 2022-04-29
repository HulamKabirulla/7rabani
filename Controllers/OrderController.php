<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Client;
use App\Cart;
use App\Product;
use App\Order;
use App\Category;
use App\SubCategory;
use App\SubSubCategory;
use App\Traits\Auth;

class OrderController extends Controller
{
	//Получение всех заказов
	public function getOrders($page="1")
    {
    	return view("orders",self::getOrdersApi($page));
    	//return response()->json([view('cart')->with('orderList',self::getOrdersApi())->render()]);
    }

    //Данные об конкретном заказе
    public function getOrderById(Request $request)
    {
    	return view("order",['orderList' => self::getOrderByIdApi($request->orderId)]);
    	//return response()->json([view('cart')->with('orderList',self::getOrdersApi())->render()]);
    }

    function getOrderByIdApi($orderId)
	{
		$uNumber=\Cookie::get('uNumber');
		$uPassword=\Cookie::get('uPassword');
		$client=Client::where('number',$uNumber)->where('password',$uPassword)->first();
		if($client)
		{
			$orderList=Cart::leftJoin('products', function($join) {
			      $join->on('products.id', '=', 'carts.id_product');
			    })->leftJoin('images', function($join) {
			      $join->on('products.id', '=', 'images.id_product');
			    })->leftJoin('orders', function($join) {
			      $join->on('orders.id', '=', 'carts.id_order');
			    })->where("images.is_main","1")->where("carts.id_client",$client['id'])->where("carts.id_order","=",$orderId)->select("orders.id AS orderId","orders.adress AS orderAdress","orders.number AS orderNumber","orders.priceDelivery AS orderPriceDelivery","orders.isPayedPayment AS isPayedPayment","orders.name AS orderName","orders.paymentType AS orderPaymentType","orders.comment AS orderComment","carts.id_client AS clientId","products.id AS productId","products.name AS productName","carts.count AS productCount","carts.price AS productPrice","images.src AS productImage")->get();
			    //->orderBy("carts.updated_at","DESC")
	    	//$newCart->id_pro
	    	return $orderList;
		}
		return null;
	}
	function getOrdersApi($page="1")
    {
    	$arrayPages=array();
		if($page<=2)
		{
			array_push($arrayPages, 1);
			array_push($arrayPages, 2);
			array_push($arrayPages, 3);
		}
		else if($page>=3)
		{
			array_push($arrayPages, $page-1);
			array_push($arrayPages, $page);
			array_push($arrayPages, $page+1);
		}

		$countPage=10;
		$skipPage=($page-1)*$countPage;

		$uNumber=\Cookie::get('uNumber');
		$uPassword=\Cookie::get('uPassword');
		$client=Client::where('number',$uNumber)->where('password',$uPassword)->first();
		if($client)
		{
			$orderListCount=Order::where("id_client",$client['id'])->select("orders.id")->get()->count();

			$orderList=Order::where("id_client",$client['id'])->select("orders.action AS orderAction","orders.created_at AS orderCreatedAt","orders.id AS orderId","orders.adress AS orderAdress","orders.number AS orderNumber","orders.name AS orderName","orders.paymentType AS orderPaymentType","orders.comment AS orderComment")->orderBy("orders.id","DESC")->skip($skipPage)->take($countPage)->get();

			$allPages=(int)($orderListCount/$countPage);
			$allPages=$allPages<=1?1:$allPages;
			    //->orderBy("carts.updated_at","DESC")
	    	//$newCart->id_pro
	    	$allCategories=Category::all();
			$allSubCategories=SubCategory::all();
			$allSubSubCategories=SubSubCategory::all();

			$clientAuth=Auth::isAuth();
	    	return ["orderList" => $orderList,"orderListCount" => $orderListCount,"allPages" => $allPages,"arrayPages" => $arrayPages,"curPage" => $page,"allCategories" => $allCategories,"allSubCategories" => $allSubCategories,"allSubSubCategories" => $allSubSubCategories,"clientAuth" => $clientAuth];
		}
		return null;
	}
}
