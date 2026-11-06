<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LoginBackgroundController extends Controller
{
    /**
     * Display the login background settings page for Waka.
     */
    public function index()
    {
        $config = SystemConfiguration::firstOrCreate([]);
        $bgTimestamp = $config->login_background_updated_at ? $config->login_background_updated_at->timestamp : time();

        return view('waka.settings.login-background', [
            'config'      => $config,
            'bgTimestamp' => $bgTimestamp,
        ]);
    }

    /**
     * Update and re-encode the login background image.
     */
    public function update(Request $request)
    {
        $request->validate([
            'background' => ['required', 'file'],
        ], [
            'background.required' => 'Silakan pilih berkas gambar untuk diunggah.',
            'background.file'     => 'Berkas yang diunggah tidak valid.',
        ]);

        $file = $request->file('background');

        // Check file size (max 3 MB)
        if ($file->getSize() > 3 * 1024 * 1024) {
            return back()->withErrors(['background' => 'Ukuran berkas maksimal 3 MB.']);
        }

        $clientName = $file->getClientOriginalName();

        // Reject double extension (e.g., evil.php.jpg)
        if (substr_count($clientName, '.') > 1) {
            return back()->withErrors(['background' => 'Berkas dengan ekstensi ganda tidak diizinkan demi keamanan.']);
        }

        // Reject SVG
        $clientExt = strtolower($file->getClientOriginalExtension());
        if ($clientExt === 'svg' || str_contains(strtolower($clientName), '.svg')) {
            return back()->withErrors(['background' => 'Format SVG tidak diizinkan. Silakan gunakan format JPG, PNG, atau WebP.']);
        }

        // Content-based MIME check via finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file->getRealPath());
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($realMime, $allowedMimes, true)) {
            return back()->withErrors(['background' => 'Format berkas asli tidak valid. Hanya berkas gambar JPG, PNG, dan WebP yang diizinkan.']);
        }

        // Dimension check via getimagesize
        $imageInfo = @getimagesize($file->getRealPath());
        if (!$imageInfo) {
            return back()->withErrors(['background' => 'Berkas gambar tidak valid atau rusak.']);
        }

        [$width, $height] = $imageInfo;

        if ($width < 1200 || $height < 800) {
            return back()->withErrors([
                'background' => "Dimensi gambar terlalu kecil ({$width}x{$height} piksel). Minimal dimensi adalah 1200x800 piksel."
            ]);
        }

        if ($width > 6000 || $height > 6000) {
            return back()->withErrors([
                'background' => "Dimensi gambar terlalu besar ({$width}x{$height} piksel). Maksimal dimensi adalah 6000 piksel."
            ]);
        }

        // Re-encode via GD to strip EXIF and downscale if width > 1920
        switch ($realMime) {
            case 'image/jpeg':
                $src = @imagecreatefromjpeg($file->getRealPath());
                break;
            case 'image/png':
                $src = @imagecreatefrompng($file->getRealPath());
                break;
            case 'image/webp':
                $src = @imagecreatefromwebp($file->getRealPath());
                break;
            default:
                $src = null;
        }

        if (!$src) {
            return back()->withErrors(['background' => 'Gagal memproses gambar dengan mesin grafis GD.']);
        }

        if ($width > 1920) {
            $targetWidth = 1920;
            $targetHeight = (int) round($height * (1920 / $width));
            $dst = imagecreatetruecolor($targetWidth, $targetHeight);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            imagedestroy($src);
        } else {
            $dst = $src;
        }

        // Random filename, stored securely in private app storage
        $randomName = 'bg_' . Str::random(32) . '.webp';
        $saveRelativePath = 'branding/' . $randomName;
        $saveFullPath = storage_path('app/' . $saveRelativePath);

        if (!is_dir(dirname($saveFullPath))) {
            mkdir(dirname($saveFullPath), 0755, true);
        }

        imagewebp($dst, $saveFullPath, 85);
        imagedestroy($dst);

        $config = SystemConfiguration::firstOrCreate([]);

        // Delete old file if present
        if ($config->login_background_path) {
            $oldFile = storage_path('app/' . $config->login_background_path);
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }

        $config->login_background_path = $saveRelativePath;
        $config->login_background_updated_by = auth()->id();
        $config->login_background_updated_at = now();
        $config->save();

        return redirect()->route('waka.settings.login-background')
            ->with('success', 'Gambar latar belakang login berhasil diperbarui.');
    }

    /**
     * Reset login background to default.
     */
    public function destroy()
    {
        $config = SystemConfiguration::first();
        if ($config && $config->login_background_path) {
            $oldFile = storage_path('app/' . $config->login_background_path);
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
            $config->login_background_path = null;
            $config->login_background_updated_by = auth()->id();
            $config->login_background_updated_at = now();
            $config->save();
        }

        return redirect()->route('waka.settings.login-background')
            ->with('success', 'Gambar latar belakang login berhasil dikembalikan ke default.');
    }

    /**
     * Stream public login background image with ETag and Cache-Control.
     * Takes NO path parameter to prevent path traversal completely.
     */
    public function stream(Request $request)
    {
        $config = SystemConfiguration::first();

        if ($config && $config->login_background_path) {
            $fullPath = storage_path('app/' . $config->login_background_path);
            if (file_exists($fullPath)) {
                $etag = '"' . md5_file($fullPath) . '"';

                if ($request->header('If-None-Match') === $etag) {
                    return response('', 304, [
                        'Cache-Control' => 'public, max-age=86400, must-revalidate',
                        'ETag'          => $etag,
                    ]);
                }

                $mime = mime_content_type($fullPath) ?: 'image/webp';

                return response()->file($fullPath, [
                    'Content-Type'  => $mime,
                    'Cache-Control' => 'public, max-age=86400, must-revalidate',
                    'ETag'          => $etag,
                ]);
            }
        }

        // Default brand SVG fallback
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="1920" height="1080" viewBox="0 0 1920 1080">
  <defs>
    <linearGradient id="brand" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0a192f"/>
      <stop offset="50%" stop-color="#112240"/>
      <stop offset="100%" stop-color="#020c1b"/>
    </linearGradient>
    <radialGradient id="glow" cx="80%" cy="20%" r="60%">
      <stop offset="0%" stop-color="#0093dd" stop-opacity="0.30"/>
      <stop offset="100%" stop-color="#0093dd" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="100%" height="100%" fill="url(#brand)"/>
  <rect width="100%" height="100%" fill="url(#glow)"/>
</svg>
SVG;
        $etag = '"' . md5($svg) . '"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, [
                'Cache-Control' => 'public, max-age=86400, must-revalidate',
                'ETag'          => $etag,
            ]);
        }

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400, must-revalidate',
            'ETag'          => $etag,
        ]);
    }
}
