@extends('layouts.dash')
@section('title', 'Create Website')
@section('content')
    <!-- Main content -->
    <div class="h-screen flex-grow-1 overflow-y-lg-auto">
        <!-- Header -->
        <header class="bg-surface-primary border-bottom pt-6">
            <div class="container-fluid">
                <div class="mb-npx">
                    <div class="row align-items-center">
                        <div class="col-sm-6 col-12 mb-4 mb-sm-0">
                            <h1 class="h3 mb-0 ls-tight">Home </h1>
                        </div>
                        {{-- <div class="col-sm-6 col-6 text-sm-end">
                            <div class="mx-n1">
                                <a href="/websites/create" class="btn d-inline-flex btn-sm btn-primary mx-1">
                                    <span class=" pe-2">
                                        <i class="bi bi-plus"></i>
                                    </span>
                                    <span>Create</span>
                                </a>

                            </div>
                        </div> --}}
                    </div>
                    <ul class="nav nav-tabs mt-4 overflow-x border-0">
                    </ul>
                </div>
            </div>
        </header>
        <!-- Main -->
        <main class="py-6 bg-surface-secondary">
            <div class="container-fluid">
                <div class="row g-6 mb-6">
                    <div class="col-xl-12 col-sm-12 col-12">
                        <div class="card shadow border-0">
                            <div class="card-body">
                                <h5>Under Development</h5>
                            </div>
                            {{-- <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <span class="h5 font-semibold text-muted text-sm d-block mb-2">Kominfo Status</span>
                                        <span class="h4 text-sm font-bold mb-0">{{ $blockedCount }} Diblokir &
                                            {{ $errorCount }} Error</span>

                                    </div>
                                    <div class="col-auto">
                                        <div class="icon icon-shape bg-tertiary text-white text-lg rounded-circle">
                                            <i class="bi bi-ban"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 mb-0 text-sm">
                                    <span class="badge badge-pill bg-soft-success text-success me-2">
                                        <i class="bi bi-arrow-up me-1"></i>13%
                                    </span>
                                    <span class="text-nowrap text-xs text-muted">Since last month</span>
                                    <a href="/websites" class="btn d-inline-flex btn-sm btn-primary mx-1">
                                        <span>Check</span>
                                    </a>
                                </div>
                            </div> --}}
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
@endsection