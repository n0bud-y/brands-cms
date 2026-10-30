<?php

namespace App\Support;

use App\Models\Site;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Tells a brand site to drop its cached API responses so edits show up at once.
 * Failure is never fatal: the brand cache simply expires on its own TTL.
 */
final class BrandCache
{
    public static function purge(Site $site): bool
    {
        $problem = self::clear($site);
        if ($problem !== null) {
            Log::warning("Brand cache purge failed for [{$site->slug}]: {$problem}");
        }

        return $problem === null;
    }

    /** Clears the brand site's cache; null on success, otherwise what went wrong in plain words. */
    public static function clear(Site $site): ?string
    {
        $url = $site->cacheClearUrl();

        try {
            $secret = (string) $site->cache_secret;
        } catch (DecryptException) {
            return 'The panel can\'t read this brand\'s cache secret (the panel\'s APP_KEY changed). '
                .'Click "↻ New cache secret" and paste it into the website\'s cms/config.php.';
        }

        try {
            $response = Http::connectTimeout(4)
                ->timeout(8)
                ->withHeaders(['X-Cache-Secret' => $secret])
                ->withOptions(['allow_redirects' => [
                    'max' => 3,
                    // Many sites 301 "page.php" to "page"; keep it a POST (with the secret) instead of a GET.
                    'strict' => true,
                    // …but never carry the secret to another domain.
                    'on_redirect' => function ($request, $response, $uri) use ($site) {
                        if (! self::sameSite($uri->getHost(), $site)) {
                            throw new RuntimeException("it redirected to another domain ({$uri->getHost()})");
                        }
                    },
                ]])
                ->post($url);
        } catch (ConnectionException $e) {
            return "Could not connect to {$url}: ".self::short($e->getMessage())
                .' If the website is on the same server as the panel, ask the host to allow the server to reach its own sites.';
        } catch (Throwable $e) {
            return "Request to {$url} failed: ".self::short($e->getMessage());
        }

        if ($response->successful() && $response->json('ok') === true) {
            return null;
        }

        return match (true) {
            $response->status() === 403 && $response->json('ok') === false => 'The website refused the cache secret. '
                .'Copy it again with "👀 Show cache secret" into the website\'s cms/config.php (cache_secret).',
            $response->status() === 404 => "{$url} was not found (404). Check that the cms/ folder is uploaded, or fix the Cache clear URL.",
            $response->successful() => "{$url} answered, but not like the brand kit (is the URL right?).",
            default => "{$url} answered with HTTP {$response->status()}.",
        };
    }

    /** Purge after the response has been sent, so the admin never waits on a brand site. */
    public static function purgeLater(Site $site): void
    {
        defer(fn () => self::purge($site), "brand-cache-{$site->id}");
    }

    /** Same domain as the brand's website or cache-clear URL, ignoring "www.". */
    private static function sameSite(string $host, Site $site): bool
    {
        $bare = fn (?string $h) => preg_replace('/^www\./', '', strtolower((string) $h));
        $allowed = [$bare(parse_url($site->url, PHP_URL_HOST)), $bare(parse_url($site->cacheClearUrl(), PHP_URL_HOST))];

        return in_array($bare($host), $allowed, true);
    }

    private static function short(string $message): string
    {
        return mb_strimwidth(preg_replace('/\s+/', ' ', $message), 0, 220, '…');
    }
}
