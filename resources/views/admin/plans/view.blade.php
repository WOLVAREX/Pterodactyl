@extends('layouts.admin')

@section('title')
    Plan — {{ $plan->name }}
@endsection

@section('content-header')
    <h1>{{ $plan->name }}<small>Edit plan details.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.plans') }}">Plans</a></li>
        <li class="active">{{ $plan->name }}</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.plans.view', $plan->id) }}" method="POST">
    {!! csrf_field() !!}
    {!! method_field('PATCH') !!}
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">Plan Details</h3></div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $plan->name }}" required />
                        </div>
                        <div class="form-group col-md-6">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="{{ $plan->price }}" required />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Currency</label>
                            <input type="text" name="currency" class="form-control" value="{{ $plan->currency }}" required />
                        </div>
                        <div class="form-group col-md-8">
                            <label>Billing Period</label>
                            <select name="billing_period" class="form-control">
                                @foreach(['monthly', 'quarterly', 'yearly'] as $period)
                                    <option value="{{ $period }}" @selected($plan->billing_period === $period)>{{ ucfirst($period) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Memory (MB)</label>
                            <input type="number" name="memory" class="form-control" value="{{ $plan->memory }}" required />
                        </div>
                        <div class="form-group col-md-4">
                            <label>Disk (MB)</label>
                            <input type="number" name="disk" class="form-control" value="{{ $plan->disk }}" required />
                        </div>
                        <div class="form-group col-md-4">
                            <label>CPU (%)</label>
                            <input type="number" name="cpu" class="form-control" value="{{ $plan->cpu }}" required />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Databases</label>
                            <input type="number" name="databases" class="form-control" value="{{ $plan->databases }}" required />
                        </div>
                        <div class="form-group col-md-4">
                            <label>Backups</label>
                            <input type="number" name="backups" class="form-control" value="{{ $plan->backups }}" required />
                        </div>
                        <div class="form-group col-md-4">
                            <label>Allocations</label>
                            <input type="number" name="allocations" class="form-control" value="{{ $plan->allocations }}" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Egg (server software to auto-install on purchase)</label>
                        <select name="egg_id" class="form-control">
                            <option value="">— Not purchasable yet (no egg linked) —</option>
                            @foreach($eggGroups as $nestName => $eggs)
                                <optgroup label="{{ $nestName }}">
                                    @foreach($eggs as $egg)
                                        <option value="{{ $egg->id }}" @selected($plan->egg_id === $egg->id)>{{ $egg->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="help-block">Buying this plan will auto-create a server using this egg's defaults.</p>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $plan->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Features (one per line)</label>
                        <textarea name="features" class="form-control" rows="4">{{ $plan->features }}</textarea>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ $plan->sort_order }}" />
                        </div>
                        <div class="col-md-4" style="padding-top: 25px;">
                            <label><input type="checkbox" name="is_active" value="1" @checked($plan->is_active)> Active</label>
                        </div>
                        <div class="col-md-4" style="padding-top: 25px;">
                            <label><input type="checkbox" name="is_featured" value="1" @checked($plan->is_featured)> Featured</label>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" name="action" value="update" class="btn btn-primary">Save</button>
                    <button type="submit" name="action" value="delete" class="btn btn-danger pull-right"
                        onclick="return confirm('Are you sure you want to delete this plan?');">Delete</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
