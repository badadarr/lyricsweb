<?php

namespace App\Http\Controllers;

use App\Models\Lyric;
use App\Models\ProjectLyric;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\DataTables;

class LyricsScraperController extends Controller
{

    public function index()
    {
        return view('lyrics-scraper.index');
    }

    public function data()
    {
        try {
            $projects = ProjectLyric::select(['project_name'])
                ->where('user_id', auth()->id())
                ->whereNull('deleted_at')
                ->get();

            if ($projects->isEmpty()) {
                return response()->json([
                    'data' => [],
                    'message' => 'No data found'
                ], 200);
            }

            return DataTables::of($projects)
                ->addIndexColumn()
                ->addColumn('actions', function ($project) {
                    return '<div class="dropdown">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="actionDropdown' . $project->project_name . '" data-bs-toggle="dropdown" aria-expanded="false">
                        Actions
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="actionDropdown' . $project->project_name . '">
                        <li><a class="dropdown-item" href="' . route('lyrics-scraper.detail', ['projectName' => $project->project_name]) . '">View Details</a></li>
                        <li><button class="dropdown-item edit-project" data-id="' . $project->project_name . '">Edit</button></li>
                        <li><button class="dropdown-item text-danger delete-project" data-id="' . $project->project_name . '">Delete</button></li>
                    </ul>
                </div>';
                })
                ->rawColumns(['actions'])
                ->make(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving projects data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function edit($projectName)
    {
        $project = ProjectLyric::where('project_name', $projectName)
            ->where('user_id', auth()->id()) // Pastikan project dimiliki oleh user yang login
            ->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found or you do not have permission to edit this project'
            ], 404);
        }

        // Lanjutkan dengan logika edit
    }

