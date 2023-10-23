<?php

namespace JPB;

use CzProject\GitPhp\GitRepository;
use GuzzleHttp\Client;
use Throwable;

require_once __DIR__ . '/vendor/autoload.php';

$git = new GitRepository(__DIR__);
try {
    $tags = $git->getTags() ?? [];
} catch (Throwable) {
    $tags = [];
}
$releases = getGithubJson('repos/wp-cli/wp-cli/releases');
if (!is_array($releases)) {
    echo "Could not find any releases!\n";
    exit(1);
}
collect($releases)
    ->reverse()
    ->filter(fn($release) => !in_array($release->tag_name, $tags))
    ->map(function ($release) {
        $assets = collect($release->assets);
        $phar = $assets->first(fn($asset) => str_ends_with($asset->browser_download_url, '.phar'))->browser_download_url ?? false;
        $sig = $assets->first(fn($asset) => str_ends_with($asset->browser_download_url, '.sha512'))->browser_download_url ?? false;
        $tag = $release->tag_name;
        return ($phar && $sig) ? (object)compact('phar', 'sig', 'tag') : false;
    })
    ->filter()
    ->each(function ($item) use ($git) {
        $pharFile = __DIR__ . DIRECTORY_SEPARATOR . 'wp';
        $sigFile = __DIR__ . DIRECTORY_SEPARATOR . 'wp-cli.phar.sha512';
        try {
            $client = new Client();
            echo "Downloading $item->tag phar...\n";
            $client->get($item->phar, ['sink' => $pharFile]);
            echo "Downloading $item->tag signature...\n";
            $client->get($item->sig, ['sink' => $sigFile]);
            echo "Verifying $item->tag phar...\n";
            $signature = trim(file_get_contents($sigFile));
            $hash = hash_file('sha512', $pharFile);
            if ($hash !== $signature) {
                echo "Signature verification failed! Not committing!\n";
                $git->execute('checkout', '--', $pharFile);
            } else {
                echo "Signature verified. Committing new phar...\n";
                $git->addFile($pharFile);
                $git->commit("Add $item->tag phar");
                $git->createTag($item->tag);
            }
        } catch (Throwable) {
        }
    });

function getGithubJson($endpoint)
{
    static $client;
    if (!$client) {
        $client = new Client(['base_uri' => 'https://api.github.com']);
    }
    try {
        $response = $client->get($endpoint);
        $content = $response->getBody()->getContents();
    } catch (Throwable) {
    }
    return json_decode($content ?? 'null');
}
