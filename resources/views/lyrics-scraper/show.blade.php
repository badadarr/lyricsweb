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
                        <!-- Form untuk Bulk Scraping -->
                        <div class="mb-4">
                            <h6>Lyrics Scraper</h6>
                            <p>Masukkan setiap pasangan Title dan Artist dalam satu baris, dipisahkan dengan
                                koma.<br>Contoh: <code>Judul Lagu, Nama Artis</code></p>
                            <textarea id="bulkInput" class="form-control" placeholder="Judul Lagu, Nama Artis"
                                rows="4"></textarea><br>
                            <button id="processBtn" class="btn btn-primary">Proses</button>
                            <a id="exportBtn"
                                href="{{ route('lyrics.export', ['project_name' => $project->project_name]) }}"
                                class="btn btn-success">
                                Export CSV
                            </a>
                        </div>

                        <!-- Accordion untuk Menampilkan Lyrics -->
                        <div class="accordion" id="lyricsAccordion">
                            @foreach ($lyrics as $index => $lyric)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading{{ $index }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse{{ $index }}" aria-expanded="false"
                                            aria-controls="collapse{{ $index }}">
                                            <strong>{{ $lyric->title }}</strong> - {{ $lyric->artist }}
                                            @if($lyric->language)
                                                <span class="badge bg-info ms-2">{{ $lyric->language }}</span>
                                            @endif
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $index }}" class="accordion-collapse collapse"
                                        aria-labelledby="heading{{ $index }}" data-bs-parent="#lyricsAccordion">
                                        <div class="accordion-body">
                                            <div class="mb-2">
                                                <strong>Language:</strong>
                                                <span class="badge bg-info">{{ $lyric->language ?? 'Unknown' }}</span>
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
            const processBtn = $('#processBtn');
            const exportBtn = $('#exportBtn');
            const bulkInput = $('#bulkInput');
            const lyricsAccordion = $('#lyricsAccordion');
            let results = [];

            // Console log to verify elements are found
            console.log('Process button:', processBtn.length);
            console.log('Bulk input:', bulkInput.length);
            console.log('Lyrics accordion:', lyricsAccordion.length);

            // Add progress bar
            let progressBar = $('<div class="progress mb-3" style="display: none;"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div></div>');
            processBtn.after(progressBar);

            // Function to add items to the accordion with status-based styling
            function addItemToAccordion(title, artist, lyric, index, status = 'success', language = null) {
                // Status-based styling for different outcomes
                let headerClass = 'accordion-button collapsed';
                let statusBadge = '';

                if (status === 'not-found' || status === 'not_found') {
                    statusBadge = '<span class="badge bg-warning ms-2">Not Found</span>';
                } else if (status === 'api-error' || status === 'api_error') {
                    statusBadge = '<span class="badge bg-danger ms-2">API Error</span>';
                } else if (status === 'general' || status === 'error') {
                    statusBadge = '<span class="badge bg-danger ms-2">Error</span>';
                } else if (status === 'connection') {
                    statusBadge = '<span class="badge bg-danger ms-2">Connection Error</span>';
                } else if (status === 'database') {
                    statusBadge = '<span class="badge bg-danger ms-2">Database Error</span>';
                } else if (language) {
                    statusBadge = `<span class="badge bg-info ms-2">${language}</span>`;
                }

                // Create the accordion item with appropriate styling
                const item = `
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading${index}">
                                            <button class="${headerClass}" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapse${index}" aria-expanded="false" 
                                                aria-controls="collapse${index}">
                                                <strong>${title}</strong> - ${artist}
                                                ${statusBadge}
                                            </button>
                                        </h2>
                                        <div id="collapse${index}" class="accordion-collapse collapse" 
                                            aria-labelledby="heading${index}" data-bs-parent="#lyricsAccordion">
                                            <div class="accordion-body">
                                                ${language && status === 'success' ? `<div class="mb-2"><strong>Language:</strong> <span class="badge bg-info">${language}</span></div>` : ''}
                                                <pre style="white-space: pre-wrap;">${lyric}</pre>
                                            </div>
                                        </div>
                                    </div>
                                `;
                lyricsAccordion.append(item);
            }

            // Process button click handler
            processBtn.click(async function (e) {
                e.preventDefault();
                console.log('Process button clicked');

                // Parse input lines
                const lines = bulkInput.val().split('\n').filter(line => line.trim());
                console.log('Input lines:', lines);

                if (!lines.length) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Input Kosong',
                        text: 'Silakan masukkan data lagu yang akan di-scrape.'
                    });
                    return;
                }

                // Clear previous results
                results = [];
                lyricsAccordion.empty();

                // Setup progress tracking
                const totalLines = lines.length;
                let completedLines = 0;
                progressBar.show();
                progressBar.find('.progress-bar').css('width', '0%');

                // Disable process button and hide export button
                processBtn.text('Processing...').prop('disabled', true);
                exportBtn.hide();

                try {
                    for (let i = 0; i < lines.length; i++) {
                        const parts = lines[i].split(',');
                        if (parts.length < 2) {
                            console.log('Invalid line format:', lines[i]);
                            results.push({
                                title: lines[i],
                                artist: 'Invalid Format',
                                lyric: 'Error: Format input harus "Judul Lagu, Nama Artis"',
                                status: 'error'
                            });

                            addItemToAccordion(
                                lines[i],
                                'Invalid Format',
                                'Error: Format input harus "Judul Lagu, Nama Artis"',
                                results.length - 1,
                                'error'
                            );

                            // Update progress
                            completedLines++;
                            const progressPercent = Math.round((completedLines / totalLines) * 100);
                            progressBar.find('.progress-bar').css('width', `${progressPercent}%`);

                            continue;
                        }

                        const title = parts[0].trim();
                        const artist = parts[1].trim();
                        console.log('Processing:', title, artist);

                        try {
                            const response = await fetch(`/lyrics/scraper/process?title=${encodeURIComponent(title)}&artist=${encodeURIComponent(artist)}&project_name=${encodeURIComponent(projectName)}`, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                }
                            });

                            const data = await response.json();
                            console.log('Response:', data);

                            if (data.success) {
                                // Success case
                                results.push({
                                    ...data.data,
                                    status: 'success'
                                });

                                addItemToAccordion(
                                    data.data.title,
                                    data.data.artist,
                                    data.data.lyric,
                                    results.length - 1,
                                    'success',
                                    data.data.language
                                );
                            } else {
                                // Error case
                                const errorMessage = data.message || 'Unknown error';
                                const errorDetails = data.details ? `\n\nDetails: ${JSON.stringify(data.details)}` : '';
                                const errorType = data.error_type || 'general';

                                results.push({
                                    title,
                                    artist,
                                    lyric: `Error: ${errorMessage}${errorDetails}`,
                                    status: errorType
                                });

                                addItemToAccordion(
                                    title,
                                    artist,
                                    `Error: ${errorMessage}${errorDetails}`,
                                    results.length - 1,
                                    errorType
                                );
                            }
                        } catch (err) {
                            console.error('Error processing line:', err);

                            results.push({
                                title,
                                artist,
                                lyric: `Error: ${err.message}`,
                                status: 'connection'
                            });

                            addItemToAccordion(
                                title,
                                artist,
                                `Error: ${err.message}`,
                                results.length - 1,
                                'connection'
                            );
                        } finally {
                            // Update progress
                            completedLines++;
                            const progressPercent = Math.round((completedLines / totalLines) * 100);
                            progressBar.find('.progress-bar').css('width', `${progressPercent}%`);
                        }
                    }
                } catch (err) {
                    console.error('Main process error:', err);

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat memproses data. Silakan coba lagi.'
                    });
                } finally {
                    // Reset button state
                    processBtn.text('Proses').prop('disabled', false);

                    // Hide progress bar with a slight delay for better UX
                    setTimeout(() => {
                        progressBar.hide();
                    }, 500);

                    // Show export button if there are results
                    if (results.length > 0) {
                        exportBtn.show();
                    }

                    // Show summary if processing is complete
                    const errorResults = results.filter(r => r.status !== 'success');
                    const errorCount = errorResults.length;
                    const successCount = results.length - errorCount;

                    if (results.length > 0) {
                        // Count errors by type
                        const errorByTypes = {
                            'not_found': results.filter(r => r.status === 'not_found' || r.status === 'not-found').length,
                            'api_error': results.filter(r => r.status === 'api_error' || r.status === 'api-error').length,
                            'connection': results.filter(r => r.status === 'connection').length,
                            'database': results.filter(r => r.status === 'database').length,
                            'general': results.filter(r => r.status === 'general' || r.status === 'error').length
                        };

                        // Create summary message
                        let summaryHtml = `
                                <div class="text-start">
                                    <h5>Summary:</h5>
                                    <ul>
                                        <li class="text-success">Successfully scraped: ${successCount} songs</li>
                            `;

                        // Only show error types that exist
                        if (errorByTypes.not_found > 0) {
                            summaryHtml += `<li class="text-warning">Not found: ${errorByTypes.not_found} songs</li>`;
                        }
                        if (errorByTypes.api_error > 0) {
                            summaryHtml += `<li class="text-danger">API errors: ${errorByTypes.api_error} songs</li>`;
                        }
                        if (errorByTypes.connection > 0) {
                            summaryHtml += `<li class="text-danger">Connection errors: ${errorByTypes.connection} songs</li>`;
                        }
                        if (errorByTypes.database > 0) {
                            summaryHtml += `<li class="text-danger">Database errors: ${errorByTypes.database} songs</li>`;
                        }
                        if (errorByTypes.general > 0) {
                            summaryHtml += `<li class="text-danger">Other errors: ${errorByTypes.general} songs</li>`;
                        }

                        summaryHtml += `
                                    </ul>
                                    ${errorCount > 0 ? '<p>Please check the details for each song with errors below.</p>' : ''}
                                </div>
                            `;

                        // Display summary
                        Swal.fire({
                            icon: errorCount > 0 ? 'info' : 'success',
                            title: 'Proses Selesai',
                            html: summaryHtml
                        });
                    }
                }
            });

            // Export to CSV functionality
            exportBtn.click(function (e) {
                // This is handled by the link's href attribute which points to the export route
                console.log('Export button clicked - using route handler');
            });
        });
    </script>
@endsection