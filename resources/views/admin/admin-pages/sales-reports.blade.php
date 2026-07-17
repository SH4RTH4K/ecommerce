@extends('admin.admin-master')
@section('admin_main_content')
<div id="content" class="span10">
    <ul class="breadcrumb"><li><i class="icon-home"></i> Admin <i class="icon-angle-right"></i></li><li>Sales &amp; Profit Reports</li></ul>
    <div class="box"><div class="box-content">
        <form class="form-inline" method="get">
            <label>From <input type="date" name="from" value="{{ $from }}"></label>
            <label>To <input type="date" name="to" value="{{ $to }}"></label>
            <button class="btn btn-primary">Apply</button>
            <a class="btn btn-success" href="{{ url('/sales-reports/export?from='.$from.'&to='.$to) }}"><i class="icon-download-alt"></i> Export CSV</a>
        </form>
    </div></div>

    <div class="alert alert-info">
        <strong>Refund accounting:</strong> Completed refunds are deducted when the refund is processed. Purchase price is recovered only for items restored to inventory.
    </div>

    <div class="row-fluid">
        <div class="span3 statbox blue"><div class="number">৳{{ number_format($netAfterRefunds, 2) }}</div><div class="title">Sales after refunds</div></div>
        <div class="span3 statbox green"><div class="number">৳{{ number_format($profitAfterRefunds, 2) }}</div><div class="title">Profit after refunds</div></div>
        <div class="span3 statbox orange"><div class="number">{{ number_format($summary->orders_count) }}</div><div class="title">Non-cancelled orders</div></div>
        <div class="span3 statbox purple"><div class="number">৳{{ number_format($summary->average_order, 2) }}</div><div class="title">Average order</div></div>
    </div>

    <div class="row-fluid">
        <div class="box span7"><div class="box-header"><h2>Daily sales before refunds</h2></div><div class="box-content">
            <table class="table table-striped"><thead><tr><th>Date</th><th>Orders</th><th>Sales</th></tr></thead><tbody>
            @forelse($daily as $day)<tr><td>{{ date('M j, Y',strtotime($day->sale_date)) }}</td><td>{{ $day->orders_count }}</td><td>৳{{ number_format($day->sales, 2) }}</td></tr>
            @empty<tr><td colspan="3">No sales in this period.</td></tr>@endforelse
            </tbody></table>
        </div></div>
        <div class="box span5"><div class="box-header"><h2>Financial summary</h2></div><div class="box-content">
            <table class="table">
                <tr><th>Product sales before refunds</th><td>৳{{ number_format($summary->net_sales, 2) }}</td></tr>
                <tr><th>Purchase cost before returns</th><td>৳{{ number_format($costs->purchase_cost, 2) }}</td></tr>
                <tr><th>Discounts</th><td>৳{{ number_format($discounts, 2) }}</td></tr>
                <tr><th>Profit before refunds</th><td>৳{{ number_format($profitBeforeRefunds, 2) }}</td></tr>
                <tr><th>Completed refunds</th><td>-৳{{ number_format($refundTotal, 2) }}</td></tr>
                <tr><th>Recovered purchase price</th><td>+৳{{ number_format($recoveredPurchaseCost, 2) }}</td></tr>
                <tr><th>Purchase cost after returns</th><td>৳{{ number_format($purchaseCostAfterReturns, 2) }}</td></tr>
                <tr><th>Profit after refunds</th><td><strong>৳{{ number_format($profitAfterRefunds, 2) }}</strong></td></tr>
                <tr><th>Delivery collected</th><td>৳{{ number_format($summary->delivery_income, 2) }}</td></tr>
            </table>
            <p class="help-block">Profit = offer-price sales - discounts - purchase price - refunds + purchase price of returned items restored to inventory. Delivery operating costs, tax, payment fees, and overhead are excluded.</p>
        </div></div>
    </div>

    <div class="box"><div class="box-header"><h2>Top products before refunds</h2></div><div class="box-content">
        <table class="table table-striped table-bordered"><thead><tr><th>Product</th><th>Units</th><th>Offer-price sales</th><th>Profit before discounts/refunds</th></tr></thead><tbody>
        @forelse($topProducts as $product)<tr><td>{{ $product->product_name }}</td><td>{{ $product->units }}</td><td>৳{{ number_format($product->sales, 2) }}</td><td>৳{{ number_format($product->profit, 2) }}</td></tr>
        @empty<tr><td colspan="4">No product sales found.</td></tr>@endforelse
        </tbody></table>
    </div></div>
</div>
@endsection
