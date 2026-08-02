@extends('layouts.admin')

@section('title')
    Transactions
@endsection

@section('content-header')
    <h1>Transactions<small>All wallet top-up transactions across every user.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Transactions</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-6 col-md-3">
        <div class="small-box bg-green" style="overflow: hidden;">
            <div class="inner" style="position: relative; z-index: 5;">
                <h3 style="font-size: 1.6rem;">KSh {{ number_format($stats['total_success'], 2) }}</h3>
                <p>Total Received</p>
            </div>
            <div class="icon" style="opacity: 0.35;"><i class="fa fa-money"></i></div>
        </div>
    </div>
    <div class="col-xs-6 col-md-3">
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3>{{ $stats['success_count'] }}</h3>
                <p>Successful</p>
            </div>
            <div class="icon"><i class="fa fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-xs-6 col-md-3">
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3>{{ $stats['pending_count'] }}</h3>
                <p>Pending</p>
            </div>
            <div class="icon"><i class="fa fa-clock-o"></i></div>
        </div>
    </div>
    <div class="col-xs-6 col-md-3">
        <div class="small-box bg-red">
            <div class="inner">
                <h3>{{ $stats['failed_count'] }}</h3>
                <p>Failed</p>
            </div>
            <div class="icon"><i class="fa fa-times-circle"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">All Transactions</h3>
                <div class="box-tools">
                    <form action="{{ route('admin.transactions') }}" method="GET" class="form-inline">
                        <div class="form-group" style="margin-right: 8px;">
                            <input type="text" name="search" class="form-control input-sm" placeholder="Search email, username, reference..." value="{{ $filters['search'] }}" style="width: 240px;">
                        </div>
                        <div class="form-group" style="margin-right: 8px;">
                            <select name="status" class="form-control input-sm">
                                <option value="">All Statuses</option>
                                <option value="success" @if($filters['status'] === 'success') selected @endif>Success</option>
                                <option value="pending" @if($filters['status'] === 'pending') selected @endif>Pending</option>
                                <option value="failed" @if($filters['status'] === 'failed') selected @endif>Failed</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-right: 8px;">
                            <select name="type" class="form-control input-sm">
                                <option value="">All Types</option>
                                <option value="deposit" @if($filters['type'] === 'deposit') selected @endif>Deposit</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                        @if($filters['search'] || $filters['status'] || $filters['type'])
                            <a href="{{ route('admin.transactions') }}" class="btn btn-sm btn-default">Reset</a>
                        @endif
                    </form>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td><code>{{ $transaction->id }}</code></td>
                                <td>
                                    @if($transaction->user)
                                        <a href="{{ route('admin.users.view', $transaction->user->id) }}">{{ $transaction->user->email }}</a>
                                    @else
                                        <span class="text-muted">Deleted user</span>
                                    @endif
                                </td>
                                <td class="capitalize">{{ $transaction->type }}</td>
                                <td>KSh {{ number_format($transaction->amount, 2) }}</td>
                                <td>
                                    @if($transaction->status === 'success')
                                        <span class="label label-success">Success</span>
                                    @elseif($transaction->status === 'pending')
                                        <span class="label label-warning">Pending</span>
                                    @else
                                        <span class="label label-danger">Failed</span>
                                    @endif
                                </td>
                                <td><small>{{ $transaction->reference }}</small></td>
                                <td>{{ $transaction->description ?? '—' }}</td>
                                <td><small>{{ $transaction->created_at->format('M j, Y g:i A') }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted" style="padding: 30px;">No transactions match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions->hasPages())
                <div class="box-footer with-border">
                    <div class="col-md-12 text-center">{!! $transactions->render() !!}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
