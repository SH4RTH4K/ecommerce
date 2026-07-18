<?php

namespace App\Http\Controllers;

use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminDataTransferController extends Controller
{
    private $resources = [
        'products' => [
            'label' => 'Products', 'table' => 'product',
            'headers' => ['product_id','sku','barcode','product_name','product_model','category_id','sub_category','manufacturer_id','product_series_id','regular_price','offer_price','purchase_price','product_condition','stock_quantity','stock_tracking','warranty','publication_status','top_product','is_new_arrival','short_description'],
            'sample' => ['PRD-1001','SKU-1001','','Example Router','XR1000','1','1','1','','2500','2250','1800','In Stock','10','1','1 year','1','0','1','Example catalog product'],
        ],
        'categories' => [
            'label' => 'Categories', 'table' => 'category',
            'headers' => ['category_id','category_name','category_description','icon_class','is_featured','display_order','publication_status'],
            'sample' => ['','Networking','Routers, switches and networking accessories','fa-signal','1','10','1'],
        ],
        'subcategories' => [
            'label' => 'Subcategories', 'table' => 'sub_category',
            'headers' => ['sub_category_id','category_id','sub_category_name','publication_status'],
            'sample' => ['','1','Wireless Router','1'],
        ],
        'manufacturers' => [
            'label' => 'Brands', 'table' => 'manufacturer',
            'headers' => ['manufacturer_id','company_id','manufacturer_name','publication_status'],
            'sample' => ['','1','Example Brand','1'],
        ],
        'companies' => [
            'label' => 'Companies', 'table' => 'companies',
            'headers' => ['id','name','is_active'],
            'sample' => ['','Example Technologies Ltd','1'],
        ],
        'series' => [
            'label' => 'Product Series', 'table' => 'product_series',
            'headers' => ['id','manufacturer_id','name','is_active'],
            'sample' => ['','1','Example Series','1'],
        ],
        'attributes' => [
            'label' => 'Catalog Attributes', 'table' => 'catalog_attributes',
            'headers' => ['id','category_id','name','slug','input_type','options','is_filterable','is_comparable','display_order'],
            'sample' => ['','1','Wi-Fi Standard','wifi-standard','select','Wi-Fi 5|Wi-Fi 6|Wi-Fi 7','1','1','10'],
        ],
        'suppliers' => [
            'label' => 'Suppliers', 'table' => 'suppliers',
            'headers' => ['id','name','contact_person','phone','email','address','tax_id','is_active'],
            'sample' => ['','Example Distribution Ltd','Sales Desk','01700000000','sales@example.com','Dhaka','TAX-1001','1'],
        ],
        'locations' => [
            'label' => 'Stock Locations', 'table' => 'inventory_locations',
            'headers' => ['id','name','code','type','address','country','division','city','postal_code','latitude','longitude','google_maps_url','contact_person','phone','email','operating_hours','pickup_available','delivery_hub','notes','is_default','is_active'],
            'sample' => ['','Uttara Warehouse','UTTARA','warehouse','Sector 7','Bangladesh','Dhaka','Dhaka','1230','','','','Warehouse Manager','01700000000','warehouse@example.com','Sat-Thu 9:00 AM-8:00 PM','1','1','','0','1'],
        ],
    ];

    public function template($resource)
    {
        $config=$this->resource($resource);
        return $this->csvResponse($resource.'-import-template.csv',function($out)use($config){fputcsv($out,$config['headers']);fputcsv($out,$config['sample']);});
    }

    public function export($resource)
    {
        $config=$this->resource($resource);
        $rows=DB::table($config['table'])->select($config['headers'])->orderBy($config['headers'][0])->get();
        return $this->csvResponse($resource.'-'.date('Y-m-d-His').'.csv',function($out)use($config,$rows){fputcsv($out,$config['headers']);foreach($rows as $row){$values=[];foreach($config['headers'] as $header){$value=$row->{$header};if($header==='options'&&$value){$decoded=json_decode($value,true);$value=is_array($decoded)?implode('|',$decoded):$value;}$values[]=$value;}fputcsv($out,$values);}});
    }

    public function import(Request $request,$resource)
    {
        $config=$this->resource($resource);
        $this->validate($request,['csv_file'=>'required|file|max:5120','mode'=>'required|in:upsert,create']);
        $file=$request->file('csv_file');
        if(strtolower($file->getClientOriginalExtension())!=='csv')return back()->with('exception','Please upload a .csv file.');

        $handle=fopen($file->getRealPath(),'r');
        $headers=fgetcsv($handle);
        if(!$headers){fclose($handle);return back()->with('exception','The CSV file is empty.');}
        $headers=array_map(function($header){return trim(preg_replace('/^\xEF\xBB\xBF/','',(string)$header));},$headers);
        $missing=array_values(array_diff($config['headers'],$headers));
        if($missing){fclose($handle);return back()->with('exception','Missing CSV columns: '.implode(', ',$missing));}

        $created=0;$updated=0;$skipped=0;$errors=[];$line=1;
        DB::beginTransaction();
        try{
            while(($values=fgetcsv($handle))!==false){
                $line++;
                if(!array_filter($values,function($value){return trim((string)$value)!=='';}))continue;
                $values=array_pad($values,count($headers),'');
                $row=[];foreach($headers as $index=>$header)$row[$header]=isset($values[$index])?trim($values[$index]):'';
                try{$result=$this->persist($resource,$row,$request->mode);if($result==='created')$created++;elseif($result==='updated')$updated++;else$skipped++;}
                catch(\Throwable $e){$errors[]='Row '.$line.': '.$e->getMessage();if(count($errors)>=50)break;}
            }
            fclose($handle);
            if($errors){DB::rollBack();return back()->withInput()->with('import_errors',$errors)->with('exception','Import cancelled. Fix the listed rows; no database changes were saved.');}
            DB::commit();
        }catch(\Throwable $e){if(is_resource($handle))fclose($handle);DB::rollBack();return back()->with('exception','Import failed: '.$e->getMessage());}

        Cache::forget('mega-menu-tree');Cache::forget('xml-sitemap');
        return back()->with('message',$config['label'].' import complete: '.$created.' created, '.$updated.' updated, '.$skipped.' skipped.');
    }

    private function persist($resource,array $row,$mode)
    {
        switch($resource){
            case 'categories': return $this->upsertCategory($row,$mode);
            case 'subcategories': return $this->upsertSubcategory($row,$mode);
            case 'manufacturers': return $this->upsertManufacturer($row,$mode);
            case 'companies': return $this->upsertCompany($row,$mode);
            case 'series': return $this->upsertSeries($row,$mode);
            case 'products': return $this->upsertProduct($row,$mode);
            case 'attributes': return $this->upsertAttribute($row,$mode);
            case 'suppliers': return $this->upsertSupplier($row,$mode);
            case 'locations': return $this->upsertLocation($row,$mode);
        }
        throw new \InvalidArgumentException('Unsupported import resource.');
    }

    private function upsertCategory($r,$mode)
    {
        $this->check($r,['category_name'=>'required|max:255','category_description'=>'nullable','icon_class'=>'nullable|max:50','is_featured'=>'required|boolean','display_order'=>'required|integer|min:0','publication_status'=>'required|boolean']);
        $existing=$r['category_id']!==''?DB::table('category')->where('category_id',(int)$r['category_id'])->first():DB::table('category')->where('category_name',$r['category_name'])->first();
        $data=['category_name'=>$r['category_name'],'category_description'=>$r['category_description'],'icon_class'=>$r['icon_class']?:'fa-folder-open','is_featured'=>(int)$r['is_featured'],'display_order'=>(int)$r['display_order'],'publication_status'=>(int)$r['publication_status'],'updated_at'=>now()];
        return $this->save('category','category_id',$existing,$data,$mode);
    }

    private function upsertSubcategory($r,$mode)
    {
        $this->check($r,['category_id'=>'required|integer|exists:category,category_id','sub_category_name'=>'required|max:255','publication_status'=>'required|boolean']);
        $query=DB::table('sub_category');$existing=$r['sub_category_id']!==''?(clone $query)->where('sub_category_id',(int)$r['sub_category_id'])->first():(clone $query)->where('category_id',(int)$r['category_id'])->where('sub_category_name',$r['sub_category_name'])->first();
        return $this->save('sub_category','sub_category_id',$existing,['category_id'=>(int)$r['category_id'],'sub_category_name'=>$r['sub_category_name'],'publication_status'=>(int)$r['publication_status'],'updated_at'=>now()],$mode);
    }

    private function upsertManufacturer($r,$mode)
    {
        $this->check($r,['company_id'=>'required|integer|exists:companies,id','manufacturer_name'=>'required|max:255','publication_status'=>'required|boolean']);
        $existing=$r['manufacturer_id']!==''?DB::table('manufacturer')->where('manufacturer_id',(int)$r['manufacturer_id'])->first():DB::table('manufacturer')->where('manufacturer_name',$r['manufacturer_name'])->first();
        return $this->save('manufacturer','manufacturer_id',$existing,['company_id'=>(int)$r['company_id'],'manufacturer_name'=>$r['manufacturer_name'],'publication_status'=>(int)$r['publication_status'],'updated_at'=>now()],$mode);
    }

    private function upsertCompany($r,$mode)
    {
        $this->check($r,['name'=>'required|max:160','is_active'=>'required|boolean']);
        $existing=$r['id']!==''?DB::table('companies')->where('id',(int)$r['id'])->first():DB::table('companies')->where('name',$r['name'])->first();
        return $this->save('companies','id',$existing,['name'=>$r['name'],'is_active'=>(int)$r['is_active'],'updated_at'=>now()],$mode);
    }

    private function upsertSeries($r,$mode)
    {
        $this->check($r,['manufacturer_id'=>'required|integer|exists:manufacturer,manufacturer_id','name'=>'required|max:160','is_active'=>'required|boolean']);
        $existing=$r['id']!==''?DB::table('product_series')->where('id',(int)$r['id'])->first():DB::table('product_series')->where('manufacturer_id',(int)$r['manufacturer_id'])->where('name',$r['name'])->first();
        return $this->save('product_series','id',$existing,['manufacturer_id'=>(int)$r['manufacturer_id'],'name'=>$r['name'],'is_active'=>(int)$r['is_active'],'updated_at'=>now()],$mode);
    }

    private function upsertProduct($r,$mode)
    {
        $this->check($r,['product_id'=>'required|max:255','sku'=>'nullable|max:255','barcode'=>'nullable|max:64','product_name'=>'required|max:255','product_model'=>'required|max:255','category_id'=>'required|integer|exists:category,category_id','sub_category'=>'required|integer|exists:sub_category,sub_category_id','manufacturer_id'=>'required|integer|exists:manufacturer,manufacturer_id','product_series_id'=>'nullable|integer|exists:product_series,id','regular_price'=>'required|numeric|min:0','offer_price'=>'nullable|numeric|min:0','purchase_price'=>'required|numeric|min:0','product_condition'=>'required|in:In Stock,Out Of Stock','stock_quantity'=>'required|integer|min:0','stock_tracking'=>'required|boolean','publication_status'=>'required|boolean','top_product'=>'required|boolean','is_new_arrival'=>'required|boolean']);
        if($r['product_series_id']!==''&&!DB::table('product_series')->where('id',(int)$r['product_series_id'])->where('manufacturer_id',(int)$r['manufacturer_id'])->exists())throw new \InvalidArgumentException('The selected product series does not belong to the selected brand.');
        $existing=$r['sku']!==''?DB::table('product')->where('sku',$r['sku'])->first():DB::table('product')->where('product_id',$r['product_id'])->first();
        $regular=(float)$r['regular_price'];$offer=$r['offer_price']!==''?(float)$r['offer_price']:null;if($offer!==null&&$offer>=$regular)$offer=null;
        $data=['product_id'=>$r['product_id'],'sku'=>$r['sku']?:null,'barcode'=>$r['barcode']?:null,'product_name'=>$r['product_name'],'product_model'=>$r['product_model'],'category_id'=>(int)$r['category_id'],'sub_category'=>(int)$r['sub_category'],'manufacturer_id'=>(int)$r['manufacturer_id'],'product_series_id'=>$r['product_series_id']!==''?(int)$r['product_series_id']:null,'regular_price'=>$regular,'offer_price'=>$offer,'purchase_price'=>(float)$r['purchase_price'],'product_condition'=>$r['product_condition'],'stock_quantity'=>(int)$r['stock_quantity'],'stock_tracking'=>(int)$r['stock_tracking'],'warranty'=>$r['warranty']?:null,'publication_status'=>(int)$r['publication_status'],'top_product'=>(int)$r['top_product'],'is_new_arrival'=>(int)$r['is_new_arrival'],'short_description'=>$r['short_description']?:null,'updated_at'=>now()];
        if(!$existing){$data+=['Product_description'=>'','product_image'=>'asset/front-end/img/home/pic 1.jpg','key_features'=>'[]','specifications'=>'{}','gallery_images'=>'[]'];}
        return $this->save('product','id',$existing,$data,$mode);
    }

    private function upsertAttribute($r,$mode)
    {
        $this->check($r,['category_id'=>'required|integer|exists:category,category_id','name'=>'required|max:255','slug'=>'required|max:255','input_type'=>'required|in:text,select,multiselect','is_filterable'=>'required|boolean','is_comparable'=>'required|boolean','display_order'=>'required|integer|min:0']);
        $existing=$r['id']!==''?DB::table('catalog_attributes')->where('id',(int)$r['id'])->first():DB::table('catalog_attributes')->where('category_id',(int)$r['category_id'])->where('slug',$r['slug'])->first();
        $options=array_values(array_filter(array_map('trim',explode('|',$r['options']))));$data=['category_id'=>(int)$r['category_id'],'name'=>$r['name'],'slug'=>str_slug($r['slug']),'input_type'=>$r['input_type'],'options'=>$options?json_encode($options):null,'is_filterable'=>(int)$r['is_filterable'],'is_comparable'=>(int)$r['is_comparable'],'display_order'=>(int)$r['display_order'],'updated_at'=>now()];
        return $this->save('catalog_attributes','id',$existing,$data,$mode);
    }

    private function upsertSupplier($r,$mode)
    {
        $this->check($r,['name'=>'required|max:255','email'=>'nullable|email|max:255','phone'=>'nullable|max:40','is_active'=>'required|boolean']);
        $existing=$r['id']!==''?DB::table('suppliers')->where('id',(int)$r['id'])->first():($r['tax_id']!==''?DB::table('suppliers')->where('tax_id',$r['tax_id'])->first():DB::table('suppliers')->where('name',$r['name'])->first());
        return $this->save('suppliers','id',$existing,['name'=>$r['name'],'contact_person'=>$r['contact_person']?:null,'phone'=>$r['phone']?:null,'email'=>$r['email']?:null,'address'=>$r['address']?:null,'tax_id'=>$r['tax_id']?:null,'is_active'=>(int)$r['is_active'],'updated_at'=>now()],$mode);
    }

    private function upsertLocation($r,$mode)
    {
        $this->check($r,['name'=>'required|max:255','code'=>'required|max:30','type'=>'required|in:warehouse,branch,store,distribution_center,office','email'=>'nullable|email|max:150','phone'=>'nullable|max:40','latitude'=>'nullable|numeric|between:-90,90','longitude'=>'nullable|numeric|between:-180,180','pickup_available'=>'required|boolean','delivery_hub'=>'required|boolean','is_default'=>'required|boolean','is_active'=>'required|boolean']);
        $existing=DB::table('inventory_locations')->where('code',strtoupper($r['code']))->first();
        if((int)$r['is_default']===1)DB::table('inventory_locations')->when($existing,function($q)use($existing){$q->where('id','<>',$existing->id);})->update(['is_default'=>0,'updated_at'=>now()]);
        return $this->save('inventory_locations','id',$existing,['name'=>$r['name'],'code'=>strtoupper($r['code']),'type'=>$r['type'],'address'=>$r['address']?:null,'country'=>$r['country']?:null,'division'=>$r['division']?:null,'city'=>$r['city']?:null,'postal_code'=>$r['postal_code']?:null,'latitude'=>$r['latitude']!==''?(float)$r['latitude']:null,'longitude'=>$r['longitude']!==''?(float)$r['longitude']:null,'google_maps_url'=>$r['google_maps_url']?:null,'contact_person'=>$r['contact_person']?:null,'phone'=>$r['phone']?:null,'email'=>$r['email']?:null,'operating_hours'=>$r['operating_hours']?:null,'pickup_available'=>(int)$r['pickup_available'],'delivery_hub'=>(int)$r['delivery_hub'],'notes'=>$r['notes']?:null,'is_default'=>(int)$r['is_default'],'is_active'=>(int)$r['is_active'],'updated_at'=>now()],$mode);
    }

    private function save($table,$key,$existing,array $data,$mode)
    {
        if($existing){if($mode==='create')return 'skipped';DB::table($table)->where($key,$existing->{$key})->update($data);return 'updated';}
        $data['created_at']=now();DB::table($table)->insert($data);return 'created';
    }

    private function check(array $row,array $rules)
    {
        $validator=Validator::make($row,$rules);if($validator->fails())throw new \InvalidArgumentException($validator->errors()->first());
    }

    private function resource($resource)
    {
        abort_unless(isset($this->resources[$resource]),404);return $this->resources[$resource];
    }

    private function csvResponse($filename,callable $writer)
    {
        return response()->streamDownload(function()use($writer){$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");$writer($out);fclose($out);},$filename,['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'no-store, no-cache']);
    }
}
