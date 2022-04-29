<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use App\SubCategory;
use App\SubSubCategory;
use App\Traits\Auth;
use App\SocialnetworkPage;

class MainController extends Controller
{
    public function getMainPage()
    {
    	$allCategories=Category::all();
        $allSubCategories=SubCategory::all();
        $allSubSubCategories=SubSubCategory::all();
        $clientAuth=Auth::isAuth();

        $socialnetworkpages=SocialnetworkPage::all();
    	return view('welcome',['clientAuth' => $clientAuth,'allCategories' => $allCategories,'allSubCategories' => $allSubCategories,'allSubSubCategories' => $allSubSubCategories,"socialnetworkpages" => $socialnetworkpages]);
    }
}
