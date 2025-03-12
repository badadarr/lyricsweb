<?php

namespace App\Http\Controllers;

use App\Models\Lyric;
use App\Models\ProjectLyric;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function processScrapeLyric(Request $request)
    {
        try {
            // Log incoming request
            Log::info('Scrape request received:', [
                'title' => $request->query('title'),
                'artist' => $request->query('artist'),
                'project_name' => $request->query('project_name')
            ]);

            // Validasi input
            $title = $request->query('title');
            $artist = $request->query('artist');
            $project_name = $request->query('project_name');

            if (empty($title) || empty($artist)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Title and artist are required.',
                    'error_type' => 'validation'
                ], 400);
            }

            // Validate project exists
            $project = ProjectLyric::where('project_name', $project_name)
                ->whereNull('deleted_at')
                ->first();

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found.',
                    'error_type' => 'not_found',
                    'details' => [
                        'requested_project' => $project_name
                    ]
                ], 404);
            }

            // Cek apakah data sudah ada
            $existingLyric = Lyric::where('title', $title)
                ->where('artist', $artist)
                ->first();

            if ($existingLyric) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lyrics already exist in the database.',
                    'error_type' => 'duplicate',
                    'details' => [
                        'title' => $title,
                        'artist' => $artist
                    ]
                ], 409); // 409 Conflict
            }

            // Konfigurasi client dengan penanganan error yang lebih baik
            $client = new Client([
                'timeout' => 600, // Meningkatkan timeout dari 60 ke 600 detik
                'connect_timeout' => 60, // Meningkatkan connect timeout dari 5 ke 60 detik
                'verify' => false,
                'http_errors' => false
            ]);

            try {
                $response = $client->get('http://localhost:3000/lyrics', [
                    'query' => [
                        'title' => $title,
                        'artist' => $artist
                    ]
                ]);

                // Handle different status codes with specific error messages
                if ($response->getStatusCode() === 404) {
                    Log::warning('Lyrics not found:', [
                        'title' => $title,
                        'artist' => $artist
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Lyrics not found for this song.',
                        'error_type' => 'not_found',
                        'details' => [
                            'title' => $title,
                            'artist' => $artist
                        ]
                    ], 404);
                } else if ($response->getStatusCode() !== 200) {
                    Log::error('API returned error status code:', [
                        'status' => $response->getStatusCode(),
                        'title' => $title,
                        'artist' => $artist
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Error from lyrics API service.',
                        'error_type' => 'api_error',
                        'details' => [
                            'title' => $title,
                            'artist' => $artist,
                            'status_code' => $response->getStatusCode()
                        ]
                    ], 500);
                }

                $data = json_decode($response->getBody(), true);

                // Validate API response structure
                if (!isset($data) || !is_array($data)) {
                    Log::error('Invalid API response format:', [
                        'title' => $title,
                        'artist' => $artist,
                        'response' => (string) $response->getBody()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid response from lyrics API.',
                        'error_type' => 'api_format',
                        'details' => [
                            'title' => $title,
                            'artist' => $artist
                        ]
                    ], 500);
                }

                // Check if lyrics exist in response
                if (!isset($data['lyrics']['lyrics']) || empty($data['lyrics']['lyrics'])) {
                    Log::warning('No lyrics found in API response:', [
                        'title' => $title,
                        'artist' => $artist
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Lyrics not available for this song.',
                        'error_type' => 'empty_lyrics',
                        'details' => [
                            'title' => $title,
                            'artist' => $artist
                        ]
                    ], 404);
                }

                // Extract language info
                $language = isset($data['lyrics']['language']['name']) ? $data['lyrics']['language']['name'] : 'Unknown';

                // Save to database with transaction
                DB::beginTransaction();

                try {
                    $lyric = new Lyric([
                        'title' => $title,
                        'artist' => $artist,
                        'lyric' => $data['lyrics']['lyrics'],
                        'language' => $language,
                        'project_name' => $project->project_name
                    ]);

                    $project->lyrics()->save($lyric);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Lyrics successfully scraped and saved.',
                        'data' => [
                            'title' => $title,
                            'artist' => $artist,
                            'lyric' => $data['lyrics']['lyrics'],
                            'language' => $language,
                            'project_name' => $project->project_name,
                            'source' => 'api'
                        ]
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();

                    Log::error('Error saving lyrics:', [
                        'error' => $e->getMessage(),
                        'title' => $title,
                        'artist' => $artist
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Error saving lyrics to database.',
                        'error_type' => 'database',
                        'details' => [
                            'title' => $title,
                            'artist' => $artist,
                            'error' => $e->getMessage()
                        ]
                    ], 500);
                }
            } catch (RequestException $e) {
                Log::error('API connection error:', [
                    'error' => $e->getMessage(),
                    'title' => $title,
                    'artist' => $artist
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to lyrics API service.',
                    'error_type' => 'connection',
                    'details' => [
                        'title' => $title,
                        'artist' => $artist,
                        'error' => $e->getMessage()
                    ]
                ], 503);
            }
        } catch (\Exception $e) {
            Log::error('Unexpected error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'error_type' => 'system',
                'details' => [
                    'title' => $title ?? 'unknown',
                    'artist' => $artist ?? 'unknown',
                    'error' => $e->getMessage(), // Tambahkan pesan error
                    'trace' => $e->getTraceAsString() // Tambahkan trace error (opsional)
                ]
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

        $row = 2; // Data dimulai dari baris kedua (baris pertama adalah header)
        foreach ($lyrics as $lyric) {
            $sheet->setCellValue("A{$row}", trim($lyric->title));
            $sheet->setCellValue("B{$row}", trim($lyric->artist));
            $sheet->setCellValue("C{$row}", trim($lyric->lyric));
            $sheet->setCellValue("D{$row}", trim($lyric->language));

            // Memastikan lirik tetap rapi dengan wrap text
            $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true);

            $row++;
        }

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
