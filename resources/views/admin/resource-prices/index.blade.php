@extends('layouts.admin')

@section('title')
    Resource Prices
@endsection

@section('content-header')
    <h1>Resource Prices<small>À la carte pricing (e.g. RAM per GB, Disk per GB). Prices in KES.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Resource Prices</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Resource Price List</h3>
                <div class="box-tools">
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newResourceModal">Add Resource</button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>Name</th>
                            <th>Unit</th>
                            <th>Price (KES)</th>
                            <th class="text-center">Active</th>
                            <th></th>
                        </tr>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->unit_label }}</td>
                                <td>KSh {{ number_format($item->price_kes, 2) }}</td>
                                <td class="text-center">
                                    @if($item->is_active)
                                        <span class="label label-success">Active</span>
                                    @else
                                        <span class="label label-default">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <button class="btn btn-xs btn-default" data-toggle="modal" data-target="#editResource{{ $item->id }}">Edit</button>
                                </td>
                            </tr>

                            <div class="modal fade" id="editResource{{ $item->id }}" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.resource-prices.update', $item->id) }}" method="POST">
                                            {!! csrf_field() !!}
                                            {!! method_field('PATCH') !!}
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                <h4 class="modal-title">Edit {{ $item->name }}</h4>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Name</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $item->name }}" required />
                                                </div>
                                                <div class="form-group">
                                                    <label>Resource Type (for custom builder calculation)</label>
                                                    <select name="resource_key" class="form-control">
                                                        <option value="">— Not used in custom builder —</option>
                                                        <option value="ram" @selected($item->resource_key === 'ram')>RAM (priced per GB)</option>
                                                        <option value="disk" @selected($item->resource_key === 'disk')>Disk (priced per GB)</option>
                                                        <option value="cpu" @selected($item->resource_key === 'cpu')>CPU (priced per 100% / 1 core)</option>
                                                        <option value="database" @selected($item->resource_key === 'database')>Database (priced per database)</option>
                                                        <option value="backup" @selected($item->resource_key === 'backup')>Backup (priced per backup)</option>
                                                        <option value="allocation" @selected($item->resource_key === 'allocation')>Allocation (priced per port)</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Unit Label</label>
                                                    <input type="text" name="unit_label" class="form-control" value="{{ $item->unit_label }}" required />
                                                </div>
                                                <div class="form-group">
                                                    <label>Price (KES)</label>
                                                    <input type="number" step="0.01" name="price_kes" class="form-control" value="{{ $item->price_kes }}" required />
                                                </div>
                                                <div class="form-group">
                                                    <label>Sort Order</label>
                                                    <input type="number" name="sort_order" class="form-control" value="{{ $item->sort_order }}" />
                                                </div>
                                                <label><input type="checkbox" name="is_active" value="1" @checked($item->is_active)> Active</label>
                                            </div>
                                            <input type="hidden" name="action" class="delete-action-field" value="" />
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-danger pull-left" data-action="confirm-delete">Delete</button>
                                                <button type="submit" class="btn btn-primary">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newResourceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.resource-prices') }}" method="POST">
                {!! csrf_field() !!}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Add Resource Price</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" placeholder="RAM" required />
                    </div>
                    <div class="form-group">
                        <label>Resource Type (for custom builder calculation)</label>
                        <select name="resource_key" class="form-control">
                            <option value="">— Not used in custom builder —</option>
                            <option value="ram">RAM (priced per GB)</option>
                            <option value="disk">Disk (priced per GB)</option>
                            <option value="cpu">CPU (priced per 100% / 1 core)</option>
                            <option value="database">Database (priced per database)</option>
                            <option value="backup">Backup (priced per backup)</option>
                            <option value="allocation">Allocation (priced per port)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unit Label</label>
                        <input type="text" name="unit_label" class="form-control" placeholder="per GB" required />
                    </div>
                    <div class="form-group">
                        <label>Price (KES)</label>
                        <input type="number" step="0.01" name="price_kes" class="form-control" placeholder="50.00" required />
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="0" />
                    </div>
                    <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Resource</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('button[data-action="confirm-delete"]').click(function (event) {
            event.preventDefault();
            var form = $(this).closest('form');
            swal({
                title: '',
                text: 'Are you sure you want to delete this resource price?',
                type: 'warning',
                showCancelButton: true,
                allowOutsideClick: true,
                closeOnConfirm: false,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d9534f',
                showLoaderOnConfirm: true
            }, function () {
                form.find('.delete-action-field').val('delete');
                form.get(0).submit();
            });
        });
    </script>
@endsection