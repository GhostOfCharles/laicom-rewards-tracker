<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisualAssetsTest extends TestCase
{
    public function test_auth_background_preload_matches_the_webp_used_by_the_page(): void
    {
        $response = $this->get(route('portal'))->assertOk();
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $preloads = $xpath->query('//link[@rel="preload" and @as="image" and contains(@href, "portal-backdrop.webp")]');

        $this->assertSame(1, $preloads->length);
        $url = $preloads->item(0)->getAttribute('href');
        $this->assertSame('image/webp', $preloads->item(0)->getAttribute('type'));
        $this->assertStringContainsString('portal-backdrop.webp', $url);
        $this->assertStringContainsString($url, $xpath->query('//style')->item(0)->textContent);
        $response->assertDontSee('portal-bg.jpg')->assertDontSee('cdn.jsdelivr.net');

        foreach ($xpath->query('//link[@rel="stylesheet"]') as $link) {
            $path = parse_url($link->getAttribute('href'), PHP_URL_PATH);
            $this->assertFileExists(public_path(ltrim($path, '/')));
        }

        foreach (['webp' => 26000, 'jpg' => 51000] as $extension => $maxBytes) {
            $path = public_path('images/portal-backdrop.'.$extension);
            $this->assertSame(1600, getimagesize($path)[0]);
            $this->assertLessThan($maxBytes, filesize($path));
        }
    }

    public function test_logo_and_icon_font_preloads_match_the_resources_used_by_the_page(): void
    {
        $response = $this->get(route('portal'))->assertOk();
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $logo = $xpath->query('//img[contains(@class, "auth-logo")]')->item(0);
        $logoPreload = $xpath->query('//link[@rel="preload" and @as="image" and contains(@href, "rts-logo-header.webp")]');

        $this->assertSame(1, $logoPreload->length);
        $this->assertSame($logo->getAttribute('src'), $logoPreload->item(0)->getAttribute('href'));
        $this->assertSame('high', $logo->getAttribute('fetchpriority'));
        $this->assertNotSame('lazy', $logo->getAttribute('loading'));
        $this->assertLessThan(30000, filesize(public_path('images/rts-logo-header.webp')));

        $fontPreload = $xpath->query('//link[@rel="preload" and @as="font"]');
        $this->assertSame(1, $fontPreload->length);
        $this->assertSame('anonymous', $fontPreload->item(0)->getAttribute('crossorigin'));
        $this->assertSame('font/woff2', $fontPreload->item(0)->getAttribute('type'));
        preg_match('/src:\s*url\("([^"]+)"\)/', file_get_contents(public_path('vendor/bootstrap-icons/font/bootstrap-icons.css')), $fontUrl);
        $this->assertStringEndsWith('/vendor/bootstrap-icons/font/'.ltrim($fontUrl[1], './'), $fontPreload->item(0)->getAttribute('href'));
    }

    public function test_private_attachment_images_reserve_their_actual_aspect_ratio(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('ticket-attachments/logo.png', file_get_contents(public_path('images/rts-logo.png')));
        $url = '/customer/tickets/123/attachment';

        $html = Blade::render('<x-attachment-image path="ticket-attachments/logo.png" :src="$url" alt="Attachment" />', ['url' => $url]);
        $document = new DOMDocument;
        $document->loadHTML($html);
        $image = $document->getElementsByTagName('img')->item(0);

        $this->assertSame('1150', $image->getAttribute('width'));
        $this->assertSame('713', $image->getAttribute('height'));
        $this->assertSame('async', $image->getAttribute('decoding'));
        $this->assertSame('lazy', $image->getAttribute('loading'));
        $this->assertSame($url, $image->getAttribute('src'));
        $this->assertStringNotContainsString(storage_path(), $html);
    }
}
