@extends('layouts.dash')
@section('title', 'Lyrics Detail')
@section('content')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3 bg-primary d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">Lyrics for Project: {{ $project->project_name }}</h5>
                        <a href="{{ route('lyrics-scraper.index') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to Project Lyric
                        </a>
                    </div>
                    <div class="card-body">
                        {{-- Tombol untuk export ke excel --}}
                        <a id="exportBtn" class="btn btn-primary btn-sm mb-4"
                            href="{{ route('lyrics.export', ['project_name' => $project->project_name]) }}"
                            class="btn btn-success">
                            Export CSV
                        </a>
                        {{-- Tombol Add Lyrics --}}
                        <button type="button" class="btn btn-success btn-sm mb-4" data-bs-toggle="modal"
                            data-bs-target="#inputModal">
                            <i class="bi bi-plus-circle"></i> Add Lyrics
                        </button>
                        <!-- Progress Bar Container -->
                        <div id="progressContainer" style="display: none; margin: 15px 0;">
                            <div class="progress">
                                <div id="mainProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="progressText" class="text-muted">Memproses data...</small>
                        </div>
                        <!-- Accordion untuk Menampilkan Lyrics -->
                        <div class="accordion" id="lyricsAccordion">
                            @foreach ($lyrics as $index => $lyric)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading{{ $index }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse{{ $index }}" aria-expanded="false"
                                            aria-controls="collapse{{ $index }}">
                                            <strong>{{ $lyric->title }}</strong> - {{ $lyric->artist }} (
                                            @if($lyric->language && $lyric->source)
                                                <span class="badge bg-secondary ms-2">{{ $lyric->source }}</span>
                                                <span class="badge bg-info ms-2">{{ $lyric->language }}</span>
                                            @endif
                                            <span
                                                class="badge bg-warning ms-2">{{ $lyric->explicit ? 'Explicit' : 'Clean' }}</span>
                                            )
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $index }}" class="accordion-collapse collapse"
                                        aria-labelledby="heading{{ $index }}" data-bs-parent="#lyricsAccordion">
                                        <div class="accordion-body">
                                            <div class="mb-2">
                                                {{-- <strong>Explicit:</strong>
                                                <span class="badge bg-warning">{{ $lyric->explicit ? 'Yes' : 'No' }}</span> --}}
                                                <strong>PIC:</strong>
                                                <span class="badge bg-warning">{{ $lyric->pic ?? 'Unknown'}}</span>
                                                <strong>Done Check:</strong>
                                                <span class="badge bg-warning">{{ $lyric->done_publish ? 'Yes' : 'No' }}</span>
                                                <strong>Tag:</strong>
                                                <span class="badge bg-warning">{{ $lyric->tag ?? 'Unknown' }}</span>
                                                <button class="btn btn-danger btn-sm float-end delete-btn"
                                                    data-id="{{ $lyric->id }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <pre style="white-space: pre-wrap;">{{ $lyric->lyric }}</pre>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('lyrics-scraper.components.modal-add-lyrics')
@endsection

