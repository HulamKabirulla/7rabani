<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use App\SubCategory;
use App\SubSubCategory;
use App\Product;
use App\Image1;
use App\AdminUser;
use App\AdminType;
use App\Order;
use App\OrderAction;
use App\Cart;
use App\Client;
use App\Blog;
use App\SeoText;
use App\ProductCategory;
use App\ProductSubCategory;
use App\ProductSubSubCategory;
use App\ProductTag;
use App\Tag;
use App\Traits\Auth;
use App\SocialnetworkPage;


class AdminController extends Controller
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
    public function getMainPage()
    {
        $menu="";
        $submenu="";
        $adminAuth=Auth::isAuthAdmin();
        if($adminAuth)
        {
            return view("admin/index",["menu" => $menu,"submenu" => $submenu,"admin" => $adminAuth]);
        }
        else
        {
            return redirect("/admin/login");
        }
    }
    public function getProductAdd()
    {
        $adminAuth=Auth::isAuthAdmin();
        if($adminAuth)
        {
            $listCategories=Category::select("url AS categoryUrl","name AS categoryName")->get();
            $listSubCategories=SubCategory::leftJoin('categories', function($join) {
                      $join->on('categories.id', '=', 'sub_categories.id_category');
                    })->select("sub_categories.url AS subCategoryUrl","categories.name AS categoryName","sub_categories.name AS subCategoryName")->get();
            $listSubSubCategories=SubSubCategory::leftJoin('sub_categories', function($join) {
                      $join->on('subsub_categories.id_subcategory', '=', 'sub_categories.id');
                    })->leftJoin('categories', function($join) {
                      $join->on('categories.id', '=', 'sub_categories.id_category');
                    })->select("subsub_categories.url AS subsubCategoryUrl","categories.name AS categoryName","sub_categories.name AS subCategoryName","subsub_categories.name AS subsubCategoryName")->get();
            $listTags=Tag::get()->all();
            $menu="products";
            $submenu="products-add";

            return view("admin/products/product-add",["listCategories" => $listCategories,"listSubCategories" => $listSubCategories,"listSubSubCategories" => $listSubSubCategories,"listTags" => $listTags,"menu" => $menu,"submenu" => $submenu,"admin" => $adminAuth]);
        }
        else
        {
            return redirect("/admin/login");
        }
    	
    }

    public function getProductEdit($productUrl)
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        $product=Product::where("url",$productUrl)->get()->first();
        $idCategory=$product['id_category'];
        $idSubCategory=$product['id_subcategory'];
        $idSubSubCategory=$product['id_subsubcategory'];

        $urlCategory="";

        if($idSubSubCategory>0)
        {
            $subsubCategory=SubSubCategory::where("id",$idSubSubCategory)->get()->first();
            $urlCategory=$subsubCategory['url'];
        }
        else if($idSubCategory>0)
        {
            $subCategory=SubCategory::where("id",$idSubCategory)->get()->first();
            $urlCategory=$subCategory['url'];
        }
        else if($idCategory>0)
        {
            $category=Category::where("id",$idCategory)->get()->first();
            $urlCategory=$category['url'];
        }


        $listImages=Image1::where("id_product",$product['id'])->orderBy("is_main","desc")->get();

        $listCategories=Category::select("url AS categoryUrl","name AS categoryName","id AS categoryId")->get();
        $listSubCategories=SubCategory::leftJoin('categories', function($join) {
                  $join->on('categories.id', '=', 'sub_categories.id_category');
                })->select("sub_categories.id AS subCategoryId","sub_categories.url AS subCategoryUrl","categories.name AS categoryName","sub_categories.name AS subCategoryName")->get();
        $listSubSubCategories=SubSubCategory::leftJoin('sub_categories', function($join) {
                  $join->on('subsub_categories.id_subcategory', '=', 'sub_categories.id');
                })->leftJoin('categories', function($join) {
                  $join->on('categories.id', '=', 'sub_categories.id_category');
                })->select("subsub_categories.id AS subsubCategoryId","subsub_categories.url AS subsubCategoryUrl","categories.name AS categoryName","sub_categories.name AS subCategoryName","subsub_categories.name AS subsubCategoryName")->get();

        $listTags=Tag::get()->all();

        $productCategoriesList=ProductCategory::where("id_product",$product['id'])->get()->all();
        $productCategories=[];
        for ($i = 0, $c = count($productCategoriesList); $i < $c; ++$i) {
            $productCategories[$i] = $productCategoriesList[$i]['id_category'];
            //(array) 
        }

        $productSubCategoriesList=ProductSubCategory::where("id_product",$product['id'])->get()->all();
        $productSubCategories=[];
        for ($i = 0, $c = count($productSubCategoriesList); $i < $c; ++$i) {
            $productSubCategories[$i] = $productSubCategoriesList[$i]['id_subcategory'];
            //(array) 
        }

        $productSubSubCategoriesList=ProductSubSubCategory::where("id_product",$product['id'])->get()->all();
        $productSubSubCategories=[];
        for ($i = 0, $c = count($productSubSubCategoriesList); $i < $c; ++$i) {
            $productSubSubCategories[$i] = $productSubSubCategoriesList[$i]['id_subsubcategory'];
            //(array) 
        }

        $productTagsList=ProductTag::where("id_product",$product['id'])->get()->all();
        $productTags=[];
        for ($i = 0, $c = count($productTagsList); $i < $c; ++$i) {
            $productTags[$i] = $productTagsList[$i]['id_tag'];
            //(array) 
        }
        //var_dump($productTags);
        $menu="products";
        $submenu="products-edit";
        return view("admin/products/product-edit",["product" => $product,"listCategories" => $listCategories,"listSubCategories" => $listSubCategories,"listSubSubCategories" => $listSubSubCategories,"urlCategory" => $urlCategory,"listImages" => $listImages,"listTags" => $listTags,"productCategories" => $productCategories,"productSubCategories" => $productSubCategories,"productSubSubCategories" => $productSubSubCategories,"productTags" => $productTags,"menu" => $menu,"submenu" => $submenu,"admin" => $adminAuth]);
    }

    function getProductListApi($searchProductName="",$page="1")
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        $countPage=10;
        $skipPage=($page-1)*$countPage;

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

        $productsArrayCount=Product::leftJoin('images', function($join) {
                      $join->on('images.id_product', '=', 'products.id');
                    })->leftJoin('product_tags', function($join) {
                      $join->on('product_tags.id_product', '=', 'products.id');
                    })->where("is_main",1)->where("products.name",'like', '%'.$searchProductName.'%')->select("products.id")->get()->count();
        $allPages=(double)($productsArrayCount/$countPage);
        $allPages=$allPages<=1?1:$allPages;

        $productList=Product::leftJoin('images', function($join) {
                      $join->on('images.id_product', '=', 'products.id');
                    })->leftJoin('product_tags', function($join) {
                      $join->on('product_tags.id_product', '=', 'products.id');
                    })->where("products.name",'like', '%'.$searchProductName.'%')->where("is_main",1)->select("products.id AS productId","products.name AS productName","images.src AS productSrc","products.price AS productPrice","products.url AS productUrl","products.isset AS productIsset","products.created_at AS productsCreatedAt")->skip($skipPage)->take($countPage)->get();
        //var_dump($productList);
        $searchProductNameUrl="";
        if(trim($searchProductName))
        {
            $searchProductNameUrl="search/".$searchProductName."/";
        }
        $menu="products";
        $submenu="products-list";
        return view("admin/products/products-list",["allPages" => $allPages,"curPage" => $page,"arrayPages" => $arrayPages,"productCount" => $productsArrayCount,"productList" => $productList,"searchProductNameUrl" => $searchProductNameUrl,"menu" => $menu,"submenu" => $submenu,"admin" => $adminAuth]);
    }

    public function getProductListWithSearch($searchProductName="",$page="1")
    {
        return self::getProductListApi($searchProductName,$page);     
    }

    public function getProductListWithoutSearch($page="1")
    {
        return self::getProductListApi("",$page);
    }

    public function addProduct(Request $request)
    {
        $idCategory=0;
        $idSubCategory=0;
        $idSubSubCategory=0;


        $productName=$request->productName;
        $productPrice=$request->productPrice;
        $productDescription=$request->productDescription;
        $productCategory=$request->productCategory;
        $productUrl=self::str2url($productName);

        $issetProduct=Product::where("url",$productUrl)->get()->first();

        $newProduct=new Product;
        $newProduct->name=$productName;
        $newProduct->description=$productDescription;
        $newProduct->price=$productPrice;
        $newProduct->url=$productUrl;

        //$newProduct->id_category=$idCategory;
        //$newProduct->id_subcategory=$idSubCategory;
        //$newProduct->id_subsubcategory=$idSubSubCategory;

        $newProduct->save();

        if($issetProduct)
        {
            $newProduct->url=$newProduct->url."-".$newProduct->id;
            $newProduct->save();
        }


        for($i=0;$i<count($productCategory);$i++)
        {
            $category=Category::where("url",$productCategory[$i])->select("categories.id")->get()->first();
            $tag=Tag::where("url",$productCategory[$i])->select("tags.id")->get()->first();
            $subCategory=SubCategory::where("url",$productCategory[$i])->select("id","id_category")->get()->first();
            $subSubCategory=SubSubCategory::leftJoin('sub_categories', function($join) {
                              $join->on('sub_categories.id', '=', 'subsub_categories.id_subcategory');
                            })->where("subsub_categories.url",$productCategory[$i])->select("subsub_categories.id_subcategory AS subCategoryId","sub_categories.id_category AS idCategory","subsub_categories.id AS subsubCategoryId")->get()->first();
            if($category)
            {
                //$idCategory=$category['id'];
                $issetProductCategory=ProductCategory::where("id_product",$newProduct->id)->where("id_category",$category['id'])->get()->first();
                if(!$issetProductCategory)
                {
                    $newProductCategory=new ProductCategory;
                    $newProductCategory->id_product=$newProduct->id;
                    $newProductCategory->id_category=$category['id'];
                    $newProductCategory->save();
                }
            }
            else if($tag)
                {
                    $issetTag=ProductTag::where("id_product",$newProduct->id)->where("id_tag",$tag['id'])->get()->first();
                    if(!$issetTag)
                    {
                        $newProductTag=new ProductTag;
                        $newProductTag->id_product=$newProduct->id;
                        $newProductTag->id_tag=$tag['id'];
                        $newProductTag->save();
                    }
                }
                else if($subCategory)
                    {

                        $idCategory=$subCategory['id_category'];
                        $idSubCategory=$subCategory['id'];
                        $issetProductCategory=ProductCategory::where("id_product",$newProduct->id)->where("id_category",$idCategory)->get()->first();
                        $issetProductSubCategory=ProductSubCategory::where("id_product",$newProduct->id)->where("id_subcategory",$idSubCategory)->get()->first();
                        if(!$issetProductCategory)
                        {
                            $newProductCategory=new ProductCategory;
                            $newProductCategory->id_product=$newProduct->id;
                            $newProductCategory->id_category=$idCategory;
                            $newProductCategory->save();
                        }
                        if(!$issetProductSubCategory)
                        {
                            $newProductSubCategory=new ProductSubCategory;
                            $newProductSubCategory->id_product=$newProduct->id;
                            $newProductSubCategory->id_subcategory=$idSubCategory;
                            $newProductSubCategory->save();
                        }
                    }
                    else if($subSubCategory)
                    {
                        $idCategory=$subSubCategory['idCategory'];
                        $idSubCategory=$subSubCategory['subCategoryId'];
                        $idSubSubCategory=$subSubCategory['subsubCategoryId'];

                        $issetProductCategory=ProductCategory::where("id_product",$newProduct->id)->where("id_category",$idCategory)->get()->first();
                        $issetProductSubCategory=ProductSubCategory::where("id_product",$newProduct->id)->where("id_subcategory",$idSubCategory)->get()->first();
                        $issetProductSubSubCategory=ProductSubSubCategory::where("id_product",$newProduct->id)->where("id_subsubcategory",$idSubSubCategory)->get()->first();
                        if(!$issetProductCategory)
                        {
                            $newProductCategory=new ProductCategory;
                            $newProductCategory->id_product=$newProduct->id;
                            $newProductCategory->id_category=$idCategory;
                            $newProductCategory->save();
                        }
                        if(!$issetProductSubCategory)
                        {
                            $newProductSubCategory=new ProductSubCategory;
                            $newProductSubCategory->id_product=$newProduct->id;
                            $newProductSubCategory->id_subcategory=$idSubCategory;
                            $newProductSubCategory->save();
                        }
                        if(!$issetProductSubSubCategory)
                        {
                            $newProductSubSubCategory=new ProductSubSubCategory;
                            $newProductSubSubCategory->id_product=$newProduct->id;
                            $newProductSubSubCategory->id_subsubcategory=$idSubSubCategory;
                            $newProductSubSubCategory->save();
                        }
                    }
        }

            $fileList = glob("uploads/temporary-products/mainPhoto/*");
     
            //Loop through the array that glob returned.
            foreach($fileList as $filename){
               //Simply print them out onto the screen.
                $newfilename = "uploads/products/".uniqid().time().stristr(substr($filename, -5),'.');
                rename($filename,$newfilename);

                $newImage=new Image1;
                $newImage->id_product=$newProduct->id;
                $newImage->src=$newfilename;
                $newImage->is_main=1;
                $newImage->save();
            }

        $fileList = glob("uploads/temporary-products/additional/*");
 
        //Loop through the array that glob returned.
        foreach($fileList as $filename){
           //Simply print them out onto the screen.
            $newfilename = "uploads/products/".uniqid().time().stristr(substr($filename, -5),'.');
            rename($filename,$newfilename);

            $newImage=new Image1;
            $newImage->id_product=$newProduct->id;
            $newImage->src=$newfilename;
            $newImage->is_main=0;
            $newImage->save();
        }

        return json_encode(true);
    }

    public function editCheckbox(Request $request)
    {
        $productId=$request->productId;
        $productCategory=$request->categoryUrl;
        $isChecked=$request->isChecked;

            $category=Category::where("url",$productCategory)->select("categories.id")->get()->first();
            $tag=Tag::where("url",$productCategory)->select("tags.id")->get()->first();
            $subCategory=SubCategory::where("url",$productCategory)->select("id","id_category")->get()->first();
            $subSubCategory=SubSubCategory::leftJoin('sub_categories', function($join) {
                              $join->on('sub_categories.id', '=', 'subsub_categories.id_subcategory');
                            })->where("subsub_categories.url",$productCategory)->select("subsub_categories.id_subcategory AS subCategoryId","sub_categories.id_category AS idCategory","subsub_categories.id AS subsubCategoryId")->get()->first();




            if($category)
            {
                if($isChecked=="true")
                {
                    $issetProductCategory=ProductCategory::where("id_product",$productId)->where("id_category",$category['id'])->get()->first();
                    if(!$issetProductCategory)
                    {
                        $newProductCategory=new ProductCategory;
                        $newProductCategory->id_product=$productId;
                        $newProductCategory->id_category=$category['id'];
                        $newProductCategory->save();
                    }
                }
                else
                {
                    ProductCategory::where("id_product",$productId)->where("id_category",$category['id'])->delete();
                }
            }
            else if($tag)
                {
                    $issetTag=ProductTag::where("id_product",$productId)->where("id_tag",$tag['id'])->get()->first();
                    if($isChecked=="true")
                    {
                        if(!$issetTag)
                        {
                            $newProductTag=new ProductTag;
                            $newProductTag->id_product=$productId;
                            $newProductTag->id_tag=$tag['id'];
                            $newProductTag->save();
                        }
                    }
                    else
                    {
                        ProductTag::where("id_product",$productId)->where("id_tag",$tag['id'])->delete();
                    }
                }
                else if($subCategory)
                    {
                        $idCategory=$subCategory['id_category'];
                        $idSubCategory=$subCategory['id'];
                        $issetProductCategory=ProductCategory::where("id_product",$productId)->where("id_category",$idCategory)->get()->first();
                        $issetProductSubCategory=ProductSubCategory::where("id_product",$productId)->where("id_subcategory",$idSubCategory)->get()->first();
                        if($isChecked=="true")
                        {
                            if(!$issetProductCategory)
                            {
                                $newProductCategory=new ProductCategory;
                                $newProductCategory->id_product=$productId;
                                $newProductCategory->id_category=$idCategory;
                                $newProductCategory->save();
                            }
                            if(!$issetProductSubCategory)
                            {
                                $newProductSubCategory=new ProductSubCategory;
                                $newProductSubCategory->id_product=$productId;
                                $newProductSubCategory->id_subcategory=$idSubCategory;
                                $newProductSubCategory->save();
                            }
                        }
                        else
                        {
                            ProductSubCategory::where("id_product",$productId)->where("id_subcategory",$idSubCategory)->delete();
                        }
                    }
                    else if($subSubCategory)
                    {
                        $idCategory=$subSubCategory['idCategory'];
                        $idSubCategory=$subSubCategory['subCategoryId'];
                        $idSubSubCategory=$subSubCategory['subsubCategoryId'];

                        $issetProductCategory=ProductCategory::where("id_product",$productId)->where("id_category",$idCategory)->get()->first();
                        $issetProductSubCategory=ProductSubCategory::where("id_product",$productId)->where("id_subcategory",$idSubCategory)->get()->first();
                        $issetProductSubSubCategory=ProductSubSubCategory::where("id_product",$productId)->where("id_subsubcategory",$idSubSubCategory)->get()->first();

                        if($isChecked=="true")
                        {
                            if(!$issetProductCategory)
                            {
                                $newProductCategory=new ProductCategory;
                                $newProductCategory->id_product=$productId;
                                $newProductCategory->id_category=$idCategory;
                                $newProductCategory->save();
                            }
                            if(!$issetProductSubCategory)
                            {
                                $newProductSubCategory=new ProductSubCategory;
                                $newProductSubCategory->id_product=$productId;
                                $newProductSubCategory->id_subcategory=$idSubCategory;
                                $newProductSubCategory->save();
                            }
                            if(!$issetProductSubSubCategory)
                            {
                                $newProductSubSubCategory=new ProductSubSubCategory;
                                $newProductSubSubCategory->id_product=$productId;
                                $newProductSubSubCategory->id_subsubcategory=$idSubCategory;
                                $newProductSubSubCategory->save();
                            }
                        }
                        else
                        {
                            ProductSubSubCategory::where("id_product",$productId)->where("id_subsubcategory",$idSubSubCategory)->delete();
                        }
                    }
    }

    public function editProduct(Request $request)
    {
        $productId = $request->productId;
        $nameProduct = $request->nameProduct;
        $priceProduct = $request->priceProduct;
        $descriptionProduct = $request->descriptionProduct;
        $productCategory = $request->productCategory;
        $issetProduct = $request->issetProduct;
        //return json_encode($productCategory);

        $idCategory=0;
        $idSubCategory=0;
        $idSubSubCategory=0;


        /*$curCategory=Category::where('url',$categoryProduct)->select("categories.id AS categoryId")->get()->first();
        if($curCategory)
        {
            $idCategory=$curCategory['categoryId'];
        }
        else
        {
            $curSubCategory=SubCategory::where('sub_categories.url',$categoryProduct)->select("sub_categories.id AS subCategoryId","sub_categories.id_category AS categoryId")->get()->first();
            if($curSubCategory)
            {
                $idCategory=$curSubCategory['categoryId'];
                $idSubCategory=$curSubCategory['subCategoryId'];
            }
            else
            {
                $curSubSubCategory=SubCategory::leftJoin('subsub_categories', function($join) {
                      $join->on('sub_categories.id', '=', 'subsub_categories.id_subcategory');
                    })->where('subsub_categories.url',$categoryProduct)->select("subsub_categories.id AS subSubCategoryId","sub_categories.id AS subCategoryId","sub_categories.id_category AS categoryId")->get()->first();
                if($curSubSubCategory)
                {
                    $idCategory=$curSubSubCategory['categoryId'];
                    $idSubCategory=$curSubSubCategory['subCategoryId'];
                    $idSubSubCategory=$curSubSubCategory['subSubCategoryId'];
                }
            }
        }
        /*$curSubCategory=SubCategory::leftJoin('categories', function($join) {
                      $join->on('categories.id', '=', 'sub_categories.id_category');
                    })->where('sub_categories.url',$categoryProduct)->select("sub_categories.id AS subCategoryId","categories.id AS categoryId")->get()->first();*/
        
        

        //$curTag=Tag::where("url",$categoryProduct)->get()->first();*/


        $updateProduct = Product::where("id",$productId)->firstOrFail();
        $updateProduct->name = $nameProduct;
        $updateProduct->price = $priceProduct;
        $updateProduct->description = $descriptionProduct;
        $updateProduct->isset=$issetProduct;
        //$updateProduct->id_category = $idCategory;
        //$updateProduct->id_subcategory = $idSubCategory;
        //$updateProduct->id_subsubcategory = $idSubSubCategory;
        $updateProduct->save();

        return json_encode(true);
    }

    public function logAdmin(Request $request)
    {
        $postNumber=$request->login;
        $postPassword=$request->password;

        $admin=AdminUser::where('number',$postNumber)->first();
        
        if(\Hash::check($postPassword,$admin['password']))
        {
            \Cookie::queue("adminNumber", $postNumber, 2628000);
            \Cookie::queue("adminPassword", $admin['password'], 2628000);
            return json_encode(true);
        }
        return json_encode(false);
    }

    public function getLoginPage()
    {
        $adminAuth=Auth::isAuthAdmin();
        if($adminAuth)
        {
            return redirect("/admin/page");
        }
        return view('admin/login');
    }

    public function getUsersAdd()
    {
        $menu="users";
        $submenu="users-add";
        $allUsers=AdminUser::get()->all();
        $listTypes=AdminType::get()->all();
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        return view("admin/users/user-add",["listTypes" => $listTypes,"menu" => $menu,"submenu" => $submenu,"admin" => $adminAuth]);
    }

    public function addUser(Request $request)
    {
        $userPassword=\Hash::make($request->userPassword);
        $user = new AdminUser;
        $user->email=$request->userLogin;
        $user->name=$request->userName;
        $user->number=$request->userNumber;
        $user->password=$userPassword;
        $user->id_type=$request->userType;
        $user->save();
        return json_encode($user);
    }

    public function banUser(Request $request)
    {
        $userId=$request->userId;
        $updateUser=Client::where("id",$userId)->firstOrFail();
        $banValue=$updateUser->is_baned==0?1:0;
        $updateUser->is_baned=$banValue;
        $updateUser->save();
        return json_encode($banValue);
    }
    
    public function editUser(Request $request)
    {   
        $userId=$request->userId;
        $userType=$request->userType;
        $userLogin=$request->userLogin;
        $userName=$request->userName;
        $userNumber=$request->userNumber;
        $userPassword=$request->userPassword;

        $updateUser=AdminUser::where("id",$userId)->firstOrFail();
        $updateUser->id_type=$userType;
        $updateUser->email=$userLogin;
        $updateUser->name=$userName;
        $updateUser->number=$userNumber;
        if(trim($userPassword))
        {
            $passwordUser=\Hash::make($request->passwordUser);
            $updateUser->password=$passwordUser;
        }
        $updateUser->save();

    }

    public function getSocialNetworkPagesEdit()
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        $socialnetworkpages=SocialnetworkPage::all();
        $menu="socialnetworkpages";
        $submenu="socialnetworkpages-edit";
        return view('admin/socialnetworkpages/socialnetwork-edit',['admin' => $adminAuth,"menu" => $menu,"submenu" => $submenu,"socialnetworkpages" => $socialnetworkpages]);
    }

    public function editSocialNetwork(Request $request)
    {
        $instagramUrl=$request->instagramUrl;
        $facebookUrl=$request->facebookUrl;

        $instagramUpdate=SocialnetworkPage::where("socialnetwork","instagram")->firstOrFail();
        $instagramUpdate->url=$instagramUrl;
        $instagramUpdate->save();

        $facebookUpdate=SocialnetworkPage::where("socialnetwork","facebook")->firstOrFail();
        $facebookUpdate->url=$facebookUrl;
        $facebookUpdate->save();

        return json_encode(true);
    }

    public function getUserEdit(Request $request)
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        $menu="users";
        $submenu="users-edit";

        $listTypes=AdminType::get()->all();
        $userEdit = AdminUser::where("email",$request->login)->get()->first();
        return view("admin/users/user-edit",["user" => $userEdit, "listTypes" => $listTypes,"admin" => $adminAuth,"menu" => $menu,"submenu" => $submenu]);     
    }

    public function getUserListWithoutSearch($page=1)
    {
        return self::getUserListApi("",$page);
    }

    public function getUserListWithSearch($searchUserName="",$page="1")
    {
        return self::getUserListApi($searchUserName,$page);     
    }

    function getUserListApi($searchUserName="",$page="1")
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        $countPage=10;
        $skipPage=($page-1)*$countPage;

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

        $usersArrayCount=AdminUser::where("admin_users.name",'like', '%'.$searchUserName.'%')->orWhere("admin_users.email",'like', '%'.$searchUserName.'%')->orWhere("admin_users.number",'like', '%'.$searchUserName.'%')->select("admin_users.id")->get()->count();
        $allPages=(double)($usersArrayCount/$countPage);
        $allPages=$allPages<=1?1:$allPages;

        $userList=AdminUser::where("admin_users.name",'like', '%'.$searchUserName.'%')->orWhere("admin_users.email",'like', '%'.$searchUserName.'%')->orWhere("admin_users.number",'like', '%'.$searchUserName.'%')->skip($skipPage)->take($countPage)->get();
        //var_dump($productList);
        $searchUserNameUrl="";
        if(trim($searchUserName))
        {
            $searchUserNameUrl="search/".$searchUserName."/";
        }
        $menu="users";
        $submenu="users-list";
        return view("admin/users/users-list",["menu" => $menu,"submenu" => $submenu,"allPages" => $allPages,"curPage" => $page,"arrayPages" => $arrayPages,"productCount" => $usersArrayCount,"userList" => $userList,"searchUserNameUrl" => $searchUserNameUrl,"admin" => $adminAuth]);
    }
    
    public function orderEditInfo(Request $request)
    {
        $orderId=$request->orderId;
        $orderName=$request->orderName;
        $orderNumber=$request->orderNumber;
        $orderAdress=$request->orderAdress;

        $updateOrder=Order::where("id",$orderId)->firstOrFail();

        $updateOrder->name=$orderName;
        $updateOrder->number=$orderNumber;
        $updateOrder->adress=$orderAdress;
        $updateOrder->save();
        return json_encode($updateOrder);   
    }
    
    public function getOrdersRejected($page="1")
    {
        return view("admin/orders/rejected",self::getOrdersInProcessApi($page,"","25"));
    }
    
    public function getOrdersShippingSevenDay($page="1")
    {
        return view("admin/orders/orders-list-shipping-day7",self::getOrdersInProcessApi($page,"","10"));
    }
    
    public function getOrdersShippingSixDay($page="1")
    {
        return view("admin/orders/orders-list-shipping-day6",self::getOrdersInProcessApi($page,"","9"));
    }
    
    public function getOrdersShippingFiveDay($page="1")
    {
        return view("admin/orders/orders-list-shipping-day5",self::getOrdersInProcessApi($page,"","8"));
    }
    
    public function getOrdersShippingFourDay($page="1")
    {
        return view("admin/orders/orders-list-shipping-day4",self::getOrdersInProcessApi($page,"","7"));
    }
    
    public function getOrdersShippingThreeDay($page="1")
    {
        return view("admin/orders/orders-list-shipping-day3",self::getOrdersInProcessApi($page,"","6"));
    }
    
    public function getOrdersShippingTwoDay($page="1")
    {
        return view("admin/orders/orders-list-shipping-day2",self::getOrdersInProcessApi($page,"","5"));
    }
    
    public function getOrdersShippingOneDay($page="1")
    {
        return view("admin/orders/orders-list-shipping-day1",self::getOrdersInProcessApi($page,"","4"));
    }

    public function getOrdersShipping($page="1")
    {
        return view("admin/orders/orders-list-shipping",self::getOrdersInProcessApi($page,"","1"));
    }

    public function getOrdersFinished($page="1")
    {
        return view("admin/orders/orders-list-finished",self::getOrdersInProcessApi($page,"","2"));
    }

    public function getOrdersCanceled($page="1")
    {
        return view("admin/orders/orders-list-canceled",self::getOrdersInProcessApi($page,"","3"));
    }

    public function getOrdersCanceledWithSearch($searchOrderNameUrl="",$page="1")
    {
        return view("admin/orders/orders-list-canceled",self::getOrdersInProcessApi($page,$searchOrderNameUrl,"3"));
    }

    public function getOrdersFinishedWithSearch($searchOrderNameUrl="",$page="1")
    {
        return view("admin/orders/orders-list-finished",self::getOrdersInProcessApi($page,$searchOrderNameUrl,"2"));
    }

    public function getOrdersShippingWithSearch($searchOrderNameUrl="",$page="1")
    {
        return view("admin/orders/orders-list-shipping",self::getOrdersInProcessApi($page,$searchOrderNameUrl,"1"));
    }
    
    public function getOrdersStatistics($page="1")
    {
        return view("admin/orders/orders-statistics",self::getOrdersStatisticsApi($page,""));
    }

    public function getOrdersInProcess($page="1")
    {
        return view("admin/orders/orders-list-inprocess",self::getOrdersInProcessApi($page));
    }

    public function getOrdersWithSearch($searchOrderNameUrl="",$page="1")
    {
        return view("admin/orders/orders-list-inprocess",self::getOrdersInProcessApi($page,$searchOrderNameUrl));
    }

    public function getClientsList($page="1")
    {
        return view("admin/clients/clients-list",self::getClientListApi($page));
    }

    public function getClientsListWithSearch($searchClient="", $page="1")
    {
        return view("admin/clients/clients-list",self::getClientListApi($page,$searchClient));
    }

    public function getClientListApi($page="1",$searchUserName="")
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        $countPage=10;
        $skipPage=($page-1)*$countPage;

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

        $usersArrayCount=Client::where("name",'like', '%'.$searchUserName.'%')->orWhere("email",'like', '%'.$searchUserName.'%')->orWhere("number",'like', '%'.$searchUserName.'%')->select("id")->get()->count();
        $allPages=(double)($usersArrayCount/$countPage);
        $allPages=$allPages<=1?1:$allPages;

        $userList=Client::where("name",'like', '%'.$searchUserName.'%')->orWhere("email",'like', '%'.$searchUserName.'%')->orWhere("number",'like', '%'.$searchUserName.'%')->skip($skipPage)->take($countPage)->get();
        //var_dump($productList);
        $searchUserNameUrl="";
        if(trim($searchUserName))
        {
            $searchUserNameUrl="search/".$searchUserName."/";
        }
        $menu="clients";
        $submenu="clients-list";
        return ["allPages" => $allPages,"curPage" => $page,"arrayPages" => $arrayPages,"productCount" => $usersArrayCount,"userList" => $userList,"searchUserNameUrl" => $searchUserNameUrl,"menu" => $menu, "submenu" => $submenu,"admin" => $adminAuth];
    }



    public function getOrdersInProcessApi($page="1",$searchOrderName="",$action="0")
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        $countPage=10;
        $skipPage=($page-1)*$countPage;

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
        $idAhmad=815;
        $idAhmadEnd=10000;
        //Цепко Юлия
        if(trim($searchOrderName)!="")
        {
            //ahmad
            if($adminAuth['id']==2)
            {
                 $orderListArrayCount=Order::where("id",">",$idAhmad)->where("id","<",$idAhmadEnd)->where("action","=",$action)->where("comment","like","Заказ с инстаграмма: Мольберт детский")->select("orders.id")->get()->count();
            }
            else
            {
                 $orderListArrayCount=Order::where("action","=",$action)->where("id",$searchOrderName)->select("orders.id")->get()->count();
            }
        }
        else
        {
            //ahmad
            if($adminAuth['id']==2)
            {
                 $orderListArrayCount=Order::where("id",">",$idAhmad)->where("id","<",$idAhmadEnd)->where("action","=",$action)->where("comment","like","Заказ с инстаграмма: Мольберт детский")->select("orders.id")->get()->count();
            }
            else
            {//where("comment","like","Заказ с инстаграмма: Пульсоксиметр")->
         $orderListArrayCount=Order::where("action","=",$action)->select("orders.id")->get()->count();   
            }
        }
        $allPages=(double)($orderListArrayCount/$countPage);
        $allPages=$allPages<=1?1:$allPages;
        $searchOrderNameUrl="";
        $znak=">";
        if(trim($searchOrderName))
        {
            $searchOrderNameUrl="search/".$searchOrderName."/";
            $znak="=";
        }

        //ahmad
        if($adminAuth['id']==2)
        {
            $orderList=Order::where("id",">",$idAhmad)->where("id","<",$idAhmadEnd)->where("action","=",$action)->where("comment","like","Заказ с инстаграмма: Мольберт детский")->select("orders.created_at AS orderCreatedAt","orders.id AS orderId","orders.adress AS orderAdress","orders.number AS orderNumber","orders.name AS orderName","orders.paymentType AS orderPaymentType","orders.comment AS orderComment")->orderBy("orders.id","DESC")->skip($skipPage)->take($countPage)->get();
        }
        else
        {
        $orderList=Order::where("id",$znak,$searchOrderName)->where("action","=",$action)->select("orders.created_at AS orderCreatedAt","orders.id AS orderId","orders.adress AS orderAdress","orders.number AS orderNumber","orders.name AS orderName","orders.paymentType AS orderPaymentType","orders.comment AS orderComment")->orderBy("orders.id","DESC")->skip($skipPage)->take($countPage)->get();
        }


        $menu="orders";
        if($action==0)
        {
            $submenu="in-process";
        }
        else if($action==1)
        {
            $submenu="shipping";
        }
        else if($action==2)
        {
            $submenu="finished";
        }
        else if($action==3)
        {
            $submenu="canceled";
        }
        else if($action==4)
        {
            $submenu="day1";
        }
        else if($action==5)
        {
            $submenu="day2";
        }
        else if($action==6)
        {
            $submenu="day3";
        }
        else if($action==7)
        {
            $submenu="day4";
        }
        else if($action==8)
        {
            $submenu="day5";
        }
        else if($action==9)
        {
            $submenu="day6";
        }
        else if($action==10)
        {
            $submenu="day7";
        }
        else if($action==25)
        {//Видмова вид отримання на НОВОЙ ПОЧТЕ
            $submenu="rejected";
        }
        return ["orderList" => $orderList, "allPages" => $allPages,"curPage" => $page,"arrayPages" => $arrayPages,"ordersCount" => $orderListArrayCount,"searchOrderNameUrl" => $searchOrderNameUrl,"menu" => $menu,"submenu" => $submenu,"admin" => $adminAuth];
    }
    
    public function getOrdersStatisticsApi($page="1",$searchOrderName="")
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        $countPage=10;
        $skipPage=($page-1)*$countPage;

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
        $idAhmad=815;
        $idAhmadEnd=10000;
        //Цепко Юлия
        if(trim($searchOrderName)!="")
        {
        //where("action","=",$action)-> вместо id
            $orderListArrayCount=Order::where("id",">=",126)->where("id",$searchOrderName)->select("orders.id")->get()->count();
        }
        else
        {//where("comment","like","Заказ с инстаграмма: Пульсоксиметр")->
        //where("action","=",$action)-> вместо id
            $orderListArrayCount=Order::where("id",">=",126)->select("orders.id")->get()->count();
        }
        $allPages=(double)($orderListArrayCount/$countPage);
        $allPages=$allPages<=1?1:$allPages;
        $searchOrderNameUrl="";
        $znak=">";
        if(trim($searchOrderName))
        {
            $searchOrderNameUrl="search/".$searchOrderName."/";
            $znak="=";
        }

        //where("action","=",$action)->
        $orderList=Order::where("id",">=","126")->where("action","!=",3)->select("orders.action AS orderAction","orders.created_at AS orderCreatedAt","orders.id AS orderId","orders.adress AS orderAdress","orders.number AS orderNumber","orders.name AS orderName","orders.paymentType AS orderPaymentType","orders.comment AS orderComment")->orderBy("orders.action","ASC")->get();
        


        $menu="orders";
        $submenu="statistics";
        return ["orderList" => $orderList, "allPages" => $allPages,"curPage" => $page,"arrayPages" => $arrayPages,"ordersCount" => $orderListArrayCount,"searchOrderNameUrl" => $searchOrderNameUrl,"menu" => $menu,"submenu" => $submenu,"admin" => $adminAuth];
    }

    public function orderEditPayment(Request $request)
    {
        $orderId=$request->orderId;
        $paymentPrice=$request->paymentPrice;

        $updateOrder=Order::where("id",$orderId)->firstOrFail();
        $oldPriceDelivery=$updateOrder->priceDelivery;

        $updateOrder->priceDelivery=$paymentPrice;
        $updateOrder->save();
        return json_encode($oldPriceDelivery);
    }
    

    public function getOrderEdit($idOrder)
    {//Here
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        //$listOrders=OrderAction::all();
        $order=Order::where("id",$idOrder)->select("orders.created_at AS orderCreatedAt","orders.id AS orderId","orders.adress AS orderAdress","orders.number AS orderNumber","orders.name AS orderName","orders.paymentType AS orderPaymentType","orders.comment AS orderComment","orders.action AS orderAction","orders.DocRef AS orderDocRef","orders.IntDocNumber AS orderIntDocNumber","orders.priceDelivery AS orderPriceDelivery","orders.isPayedPayment")->get()->first();
        $cartList=Cart::leftJoin('products', function($join) {
                  $join->on('products.id', '=', 'carts.id_product');
                })->leftJoin('images', function($join) {
                  $join->on('products.id', '=', 'images.id_product');
                })->where("images.is_main","1")->where("carts.id_order",$idOrder)->select("carts.id AS cartId","carts.id_client AS clientId","products.id AS productId","products.name AS productName","carts.count AS productCount","carts.price AS productPrice","images.src AS productImage")->get();
        $menu="orders";
        $submenu="orders-edit";
        header('Content-Type: text/html; charset=utf-8');
        //$data = ["apiKey" => "9911c50e77acd5aeafaeba1af3a719d3", "modelName" => "InternetDocument", "calledMethod" => "getDocumentList", "methodProperties" => ["DateTimeFrom" => "21.04.2021", "DateTimeTo" => "28.04.2021", "Page" => "1", "GetFullList" => "0"]];
        //1 - Идентификатор города КИЕВ
        $data = ["apiKey" => "9911c50e77acd5aeafaeba1af3a719d3", "modelName" => "Address", "calledMethod" => "getCities", "methodProperties" => ["FindByString" => "Одеса"]];
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
curl_close($curl);
echo explode("\"",explode("\"Ref\":\"",$result)[1])[0];

//2 f9652379-3dd7-11ea-9937-005056881c6b - Гулам Насрин Ref


       /* {
"apiKey": "9911c50e77acd5aeafaeba1af3a719d3",
"modelName": "InternetDocument",
"calledMethod": "getDocumentList",
"methodProperties": {
"DateTimeFrom": "21.06.2016",
"DateTimeTo": "24.06.2016",
"Page": "1",
"GetFullList": "0"
}
}*/
        return view("admin/orders/order-edit",["order" => $order,"cartList" => $cartList,"menu" => $menu,"submenu" => $submenu,"admin" => $adminAuth]);
    }

    public function orderEditCount(Request $request)
    {
        $cartId=$request->cartId;
        $productCount=$request->productCount;
        $updateCart=Cart::where("id",$cartId)->firstOrFail();
        $updateCart->count=$productCount;
        $updateCart->save();

        return json_encode($updateCart);
    }

    public function orderEditPrice(Request $request)
    {
        $cartId=$request->cartId;
        $productPrice=$request->productPrice;
        $updateCart=Cart::where("id",$cartId)->firstOrFail();
        $updateCart->price=$productPrice;
        $updateCart->save();

        return json_encode($updateCart);
    }

    public function removeOrderProduct(Request $request)
    {
        $cartId=$request->cartId;
        $removeCart=Cart::where("id",$cartId)->delete();

        return json_encode($removeCart);
    }

    public function cancelOrder(Request $request)
    {
        $orderId=$request->orderId;
        $updateOrder=Order::where("id",$orderId)->firstOrFail();
        $updateOrder->action=3;
        $updateOrder->save();
        return json_encode($updateOrder);
    }

    public function confirmOrder(Request $request)
    {
        $orderId=$request->orderId;
        $updateOrder=Order::where("id",$orderId)->firstOrFail();
        $updateOrder->action=1;
        $updateOrder->save();
        return json_encode($updateOrder);
    }

    public function finishOrder(Request $request)
    {
        $orderId=$request->orderId;
        $updateOrder=Order::where("id",$orderId)->firstOrFail();
        $updateOrder->action=2;
        $updateOrder->save();
        return json_encode($updateOrder);
    }

    public function getClientsOrders($clientEmail,$page="1")
    {
        return view("admin/clients/orders/orders-list",self::getClientsOrdersApi($clientEmail,$page));

        //return json_encode($listOrders);
    }

    public function getClientsOrdersWithSearch($clientEmail,$searchName,$page="1")
    {
        return view("admin/clients/orders/orders-list",self::getClientsOrdersApi($clientEmail,$page,$searchName));

        //return json_encode($listOrders);
    }

    public function getClientsOrdersApi($clientNumber,$page="1",$searchOrderName="")
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        $client=Client::where("number",$clientNumber)->get()->first();


        $countPage=10;
        $skipPage=($page-1)*$countPage;

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

        $orderListArrayCount=Order::where("id_client",$client['id'])->select("id")->get()->count();
        $allPages=(double)($orderListArrayCount/$countPage);
        $allPages=$allPages<=1?1:$allPages;
        $searchOrderNameUrl="";
        $znak=">";
        if(trim($searchOrderName))
        {
            $searchOrderNameUrl="search/".$searchOrderName."/";
            $znak="=";
        }
