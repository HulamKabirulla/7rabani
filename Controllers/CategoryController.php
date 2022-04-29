<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use App\Product;
use App\Image;
use App\Tag;
use App\SubCategory;
use App\SubSubCategory;
use App\SeoText;
use App\Traits\Auth;

class CategoryController extends Controller
{

    public function rus2translit($string) {
	    $converter = array(
	        'а' => 'a',   'б' => 'b',   'в' => 'v',
	        'г' => 'g',   'д' => 'd',   'е' => 'e',
	        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
	        'и' => 'i',   'й' => 'y',   'к' => 'k',
	        'л' => 'l',   'м' => 'm',   'н' => 'n',
	        'о' => 'o',   'п' => 'p',   'р' => 'r',
	        'с' => 's',   'т' => 't',   'у' => 'u',
	        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
	        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
	        'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
	        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

	        'А' => 'A',   'Б' => 'B',   'В' => 'V',
	        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
	        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
	        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
	        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
	        'О' => 'O',   'П' => 'P',   'Р' => 'R',
	        'С' => 'S',   'Т' => 'T',   'У' => 'U',
	        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
	        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
	        'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
	        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
	    );
	    return strtr($string, $converter);
	}
	public function str2url($str) {
	    // переводим в транслит
	    $str = self::rus2translit($str);
	    // в нижний регистр
	    $str = strtolower($str);
	    // заменям все ненужное нам на "-"
	    $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
	    // удаляем начальные и конечные '-'
	    $str = trim($str, "-");
	    return $str;
	}
    /*public function getAllCategory()
    {
		echo self::str2url("Микроволновые печи");
		$allCategory=Category::all();
    	return view('welcome',["categoryArray"=>$allCategory]);
    }*/

    public function getProductsBySubSubCategory($categoryUrl,$subcategoryUrl,$subsubcategoryUrl,$orderBy="popular",$page="1")
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

		$seoOptimization=SeoText::where('url',$_SERVER['REQUEST_URI'])->get()->first();
		$curCategory=Category::where('url',$categoryUrl)->get()->first();
		$curSubCategory=SubCategory::where('url',$subcategoryUrl)->get()->first();
		$curSubSubCategory=SubsubCategory::where('url',$subsubcategoryUrl)->get()->first();
		$curTag="";

		//Если нет такой категории и подкатегории
		if(!$curCategory||!$curSubCategory||!$curSubSubCategory)
		{
			return abort(404);
		}//Если Подкатегория не соответсвует Категории
		else if(($curCategory['id']!=$curSubCategory['id_category'])||$curSubCategory['id']!=$curSubSubCategory['id_subcategory'])
		{
			return abort(404);
		}

		$h1=$curSubSubCategory['name'];
		$seoText="";
		$metaTitle=$curSubSubCategory['name'];
		$metaDescription="";
		$metaNoIndex="0";
		$metaCannonical="/ru/shop/".$categoryUrl."/".$subcategoryUrl."/".$subsubcategoryUrl;

		$countPage=20;
		$skipPage=($page-1)*$countPage;

		$listSubCategory=null;
		$h1=$curSubSubCategory['name'];
		$firstElement="product_subsubcategories.id_subsubcategory";
		$secondElement=$curSubSubCategory['id'];

		$urlOrderBy="";
		//Получаем все подПодКатегории
        
		$metaTitle="Купить ".$metaTitle." оптом в Украине на 7км";

		switch ($orderBy) {
			case 'popular':
				$firstOrderBy="products.id";
				$secondOrderBy="desc";
				$urlOrderBy="orderBy/popular";
				break;
			case 'price-asc':
				$urlOrderBy="orderBy/price-asc";
				$firstOrderBy="products.price";
				$secondOrderBy="asc";
				break;
			case 'price-desc':
				$urlOrderBy="orderBy/price-desc";
				$firstOrderBy="products.price";
				$secondOrderBy="desc";
				break;
			default:
				# code...
				break;
		}
		$productsArray=Product::leftJoin('images', function($join) {
				      $join->on('images.id_product', '=', 'products.id');
				    })->leftJoin('product_tags', function($join) {
				      $join->on('product_tags.id_product', '=', 'products.id');
				    })->leftJoin('product_subsubcategories', function($join) {
				      $join->on('product_subsubcategories.id_product', '=', 'products.id');
				    })->where($firstElement,$secondElement)->where("is_main",1)->select("products.id AS productId","products.name AS productName","images.src AS productSrc","products.price AS productPrice","products.url AS productUrl","products.isset AS productIsset")->groupBy('products.id')->orderBy("productIsset","DESC")->orderBy($firstOrderBy,$secondOrderBy)->skip($skipPage)->take($countPage)->get();

