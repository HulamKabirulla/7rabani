<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Blog;
use App\Category;
use App\SubCategory;
use App\SubSubCategory;
use App\SeoText;
use App\Traits\Auth;

class BlogController extends Controller
{
	public function getBlogPost($url)
    {
        $clientAuth=Auth::isAuth();
    	$blog=Blog::where("url",$url)->get()->first();
        if(!$blog)
        {
            return abort("404");
        }
        $h1=$blog->name;
        $metaTitle=$blog->name;
        $metaDescription="";
        $metaNoIndex="0";
        $metaCannonical="/ru/blog/".$url;
        $seoText=$blog->text;

        $seoOptimization=SeoText::where('url',$_SERVER['REQUEST_URI'])->get()->first();
        if($seoOptimization)
        {
            $h1=$seoOptimization->h1;
            $metaTitle=$seoOptimization->metaTitle;
            $metaDescription=$seoOptimization->metaDescription;
            $metaNoIndex=$seoOptimization->metaNoIndex;
            $metaCannonical=$seoOptimization->metaCannonical;
            $seoText=$seoOptimization->seoText;
        }
        $allCategories=Category::all();
        $allSubCategories=SubCategory::all();
        $allSubSubCategories=SubSubCategory::all();
    	return view("blog_single",["clientAuth" => $clientAuth,"h1" => $h1,"metaTitle" => $metaTitle,"metaDescription" => $metaDescription,"metaNoIndex" => $metaNoIndex,"metaCannonical" => $metaCannonical,"blog" => $blog,"seoText" => $seoText,"allCategories" => $allCategories, "allSubCategories" => $allSubCategories, "allSubSubCategories" => $allSubSubCategories]);
    }

    public function getAllBlog($page=1)
    {
        $clientAuth=Auth::isAuth();
        $countPage=6;
        $skipPage=($page-1)*$countPage;
    	$listBlog=Blog::select("name","url","main_image")->skip($skipPage)->take($countPage)->get()->all();


        $blogCount=Blog::get()->count();
        $allPages=(int)($blogCount/$countPage);
        $allPages=$allPages<=1?1:$allPages;


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
        $allCategories=Category::all();
        $allSubCategories=SubCategory::all();
        $allSubSubCategories=SubSubCategory::all();

    	return view("blog",["clientAuth" => $clientAuth,"listBlog" => $listBlog,"allPages" => $allPages,"arrayPages" => $arrayPages,"curPage" => $page,"allCategories" => $allCategories,"allSubCategories" => $allSubCategories,"allSubSubCategories" => $allSubSubCategories]);
    }
}