//searchOrderName
        $orderList=Order::where("id_client",$client['id'])->where("id",$znak,$searchOrderName)->select("orders.action AS ordersAcion","orders.created_at AS orderCreatedAt","orders.id AS orderId","orders.adress AS orderAdress","orders.number AS orderNumber","orders.name AS orderName","orders.paymentType AS orderPaymentType","orders.comment AS orderComment")->orderBy("orders.id","DESC")->skip($skipPage)->take($countPage)->get();

        $menu="clients";
        $submenu="client-orders";
        return ["client"=>$client,"orderList" => $orderList, "allPages" => $allPages,"curPage" => $page,"arrayPages" => $arrayPages,"ordersCount" => $orderListArrayCount,"searchOrderNameUrl" => $searchOrderNameUrl,"admin" => $adminAuth,"menu" => $menu, "submenu" => $submenu];

        //return json_encode($listOrders);
    }

    public function getBlogList($page="1")
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        return view("admin/blog/blog-list",self::getBlogListApi("",$page));
    }

    public function getBlogListWithSearch($searchBlog="",$page="1")
    {
        $blogListVariables=self::getBlogListApi($searchBlog,$page);

        $adminAuth=$blogListVariables['admin'];
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        return view("admin/blog/blog-list",$blogListVariables);
    }

    public function getBlogListApi($searchBlog="",$page="1")
    {
        $adminAuth=Auth::isAuthAdmin();
        $countPage=10;
        $skipPage=($page-1)*$countPage;

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

        $blogListCount=Blog::where("name",'like','%'.$searchBlog.'%')->count();

        $allPages=(double)($blogListCount/$countPage);
        $allPages=$allPages<=1?1:$allPages;

        $blogList=Blog::where("name",'like','%'.$searchBlog.'%')->skip($skipPage)->take($countPage)->get();
        //var_dump($productList);
        $searchBlogNameUrl="";
        if(trim($searchBlog))
        {
            $searchBlogNameUrl="search/".$searchBlog."/";
        }

        $menu="blog";
        $submenu="blog-list";

        return ["allPages" => $allPages,"curPage" => $page,"arrayPages" => $arrayPages,"blogListCount" => $blogListCount,"blogList" => $blogList,"searchBlogNameUrl" => $searchBlogNameUrl,"menu" => $menu,"submenu" => $submenu,"admin" => $adminAuth];
    }

    public function getAddBlog()
    {
        $menu="blog";
        $submenu="blog-add";
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        return view("admin/blog/blog-add",["menu" => $menu,"submenu" => $submenu,"admin" => $adminAuth]);
    }

    public function addBlog(Request $request)
    {
        $blogName=$request->blogName;
        $blogText=$request->blogText;
        $blogUrl=self::str2url($blogName);
        $blogImage="";

        $fileList = glob("uploads/temporary-blog/*");
 
        //Loop through the array that glob returned.
        $count=0;
        foreach($fileList as $filename){
           //Simply print them out onto the screen.
            $newfilename = "uploads/blog/".uniqid().time().stristr(substr($filename, -5),'.');
            rename($filename,$newfilename);
            $blogImage=$newfilename;
            $count++;
        }

        if(empty($blogImage))
        {
            return json_encode("empty_image");
        }

        $newBlog=new Blog;
        $newBlog->name=$blogName;
        $newBlog->text=$blogText;
        $newBlog->url=$blogUrl;
        $newBlog->main_image=$blogImage;
        $newBlog->save();
        return json_encode($newBlog);
    }

    public function getBlogEdit($blogUrl)
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        $menu="blog";
        $submenu="blog-edit";
        $blogPost=Blog::where("url",$blogUrl)->get()->first();
        return view("admin/blog/blog-edit",["admin" => $adminAuth,'blogPost' => $blogPost,"menu" => $menu,"submenu" => $submenu]);
    }

    public function editBlog(Request $request)
    {
        $nameBlog=$request->namePost;
        $textBlog=$request->textPost;
        $idBlog=$request->idBlog;
        $newBlog=Blog::where("id",$idBlog)->get()->first();
        $newBlog->name=$nameBlog;
        $newBlog->text=$textBlog;
        $newBlog->save();
        return json_encode($newBlog);
    }

    public function getSeoAdd()
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        $seoText=null;
        $domainName=$_SERVER['SERVER_NAME'];
        if(isset($_GET['url']))
        {
            $seoText = SeoText::where("url",$_GET['url'])->get()->first();
            $seoText['url']=$domainName.$seoText['url'];
        }
        $menu="seo";
        $submenu="seo-optimized";
        return view("admin/seo/seo-add",["menu" => $menu,"submenu" => $submenu,'seoText' => $seoText,"admin" => $adminAuth]);
    }

    public function addSeo(Request $request)
    {
        $domainName=$_SERVER['SERVER_NAME'];
        $curUrl=$request->seoUrl;
        $seoTitle=$request->seoTitle;
        $seoH1=$request->seoH1;
        $seoDescription=$request->seoDescription;
        $seoCannonical=$request->seoCannonical;
        $seoText=$request->seoText;
        $metaNoIndex=$request->metaNoIndex;

        $explodeUrl = explode($domainName,$curUrl);
        $resUrl="";
        for($i=1;$i<count($explodeUrl);$i++)
        {
            $resUrl.=$explodeUrl[$i];
        }
        /*Cart::updateOrCreate(["id_client" => $client['id'],
            "id_product" => $product['id'],
            "id_order" => 0
            ],["count" => $request->productCount,"price" => $product['price']]);*/

        $newSeoText = SeoText::updateOrCreate(
            ['url' => $resUrl],
            ['h1' => $seoH1,"metaTitle" => $seoTitle,"metaDescription" => $seoDescription,"metaCannonical" => $seoCannonical,"seoText" => $seoText,"metaNoIndex" => $metaNoIndex]
        );
        return json_encode($newSeoText);
    }

    public function getSeoList($page="1")
    {
        return view("admin/seo/seo-list",self::getSeoListApi("",$page));
    }

    public function getSeoListWithSearch()
    {
        $search="";
        $page=1;
        if(isset($_GET['page']))
        {
            $page=(int)$_GET['page'];
        }
        if(isset($_GET['search']))
        {
            $search=$_GET['search'];
        }
        return view("admin/seo/seo-list",self::getSeoListApi($search,$page));
    }

    public function getSeoListApi($searchSeoUrl="",$page="1")
    {
        $adminAuth=Auth::isAuthAdmin();
        if(!$adminAuth)
        {
            return redirect("/admin/login");
        }
        $countPage=10;
        $skipPage=($page-1)*$countPage;

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

        $seoListCount=SeoText::where("url",'like','%'.$searchSeoUrl.'%')->count();

        $allPages=(double)($seoListCount/$countPage);
        $allPages=$allPages<=1?1:$allPages;

        $seoList=SeoText::where("url",'like','%'.$searchSeoUrl.'%')->skip($skipPage)->take($countPage)->get();
        
        //var_dump($productList);
        $searchSeoNameUrl="";
        if(trim($searchSeoUrl))
        {
            $searchSeoNameUrl="search=".$searchSeoUrl."&";
        }

        $menu="seo";
        $submenu="seo-list";
        return ["menu" => $menu,"submenu" => $submenu,"seoList" => $seoList,"allPages" => $allPages,"curPage" => $page,"arrayPages" => $arrayPages,"seoListCount" => $seoListCount,"searchSeoNameUrl" => $searchSeoNameUrl,"admin" => $adminAuth];
    }

    public function removeSeo(Request $request)
    {
        $idPost=$request->postId;

        $removeCart=SeoText::where("id",$idPost)->delete();
        return json_encode($removeCart);
    }
}