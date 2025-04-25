<div class="modal fade" id="inputModal" tabindex="-1" aria-labelledby="inputModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inputModalLabel">Add Lyrics Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addLyricsForm" action="{{ route('lyrics-scraper.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="project_name" value="{{ $project->project_name }}">
                    <div class="mb-3">
                        <label for="tagInput" class="form-label">Tag</label>
                        <input type="text" class="form-control" id="tagInput" name="tag" placeholder="Tag">
                    </div>
                    <div class="mb-3">
                        <label for="picInput" class="form-label">PIC</label>
                        <input type="text" class="form-control" id="picInput" name="pic"
                            value="{{ auth()->user()->name }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="doneCheckInput" class="form-label">Done Check</label>
                        <select class="form-select" id="doneCheckInput" name="done_check">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="priorityInput" class="form-label">Priority</label>
                        <select class="form-select" id="priorityInput" name="priority">
                            <option value="1">Normal</option>
                            <option value="2">High</option>
                            <option value="3">Urgent</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <p>Masukkan setiap pasangan Title dan Artist dalam satu baris, dipisahkan dengan
                            koma.<br>Contoh: <code>Judul Lagu, Nama Artis</code></p>
                        <textarea id="bulkInputModal" name="bulk_input" class="form-control"
                            placeholder="Judul Lagu, Nama Artis" rows="4"></textarea><br>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="processModalBtn">Proses</button>
            </div>
        </div>
    </div>
</div>