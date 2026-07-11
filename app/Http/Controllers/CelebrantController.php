<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BulkCelebrant;
use App\Models\User;
use App\Mail\CelebrantImportFinished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CelebrantController extends Controller
{
    /**
     * Handle bulk upload of celebrants via Excel file.
     */
    public function bulkUpload(Request $request)
    {
        $user = Auth::user();

        // Ensure corporate account
        if ($user->account_type !== 'corporate') {
            return redirect()->back()->with('error', 'Only corporate accounts can upload celebrants.');
        }

        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xls,xlsx'],
            'photos.*'   => ['nullable', 'image', 'max:5120'], // 5MB max per photo
            'send_email' => ['sometimes', 'boolean'],
        ]);

        $sendEmail = $request->boolean('send_email', $user->email_notifications_enabled ?? false);

        // Store uploaded photos temporarily
        $photoMap = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('celebrant_photos', 'public');
                $photoMap[$photo->getClientOriginalName()] = $path;
            }
        }

        // Import Excel rows
        $rows = Excel::toArray([], $request->file('excel_file'))[0]; // first sheet
        $processed = 0;
        $errors = [];
        foreach ($rows as $index => $row) {
            // Skip header row if it contains column names
            if ($index === 0 && isset($row['organisation_uuid'])) {
                // assume header
                continue;
            }
            // Expected columns (case‑insensitive)
            $orgUuid = $row['organisation_uuid'] ?? $row['Organisation UUID'] ?? null;
            $firstName = $row['first_name'] ?? $row['First Name'] ?? null;
            $lastName = $row['last_name'] ?? $row['Last Name'] ?? null;
            $email = $row['email'] ?? null;
            $phone = $row['phone'] ?? null;
            $type = $row['celebration_type'] ?? $row['Celebration Type'] ?? null;
            $date = $row['celebration_date'] ?? $row['Celebration Date'] ?? null;
            $photoName = $row['photo'] ?? null;

            // Basic validation
            if (! $orgUuid || ! $firstName || ! $lastName || ! $email || ! $type || ! $date) {
                $errors[] = "Row {$index}: missing required fields.";
                continue;
            }

            try {
                $celebrationDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
            } catch (\Exception $e) {
                $errors[] = "Row {$index}: invalid date format.";
                continue;
            }

            // Duplicate check (same email & date for same organisation)
            $exists = BulkCelebrant::where('email', $email)
                ->where('celebration_date', $celebrationDate)
                ->where('organisation_uuid', $orgUuid)
                ->exists();
            if ($exists) {
                $errors[] = "Row {$index}: duplicate entry for {$email} on {$celebrationDate}.";
                continue;
            }

            $photoPath = null;
            if ($photoName && isset($photoMap[$photoName])) {
                $photoPath = $photoMap[$photoName];
            }

            BulkCelebrant::create([
                'organisation_uuid' => $orgUuid,
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'email'             => $email,
                'phone'             => $phone,
                'celebration_type'  => $type,
                'celebration_date'  => $celebrationDate,
                'photo_path'        => $photoPath,
                'processed'         => false,
                'next_occurrence'   => $celebrationDate, // first occurrence
            ]);
            $processed++;
        }

        if ($sendEmail) {
            Mail::to($user->email)->send(new CelebrantImportFinished($processed, $errors));
        }

        return redirect()->back()->with('status', "Import completed. {$processed} rows added. " . count($errors) . " errors.");
    }

    /**
     * Show the bulk upload form (GET).
     */
    public function showBulkUploadForm()
    {
        $user = Auth::user();
        if ($user->account_type !== 'corporate') {
            return redirect()->back()->with('error', 'Only corporate accounts can access bulk upload.');
        }
        return view('celebrant.bulk-upload');
    }

    /**
     * Preview uploaded Excel rows — returns JSON for the dashboard overlay.
     */
    public function previewBulkUpload(Request $request)
    {
        $user = Auth::user();
        if ($user->account_type !== 'corporate') {
            return response()->json(['error' => 'Only corporate accounts can preview bulk upload.'], 403);
        }

        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xls,xlsx'],
        ]);

        $raw = Excel::toArray([], $request->file('excel_file'));
        $sheet = $raw[0] ?? [];

        if (empty($sheet)) {
            return response()->json(['rows' => [], 'headers' => []]);
        }

        // Detect if first row is a header row (strings vs numeric keys)
        $firstRow  = $sheet[0];
        $hasHeader = ! is_numeric(array_key_first($firstRow));

        if ($hasHeader) {
            $headers  = array_values($firstRow);
            $dataRows = array_slice($sheet, 1);
        } else {
            $headers  = ['Organisation UUID', 'First Name', 'Last Name', 'Email', 'Phone', 'Celebration Type', 'Celebration Date', 'Photo'];
            $dataRows = $sheet;
        }

        $preview = [];
        foreach ($dataRows as $i => $row) {
            $cells = array_values((array) $row);
            // Skip completely empty rows
            if (empty(array_filter($cells, fn($v) => $v !== null && $v !== ''))) {
                continue;
            }
            $mapped = [];
            foreach ($headers as $hi => $head) {
                $mapped[$head] = $cells[$hi] ?? '';
            }
            // Validate required fields and tag status
            $required = ['Organisation UUID', 'First Name', 'Last Name', 'Email', 'Celebration Type', 'Celebration Date'];
            $missing  = [];
            foreach ($required as $req) {
                if (empty($mapped[$req])) {
                    $missing[] = $req;
                }
            }
            $mapped['_row']    = $i + ($hasHeader ? 2 : 1);
            $mapped['_status'] = empty($missing) ? 'ok' : 'error';
            $mapped['_errors'] = $missing;
            $preview[]         = $mapped;
        }

        return response()->json(['rows' => $preview, 'headers' => $headers]);
    }
}
?>
