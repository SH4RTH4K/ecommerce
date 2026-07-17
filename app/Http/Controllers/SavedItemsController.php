<?php
namespace App\Http\Controllers;

use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavedItemsController extends Controller
{
 public function __construct(){ $this->middleware('auth'); }
 public function wishlist(){ $products=Product::join('wishlists','wishlists.product_id','=','product.id')->where('wishlists.user_id',auth()->id())->select('product.*','wishlists.created_at as saved_at')->latest('wishlists.created_at')->paginate(20);return view('account.wishlist',compact('products')); }
 public function addWishlist($id){$product=Product::where('publication_status',1)->findOrFail($id);DB::table('wishlists')->updateOrInsert(['user_id'=>auth()->id(),'product_id'=>$product->id],['updated_at'=>now(),'created_at'=>now()]);return redirect()->back()->with('success','Product saved to your wishlist.');}
 public function removeWishlist($id){DB::table('wishlists')->where('user_id',auth()->id())->where('product_id',$id)->delete();return redirect()->back()->with('success','Product removed from your wishlist.');}
 public function builds(){ $builds=DB::table('saved_pc_builds')->where('user_id',auth()->id())->latest('updated_at')->get();$productIds=[];foreach($builds as $build)$productIds=array_merge($productIds,array_values((array)json_decode($build->components,true)));$products=Product::whereIn('id',array_unique($productIds))->get()->keyBy('id');return view('account.saved-builds',compact('builds','products')); }
 public function saveBuild(Request $request){$this->validate($request,['name'=>'required|max:100']);$components=(array)$request->session()->get('pc_build',[]);if(!$components)return redirect()->back()->with('error','Select at least one component before saving.');$total=(float)Product::whereIn('id',array_values($components))->selectRaw('COALESCE(SUM('.Product::sellingPriceSql().'),0) total')->value('total');DB::table('saved_pc_builds')->insert(['user_id'=>auth()->id(),'name'=>$request->name,'components'=>json_encode($components),'estimated_total'=>$total,'created_at'=>now(),'updated_at'=>now()]);return redirect()->route('saved-builds.index')->with('success','PC build saved.');}
 public function restoreBuild(Request $request,$id){$build=DB::table('saved_pc_builds')->where('id',$id)->where('user_id',auth()->id())->first();abort_unless($build,404);$request->session()->put('pc_build',(array)json_decode($build->components,true));return redirect()->route('pc-builder.index')->with('success','Saved build restored.');}
 public function addBuildToCart(Request $request,$id){$build=DB::table('saved_pc_builds')->where('id',$id)->where('user_id',auth()->id())->first();abort_unless($build,404);$components=(array)json_decode($build->components,true);$valid=Product::whereIn('id',array_values($components))->where('publication_status',1)->where('product_condition','In Stock')->pluck('id')->all();if(count($valid)!==count($components))return redirect()->back()->with('error','Some saved components are no longer available. Restore the build to replace them.');$cart=(array)$request->session()->get('cart',[]);foreach($valid as $productId)$cart[$productId]=min(99,($cart[$productId]??0)+1);$request->session()->put('cart',$cart);return redirect()->route('cart.index')->with('success','Saved build added to cart.');}
 public function deleteBuild($id){DB::table('saved_pc_builds')->where('id',$id)->where('user_id',auth()->id())->delete();return redirect()->back()->with('success','Saved build deleted.');}
}