		$productsArrayCount=Product::leftJoin('images', function($join) {
				      $join->on('images.id_product', '=', 'products.id');
				    })->leftJoin('product_tags', function($join) {
				      $join->on('product_tags.id_product', '=', 'products.id');
				    })->leftJoin('product_subsubcategories', function($join) {
				      $join->on('product_subsubcategories.id_product', '=', 'products.id');
				    })->where($firstElement,$secondElement)->where("is_main",1)->select("products.id AS productId","products.name AS productName","images.src AS productSrc","products.price AS productPrice","products.url AS productUrl","products.isset AS productIsset")->groupBy('products.id')->orderBy("productIsset","DESC")->orderBy($firstOrderBy,$secondOrderBy)->get()->count();
	$allPages=(double)($productsArrayCount/$countPage);
		$allPages=$allPages<=1?1:ceil($allPages);
		if($seoOptimization)
		{
			$h1=$seoOptimization->h1;
			$seoText=$seoOptimization->seoText;
			$metaTitle=$seoOptimization->metaTitle;
			$metaDescription=$seoOptimization->metaDescription;
			$metaNoIndex=$seoOptimization->metaNoIndex;
			$metaCannonical=$seoOptimization->metaCannonical;
		}
		
		$allCategories=Category::all();
		$allSubCategories=SubCategory::all();
		$allSubSubCategories=SubSubCategory::all();
		$clientAuth=Auth::isAuth();
    	return view("shop",["clientAuth" => $clientAuth,"search" => "","allCategories" => $allCategories,"allSubCategories" => $allSubCategories,"allSubSubCategories" => $allSubSubCategories,"allPages" => $allPages,"curPage" => $page,"arrayPages" => $arrayPages,"productCount" => $productsArrayCount,"urlOrderBy" => $urlOrderBy,"categoryUrl" => $categoryUrl."/","subcategoryUrl" => $subcategoryUrl."/","subsubcategoryUrl" => $subsubcategoryUrl."/","h1" => $h1,"productsArray" => $productsArray,"listSubCategory" => $listSubCategory,"metaDescription" => $metaDescription,"metaNoIndex" => $metaNoIndex,"metaCannonical" => $metaCannonical,"seoText" => $seoText,"curTag" => $curTag,"metaTitle" => $metaTitle]);
    }

    public function getProductsBySubCategory($categoryUrl,$subcategoryUrl,$orderBy="popular",$page="1")
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

		$seoOptimization=SeoText::where('url',$_SERVER['REQUEST_URI'])->get()->first();
		$curCategory=Category::where('url',$categoryUrl)->get()->first();
		$curSubCategory=SubCategory::where('url',$subcategoryUrl)->get()->first();
		$curTag="";
		//Если нет такой категории и подкатегории
		if(!$curCategory||!$curSubCategory)
		{
			return abort(404);
		}//Если Подкатегория не соответсвует Категории
		else if($curCategory['id']!=$curSubCategory['id_category'])
		{
			return abort(404);
		}
		


		$countPage=20;
		$skipPage=($page-1)*$countPage;

		$listSubCategory=null;
		$h1=$curSubCategory['name'];
		$seoText="";
		$metaTitle=$curSubCategory['name'];
		$metaDescription="";
		$metaNoIndex="0";
		$metaCannonical="/ru/shop/".$categoryUrl."/".$subcategoryUrl;
		$firstElement="product_subcategories.id_subcategory";
		$secondElement=$curSubCategory['id'];

		$urlOrderBy="";
		//Получаем все подПодКатегории
		$listSubSubCategory=SubsubCategory::where("id_subcategory",$curSubCategory['id'])->get();

		switch ($orderBy) {
			case 'popular':
				$firstOrderBy="products.id";
				$secondOrderBy="desc";
				$urlOrderBy="orderBy/popular";
				break;
			case 'price-asc':
				$urlOrderBy="orderBy/price-asc";
				$firstOrderBy="products.price";
				$secondOrderBy="asc";
				break;
			case 'price-desc':
				$urlOrderBy="orderBy/price-desc";
				$firstOrderBy="products.price";
				$secondOrderBy="desc";
				break;
			default:
				# code...
				break;
		}
		
		$productsArray=Product::leftJoin('images', function($join) {
				      $join->on('images.id_product', '=', 'products.id');
				    })->leftJoin('product_tags', function($join) {
				      $join->on('product_tags.id_product', '=', 'products.id');
				    })->leftJoin('product_subcategories', function($join) {
				      $join->on('product_subcategories.id_product', '=', 'products.id');
				    })->where($firstElement,$secondElement)->where("is_main",1)->select("products.id AS productId","products.name AS productName","images.src AS productSrc","products.price AS productPrice","products.url AS productUrl","products.isset AS productIsset")->orderBy("productIsset","DESC")->orderBy($firstOrderBy,$secondOrderBy)->skip($skipPage)->take($countPage)->get();

		$productsArrayCount=Product::leftJoin('images', function($join) {
				      $join->on('images.id_product', '=', 'products.id');
				    })->leftJoin('product_tags', function($join) {
				      $join->on('product_tags.id_product', '=', 'products.id');
				    })->leftJoin('product_subcategories', function($join) {
				      $join->on('product_subcategories.id_product', '=', 'products.id');
				    })->where($firstElement,$secondElement)->where("is_main",1)->select("products.id AS productId","products.name AS productName","images.src AS productSrc","products.price AS productPrice","products.url AS productUrl")->orderBy($firstOrderBy,$secondOrderBy)->get()->count();
		$allPages=(int)($productsArrayCount/$countPage);
		$allPages=$allPages<=1?1:$allPages;
		if($seoOptimization)
		{
			$h1=$seoOptimization->h1;
			$seoText=$seoOptimization->seoText;
			$metaTitle=$seoOptimization->metaTitle;
			$metaDescription=$seoOptimization->metaDescription;
			$metaNoIndex=$seoOptimization->metaNoIndex;
			$metaCannonical=$seoOptimization->metaCannonical;
		}
		
		$allCategories=Category::all();
		$allSubCategories=SubCategory::all();
		$allSubSubCategories=SubSubCategory::all();
    	$clientAuth=Auth::isAuth();
    	return view("shop",["clientAuth" => $clientAuth,"allCategories" => $allCategories,"allSubCategories" => $allSubCategories,"allSubSubCategories" => $allSubSubCategories,"allPages" => $allPages,"curPage" => $page,"arrayPages" => $arrayPages,"productCount" => $productsArrayCount,"urlOrderBy" => $urlOrderBy,"categoryUrl" => $categoryUrl."/","subcategoryUrl" => $subcategoryUrl."/","subsubcategoryUrl"=>"","h1" => $h1,"productsArray" => $productsArray,"listSubCategory" => $listSubCategory,"listSubSubCategory" => $listSubSubCategory,"curTag" => $curTag,"search" => "","metaTitle" => $metaTitle, "metaDescription" => $metaDescription, "metaNoIndex" => $metaNoIndex, "metaCannonical" => $metaCannonical, "seoText" => $seoText]);
    }

    public function getProductsByCategory($categoryUrl,$orderBy="popular",$page="1")
    {	
    	//Проверяю если сортировка по "популярности" и со страницей
    	/*switch ($orderVal) {
    		case is_numeric($orderVal):
    			# code...
    			$page=$orderVal;
    			$orderVal=0;
    			break;
    		
    		default:
    			# code...
    			break;
    	}*/
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

		$seoOptimization=SeoText::where('url',$_SERVER['REQUEST_URI'])->get()->first();
		$curCategory=Category::where('url',$categoryUrl)->get()->first();
		$curSubCategory="";
		$curTag=Tag::where("url",$categoryUrl)->get()->first();
		$subcategoryUrl="";

		$countPage=20;
		$skipPage=($page-1)*$countPage;


		$listSubCategory=SubCategory::where("id_category",$curCategory['id'])->get();
		//$listSubCategory=null;
		if($curCategory)
		{
			$h1=$curCategory['name'];
			$seoText="";
			$metaTitle=$curCategory['name'];
			$metaDescription="";
			$metaNoIndex="0";
			$metaCannonical="/ru/shop/".$categoryUrl;

			$firstElement="product_categories.id_category";
			$secondElement=$curCategory['id'];
		}
		else if($curTag)
		{
			$h1=$curTag['name'];
			$seoText="";
			$metaTitle=$curTag['name'];
			$metaDescription="";
			$metaNoIndex="0";
			$metaCannonical="/ru/shop/".$curTag['url'];


			$firstElement="product_tags.id_tag";
			$secondElement=$curTag['id'];
		}
		else
		{
			 return abort(404);
		}

		$urlOrderBy="";
		
		switch ($orderBy) {
			case 'popular':
				$firstOrderBy="products.id";
				$secondOrderBy="desc";
				$urlOrderBy="orderBy/popular";
				break;
			case 'price-asc':
				$urlOrderBy="orderBy/price-asc";
				$firstOrderBy="products.price";
				$secondOrderBy="asc";
				break;
			case 'price-desc':
				$urlOrderBy="orderBy/price-desc";
				$firstOrderBy="products.price";
				$secondOrderBy="desc";
				break;
			default:
				# code...
				break;
		}
		$productsArray=Product::leftJoin('images', function($join) {
				      $join->on('images.id_product', '=', 'products.id');
				    })->leftJoin('product_tags', function($join) {
				      $join->on('product_tags.id_product', '=', 'products.id');
				    })->leftJoin('product_categories', function($join) {
				      $join->on('product_categories.id_product', '=', 'products.id');
				    })->where($firstElement,$secondElement)->where("is_main",1)->select("products.id AS productId","products.name AS productName","images.src AS productSrc","products.price AS productPrice","products.url AS productUrl","products.isset AS productIsset")->groupBy('products.id')->orderBy("productIsset","DESC")->orderBy($firstOrderBy,$secondOrderBy)->skip($skipPage)->take($countPage)->get();

		$productsArrayCount=Product::leftJoin('images', function($join) {
				      $join->on('images.id_product', '=', 'products.id');
				    })->leftJoin('product_tags', function($join) {
				      $join->on('product_tags.id_product', '=', 'products.id');
				    })->leftJoin('product_categories', function($join) {
				      $join->on('product_categories.id_product', '=', 'products.id');
				    })->where($firstElement,$secondElement)->where("is_main",1)->groupBy('products.id')->select("products.id AS productId","products.name AS productName","images.src AS productSrc","products.price AS productPrice","products.url AS productUrl")->orderBy($firstOrderBy,$secondOrderBy)->get()->count();
		$allPages=(double)($productsArrayCount/$countPage);
		$allPages=$allPages<=1?1:ceil($allPages);

		if($seoOptimization)
		{
			$h1=$seoOptimization->h1;
			$seoText=$seoOptimization->seoText;
			$metaTitle=$seoOptimization->metaTitle;
			$metaDescription=$seoOptimization->metaDescription;
			$metaNoIndex=$seoOptimization->metaNoIndex;
			if(trim($seoOptimization->metaCannonical))
			{
				$metaCannonical=$seoOptimization->metaCannonical;
			}
		}

		$allCategories=Category::all();
		$allSubCategories=SubCategory::all();
		$allSubSubCategories=SubSubCategory::all();

		
        $clientAuth=Auth::isAuth();
    	return view("shop",["clientAuth" => $clientAuth,"allCategories" => $allCategories,"allSubCategories" => $allSubCategories,"allSubSubCategories" => $allSubSubCategories,"allPages" => $allPages,"curPage" => $page,"arrayPages" => $arrayPages,"productCount" => $productsArrayCount,"urlOrderBy" => $urlOrderBy,"categoryUrl" => $categoryUrl."/","subcategoryUrl" => ""."","subsubcategoryUrl" => "","h1" => $h1,"seoText" => $seoText,"metaTitle" => $metaTitle,"metaDescription" => $metaDescription,"metaNoIndex" => $metaNoIndex,"metaCannonical" => $metaCannonical,"productsArray" => $productsArray,"listSubCategory" => $listSubCategory,"curTag" => $curTag,"search" => ""]);
    }

    public function getProductsSearch($orderBy="popular",$page="1")
    {
    	$search="";
    	if(isset($_GET['search']))
    	{
    		$search=$_GET['search'];
    	}
    	//Проверяю если сортировка по "популярности" и со страницей
    	/*switch ($orderVal) {
    		case is_numeric($orderVal):
    			# code...
    			$page=$orderVal;
    			$orderVal=0;
    			break;
    		
    		default:
    			# code...
    			break;
    	}*/

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

        $domainName=$_SERVER['SERVER_NAME'];
		$h1="«".$search."»";
		if(!trim($search))
		{
			$h1="«"."Поиск"."»";
		}
		$seoText="";
		$metaTitle=$domainName." - Результаты поиска: «".$search."» | Поиск";
		$metaDescription="";
		$metaNoIndex="1";
		$metaCannonical="";

		$seoOptimization=SeoText::where('url',$_SERVER['REQUEST_URI'])->get()->first();


		$subcategoryUrl="";

		$countPage=20;
		$skipPage=($page-1)*$countPage;

		$listSubCategory=null;

		$urlOrderBy="";

		
		switch ($orderBy) {
			case 'popular':
				$firstOrderBy="products.id";
				$secondOrderBy="desc";
				$urlOrderBy="orderBy/popular";
				break;
			case 'price-asc':
				$urlOrderBy="orderBy/price-asc";
				$firstOrderBy="products.price";
				$secondOrderBy="asc";
				break;
			case 'price-desc':
				$urlOrderBy="orderBy/price-desc";
				$firstOrderBy="products.price";
				$secondOrderBy="desc";
				break;
			default:
				# code...
				break;
		}
		$productsArray=Product::leftJoin('images', function($join) {
				      $join->on('images.id_product', '=', 'products.id');
				    })->leftJoin('product_tags', function($join) {
				      $join->on('product_tags.id_product', '=', 'products.id');
				    })->where("name",'like','%'.$search.'%')->where("is_main",1)->groupBy("products.id")->select("products.id AS productId","products.name AS productName","images.src AS productSrc","products.price AS productPrice","products.url AS productUrl","products.isset AS productIsset")->orderBy("productIsset","DESC")->orderBy($firstOrderBy,$secondOrderBy)->skip($skipPage)->take($countPage)->get();

		$productsArrayCount=Product::leftJoin('images', function($join) {
				      $join->on('images.id_product', '=', 'products.id');
				    })->leftJoin('product_tags', function($join) {
				      $join->on('product_tags.id_product', '=', 'products.id');
				    })->where("name",'like','%'.$search.'%')->where("is_main",1)->groupBy("products.id")->select("products.id AS productId","products.name AS productName","images.src AS productSrc","products.price AS productPrice","products.url AS productUrl")->orderBy($firstOrderBy,$secondOrderBy)->get()->count();
		$allPages=(int)($productsArrayCount/$countPage);
		$allPages=$allPages<=1?1:$allPages;
		if($seoOptimization)
		{
			$h1=$seoOptimization->h1;
			$seoText=$seoOptimization->seoText;
			$metaTitle=$seoOptimization->seoText;
			$metaDescription=$seoOptimization->metaDescription;
			$metaNoIndex=$seoOptimization->metaNoIndex;
			$metaCannonical=$seoOptimization->metaCannonical;
		}

		$allCategories=Category::all();
		$allSubCategories=SubCategory::all();
		$allSubSubCategories=SubSubCategory::all();

		if(!trim($search))
		{
			$search="";
		}
		else
		{
			$search="?search=".$search;
		}
		$clientAuth=Auth::isAuth();
    	return view("shop",["clientAuth" => $clientAuth,"metaTitle" => $metaTitle,"metaDescription" => $metaDescription,"metaNoIndex" => $metaNoIndex,"metaCannonical" => $metaCannonical,"seoText" => $seoText,"allCategories" => $allCategories,"allSubCategories" => $allSubCategories,"allSubSubCategories" => $allSubSubCategories,"allPages" => $allPages,"curPage" => $page,"arrayPages" => $arrayPages,"productCount" => $productsArrayCount,"urlOrderBy" => $urlOrderBy,"categoryUrl" => "","subcategoryUrl" => "","subsubcategoryUrl" => "","h1" => $h1,"productsArray" => $productsArray,"listSubCategory" => $listSubCategory,"curTag" => "","search" => $search]);
    }
}