<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Admin-only links for hosting without a terminal: open the URL, read the result.
 * Each task is safe to run more than once.
 */
class MaintenanceController extends Controller
{
    private const TASKS = [
        'storage-link' => ['storage:link', [], 'Make uploaded images public (public/storage → storage/app/public)'],
        'migrate' => ['migrate', ['--force' => true], 'Apply new database changes after uploading an update'],
        'optimize' => ['optimize', [], 'Rebuild the config/route/view caches after an update or .env change'],
        'clear-cache' => ['optimize:clear', [], 'Clear every cache (use if a change to .env is not picked up)'],
    ];

    public function index()
    {
        $links = collect(self::TASKS)->map(fn ($task, $key) => '<li><a href="/maintenance/'.$key.'">/maintenance/'.$key.'</a> – '.e($task[2]).'</li>');

        return response('<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title>Maintenance</title>'
            .'<body style="font:16px/1.6 system-ui;max-width:720px;margin:40px auto;padding:0 16px">'
            .'<h1>Maintenance</h1><ul>'.$links->implode('').'</ul><p><a href="/">← Back to the panel</a></p>');
    }

    public function run(string $task)
    {
        abort_unless(isset(self::TASKS[$task]), 404);
        [$command, $options] = self::TASKS[$task];
        $notes = [];

        try {
            if ($task === 'storage-link') {
                $notes = $this->replaceUploadedStorageFolder();
            }
            $code = Artisan::call($command, $options);
            $output = trim(Artisan::output()) ?: 'Done.';
        } catch (Throwable $e) {
            $code = 1;
            $output = $e->getMessage();
        }

        if ($task === 'storage-link') {
            $link = public_path('storage');
            $code = is_link($link) ? 0 : 1;
            $notes[] = is_link($link)
                ? 'public/storage is linked to '.readlink($link).'. Uploaded images will show.'
                : 'public/storage is still not a link. This host may block symlinks: ask support to enable symlink(), '
                    .'or to create public/storage as a link to storage/app/public.';
        }

        $text = ($code === 0 ? "OK: {$command}" : "FAILED: {$command}")."\n\n".implode("\n", [...$notes, '', $output]);

        return response($text."\n", $code === 0 ? 200 : 500)->header('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * An FTP/zip upload turns the public/storage link into a plain folder, which hides every new upload.
     * Keep any images that only exist in that copy, then remove it so the real link can be made.
     */
    private function replaceUploadedStorageFolder(): array
    {
        $link = public_path('storage');
        $target = storage_path('app/public');
        if (! is_dir($link) || is_link($link)) {
            return [];
        }

        // Don't touch the folder unless a link can actually replace it.
        $probe = public_path('storage-link-check');
        if (! @symlink($target, $probe)) {
            return ['public/storage is a normal folder, and this host refused to create a symlink, so it was left as is.'];
        }
        @unlink($probe) || @rmdir($probe);

        $kept = 0;
        foreach (File::allFiles($link) as $file) {
            $copy = $target.DIRECTORY_SEPARATOR.$file->getRelativePathname();
            if (! file_exists($copy)) {
                File::ensureDirectoryExists(dirname($copy));
                File::copy($file->getPathname(), $copy);
                $kept++;
            }
        }
        File::deleteDirectory($link);

        return ["public/storage was a normal folder (from the upload): kept {$kept} image(s) that only it had, then replaced it with a link."];
    }
}
