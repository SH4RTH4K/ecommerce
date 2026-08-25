<?php
namespace App\Http\Controllers;

use App\Category;
use App\Product;
use App\Services\PcBuilderConfigurationService;
use Illuminate\Http\Request;

class PcBuilderController extends Controller
{
    private function slots()
    {
        return collect(app(PcBuilderConfigurationService::class)->slots())->keyBy('key')->all();
    }

    public function index(Request $request)
    {
        $slots=$this->slots(); $build=(array)$request->session()->get('pc_build',[]); $selected=Product::whereIn('id',array_values($build))->get()->keyBy('id');
        $categoryNames=array_unique(array_column($slots,'category'));
        $categoryIds=collect($slots)->pluck('category_id')->filter()->map(fn($id)=>(int)$id)->all();
        $categories=Category::where(function($query)use($categoryNames,$categoryIds){$query->whereIn(\DB::raw('LOWER(category_name)'),$categoryNames);if($categoryIds)$query->orWhereIn('category_id',$categoryIds);})->get()->keyBy('category_id');
        $products=[];
        foreach($slots as $key=>$slot){$category=$slot['category_id']?$categories->get((int)$slot['category_id']):$categories->first(fn($item)=>strtolower($item->category_name)===strtolower($slot['category']));$productQuery=$category?Product::where('category_id',$category->category_id)->where('publication_status',1)->where('product_condition','In Stock'):null;if($productQuery&&$slot['sub_category_id'])$productQuery->where('sub_category',(string)$slot['sub_category_id']);$products[$key]=$productQuery?$productQuery->orderByRaw(Product::sellingPriceSql())->get():collect();}
        $warnings=$this->compatibilityWarnings($selected,$build,$slots); $total=collect($build)->sum(function($id)use($selected){return $selected->has($id)?$selected[$id]->selling_price:0;});
        return view('front-end.pages.pc-builder',compact('slots','build','selected','products','warnings','total'));
    }

    public function choose(Request $request, $slot)
    {
        $slots=$this->slots(); abort_unless(isset($slots[$slot]),404);
        [$config,$category]=$this->resolveSlot($slots[$slot]);
        $query=Product::where('category_id',$category->category_id)->where('publication_status',1)->where('product_condition','In Stock'); if($config['sub_category_id'])$query->where('sub_category',(string)$config['sub_category_id']);
        if($request->filled('q')){$term=trim($request->q);$query->where(function($q)use($term){$q->where('product_name','like','%'.$term.'%')->orWhere('product_model','like','%'.$term.'%')->orWhere('sku','like','%'.$term.'%');});}
        if($request->filled('min_price'))$query->whereRaw(Product::sellingPriceSql().' >= ?',[(float)$request->min_price]);
        if($request->filled('max_price'))$query->whereRaw(Product::sellingPriceSql().' <= ?',[(float)$request->max_price]);
        foreach((array)$request->input('attributes',[]) as $attributeId=>$value){$values=array_values(array_filter((array)$value,fn($item)=>(string)$item!==''));if(!$values)continue;$query->whereHas('attributeValues',function($q)use($attributeId,$values){$q->where('attribute_id',(int)$attributeId)->where(function($v)use($values){foreach($values as $item)$v->orWhere('value',$item)->orWhere('value','like','%\"'.$item.'\"%');});});}
        $products=$query->with('attributeValues.attribute')->select('product.*')->selectRaw(Product::sellingPriceSql().' as selling_price')->latest('id')->paginate(18)->appends($request->query());
        $attributeFilters=\DB::table('catalog_attributes')->where('category_id',$category->category_id)->where('is_filterable',1)->orderBy('display_order')->get()->map(function($attribute)use($category){$attribute->values=\DB::table('product_attribute_values')->join('product','product_attribute_values.product_id','=','product.id')->where('product.category_id',$category->category_id)->where('product.publication_status',1)->where('product_attribute_values.attribute_id',$attribute->id)->pluck('product_attribute_values.value')->map(function($value){$decoded=json_decode($value,true);return is_array($decoded)?$decoded:[$value];})->flatten()->filter()->unique()->sort()->values();return $attribute;})->filter(fn($attribute)=>$attribute->values->isNotEmpty())->values();
        return view('front-end.pages.pc-builder-choose',compact('slot','config','category','products','attributeFilters'));
    }

