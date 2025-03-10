@extends('layouts.dash')
@section('title', 'Lyrics List')
@section('head')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .card {
            border-radius: 15px;
            overflow: hidden;
        }

        .table th {
            font-weight: 600;
        }

        #projectTable {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 15px;
            font-size: 14px;
        }

        #projectTable thead th {
            background-color: #f8f9fa;
            border: none;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #6c757d;
        }

        #projectTable tbody tr {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        #projectTable tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        #projectTable tbody td {
            background-color: #ffffff;
            border: none;
            padding: 15px;
            vertical-align: middle;
        }

        #projectTable tbody td:first-child {
            border-top-left-radius: 5px;
            border-bottom-left-radius: 5px;
        }

        #projectTable tbody td:last-child {
            border-top-right-radius: 5px;
            border-bottom-right-radius: 5px;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 0.25rem 2rem 0.25rem 0.75rem;
            background-color: #fff;
            color: #4a5568;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .progress {
            height: 24px;
            background-color: #e9ecef;
        }

        .progress-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #4e73df !important;
            border-color: #4e73df !important;
            color: #fff !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #eceef1 !important;
            border-color: #eceef1 !important;
            color: #4e73df !important;
        }

        .page-item.active .page-link {
            background-color: #4e73df !important;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-pending {
            background-color: #faf089;
            color: #744210;
        }

        .status-scraping {
            background-color: #90cdf4;
            color: #2c5282;
        }

        .status-success {
            background-color: #9ae6b4;
            color: #22543d;
        }

        .status-fail {
            background-color: #feb2b2;
            color: #742a2a;
        }

        .status-waiting {
            background-color: #e2e8f0;
            color: #2d3748;
        }

        .status-completed {
            background-color: #c6f6d5;
            color: #22543d;
        }

        .status-generating {
            background-color: #fbd38d;
            color: #7b341e;
        }

        .status-default {
            background-color: #cbd5e0;
            color: #2d3748;
        }
    </style>
@endsection
@section('content')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3 bg-primary">
                        <h5 class="mb-0 text-white">Lyric Scraper</h5>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn d-inline-flex btn-sm btn-primary mx-1 mb-5" data-bs-toggle="modal"
                            data-bs-target="#createProjectModal">
                            <span class="pe-2"><i class="bi bi-plus"></i></span>
                            <span>Create Project</span>
                        </button>
                        <div class="table-responsive">
                            <table id="lyricTable" class="table table-hover table-striped w-100">
                                <thead>
                                    <tr>
                                        <th>Project Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Create Project Modal -->
    <div class="modal fade" id="createProjectModal" tabindex="-1" aria-labelledby="createProjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createProjectModalLabel">Add New Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createProjectForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="project_name" class="form-label">Project Name</label>
                            <input type="text" class="form-control" id="project_name" name="project_name" required
                                oninput="this.value = this.value.replace(/\s/g, '')">

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Project Modal -->
    <div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editProjectForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="project_name" class="form-label">Project Name</label>
                            <input type="text" class="form-control" id="project_name" name="project_name" required>
                            <input type="hidden" name="project_id"> <!-- Input tersembunyi untuk project_id -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('foot')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function () {
            $('#lyricTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route('lyrics-scraper.data') }}',
                    method: 'GET',
                    error: function (xhr) {
                        console.error(xhr.responseText);
                    }
                },
                columns: [
                    { data: 'project_name', name: 'project_name' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                responsive: true,
                language: {
                    emptyTable: "No projects found. Click the button above to create a new project.", // Pesan kustom ketika tidak ada data
                    search: "_INPUT_",
                    searchPlaceholder: "Search projects...",
                    lengthMenu: "Show _MENU_ entries",
                    paginate: {
                        previous: '<i class="bi bi-arrow-left"></i>',
                        next: '<i class="bi bi-arrow-right"></i>',
                    }
                },
                drawCallback: function () {
                    $('.dataTables_paginate > .pagination').addClass('pagination-sm justify-content-end');
                }
            });
            // Handle form submission for creating a new project
            $('#createProjectForm').on('submit', function (e) {
                e.preventDefault();

                let projectName = $('#project_name').val();

                if (/\s/.test(projectName)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Input',
                        text: 'Project name cannot contain spaces.',
                    });
                    return;
                }

                $.ajax({
                    url: '{{ route('lyrics-scraper.store') }}',
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                            }).then(() => {
                                $('#createProjectModal').modal('hide');
                                $('#lyricTable').DataTable().ajax.reload();
                            });
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON.message || 'Failed to create project',
                        });
                    }
                });
            });
        });
    </script>
@endsection