<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Session;
use Illuminate\Support\Facades\Redirect;

class WelcomeController extends Controller
{
    public function index()
    {
        $all_published_product=DB::table('product')
                ->where('publication_status',1)
                ->latest()
                ->get();
        $home= view('front-end.components.features-item')
                ->with('all_published_product', $all_published_product);
        return view('front-end.master')
                    ->with('main_content', $home);
    }

    public function computers()
    {
        $computers= view('front-end.pages.full-pc');
        return view('front-end.master')
                    ->with('main_content', $computers);
    }
    
    public function monitor()
    {
        $monitor= view('front-end.pages.monitor');
        return view('front-end.master')
                    ->with('main_content', $monitor);
    }
    
    public function motherboard()
    {
        $motherboard= view('front-end.pages.motherboard');
        return view('front-end.master')
                    ->with('main_content', $motherboard);
    }
    
    public function processor()
    {
        $processor= view('front-end.pages.processor');
        return view('front-end.master')
                    ->with('main_content', $processor);
    }
    
    public function hard_disk()
    {
        $hard_disk= view('front-end.pages.hard-disk');
        return view('front-end.master')
                    ->with('main_content', $hard_disk);
    }
    
    public function dvd_writer()
    {
        $dvd_writer= view('front-end.pages.dvd-writer');
        return view('front-end.master')
                    ->with('main_content', $dvd_writer);
    }
    
    public function power_supply()
    {
        $power_supply= view('front-end.pages.power-supply');
        return view('front-end.master')
                    ->with('main_content', $power_supply);
    }
    
    public function casing()
    {
        $casing= view('front-end.pages.casing');
        return view('front-end.master')
                    ->with('main_content', $casing);
    }
    
    public function use_pc()
    {
        $use_pc= view('front-end.pages.use-pc');
        return view('front-end.master')
                    ->with('main_content', $use_pc);
    }
    
    public function use_laptop()
    {
        $use_laptop= view('front-end.pages.use-laptop');
        return view('front-end.master')
                    ->with('main_content', $use_laptop);
    }
    
    public function laptop()
    {
        $laptop= view('front-end.pages.laptop');
        return view('front-end.master')
                    ->with('main_content', $laptop);
    }
    
    public function use_monitor()
    {
        $use_monitor= view('front-end.pages.use-monitor');
        return view('front-end.master')
                    ->with('main_content', $use_monitor);
    }
    
    public function use_router()
    {
        $use_router= view('front-end.pages.use-router');
        return view('front-end.master')
                    ->with('main_content', $use_router);
    }
    
    public function router()
    {
        $router= view('front-end.pages.router');
        return view('front-end.master')
                    ->with('main_content', $router);
    }
    
    public function use_printer()
    {
        $use_printer= view('front-end.pages.use-printer');
        return view('front-end.master')
                    ->with('main_content', $use_printer);
    }
    
    public function printer()
    {
        $printer= view('front-end.pages.printer');
        return view('front-end.master')
                    ->with('main_content', $printer);
    }
    
    
    public function pendrive()
    {
        $pendrive= view('front-end.pages.pendrive');
        return view('front-end.master')
                    ->with('main_content', $pendrive);
    }
    
    
    public function gift_item()
    {
        $gift_item= view('front-end.pages.gift-item');
        return view('front-end.master')
                    ->with('main_content', $gift_item);
    }
    
    public function physiotherapy()
    {
        $physiotherapy= view('front-end.pages.physiotherapy');
        return view('front-end.master')
                    ->with('main_content', $physiotherapy);
    }
    
    
    public function about_us()
    {
        return view('front-end.pages.about-us');
    }
    
    public function contact_us()
    {
        return view('front-end.pages.contact-us');
    }
    
    public function termsandconditions()
    {
        return view('front-end.pages.terms&conditions');
    }
    
    
    
    public function product_details($id)
    {
        $product_details = DB::table('product')
           // ->join('product', 'product.manufacturer_id', '=', 'product.manufacturer_id')
            ->join('manufacturer', 'product.manufacturer_id', '=', 'manufacturer.manufacturer_id')
            ->select('product.*', 'manufacturer.manufacturer_name')
            ->where('product.id',$id)
            ->first();
//        $product_details= view('front-end.pages.product-details');
        return view('front-end.pages.product-details')
                    ->with('product_details',$product_details);
//                    ->with('main_content', $product_details);
    }
    
    

    public function store(Request $request)
    {
        //
    }

}
