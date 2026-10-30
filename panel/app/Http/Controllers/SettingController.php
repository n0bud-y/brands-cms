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
    public function edit(Request $request, Site $site)
    {
        $request->session()->put('last_site', $site->slug);

        return Inertia::render('settings/edit', [
            'site' => $site,
            'settings' => (object) $site->settings()->pluck('value', 'key')->all(),
        ]);
    }

    /** Replaces the whole set: keys left out or left empty are deleted. */
    public function update(Request $request, Site $site)
    {
        $request->validate([
            'settings' => ['present', 'array', 'max:200'],
            'settings.*' => ['nullable', 'string', 'max:20000'],
        ]);

        $values = [];
        foreach ($request->input('settings', []) as $key => $value) {
            if (! is_string($key) || ! preg_match('/^[a-z][a-z0-9_]{0,63}$/', $key)) {
                throw ValidationException::withMessages(['settings' => "Invalid setting name \"{$key}\". Use lowercase letters, numbers and underscores."]);
            }
            if (($value = trim((string) $value)) === '') {
                continue;
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
