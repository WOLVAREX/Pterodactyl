@extends('layouts.admin')

@section('title')
    Currencies
@endsection

@section('content-header')
    <h1>Currencies<small>Manage exchange rates. Base currency is KES.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Currencies</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Currency List</h3>
                <div class="box-tools">
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newCurrencyModal">Add Currency</button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>Code</th>
                            <th>Symbol</th>
                            <th>Rate (1 unit = X KES)</th>
                            <th class="text-center">Active</th>
                            <th></th>
                        </tr>
                        @foreach ($currencies as $currency)
                            <tr>
                                <td><b>{{ $currency->code }}</b></td>
                                <td>{{ $currency->symbol }}</td>
                                <td>{{ $currency->rate_to_kes }}</td>
                                <td class="text-center">
                                    @if($currency->is_active)
                                        <span class="label label-success">Active</span>
                                    @else
                                        <span class="label label-default">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <button class="btn btn-xs btn-default" data-toggle="modal" data-target="#editCurrency{{ $currency->id }}">Edit</button>
                                </td>
                            </tr>

                            <div class="modal fade" id="editCurrency{{ $currency->id }}" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.currencies.update', $currency->id) }}" method="POST">
                                            {!! csrf_field() !!}
                                            {!! method_field('PATCH') !!}
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                <h4 class="modal-title">Edit {{ $currency->code }}</h4>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Symbol</label>
                                                    <input type="text" name="symbol" class="form-control" value="{{ $currency->symbol }}" required />
                                                </div>
                                                <div class="form-group">
                                                    <label>Rate to KES</label>
                                                    <input type="number" step="0.000001" name="rate_to_kes" class="form-control"
                                                        value="{{ $currency->rate_to_kes }}" {{ $currency->code === 'KES' ? 'readonly' : '' }} required />
                                                    <p class="help-block">How many KES equal 1 {{ $currency->code }}. e.g. USD = 130 means 1 USD = 130 KES.</p>
                                                </div>
                                                <label><input type="checkbox" name="is_active" value="1" @checked($currency->is_active)> Active</label>
                                            </div>
                                            <input type="hidden" name="action" class="delete-action-field" value="" />
                                            <div class="modal-footer">
                                                @if($currency->code !== 'KES')
                                                    <button type="button" class="btn btn-danger pull-left" data-action="confirm-delete">Delete</button>
                                                @endif
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

<div class="modal fade" id="newCurrencyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.currencies') }}" method="POST">
                {!! csrf_field() !!}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Add Currency</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Code (e.g. USD, NGN, GHS, ZAR)</label>
                        <input type="text" name="code" class="form-control" style="text-transform: uppercase;" required />
                    </div>
                    <div class="form-group">
                        <label>Symbol</label>
                        <input type="text" name="symbol" class="form-control" placeholder="$" required />
                    </div>
                    <div class="form-group">
                        <label>Rate to KES</label>
                        <input type="number" step="0.000001" name="rate_to_kes" class="form-control" placeholder="130" required />
                        <p class="help-block">How many KES equal 1 unit of this currency.</p>
                    </div>
                    <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Currency</button>
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
                text: 'Are you sure you want to delete this currency?',
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