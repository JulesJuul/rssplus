<?php

namespace App\Controller;

use App\Repository\SourceRepository;
use App\Repository\UserSourceRepository;
use App\Service\FeedSyncService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SyncFeedController extends AbstractController
{
    public function syncAllAdmin(
        SourceRepository $sourceRepo,
        FeedSyncService $feedSyncService
    ): bool {
        $sources = $sourceRepo->findAll();

        foreach ($sources as $source) {
            $feedSyncService->sync($source);
        }

        return true;
    }

    #[Route("/sync", name: "sync_all", methods: ["POST"])]
    public function syncAll(
        UserSourceRepository $userSourceRepository,
        SourceRepository $sourceRepo,
        FeedSyncService $feedSyncService
    ): Response {
        $userSources = $userSourceRepository->findBy([
            "user" => $this->getUser(),
        ]);
        $sources = array_map(
            fn($userSource) => $userSource->getSource(),
            $userSources
        );

        foreach ($sources as $source) {
            $feedSyncService->sync($source);
        }

        $this->addFlash("success", "Tous vos flux ont été synchronisés");
        return $this->redirectToRoute("user_sources");
    }

    #[Route("/sync/{id}", name: "sync_one", methods: ["POST"])]
    public function syncOne(
        int $id,
        SourceRepository $sourceRepo,
        UserSourceRepository $userSourceRepository,
        FeedSyncService $feedSyncService
    ): Response {
        $source = $sourceRepo->find($id);
        if (!$source) {
            $this->addFlash("error", 'La source n\'a pas été trouvée');
            return $this->redirectToRoute("user_sources");
        }

        $res = $feedSyncService->sync($source);
        $userSource = $userSourceRepository->findOneBy([
            "user" => $this->getUser(),
            "source" => $source->getId(),
        ]);

        if (!$res["success"]) {
            $this->addFlash(
                "error",
                "Le flux " .
                    $userSource->getCustomName() .
                    " a échoué à être synchronisé"
            );
            return $this->redirectToRoute("user_sources");
        }

        $this->addFlash(
            "success",
            "Le flux " . $userSource->getCustomName() . " a été synchronisé"
        );
        return $this->redirectToRoute("user_sources");
    }
}
