@extends('layouts.admin')

@section('content')
    <div class="row pt-3" style="font-family: sans-serif !important;">

        <!-- Total Contacts -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12">
            <div class="card" style="background:#1A202C; color:white;">
                <div class="card-body">
                    <h5 class="text-muted" style="color:#C1EC4A !important; font-family: sans-serif !important;">Total Contacts</h5>
                    <h1 class="mb-1" style="color:#C1EC4A; font-family: sans-serif !important;">{{ $totalContacts }}</h1>
                </div>
            </div>
        </div>

        <!-- Unread Chats -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12">
            <div class="card" style="background:#1A202C; color:white;">
                <div class="card-body">
                    <h5 class="text-muted" style="color:#C1EC4A !important; font-family: sans-serif !important;">Unread Chats</h5>
                    <h1 class="mb-1" style="color:#C1EC4A; font-family: sans-serif !important;">{{ $unreadChats }}</h1>
                </div>
            </div>
        </div>

        <!-- Blocked Contacts -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12">
            <div class="card" style="background:#1A202C; color:white;">
                <div class="card-body">
                    <h5 class="text-muted" style="color:#C1EC4A !important; font-family: sans-serif !important;">Blocked Contacts</h5>
                    <h1 class="mb-1" style="color:#C1EC4A; font-family: sans-serif !important;">{{ $blockedContacts }}</h1>
                </div>
            </div>
        </div>

        <!-- Auto Reply Paused -->
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12">
            <div class="card" style="background:#1A202C; color:white;">
                <div class="card-body">
                    <h5 class="text-muted" style="color:#C1EC4A !important; font-family: sans-serif !important;">Paused Automation</h5>
                    <h1 class="mb-1" style="color:#C1EC4A; font-family: sans-serif !important;">{{ $pausedAutoReply }}</h1>
                </div>
            </div>
        </div>

    </div>

    <div class="row mt-5">
        <div class="col-md-6">
            <canvas id="barChart" style="max-height:300px;"></canvas>
        </div>
        <div class="col-md-6">
            <canvas id="donutChart" style="max-height:300px;"></canvas>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const donutCtx = document.getElementById('donutChart').getContext('2d');
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active Contacts', 'Blocked', 'Paused Automation'],
                datasets: [{
                    data: [
                        {{ $totalContacts - $blockedContacts - $pausedAutoReply }},
                        {{ $blockedContacts }},
                        {{ $pausedAutoReply }}
                    ],
                    backgroundColor: ['#1A202C', '#fd6061', '#C1EC4A'], // Only Paused is Green
                }]
            },
            options: {
                plugins: {
                    legend: {
                        labels: {
                            color: '#1A202C'
                        }
                    }
                }
            }
        });

        // Bar Chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Total Contacts', 'Unread', 'Blocked', 'Paused'],
                datasets: [{
                    data: [{{ $totalContacts }}, {{ $unreadChats }}, {{ $blockedContacts }},
                        {{ $pausedAutoReply }}
                    ],
                    backgroundColor: ['#1A202C', 'rgba(26,32,44,0.7)', '#fd6061', '#C1EC4A'],
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            color: '#1A202C',
                            beginAtZero: true
                        }
                    },
                    x: {
                        ticks: {
                            color: '#1A202C'
                        }
                    }
                }
            }
        });
    </script>
@endsection
