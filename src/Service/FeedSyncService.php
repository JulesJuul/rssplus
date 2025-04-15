<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Source;
use Doctrine\ORM\EntityManagerInterface;
use Laminas\Feed\Reader\Reader;

class FeedSyncService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function sync(Source $source): array
    {
        try {
            $feed = Reader::import($source->getUrl());
        } catch (\Exception $e) {
            return ["error" => "Invalid RSS feed: " . $e->getMessage()];
        }

        foreach ($feed as $entry) {
            $guid = $entry->getId();
            $article = $this->em->getRepository(Article::class)->findOneBy([
                "guid" => $guid,
                "source" => $source,
            ]);

            if (!$article) {
                $article = new Article();
                $article->setSource($source);
                $article->setGuid($guid);
            }

            $article->setTitle(trim($entry->getTitle()));
            $article->setLink($entry->getLink());
            $article->setDescription(trim($entry->getDescription()));
            $article->setContent(trim($entry->getContent()));

            $author = $entry->getAuthor();
            $name = $author["name"] ?? null;
            $article->setAuthor($name !== null ? trim($name) : null);

            $date = $entry->getDateCreated();
            $article->setPubDate(
                $date ? \DateTimeImmutable::createFromMutable($date) : null
            );

            $this->em->persist($article);
        }

        $source->setLastSyncedAt(new \DateTimeImmutable());
        $this->em->flush();

        return ["success" => true];
    }
}
