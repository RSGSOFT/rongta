<?php

namespace App\Http\Controllers;

use App\Support\SearchTools;
use App\Support\SeoFiles;
use Illuminate\Http\Response;

/** sitemap.xml, robots.txt, llms.txt and llms-full.txt, built from the database on each request. */
class SeoController extends Controller
{
    public function sitemap(): Response
    {
        return response(SeoFiles::sitemap(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        return $this->text(SeoFiles::robots());
    }

    /** IndexNow key file: engines fetch it to confirm our pings. Missing while IndexNow is off. */
    public function indexNowKey(): Response
    {
        $settings = SearchTools::all();
        abort_unless($settings['indexnow'] && $settings['indexnow_key'] !== '', 404);

        return $this->text($settings['indexnow_key']);
    }

    public function llms(): Response
    {
        return $this->text(SeoFiles::llms());
    }

    public function llmsFull(): Response
    {
        return $this->text(SeoFiles::llmsFull());
    }

    protected function text(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