    public function delete($projectName)
    {
        $project = ProjectLyric::where('project_name', $projectName)
            ->where('user_id', auth()->id()) // Pastikan project dimiliki oleh user yang login
            ->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found or you do not have permission to delete this project'
            ], 404);
        }

        // Lanjutkan dengan logika delete
    }

    public function store(Request $request)
    {
        try {
            // Debug data request
            Log::info('Data received:', $request->all());

            // Validasi input
            $request->validate([
                'project_name' => 'required|string|max:255|unique:project_lyrics,project_name',
            ]);

            // dd($request->all());

            // Simpan data project baru
            $project = new ProjectLyric();
            $project->project_name = $request->project_name;
            $project->user_id = auth()->id(); // Pastikan user_id diisi jika diperlukan
            $project->save();

            // Debug data yang disimpan
            Log::info('Project saved:', $project->toArray());

            // Redirect atau response JSON
            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'data' => $project
            ], 201);

        } catch (\Exception $e) {
            // Log error
            Log::error('Error creating project:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function details($project_name)
    {
        try {
            $project = ProjectLyric::where('project_name', $project_name)->firstOrFail();
            $lyrics = Lyric::where('project_name', $project_name)->get();

            return view('lyrics-scraper.show', compact('project', 'lyrics'));
        } catch (\Exception $e) {
            Log::error('Error in details view:', ['error' => $e->getMessage()]);
            return redirect()->route('lyrics-scraper.index')
                ->with('error', 'Project tidak ditemukan');
        }
    }

    public function storeScrapeLyric(Request $request)
    {
        $request->validate([
            'tag' => 'nullable|string|max:255',
            'pic' => 'nullable|string|max:255',
            'done_check' => 'required|boolean',
            'priority' => 'required|integer|in:1,2,3',
            'bulk_input' => 'required|string',
            'project_name' => 'required|string|max:255',
        ]);

        $bulkInput = explode("\n", $request->bulk_input);
        $lyrics = [];
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($bulkInput as $line) {
            $line = trim($line);
            if (empty($line))
                continue;

            $parts = explode(',', $line);
            if (count($parts) < 2) {
                $errors[] = "Invalid format for line: '$line'. Format should be 'Title, Artist'";
                $errorCount++;
                continue;
            }

            $title = trim($parts[0]);
            $artist = trim($parts[1]);

            try {
                // Check if lyric already exists
                $existingLyric = Lyric::where('title', $title)
                    ->where('artist', $artist)
                    ->where('project_name', $request->project_name)
                    ->first();

                if ($existingLyric) {
                    $lyrics[] = $existingLyric;
                    $successCount++;
                    continue;
                }

                // Scrape lyrics from API
                $scrapedData = $this->scrapeLyrics($title, $artist);

                if (!$scrapedData['success']) {
                    $errors[] = "Failed to scrape lyrics for '$title - $artist': " . $scrapedData['message'];
                    $errorCount++;
                    continue;
                }

                // Save to database
                $lyric = Lyric::create([
                    'title' => $title,
                    'artist' => $artist,
                    'lyric' => $scrapedData['data']['lyric'],
                    'language' => $scrapedData['data']['language'] ?? null,
                    'explicit' => $scrapedData['data']['explicit'] ?? false,
                    'source' => $scrapedData['data']['source'] ?? null,
                    'project_name' => $request->project_name,
                    'tag' => $request->tag,
                    'priority' => $request->priority,
                    'done_publish' => $request->done_check,
                    'pic' => $request->pic,
                ]);

                $lyrics[] = $lyric;
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Error processing '$title - $artist': " . $e->getMessage();
                $errorCount++;
                Log::error("Error processing lyric", [
                    'title' => $title,
                    'artist' => $artist,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'success' => $successCount > 0,
            'message' => $successCount > 0 ?
                "Successfully processed $successCount songs" . ($errorCount > 0 ? ", with $errorCount errors" : "") :
                "Failed to process any songs",
            'data' => $lyrics,
            'errors' => $errors,
            'success_count' => $successCount,
            'error_count' => $errorCount
        ]);
    }

    private function scrapeLyrics($title, $artist)
    {
        // Konfigurasi client with better error handling
        $client = new Client([
            'timeout' => 480, // 8 menit
            'connect_timeout' => 120, // 2 menit untuk koneksi awal
            'verify' => false,
            'http_errors' => false,
        ]);

        // API_URL_SERVER = http://170.64.233.37:3000/lyrics
        // API_URL_LOCAL = htttp://localhost:3000/lyrics

        try {
            $response = $client->get('http://170.64.233.37:3000/lyrics', [
                'query' => [
                    'title' => $title,
                    'artist' => $artist
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return [
                    'success' => false,
                    'message' => 'API returned status code ' . $response->getStatusCode()
                ];
            }

            $data = json_decode($response->getBody(), true);

            if (!isset($data['lyrics']) || empty($data['lyrics']['lyrics'])) {
                return [
                    'success' => false,
                    'message' => 'No lyrics found in API response'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'lyric' => $data['lyrics']['lyrics'],
                    'language' => $data['lyrics']['language'] ?? null,
                    'explicit' => $data['lyrics']['explicit'] ?? false,
                    'source' => $data['source'] ?? null
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    public function deleteScrapeLyric($id)
    {
        try {
            $lyric = Lyric::findOrFail($id);
            $lyric->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lyric deleted successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting lyric:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting lyric.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportCsv($project_name)
    {
        // Cek apakah template tersedia
        $templatePath = storage_path('app/public/template_lyric.xlsx');
        if (!file_exists($templatePath)) {
            return response()->json(['error' => 'Template file not found.'], 404);
        }

        // Load template Excel
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Ambil data lirik berdasarkan project_name
        $lyrics = Lyric::where('project_name', $project_name)->get();

        if ($lyrics->isEmpty()) {
            return back()->with('error', 'No lyrics found for this project.');
        }

        Log::info('Lyrics data:', $lyrics->toArray());

        // Daftar bahasa yang valid
        $validLanguages = ['ID', 'EN', 'KR', 'JP']; // Ganti dengan daftar bahasa Anda

        $currentUser = Auth::user()->name; // Ambil nama user yang sedang login

        $row = 2; // Data dimulai dari baris kedua (baris pertama adalah header)
        foreach ($lyrics as $lyric) {
            $sheet->setCellValue("A{$row}", trim($lyric->title));
            $sheet->setCellValue("B{$row}", trim($lyric->artist));
            $sheet->setCellValue("C{$row}", trim($lyric->lyric));
            $sheet->setCellValue("D{$row}", strtoupper(trim($lyric->language)));
            $sheet->setCellValue("E{$row}", $lyric->explicit ? 1 : 0); // Set 1 if true, 0 if false
            $sheet->setCellValue("F{$row}", $lyric->tag ?? 'Kosong'); // Tag
            $sheet->setCellValue("G{$row}", $lyric->priority ?? 0); // Prioritas
            $sheet->setCellValue("H{$row}", $lyric->done_publish ? 1 : 0); // Done check
            $sheet->setCellValue("I{$row}", $currentUser);
            $sheet->setCellValue("J{$row}", ''); // Done Publish (kosong)
            $sheet->setCellValue("K{$row}", ''); // Tanggal Publish (Kosong)

            // Memastikan lirik tetap rapi dengan wrap text
            $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("C{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);


            Log::info("Writing row {$row}: ", [
                'title' => $lyric->title,
                'artist' => $lyric->artist,
                'lyric' => $lyric->lyric,
                'language' => $lyric->language,
                'explicit' => $lyric->explicit,
                'created_at' => $lyric->created_at->format('Y-m-d')
            ]);

            $row++;
        }

        // Atur lebar kolom C secara otomatis
        $sheet->getColumnDimension('C')->setAutoSize(true);

        // Buat folder penyimpanan jika belum ada
        $exportPath = storage_path('app/public/exports/');
        if (!file_exists($exportPath)) {
            mkdir($exportPath, 0777, true);
        }

        // Gunakan nama file yang sesuai dengan project_name
        $fileName = "KLY_Lyric_{$project_name}_" . date('Y-m-d') . ".xlsx";
        $filePath = $exportPath . $fileName;

        // Simpan file Excel
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        // Download file dan hapus setelah dikirim
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

}
