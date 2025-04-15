<?php

namespace App\Controller;

use App\Repository\SourceRepository;
use App\Service\FeedSyncService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SyncFeedController extends AbstractController
{
    #[Route("/sync", name: "sync_all", methods: ["POST"])]
    public function syncAll(
        SourceRepository $sourceRepo,
        FeedSyncService $feedSyncService
    ): JsonResponse {
        $sources = $sourceRepo->findAll();

        foreach ($sources as $source) {
            $feedSyncService->sync($source);
        }

        return new JsonResponse(["message" => "All feeds synced"]);
    }

    #[Route("/sync/{id}", name: "sync_one", methods: ["POST"])]
    public function syncOne(
        int $id,
        SourceRepository $sourceRepo,
        FeedSyncService $feedSyncService
    ): JsonResponse {
        $source = $sourceRepo->find($id);
        if (!$source) {
            return new JsonResponse(["error" => "Source not found"], 404);
        }

        $feedSyncService->sync($source);

        return new JsonResponse(["message" => "Feed for source #$id synced"]);
    }
}
