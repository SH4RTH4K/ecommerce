<?php
namespace App\Http\Controllers;

use App\Category;
use App\Product;
use Illuminate\Http\Request;

class PcBuilderController extends Controller
{
    private function slots()
    {
        return ['processor'=>['label'=>'Processor','category'=>'processor','required'=>true,'icon'=>'microchip'],'motherboard'=>['label'=>'Motherboard','category'=>'motherboard','required'=>true,'icon'=>'server'],'ram'=>['label'=>'Memory (RAM)','category'=>'ram','required'=>true,'icon'=>'memory'],'graphics'=>['label'=>'Graphics Card','category'=>'graphics card','required'=>false,'icon'=>'television'],'storage'=>['label'=>'Primary Storage','category'=>'ssd','required'=>true,'icon'=>'hdd-o'],'hdd'=>['label'=>'Additional HDD','category'=>'hdd','required'=>false,'icon'=>'database'],'power'=>['label'=>'Power Supply','category'=>'power supply','required'=>true,'icon'=>'bolt'],'casing'=>['label'=>'Casing','category'=>'casing','required'=>true,'icon'=>'cube'],'cooler'=>['label'=>'CPU Cooler','category'=>'cpu cooler','required'=>false,'icon'=>'snowflake-o'],'monitor'=>['label'=>'Monitor','category'=>'monitor','required'=>false,'icon'=>'desktop']];
    }

    public function index(Request $request)
    {
        $slots=$this->slots(); $build=(array)$request->session()->get('pc_build',[]); $selected=Product::whereIn('id',array_values($build))->get()->keyBy('id');
        $categoryNames=array_unique(array_column($slots,'category'));
        $categories=Category::whereIn(\DB::raw('LOWER(category_name)'),$categoryNames)->get()->keyBy(function($category){return strtolower($category->category_name);});
        $products=[];
        foreach($slots as $key=>$slot){$category=$categories->get($slot['category']);$products[$key]=$category?Product::where('category_id',$category->category_id)->where('publication_status',1)->where('product_condition','In Stock')->orderByRaw(Product::sellingPriceSql())->get():collect();}
        $warnings=$this->compatibilityWarnings($selected,$build); $total=collect($build)->sum(function($id)use($selected){return $selected->has($id)?$selected[$id]->selling_price:0;});
        return view('front-end.pages.pc-builder',compact('slots','build','selected','products','warnings','total'));
    }

    public function select(Request $request)
    {
        $this->validate($request,['slot'=>'required','product_id'=>'required|integer']); $slots=$this->slots(); abort_unless(isset($slots[$request->slot]),422);
        $category=Category::whereRaw('LOWER(category_name) = ?',[strtolower($slots[$request->slot]['category'])])->firstOrFail();
        $product=Product::where('id',$request->product_id)->where('category_id',$category->category_id)->where('publication_status',1)->where('product_condition','In Stock')->firstOrFail();
        $build=(array)$request->session()->get('pc_build',[]);$build[$request->slot]=$product->id;$request->session()->put('pc_build',$build);
        return redirect()->route('pc-builder.index')->with('success',$product->product_name.' selected.');
    }

    public function remove(Request $request,$slot){$build=(array)$request->session()->get('pc_build',[]);unset($build[$slot]);$request->session()->put('pc_build',$build);return redirect()->route('pc-builder.index');}

    public function addToCart(Request $request)
    {
        $slots=$this->slots();$build=(array)$request->session()->get('pc_build',[]);
        foreach($slots as $key=>$slot) if($slot['required']&&empty($build[$key])) return redirect()->back()->with('error','Please select all required components first.');
        $valid=Product::whereIn('id',array_values($build))->where('publication_status',1)->where('product_condition','In Stock')->pluck('id')->all();
        if(count($valid)!==count($build)) return redirect()->back()->with('error','One or more selected components are no longer available.');
        $cart=(array)$request->session()->get('cart',[]);foreach($valid as $id)$cart[$id]=min(99,($cart[$id]??0)+1);$request->session()->put('cart',$cart);
        return redirect()->route('cart.index')->with('success','Your PC build was added to the cart.');
    }

    private function compatibilityWarnings($selected,$build)
    {
        if(empty($build['processor'])||empty($build['motherboard']))return [];
        $values=\DB::table('product_attribute_values as v')->join('catalog_attributes as a','a.id','=','v.attribute_id')->whereIn('v.product_id',[$build['processor'],$build['motherboard']])->where(function($q){$q->where('a.slug','like','%socket%')->orWhere('a.name','like','%socket%');})->select('v.product_id','v.value')->get()->keyBy('product_id');
        if($values->count()===2&&strtolower(trim($values[$build['processor']]->value))!==strtolower(trim($values[$build['motherboard']]->value)))return ['Processor and motherboard socket values do not match.'];
        return [];
    }
}
