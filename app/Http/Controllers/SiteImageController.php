<?php

namespace App\Http\Controllers;

use App\SiteImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteImageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('site_images.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeSlider(Request $request)
    {
        $image_index[0] = null;
        foreach ($request->file('file') as $key => $value) {
            $slider_images = new SiteImage();
            $name = Storage::put('img/website/slider', $value);
            $slider_images->image = $name;
            $slider_images->image_for = 'slider';
            $slider_images->save();
        }
        $output = [
            'success' => 1,
            'msg' => "Images Saved"
        ];
        return redirect()->back()->with('status', $output);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function categoryImage(Request $request)
    {
        $image_index[0] = null;
        // foreach ($request->file('file') as $key => $value) {
            $slider_images = SiteImage::where('image_for', $request->type)->first();
            if(!$slider_images){
                $slider_images = new SiteImage();
            }
            $name = Storage::put('img/website/banner', $request->file('image'));
            $slider_images->image = $name;
            $slider_images->image_for = $request->type;
            $slider_images->save();
        // }
        $output = [
            'success' => 1,
            'msg' => "Image Saved"
        ];
        return redirect()->back()->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\SiteImage  $siteImage
     * @return \Illuminate\Http\Response
     */
    public function show(SiteImage $siteImage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\SiteImage  $siteImage
     * @return \Illuminate\Http\Response
     */
    public function edit(SiteImage $siteImage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\SiteImage  $siteImage
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SiteImage $siteImage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\SiteImage  $siteImage
     * @return \Illuminate\Http\Response
     */
    public function destroySlider($id)
    {
        $image = SiteImage::find($id);
        $image->delete();
        $output = [
            'success' => 1,
            'msg' => "Images Deleted Successfully"
        ];
        return redirect()->back()->with('status', $output);
    }
}
