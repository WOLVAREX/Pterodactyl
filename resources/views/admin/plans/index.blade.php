@extends('layouts.admin')

@section('title')
    Plans
@endsection

@section('content-header')
    <h1>Plans<small>Manage the hosting plans shown on the public pricing page.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Plans</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Plan List</h3>
                <div class="box-tools">
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newPlanModal">Create New</button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Resources</th>
                            <th class="text-center">Featured</th>
                            <th class="text-center">Active</th>
                        </tr>
                        @foreach ($plans as $plan)
                            <tr>
                                <td><a href="{{ route('admin.plans.view', $plan->id) }}">{{ $plan->name }}</a></td>
                                <td>{{ $plan->currency }} {{ number_format($plan->price, 2) }} / {{ $plan->billing_period }}</td>
                                <td>{{ $plan->memory }} MB RAM &middot; {{ $plan->disk }} MB Disk &middot; {{ $plan->cpu }}% CPU</td>
                                <td class="text-center">
                                    @if($plan->is_featured)<i class="fa fa-star text-yellow-500"></i>@endif
                                </td>
                                <td class="text-center">
                                    @if($plan->is_active)
                                        <span class="label label-success">Active</span>
                                    @else
                                        <span class="label label-default">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newPlanModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.plans') }}" method="POST">
                {!! csrf_field() !!}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Create Plan</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required />
                        </div>
                        <div class="form-group col-md-6">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="0.00" required />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Currency</label>
                            <input type="text" name="currency" class="form-control" value="USD" required />
                        </div>
                        <div class="form-group col-md-8">
                            <label>Billing Period</label>
                            <select name="billing_period" class="form-control">
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Memory (MB)</label>
                            <input type="number" name="memory" class="form-control" value="1024" required />
                        </div>
                        <div class="form-group col-md-4">
                            <label>Disk (MB)</label>
                            <input type="number" name="disk" class="form-control" value="5120" required />
                        </div>
                        <div class="form-group col-md-4">
                            <label>CPU (%)</label>
                            <input type="number" name="cpu" class="form-control" value="100" required />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Databases</label>
                            <input type="number" name="databases" class="form-control" value="1" required />
                        </div>
                        <div class="form-group col-md-4">
                            <label>Backups</label>
                            <input type="number" name="backups" class="form-control" value="1" required />
                        </div>
                        <div class="form-group col-md-4">
                            <label>Allocations</label>
                            <input type="number" name="allocations" class="form-control" value="1" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Egg (server software to auto-install on purchase)</label>
                        <select name="egg_id" class="form-control">
                            <option value="">— Not purchasable yet (no egg linked) —</option>
                            @foreach($eggGroups as $nestName => $eggs)
                                <optgroup label="{{ $nestName }}">
                                    @foreach($eggs as $egg)
                                        <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="help-block">Buying this plan will auto-create a server using this egg's defaults.</p>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Features (one per line)</label>
                        <textarea name="features" class="form-control" rows="4" placeholder="DDoS Protection&#10;Instant Setup&#10;24/7 Support"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label><input type="checkbox" name="is_active" value="1" checked> Active (visible on landing page)</label>
                        </div>
                        <div class="col-md-6">
                            <label><input type="checkbox" name="is_featured" value="1"> Featured (highlighted plan)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
