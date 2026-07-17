<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function __construct(){ $this->middleware('auth'); }

    public function index(){ $returns=DB::table('order_returns')->where('user_id',auth()->id())->latest()->paginate(15);return view('account.returns',compact('returns')); }

    public function create($orderId)
    {
        $order=DB::table('orders')->where('id',$orderId)->where('user_id',auth()->id())->first();abort_unless($order,404);
        abort_unless($order->status==='delivered',422,'Only delivered orders are eligible for return.');
        $items=DB::table('order_items')->where('order_id',$orderId)->get();
        $returned=DB::table('order_return_items as i')->join('order_returns as r','r.id','=','i.order_return_id')->where('r.order_id',$orderId)->where('r.status','<>','rejected')->groupBy('i.order_item_id')->pluck(DB::raw('SUM(i.quantity)'),'i.order_item_id');
        return view('account.return-request',compact('order','items','returned'));
    }

    public function store(Request $request,$orderId)
    {
        $this->validate($request,['reason'=>'required|in:damaged,defective,wrong_item,not_as_described,changed_mind,other','details'=>'required|min:10|max:2000','quantity'=>'required|array']);
        $order=DB::table('orders')->where('id',$orderId)->where('user_id',auth()->id())->first();abort_unless($order,404);
        if($order->status!=='delivered') return redirect()->back()->with('error','Only delivered orders are eligible for return.');
        $selected=[];$amount=0;$returnId=null;
        try{DB::transaction(function()use($request,$order,&$selected,&$amount,&$returnId){
            $items=DB::table('order_items')->where('order_id',$order->id)->lockForUpdate()->get()->keyBy('id');
            foreach((array)$request->quantity as $itemId=>$raw){$quantity=max(0,(int)$raw);if(!$quantity)continue;$item=$items->get((int)$itemId);if(!$item)throw new \RuntimeException('Invalid order item selected.');$used=(int)DB::table('order_return_items as i')->join('order_returns as r','r.id','=','i.order_return_id')->where('r.order_id',$order->id)->where('i.order_item_id',$item->id)->where('r.status','<>','rejected')->sum('i.quantity');if($quantity>$item->quantity-$used)throw new \RuntimeException('Return quantity exceeds the available quantity for '.$item->product_name.'.');$line=round($item->offer_price*$quantity,2);$amount+=$line;$selected[]=['order_item_id'=>$item->id,'product_id'=>$item->product_id,'product_name'=>$item->product_name,'sku'=>$item->sku,'unit_price'=>$item->offer_price,'quantity'=>$quantity,'amount'=>$line,'restock'=>1,'created_at'=>now(),'updated_at'=>now()];}
            if(!$selected)throw new \RuntimeException('Select at least one item to return.');
            $returnId=DB::table('order_returns')->insertGetId(['return_number'=>'RET-'.date('ymd').'-'.strtoupper(str_random(6)),'order_id'=>$order->id,'user_id'=>auth()->id(),'customer_name'=>$order->customer_name,'email'=>$order->email,'phone'=>$order->phone,'reason'=>$request->reason,'details'=>$request->details,'status'=>'requested','requested_amount'=>$amount,'created_at'=>now(),'updated_at'=>now()]);
            foreach($selected as $line){$line['order_return_id']=$returnId;DB::table('order_return_items')->insert($line);}
        });}catch(\RuntimeException $e){return redirect()->back()->withInput()->with('error',$e->getMessage());}
        $created=DB::table('order_returns')->where('id',$returnId)->first();DB::table('store_notifications')->insert(['recipient_type'=>'admin','order_id'=>$order->id,'email'=>config('mail.username'),'title'=>'New return '.$created->return_number,'message'=>$order->customer_name.' requested a return worth ৳'.number_format($amount,2).'.','action_url'=>url('/returns/'.$returnId),'email_status'=>'not_configured','created_at'=>now(),'updated_at'=>now()]);
        return redirect()->route('account.returns')->with('success','Your return request has been submitted.');
    }

    public function show($id){$return=DB::table('order_returns')->where('id',$id)->where('user_id',auth()->id())->first();abort_unless($return,404);$order=DB::table('orders')->where('id',$return->order_id)->first();$items=DB::table('order_return_items')->where('order_return_id',$id)->get();$refund=DB::table('refunds')->where('order_return_id',$id)->first();$creditNote=DB::table('credit_notes')->where('order_return_id',$id)->first();return view('account.return-details',compact('return','order','items','refund','creditNote'));}
}
