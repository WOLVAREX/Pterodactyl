@extends('layouts.admin')

@section('title')
    Administration
@endsection

@section('content-header')
    <h1>Administrative Overview<small>A quick glance at your system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Index</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-6 col-md-3">
        <div class="small-box bg-aqua" style="min-height: 90px;">
            <div class="inner">
                <h3>{{ number_format($totalServers) }}</h3>
                <p>Total Servers</p>
            </div>
        </div>
    </div>
    <div class="col-xs-6 col-md-3">
        <div class="small-box bg-green" style="min-height: 90px;">
            <div class="inner">
                <h3>{{ number_format($totalUsers) }}</h3>
                <p>Total Users</p>
            </div>
        </div>
    </div>
    <div class="col-xs-6 col-md-3">
        <div class="small-box bg-purple" style="min-height: 90px;">
            <div class="inner">
                <h3>{{ number_format($totalNodes) }}</h3>
                <p>Total Nodes</p>
            </div>
        </div>
    </div>
    <div class="col-xs-6 col-md-3">
        <div class="small-box bg-yellow" style="min-height: 90px;">
            <div class="inner">
                <h3>KSh {{ number_format($totalRevenue, 2) }}</h3>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">New Users (Last 14 Days)</h3>
            </div>
            <div class="box-body">
                <div style="position: relative; height: 260px;">
                    <canvas id="signups-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Servers by Node</h3>
            </div>
            <div class="box-body">
                @if($nodeDistribution->isEmpty())
                    <p class="text-muted text-center" style="padding: 40px 0;">No nodes configured yet.</p>
                @else
                    <div style="position: relative; height: 260px;">
                        <canvas id="node-distribution-chart"></canvas>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="box
            @if($version->isLatestPanel())
                box-success
            @else
                box-danger
            @endif
        ">
            <div class="box-header with-border">
                <h3 class="box-title">System Information</h3>
            </div>
            <div class="box-body">
                @if ($version->isLatestPanel())
                    You are running Pterodactyl Panel version <code>{{ config('app.version') }}</code>. Your panel is up-to-date!
                @else
                    Your panel is <strong>not up-to-date!</strong> The latest version is <a href="https://github.com/Pterodactyl/Panel/releases/v{{ $version->getPanel() }}" target="_blank"><code>{{ $version->getPanel() }}</code></a> and you are currently running version <code>{{ config('app.version') }}</code>. You can find instructions on how to update your panel <a href="https://pterodactyl.io/panel/1.0/updating.html">here</a>.
                @endif
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-6 col-sm-3 text-center">
        <a href="{{ $version->getDiscord() }}"><button class="btn btn-warning" style="width:100%;"><i class="fa fa-fw fa-support"></i> Get Help <small>(via Discord)</small></button></a>
    </div>
    <div class="col-xs-6 col-sm-3 text-center">
        <a href="https://pterodactyl.io"><button class="btn btn-primary" style="width:100%;"><i class="fa fa-fw fa-link"></i> Documentation</button></a>
    </div>
    <div class="clearfix visible-xs-block">&nbsp;</div>
    <div class="col-xs-6 col-sm-3 text-center">
        <a href="https://github.com/pterodactyl/panel"><button class="btn btn-primary" style="width:100%;"><i class="fa fa-fw fa-support"></i> GitHub</button></a>
    </div>
    <div class="col-xs-6 col-sm-3 text-center">
        <a href="{{ $version->getDonations() }}"><button class="btn btn-success" style="width:100%;"><i class="fa fa-fw fa-money"></i> Support the Project</button></a>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script src="/js/vendor/chart.umd.min.js"></script>
    <script>
        (function () {
            var signupsCanvas = document.getElementById('signups-chart');
            if (signupsCanvas) {
                new Chart(signupsCanvas, {
                    type: 'bar',
                    data: {
                        labels: @json($signupLabels),
                        datasets: [{
                            label: 'New Users',
                            data: @json($signupData),
                            backgroundColor: 'rgba(60, 141, 188, 0.8)',
                            borderRadius: 3,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                        },
                    },
                });
            }

            var nodeCanvas = document.getElementById('node-distribution-chart');
            if (nodeCanvas) {
                var nodeData = @json($nodeDistribution);
                new Chart(nodeCanvas, {
                    type: 'pie',
                    data: {
                        labels: nodeData.map(function (n) { return n.name; }),
                        datasets: [{
                            data: nodeData.map(function (n) { return n.count; }),
                            backgroundColor: [
                                '#00c0ef', '#00a65a', '#f39c12', '#dd4b39',
                                '#605ca8', '#0073b7', '#39cccc', '#3c8dbc',
                            ],
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                    },
                });
            }
        })();
    </script>
@endsection
