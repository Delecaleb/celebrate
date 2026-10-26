<?php

namespace App\Http\Controllers;

/**
 * The two files that let a celebratemi.com link open in the app.
 *
 * Without them a shared link to a celebration opens in the browser even for
 * someone who has the app. Each platform fetches its file from the site when
 * the app is installed and checks that the app named in it is the one asking.
 * Values come from config/services.php → app_links.
 */
class AppLinksController extends Controller
{
    /** Android App Links: which signed app may open this site's links. */
    public function android()
    {
        $config = config('services.app_links');

        $statements = $config['android_fingerprints']
            ? [[
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target'   => [
                    'namespace'                => 'android_app',
                    'package_name'             => $config['android_package'],
                    'sha256_cert_fingerprints' => array_values($config['android_fingerprints']),
                ],
            ]]
            : [];

        return response()->json($statements, 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /** iOS Universal Links: which app may open which paths. */
    public function apple()
    {
        $config = config('services.app_links');
        $appId  = $config['apple_team_id'] ? $config['apple_team_id'].'.'.$config['ios_bundle_id'] : null;

        $body = [
            'applinks' => [
                'details' => $appId
                    ? [[
                        'appIDs'     => [$appId],
                        'components' => array_map(fn ($path) => ['/' => $path], $config['paths']),
                    ]]
                    : [],
            ],
        ];

        return response()->json($body, 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
