<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Category;
use App\SubCategory;
use App\SubSubCategory;
use App\SeoText;
use App\Client;

class LandingController extends Controller
{
	public function getPulsoximetr()
	{
		return view("landing-instagram/pulsoximetr");
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
	
	public function getCompletePulsoximetr()
	{
		return view("landing-instagram/complete-pulsoximetr");
	}
}