@section('foot')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Tambahkan route untuk JavaScript
        const projectName = "{{ $project->project_name }}";
    </script>
    <script src="{{ asset('vendor/route.js') }}"></script>
    <script>
        $(document).ready(function () {

            // Identify key elements
            const exportBtn = $('#exportBtn');
            const deleteBtn = $('.delete-btn');
            const processModalBtn = $('#processModalBtn');
            const addLyricsForm = $('#addLyricsForm');
            const progressContainer = $('#progressContainer');
            const progressBar = $('#mainProgressBar');
            const progressText = $('#progressText');
            const lyricsAccordion = $('#lyricsAccordion');
            let results = [];

            processModalBtn.after(progressBar);

            // Handle tombol proses di modal
            $('#processModalBtn').click(function (e) {
                e.preventDefault();

                const bulkInput = $('#bulkInputModal').val().trim();
                if (!bulkInput) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Input Kosong',
                        text: 'Silakan masukkan data lagu yang akan di-scrape.'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Konfirmasi Input',
                    text: 'Apakah Anda yakin input sudah benar? Format yang benar adalah "Judul Lagu, Nama Artis".',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Proses',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    // Sembunyikan modal dan tampilkan progress bar
                    $('#inputModal').modal('hide');
                    showProgressBar();

                    // Kirim data ke server
                    $.ajax({
                        url: $('#addLyricsForm').attr('action'),
                        method: 'POST',
                        data: $('#addLyricsForm').serialize(),
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        xhr: function () {
                            const xhr = new window.XMLHttpRequest();
                            xhr.addEventListener('progress', function (e) {
                                if (e.lengthComputable) {
                                    const percent = Math.round((e.loaded / e.total) * 90);
                                    updateProgressBar(percent, 'Mengunggah data...');
                                }
                            });
                            return xhr;
                        },
                        beforeSend: function () {
                            updateProgressBar(10, 'Memulai proses...');
                        },
                        success: function (response) {
                            updateProgressBar(100, 'Proses selesai!');

                            // Tambahkan hasil ke accordion
                            if (response.data && response.data.length > 0) {
                                const existingCount = $('.accordion-item').length;
                                response.data.forEach((lyric, index) => {
                                    addItemToAccordion(
                                        lyric.title,
                                        lyric.artist,
                                        lyric.lyric,
                                        existingCount + index,
                                        'success',
                                        lyric.language,
                                        lyric.source,
                                        lyric.explicit,
                                        lyric.tag,
                                        lyric.priority,
                                        lyric.done_publish,
                                        lyric.pic
                                    );
                                });
                            }

                            // Tampilkan notifikasi
                            showResultNotification(response);
                        },
                        error: function (xhr) {
                            updateProgressBar(0, 'Error terjadi!');
                            showErrorNotification(xhr);
                        },
                        complete: function () {
                            setTimeout(hideProgressBar, 1500);
                        }
                    });
                });
            });
            // Fungsi-fungsi pendukung
            function showProgressBar() {
                progressContainer.slideDown();
                progressBar.css('width', '0%');
                progressText.text('Memproses data...');
            }

            function updateProgressBar(percent, text) {
                progressBar.css('width', percent + '%');
                if (text) progressText.text(text);
            }

            function hideProgressBar() {
                progressContainer.slideUp();
                progressBar.css('width', '0%');
            }

            function showResultNotification(response) {
                let successMsg = `Berhasil memproses ${response.success_count} lagu`;
                if (response.error_count > 0) {
                    successMsg += `, dengan ${response.error_count} error`;
                }

                if (response.errors?.length > 0) {
                    let errorDetails = response.errors.join('<br>');
                    if (errorDetails.length > 500) errorDetails = errorDetails.substring(0, 500) + '...';

                    Swal.fire({
                        icon: response.success_count > 0 ? 'info' : 'error',
                        title: response.success_count > 0 ? 'Proses Selesai' : 'Proses Gagal',
                        html: `<div>
                            <p>${successMsg}</p>
                            ${response.error_count > 0 ?
                                `<details><summary>Detail Error (${response.error_count})</summary>
                                <div style="max-height: 200px; overflow-y: auto; margin-top: 10px;">
                                    ${errorDetails}
                                </div></details>` : ''}
                        </div>`,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Proses Selesai',
                        text: successMsg
                    });
                }
            }

            function showErrorNotification(xhr) {
                let errorMsg = 'Terjadi kesalahan saat memproses data. Silakan coba lagi.';
                if (xhr.responseJSON?.message) {
                    errorMsg = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
            }
            // Fungsi untuk menambahkan item ke accordion (diperbarui)
            function addItemToAccordion(title, artist, lyric, index, status = 'success',
                language = null, source = null, explicit = false,
                tag = null, priority = null, done_publish = false, pic = null) {
                let statusBadge = '';

                if (status === 'success') {
                    statusBadge = '<span class="badge bg-success ms-2">Success</span>';
                } else if (status === 'error') {
                    statusBadge = '<span class="badge bg-danger ms-2">Error</span>';
                }

                // Format priority badge
                let priorityBadge = '';
                if (priority) {
                    const priorityText =
                        priority == 1 ? 'Normal' :
                            priority == 2 ? 'High' :
                                priority == 3 ? 'Urgent' : '';
                    const priorityClass =
                        priority == 1 ? 'bg-secondary' :
                            priority == 2 ? 'bg-warning' :
                                priority == 3 ? 'bg-danger' : '';
                    priorityBadge = `<span class="badge ${priorityClass} ms-2">${priorityText}</span>`;
                }

                const item = `
                                                    <div class="accordion-item" id="accordion-item-${index}">
                                                        <h2 class="accordion-header" id="heading${index}">
                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                                data-bs-target="#collapse${index}" aria-expanded="false" 
                                                                aria-controls="collapse${index}">
                                                                <strong>${title}</strong> - ${artist} ${statusBadge}
                                                                ${priorityBadge}
                                                            </button>
                                                        </h2>
                                                        <div id="collapse${index}" class="accordion-collapse collapse" 
                                                            aria-labelledby="heading${index}" data-bs-parent="#lyricsAccordion">
                                                            <div class="accordion-body">
                                                                <div class="mb-2">
                                                                    <strong>Language:</strong>
                                                                    <span class="badge bg-info">${language || 'Unknown'}</span>
                                                                    <strong>Source:</strong>
                                                                    <span class="badge bg-secondary">${source || 'Unknown'}</span>
                                                                    <strong>Explicit:</strong>
                                                                    <span class="badge bg-warning">${explicit ? 'Yes' : 'No'}</span>
                                                                    <strong>Tag:</strong>
                                                                    <span class="badge bg-primary">${tag || 'Unknown'}</span>
                                                                    <strong>PIC:</strong>
                                                                    <span class="badge bg-info">${pic || 'Unknown'}</span>
                                                                    <strong>Done Check:</strong>
                                                                    <span class="badge ${done_publish ? 'bg-success' : 'bg-secondary'}">${done_publish ? 'Yes' : 'No'}</span>
                                                                    <button class="btn btn-danger btn-sm float-end delete-btn" data-id="${index}">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </div>
                                                                <pre style="white-space: pre-wrap;">${lyric}</pre>
                                                            </div>
                                                        </div>
                                                    </div>
                                                `;
                lyricsAccordion.append(item);
            }

            // Delete button functionality
            deleteBtn.click(async function (e) {
                const lyricId = $(this).data('id'); // Ambil ID lirik dari atribut data-id
                const accordionItem = $(this).closest('.accordion-item'); // Ambil elemen accordion item

                // Tampilkan konfirmasi sebelum menghapus
                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    text: "Apakah Anda yakin ingin menghapus lirik ini?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Kirim permintaan DELETE ke server
                        $.ajax({
                            url: `/lyrics/scraper/delete/${lyricId}`, // Endpoint untuk delete
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Tambahkan CSRF token
                            },
                            success: function (response) {
                                if (response.success) {
                                    // Hapus item dari accordion
                                    accordionItem.remove();

                                    // Tampilkan pesan sukses
                                    Swal.fire('Terhapus!', response.message, 'success');
                                } else {
                                    // Tampilkan pesan error jika gagal
                                    Swal.fire('Gagal!', response.message, 'error');
                                }
                            },
                            error: function (xhr) {
                                // Tampilkan pesan error jika terjadi kesalahan
                                Swal.fire('Error!', 'Terjadi kesalahan saat menghapus lirik.', 'error');
                            }
                        });
                    }
                });
            });

            // Function to determine if the input is likely in "Artist, Title" format
            function isLikelyArtistFirst(title, artist) {
                // Check if the first part is a single word or a common artist name pattern
                const artistPattern = /^[A-Z][a-z]+(?: [A-Z][a-z]+)*$/;
                const titlePattern = /^[A-Z][a-z]+(?: [A-Za-z]+)*$/;

                // Check if the first part is shorter than the second part (common in artist, title format)
                const isArtistShorter = title.length < artist.length;

                // Check if the first part contains common punctuation marks that are less likely in artist names
                const hasPunctuation = /[.,!?;:]/.test(title);

                // Combine all checks
                return artistPattern.test(title) && titlePattern.test(artist) && isArtistShorter && !hasPunctuation;
            }

            // Function to capitalize the first letter of each word
            function capitalizeWords(str) {
                return str.replace(/\b\w/g, char => char.toUpperCase());
            }

            // Export to CSV functionality
            exportBtn.click(function (e) {
                // This is handled by the link's href attribute which points to the export route
                console.log('Export button clicked - using route handler');
            });
        });
    </script>
@endsection