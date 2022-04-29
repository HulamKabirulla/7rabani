<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use App\Image1;
use Image;

class UploadFileController extends Controller
{
	public function uploadNewAdditionalPhoto(Request $request)
	{
		$destinationPath = public_path("uploads/products");
	    $this->validate($request, [
	        'morePhoto' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
	    ]);


	    $file = $request->file('morePhoto');

    	$filename = uniqid().time().".".$file->extension();
	  		
	     //перемещаем загруженный файл
	     if($file->move($destinationPath,$filename))
	     {
	     	$newImage=new Image1;
            $newImage->id_product=$request->productId;
            $newImage->src="uploads/products"."/".$filename;
            $newImage->is_main=1;
            $newImage->save();
	        return json_encode(true);
	     }
	    return json_encode(null);
	}
	public function changeMainPhoto(Request $request)
	{

	       //return json_encode("meow");
		$destinationPath = public_path("uploads/products/");
		$photoUrl = $request->photoUrl;

		$file = $request->file('image');
		if($file->move($destinationPath,$photoUrl))
	    {
	       return json_encode(true);
	    }
	}

	public function removePhoto(Request $request)
	{
		$removePhoto=substr($request->removePhoto,1);
		Image1::where('src',$removePhoto)->delete();
		unlink($removePhoto);
		return json_encode(true);
	}

    public function uploadMainPhotoOfProduct(Request $request){
	    $destinationPath = public_path("uploads/temporary-products/mainPhoto");

		$file = new Filesystem;
		$file->cleanDirectory($destinationPath);

	    $this->validate($request, [
	        'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:30000',
	    ]);

	    $image = $request->file('image');

        $input['imagename'] = uniqid().time().".".$image->getClientOriginalExtension();
     

        //$destinationPath = public_path('uploads/thumbnail');

        $img = Image::make($image->getRealPath());

        $img->resize(300, 300, function ($constraint) {

            $constraint->aspectRatio();

        })->save($destinationPath.'/'.$input['imagename']);


   

        //$destinationPath = public_path('/images');

        //$image->move($destinationPath, $input['imagename']);

        return json_encode(true);

	    /*$file = new Filesystem;
		$file->cleanDirectory($destinationPath);

	    $file = $request->file('image');

    	$filename = uniqid().time().".".$file->extension();
	  		
	     //перемещаем загруженный файл
	     if($file->move($destinationPath,$filename))
	     {
	        return json_encode(true);
	     }
	    return json_encode(null);*/
	}

	public function uploadBlogPhoto(Request $request)
	{
		$destinationPath = public_path("uploads/temporary-blog");
	    $this->validate($request, [
	        'blogPhoto' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10000',
	    ]);

	    $file = new Filesystem;
		$file->cleanDirectory($destinationPath);

	    $file = $request->file('blogPhoto');

    	$filename = uniqid().time().".".$file->extension();
	  		
	     //перемещаем загруженный файл
	     if($file->move($destinationPath,$filename))
	     {
	        return json_encode(true);
	     }
	    return json_encode(null);
	}

	public function uploadAdditionalPhotoesOfProduct(Request $request)
	{
		$destinationPath = public_path("uploads/temporary-products/additional");

		$file = new Filesystem;
		$file->cleanDirectory($destinationPath);

		$files = $request->file('additionalPhotoes');
		if($request->hasFile('additionalPhotoes'))
		{
			$i=0;
		    foreach ($files as $file) {
		        $file->move($destinationPath,$i."additional.jpg");
		        $i++;
		    }
		}
	}

	public function changeMainBlogPhoto(Request $request)
	{
		$destinationPath = "uploads/blog";
		$photoName=$request->photoUrl;
		$file = $request->file('image');
		rename($file->getPathname(),$photoName);
		return json_encode(true);
	}
}