    public function select(Request $request)
    {
        $this->validate($request,['slot'=>'required','product_id'=>'required|integer']); $slots=$this->slots(); abort_unless(isset($slots[$request->slot]),422);
        $slot=$slots[$request->slot];
        [$slot,$category]=$this->resolveSlot($slot);
        $product=Product::where('id',$request->product_id)->where('category_id',$category->category_id)->when($slot['sub_category_id'],fn($q)=>$q->where('sub_category',(string)$slot['sub_category_id']))->where('publication_status',1)->where('product_condition','In Stock')->firstOrFail();
        $build=(array)$request->session()->get('pc_build',[]);$build[$request->slot]=$product->id;
        $selected=Product::whereIn('id',array_values($build))->get()->keyBy('id');
        $warnings=$this->compatibilityWarnings($selected,$build,$slots);
        if($warnings)return redirect()->route('pc-builder.index')->with('error',$warnings[0]);
        $request->session()->put('pc_build',$build);
        return redirect()->route('pc-builder.index')->with('success',$product->product_name.' selected.');
    }

    public function remove(Request $request,$slot){$build=(array)$request->session()->get('pc_build',[]);unset($build[$slot]);$request->session()->put('pc_build',$build);return redirect()->route('pc-builder.index');}

    public function addToCart(Request $request)
    {
        $slots=$this->slots();$build=(array)$request->session()->get('pc_build',[]);
        foreach($slots as $key=>$slot) if($slot['required']&&empty($build[$key])) return redirect()->back()->with('error','Please select all required components first.');
        $valid=Product::whereIn('id',array_values($build))->where('publication_status',1)->where('product_condition','In Stock')->pluck('id')->all();
        if(count($valid)!==count($build)) return redirect()->back()->with('error','One or more selected components are no longer available.');
        $selected=Product::whereIn('id',$valid)->get()->keyBy('id');
        $warnings=$this->compatibilityWarnings($selected,$build,$slots);
        if($warnings)return redirect()->back()->with('error',$warnings[0]);
        $cart=(array)$request->session()->get('cart',[]);foreach($valid as $id)$cart[$id]=min(99,($cart[$id]??0)+1);$request->session()->put('cart',$cart);
        return redirect()->route('cart.index')->with('success','Your PC build was added to the cart.');
    }

    private function compatibilityWarnings($selected,$build,$slots)
    {
        $warnings=[]; $rules=app(PcBuilderConfigurationService::class)->rules();
        foreach($rules as $rule){if(empty($rule['enabled'])||empty($build[$rule['left_slot']])||empty($build[$rule['right_slot']]))continue;$left=$this->attributeValue($build[$rule['left_slot']],$rule['left_attribute']);$right=$this->attributeValue($build[$rule['right_slot']],$rule['right_attribute']);if($left!==null&&$right!==null&&$this->normalizeValue($left)!==$this->normalizeValue($right))$warnings[]=$rule['message']?:($rule['name'].' values do not match.');}
        return array_values(array_unique($warnings));
    }

    /**
     * PC parts are stored as subcategories under the Component category in
     * this catalog. Keep explicit admin mappings authoritative, but allow the
     * default slot name (for example, "processor") to resolve to a matching
     * subcategory when no mapping has been saved yet.
     */
    private function resolveSlot(array $slot): array
    {
        if (!empty($slot['category_id'])) {
            return [$slot, Category::findOrFail((int)$slot['category_id'])];
        }

        $category=Category::whereRaw('LOWER(category_name) = ?',[strtolower($slot['category'])])->first();
        if ($category) return [$slot, $category];

        $subcategory=\DB::table('sub_category')
            ->whereRaw('LOWER(sub_category_name) = ?',[strtolower($slot['category'])])
            ->first();
        abort_unless($subcategory,404);

        $slot['category_id']=(int)$subcategory->category_id;
        $slot['sub_category_id']=(int)$subcategory->sub_category_id;
        return [$slot, Category::findOrFail((int)$subcategory->category_id)];
    }

    private function attributeValue($productId,$attribute){return \DB::table('product_attribute_values as v')->join('catalog_attributes as a','a.id','=','v.attribute_id')->where('v.product_id',$productId)->where(function($q)use($attribute){$q->where('a.slug',str_slug($attribute))->orWhereRaw('LOWER(a.name)=?', [strtolower(trim($attribute))]);})->value('v.value');}
    private function normalizeValue($value){$value=trim(strtolower((string)$value));$decoded=json_decode($value,true);if(is_array($decoded))$value=implode('|',$decoded);return preg_replace('/[^a-z0-9]+/','', $value);}
}
