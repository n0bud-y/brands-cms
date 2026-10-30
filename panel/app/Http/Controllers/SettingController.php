<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Site;
use App\Support\BrandCache;
use App\Support\JsonLd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use InvalidArgumentException;

class SettingController extends Controller
{
    /** Code the website prints in its <head>, after <body> and before </body>. It can do anything, so admins only. */
    public const CODE_KEYS = ['code_head', 'code_body', 'code_footer'];

    public function edit(Request $request, Site $site)
    {
        $request->session()->put('last_site', $site->slug);
        $settings = $site->settings()->pluck('value', 'key');
        $canEditCode = $request->user()->isAdmin();

        return Inertia::render('settings/edit', [
            'site' => $site,
            'settings' => (object) ($canEditCode ? $settings : $settings->except(self::CODE_KEYS))->all(),
            'can_edit_code' => $canEditCode,
        ]);
    }

    /** Replaces the whole set: keys left out or left empty are deleted (code stays as it is for editors). */
    public function update(Request $request, Site $site)
    {
        $request->validate([
            'settings' => ['present', 'array', 'max:200'],
            'settings.*' => ['nullable', 'string', 'max:60000'],
        ]);

        $input = $request->input('settings', []);
        if (! $request->user()->isAdmin()) {
            $input = array_merge(
                array_diff_key($input, array_flip(self::CODE_KEYS)),
                $site->settings()->whereIn('key', self::CODE_KEYS)->pluck('value', 'key')->all(),
            );
        }

        $values = [];
        foreach ($input as $key => $value) {
            if (! is_string($key) || ! preg_match('/^[a-z][a-z0-9_]{0,63}$/', $key)) {
                throw ValidationException::withMessages(['settings' => "Invalid setting name \"{$key}\". Use lowercase letters, numbers and underscores."]);
            }
            if (($value = trim((string) $value)) === '') {
                continue;
            }
            // Theme values end up in the website's CSS, so only real colours and plain font names.
            if (str_starts_with($key, 'theme_') && ! preg_match('/^#[0-9a-f]{6}$/i', $value)) {
                throw ValidationException::withMessages(["settings.{$key}" => 'Pick a colour like #1a2b3c.']);
            }
            if (str_starts_with($key, 'font_') && $key !== 'font_google' && ! preg_match('/^[a-z0-9 \-]{1,60}$/i', $value)) {
                throw ValidationException::withMessages(["settings.{$key}" => 'Use just the font name, e.g. Poppins or Playfair Display.']);
            }
            if (! in_array($key, self::CODE_KEYS, true) && mb_strlen($value) > 20000) {
                throw ValidationException::withMessages(["settings.{$key}" => 'This is too long (20,000 characters at most).']);
            }
            // Brand-wide JSON-LD: store it normalized so brand sites can trust it.
            if ($key === 'schema') {
                try {
                    $value = JsonLd::pretty(JsonLd::parse($value));
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['settings.schema' => $e->getMessage()]);
                }
            }
            $values[$key] = $value;
        }

        DB::transaction(function () use ($site, $values) {
            $site->settings()->whereNotIn('key', array_keys($values))->delete();

            $now = now();
            Setting::upsert(
                array_map(fn ($key, $value) => [
                    'site_id' => $site->id, 'key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now,
                ], array_keys($values), $values),
                ['site_id', 'key'],
                ['value', 'updated_at'],
            );
        });

        BrandCache::purgeLater($site);

        $this->toast('Saved! The website updates in seconds ⚡');

        return back();
    }
}
