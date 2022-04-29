<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Client;
use App\Category;
use App\SubCategory;
use App\SubSubCategory;

class ClientController extends Controller
{
    //
    public function regUser(Request $request)
    {
    	$uNumber=$request->uNumber;
    	$uEmail=$request->uEmail;
		$uPassword=\Hash::make($request->uPassword);
		$newClient=Client::where('number',$uNumber)->first();

		
		if($newClient)
		{
	    	return json_encode(["error" => "already_exist"]);
		}
		$newClient = new Client;
    	$newClient->email=$request->uEmail;
    	$newClient->number=$request->uNumber;
    	$newClient->name=$request->uName;
    	$newClient->password=$uPassword;
    	$newClient->save();

		\Cookie::queue("uNumber", $uNumber, 2628000);
		\Cookie::queue("uPassword", $uPassword, 2628000);

    	return json_encode(["newClient" => $newClient]);
    }

    public function login(Request $request)
    {
    	$postNumber=$request->uNumber;
		$postPassword=$request->uPassword;
		$client=Client::where('number',$postNumber)->first();
		
		if(\Hash::check($postPassword,$client['password']))
		{
			\Cookie::queue("uNumber", $postNumber, 2628000);
			\Cookie::queue("uPassword", $client['password'], 2628000);
	    	$allCategories=Category::all();
	    	$allSubCategories=SubCategory::all();
	    	$allSubSubCategories=SubSubCategory::all();
	    	return response()->json([
			    'header' => view('includes/header')->with(['clientAuth' => $client,"allCategories" => $allCategories, "allSubCategories" => $allSubCategories, "allSubSubCategories" => $allSubSubCategories])->render(),
			]);
		}
		return json_encode(null);
    }

    public function logout()
    {
		\Cookie::queue(\Cookie::forget("uNumber"));
		\Cookie::queue(\Cookie::forget("uPassword"));
		$allCategories=Category::all();
	    $allSubCategories=SubCategory::all();
	    $allSubSubCategories=SubSubCategory::all();
	    return response()->json([
			    'header' => view('includes/header')->with(['clientAuth' => null,"allCategories" => $allCategories, "allSubCategories" => $allSubCategories, "allSubSubCategories" => $allSubSubCategories])->render(),
			]);
    }
}