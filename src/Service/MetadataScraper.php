<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MetadataScraper
{
    public function __construct(
        private HttpClientInterface $client
    ) {
    }

    public function scrape(string $url): array
    {
        try {
            $response = $this->client->request('GET', $url);
            $content = $response->getContent();
            $crawler = new Crawler($content);

            $title = $this->getMetaContent($crawler, 'og:title') ?? $crawler->filter('title')->text('');
            $description = $this->getMetaContent($crawler, 'og:description') ?? $this->getMetaContent($crawler, 'description');
            $image = $this->getMetaContent($crawler, 'og:image');

            return [
                'title' => $title,
                'description' => $description,
                'image' => $image,
            ];
        } catch (\Throwable $e) {
            return [
                'title' => null,
                'description' => null,
                'image' => null,
            ];
        }
    }

    private function getMetaContent(Crawler $crawler, string $property): ?string
    {
        $node = $crawler->filter("meta[property='$property'], meta[name='$property']");

        return $node->count() > 0 ? $node->attr('content') : null;
    }
}
