<?php

namespace App\Http\Controllers;

use App\Company;
use App\Manufacturer;
use App\ProductSeries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CatalogHierarchyController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100', 'company_id' => 'nullable|integer|exists:companies,id',
            'brand_id' => 'nullable|integer|exists:manufacturer,manufacturer_id', 'status' => 'nullable|in:active,inactive',
        ]);
        $search = trim((string) ($filters['q'] ?? ''));
        $companyId = $filters['company_id'] ?? null;
        $brandId = $filters['brand_id'] ?? null;
        $active = isset($filters['status']) ? $filters['status'] === 'active' : null;

        $companies = Company::with(['brands' => fn ($query) => $query->withCount(['series'])->orderBy('manufacturer_name')])
            ->when($search, fn ($query) => $query->where('name','like','%'.$search.'%'))
            ->when($companyId, fn ($query) => $query->where('id',$companyId))
            ->when($brandId, fn ($query) => $query->whereHas('brands',fn ($brand) => $brand->where('manufacturer_id',$brandId)))
            ->when($active !== null, fn ($query) => $query->where('is_active',$active))->orderBy('name')->get();
        $brands = Manufacturer::with('company')
            ->when($search, fn ($query) => $query->where('manufacturer_name','like','%'.$search.'%'))
            ->when($companyId, fn ($query) => $query->where('company_id',$companyId))
            ->when($brandId, fn ($query) => $query->where('manufacturer_id',$brandId))
            ->when($active !== null, fn ($query) => $query->where('publication_status',$active))->orderBy('manufacturer_name')->get();
        $series = ProductSeries::with('brand.company')
            ->when($search, fn ($query) => $query->where('name','like','%'.$search.'%'))
            ->when($companyId, fn ($query) => $query->whereHas('brand',fn ($brand) => $brand->where('company_id',$companyId)))
            ->when($brandId, fn ($query) => $query->where('manufacturer_id',$brandId))
            ->when($active !== null, fn ($query) => $query->where('is_active',$active))->orderBy('name')->get();
        $filterCompanies = Company::orderBy('name')->get();
        $filterBrands = Manufacturer::with('company')->orderBy('manufacturer_name')->get();

        return view('admin.admin-master')->with('admin_main_content', view('admin.admin-pages.catalog-hierarchy', compact('companies', 'brands', 'series', 'filterCompanies', 'filterBrands', 'filters')));
    }

    public function storeCompany(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:160|unique:companies,name']);
        Company::create(['name' => trim($data['name']), 'is_active' => $request->boolean('is_active', true)]);
        return back()->with('message', 'Company added successfully.');
    }

    public function updateCompany(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $data = $request->validate(['name' => ['required', 'string', 'max:160', Rule::unique('companies')->ignore($company->id)]]);
        $company->update(['name' => trim($data['name']), 'is_active' => $request->boolean('is_active')]);
        return back()->with('message', 'Company updated successfully.');
    }

    public function deleteCompany($id)
    {
        $company = Company::withCount('brands')->findOrFail($id);
        if ($company->brands_count) return back()->with('exception', 'Move or delete this company’s brands before deleting the company.');
        $company->delete();
        return back()->with('message', 'Company deleted successfully.');
    }

    public function storeBrand(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'manufacturer_name' => 'required|string|max:160|unique:manufacturer,manufacturer_name',
        ]);
        Manufacturer::create($data + ['publication_status' => $request->boolean('publication_status', true)]);
        return back()->with('message', 'Brand added successfully.');
    }

    public function updateBrand(Request $request, $id)
    {
        $brand = Manufacturer::findOrFail($id);
        $data = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'manufacturer_name' => ['required', 'string', 'max:160', Rule::unique('manufacturer')->ignore($brand->manufacturer_id, 'manufacturer_id')],
        ]);
        $brand->update($data + ['publication_status' => $request->boolean('publication_status')]);
        return back()->with('message', 'Brand updated successfully.');
    }

    public function deleteBrand($id)
    {
        $brand = Manufacturer::withCount('series')->findOrFail($id);
        $used = DB::table('product')->where('manufacturer_id', $id)->exists();
        if ($used || $brand->series_count) return back()->with('exception', 'This brand is used by products or series and cannot be deleted.');
        $brand->delete();
        return back()->with('message', 'Brand deleted successfully.');
    }

    public function storeSeries(Request $request)
    {
        $data = $request->validate([
            'manufacturer_id' => 'required|integer|exists:manufacturer,manufacturer_id',
            'name' => ['required', 'string', 'max:160', Rule::unique('product_series')->where(fn ($query) => $query->where('manufacturer_id', $request->manufacturer_id))],
        ]);
        ProductSeries::create($data + ['is_active' => $request->boolean('is_active', true)]);
        return back()->with('message', 'Product series added successfully.');
    }

    public function updateSeries(Request $request, $id)
    {
        $series = ProductSeries::findOrFail($id);
        $data = $request->validate([
            'manufacturer_id' => 'required|integer|exists:manufacturer,manufacturer_id',
            'name' => ['required', 'string', 'max:160', Rule::unique('product_series')->where(fn ($query) => $query->where('manufacturer_id', $request->manufacturer_id))->ignore($series->id)],
        ]);
        $series->update($data + ['is_active' => $request->boolean('is_active')]);
        return back()->with('message', 'Product series updated successfully.');
    }

    public function deleteSeries($id)
    {
        $series = ProductSeries::findOrFail($id);
        if (DB::table('product')->where('product_series_id', $id)->exists()) return back()->with('exception', 'This series is assigned to products and cannot be deleted.');
        $series->delete();
        return back()->with('message', 'Product series deleted successfully.');
    }

    public function bulkDeleteCompanies(Request $request)
    {
        $ids = $this->validatedIds($request, 'company_ids', 'companies,id');
        $blocked = DB::table('manufacturer')->whereIn('company_id',$ids)->pluck('company_id')->map(fn ($id)=>(int)$id)->unique()->all();
        return $this->bulkDelete('companies','id',$ids,$blocked,'companies','brands');
    }

    public function bulkDeleteBrands(Request $request)
    {
        $ids = $this->validatedIds($request, 'brand_ids', 'manufacturer,manufacturer_id');
        $blocked = DB::table('product')->whereIn('manufacturer_id',$ids)->pluck('manufacturer_id')
            ->merge(DB::table('product_series')->whereIn('manufacturer_id',$ids)->pluck('manufacturer_id'))->map(fn ($id)=>(int)$id)->unique()->all();
        return $this->bulkDelete('manufacturer','manufacturer_id',$ids,$blocked,'brands','products or series');
    }

    public function bulkDeleteSeries(Request $request)
    {
        $ids = $this->validatedIds($request, 'series_ids', 'product_series,id');
        $blocked = DB::table('product')->whereIn('product_series_id',$ids)->pluck('product_series_id')->map(fn ($id)=>(int)$id)->unique()->all();
        return $this->bulkDelete('product_series','id',$ids,$blocked,'series','products');
    }

    private function validatedIds(Request $request, string $field, string $exists): array
    {
        $request->validate([$field=>'required|array|min:1',$field.'.*'=>'required|integer|distinct|exists:'.$exists]);
        return array_values(array_unique(array_map('intval',$request->input($field))));
    }

    private function bulkDelete(string $table, string $key, array $ids, array $blocked, string $label, string $dependency)
    {
        $deletable = array_values(array_diff($ids,$blocked));
        $deleted = $deletable ? DB::transaction(fn () => DB::table($table)->whereIn($key,$deletable)->delete()) : 0;
        $skipped = count($blocked);
        $message = $deleted.' '.$label.' deleted.';
        if ($skipped) $message .= ' '.$skipped.' skipped because they are used by '.$dependency.'.';
        return back()->with($deleted ? 'message' : 'exception',$message);
    }
}
