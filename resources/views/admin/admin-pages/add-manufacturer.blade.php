@extends('admin.admin-master')
@section('admin_main_content')

<div id="content" class="span10">
    <ul class="breadcrumb">
        <li>
            <i class="icon-home"></i>
            <a href="#">Home</a>
            <i class="icon-angle-right"></i> 
        </li>
        <li>
            <i class="icon-edit"></i>
            <a href="#">Add Manufacturer</a>
        </li>
    </ul>

    <div class="row-fluid sortable">
        <div class="box span12">
            <div class="box-header" data-original-title>
                <h2><i class="halflings-icon edit"></i><span class="break"></span>Add Manufacturer</h2>
                <div class="box-icon">
                    <a href="#" class="btn-setting"><i class="halflings-icon wrench"></i></a>
                    <a href="#" class="btn-minimize"><i class="halflings-icon chevron-up"></i></a>
                    <a href="#" class="btn-close"><i class="halflings-icon remove"></i></a>
                </div>
            </div>
            <div class="box-content">
                <h3 style="color: green">
                    <?php
                    $message = Session::get('message');
                    if ($message) {
                        echo $message;
                        Session::put('message', '');
                    }
                    ?>
                </h3>
                <form action="{{ url('/save-manufacturer') }}" method="POST">
                    @csrf
                <fieldset class="form-horizontal">
                    <div class="control-group">
                        <label class="control-label" for="typeahead">Company Name </label>
                        <div class="controls">
                            <input type="text" name="manufacturer_name" class="span6 typeahead" id="typeahead" data-provide="typeahead" data-items="4" data-source="[&quot;Alabama&quot;,&quot;Alaska&quot;,&quot;Arizona&quot;,&quot;Arkansas&quot;,&quot;California&quot;,&quot;Colorado&quot;,&quot;Connecticut&quot;,&quot;Delaware&quot;,&quot;Florida&quot;,&quot;Georgia&quot;,&quot;Hawaii&quot;,&quot;Idaho&quot;,&quot;Illinois&quot;,&quot;Indiana&quot;,&quot;Iowa&quot;,&quot;Kansas&quot;,&quot;Kentucky&quot;,&quot;Louisiana&quot;,&quot;Maine&quot;,&quot;Maryland&quot;,&quot;Massachusetts&quot;,&quot;Michigan&quot;,&quot;Minnesota&quot;,&quot;Mississippi&quot;,&quot;Missouri&quot;,&quot;Montana&quot;,&quot;Nebraska&quot;,&quot;Nevada&quot;,&quot;New Hampshire&quot;,&quot;New Jersey&quot;,&quot;New Mexico&quot;,&quot;New York&quot;,&quot;North Dakota&quot;,&quot;North Carolina&quot;,&quot;Ohio&quot;,&quot;Oklahoma&quot;,&quot;Oregon&quot;,&quot;Pennsylvania&quot;,&quot;Rhode Island&quot;,&quot;South Carolina&quot;,&quot;South Dakota&quot;,&quot;Tennessee&quot;,&quot;Texas&quot;,&quot;Utah&quot;,&quot;Vermont&quot;,&quot;Virginia&quot;,&quot;Washington&quot;,&quot;West Virginia&quot;,&quot;Wisconsin&quot;,&quot;Wyoming&quot;]">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="company_id">Parent Company</label>
                        <div class="controls">
                            <select id="company_id" name="company_id" class="span6">
                                <option value="">No company / legacy brand</option>
                                @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}{{ $company->company_code ? ' - '.$company->company_code : '' }}</option>
                                @endforeach
                            </select>
                            <p class="help-block">Optional. Pick a company to group this brand under the newer hierarchy.</p>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="brand_code">Brand Code</label>
                        <div class="controls"><input type="text" name="brand_code" id="brand_code" value="{{ old('brand_code') }}" class="span3" maxlength="30" placeholder="Example: SAM"><p class="help-block">Optional. Leave blank to auto-generate from the brand name.</p></div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="selectError3">Publication Status</label>
                        <div class="controls">
                            <select id="selectError3" name="publication_status">
                                <option value="0">Unpublished</option>
                                <option value="1">Published</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="reset" class="btn">Cancel</button>
                    </div>
                    
                </fieldset>
                </form>

            </div>
        </div><!--/span-->

    </div><!--/row-->
</div><!--/.fluid-container-->

<!-- end: Content -->
@endsection
