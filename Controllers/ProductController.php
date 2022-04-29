<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Category;
use App\SubCategory;
use App\SubSubCategory;
use App\SeoText;
use App\Client;

class ProductController extends Controller
{
    public function getCompleteRabaniCar()
    {
        return view("landing-instagram/complete-car");
    }
    
    public function getCompleteOrder()
	{
		return view("landing-instagram/complete");
	}
	
	public function getRabaniChampion()
	{
	    return view("landing-instagram/rabani-champion");
	}
	
	public function getMashinka2488()
	{
	    return view("landing-instagram/mashinka2488");
	}
	
    public function getMashinka3255()
	{
		return view("landing-instagram/mashinka3255");
	}
    
    public function getMolbertDetskij()
	{
		//return view("landing-instagram/molbert-detskij-instagram");
		return view("landing-instagram/molbert-insta");
	}
    
    public function getMolbertDetskijOld()
	{
		return view("landing-instagram/molbert-detskij");
	}
	
	public function getAuthClient($login, $password)
	{
		$client=Client::where('number',$login)->where('password',$password)->get()->first();
		return $client;
	}

    public function getProductByUrl($productUrl)
    {
    	$cookieNumber=request()->cookie("uNumber");
    	$cookiePassword=request()->cookie("uPassword");
    	$clientAuth=self::getAuthClient($cookieNumber,$cookiePassword);

    	$allCategories=Category::all();
    	$allSubCategories=SubCategory::all();
    	$allSubSubCategories=SubSubCategory::all();
    	
    	/*return response()->json([
	        'header' => view('includes/header')->with(['clientAuth' => $client,"allCategories" => "", "allSubCategories" => "", "allSubSubCategories" => ""])->render()
	    ]);*/
    	$allCategories=Category::all();
    	$allSubCategories=SubCategory::all();
    	$allSubSubCategories=SubSubCategory::all();

    	
    	$product=Product::leftJoin('images', function($join) {
			      $join->on('images.id_product', '=', 'products.id');
			    })->where("url",$productUrl)->select("products.id AS productId","products.name AS productName","products.price AS productPrice","products.description AS productDescription","images.src AS productImage","products.isset AS productIsset")->orderBy("images.is_main","DESC")->get();
    	//$productCategory=Category::where("id",$product[0]->productIdCategory)->get()->first();
    	//$productSubCategory=SubCategory::where("id",$product[0]->productIdSubCategory)->get()->first();
    	//$productSubSubCategory=SubSubCategory::where("id",$product[0]->productIdSubSubCategory)->get()->first();

		$seoOptimization=SeoText::where('url',$_SERVER['REQUEST_URI'])->get()->first();


    	$h1=$product[0]->productName;
		$seoText=$product[0]->productDescription;
		$metaTitle="Купить ".$product[0]->productName." оптом в Украине на 7км";
		$metaDescription=$product[0]->productName." оптом в Украине на 7км по лучшей цене! Срочно заказывайте, действуют скидки!";
		$metaNoIndex="0";
		$metaCannonical="/ru/product/".$productUrl;

		if($seoOptimization)
		{
			$h1=$seoOptimization->h1;
			$metaTitle=$seoOptimization->metaTitle;
			$seoText=$seoOptimization->seoText;
			$metaDescription=$seoOptimization->metaDescription;
			$metaNoIndex=$seoOptimization->metaNoIndex;
			if($seoOptimization->metaCannonical)
			{
				$metaCannonical=$seoOptimization->metaCannonical;
			}
		}

    	return view("product",["clientAuth" => $clientAuth,"metaTitle" => $metaTitle,"metaDescription" => $metaDescription,"metaNoIndex" => $metaNoIndex,"metaCannonical" => $metaCannonical,"h1" => $h1,"product" => $product,"seoText" => $seoText,"productCategory" => null,"productSubCategory" => "","productSubSubCategory" => "","allCategories" => $allCategories,"allSubCategories" => $allSubCategories,"allSubSubCategories" => $allSubSubCategories]);
    }
}