<?php

namespace App\Controller;

use App\Entity\Source;
use App\Entity\UserSource;
use App\Enum\SourceStatus;
use App\Form\AddSourceType;
use App\Form\EditUserSourceType;
use App\Repository\UserSourceRepository;
use App\Service\FeedSyncService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

class SourceController extends AbstractController
{
    // Add a new flow
    #[Route("/flux/add", name: "add_source")]
    public function add(
        Request $request,
        EntityManagerInterface $em,
        FeedSyncService $feedSyncService
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute("app_login");
        }

        $source = new Source();
        $form = $this->createForm(AddSourceType::class, $source);
        $form->handleRequest($request);

        // Check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            $url = $source->getUrl();

            // Check if the URL is valid
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $this->addFlash("error", 'L\'URL fournie n\'est pas valide.');
                return $this->redirectToRoute("add_source");
            }

            $headers = get_headers($url, 1);
            $contentType = null;

            // Check if headers were retrieved successfully
            if ($headers === false || $headers === null) {
                $this->addFlash(
                    "error",
                    'Impossible de récupérer les en-têtes de l\'URL fournie.'
                );
                return $this->redirectToRoute("add_source");
            }

            foreach ($headers as $key => $value) {
                if (strtolower($key) === "content-type") {
                    $contentType = is_array($value) ? $value[0] : $value;
                    break;
                }
            }

            if (
                !$contentType ||
                !str_contains(strtolower($contentType), "xml")
            ) {
                $this->addFlash(
                    "error",
                    'L\'URL fournie ne semble pas être un flux RSS/XML valide.'
                );
                return $this->redirectToRoute("add_source");
            }

            // Check if the URL is already in the database
            $existingSource = $em
                ->getRepository(Source::class)
                ->findOneBy(["url" => $url]);

            // If not found, create a new Source entity
            if ($existingSource) {
                $source = $existingSource;
            } else {
                $source->setCreatedAt(new \DateTimeImmutable());
                $source->setUpdatedAt(new \DateTimeImmutable());
                $source->setUrl($url);
                $source->setStatus(SourceStatus::INIT);
                $em->persist($source);
                $em->flush();
            }

            // Check if the source is already in the user's list
            try {
                $userSource = new UserSource();
                $userSource->setUser($this->getUser());
                $userSource->setSource($source);
                $userSource->setCustomName($form->get("customName")->getData());
                $userSource->setCreatedAt(new \DateTimeImmutable());
                $em->persist($userSource);
                $em->flush();

                if ($source->getStatus($url) === SourceStatus::INIT) {
                    $result = $feedSyncService->sync($source);
                    if ($result["success"]) {
                        $source->setStatus(SourceStatus::COMPLETE);
                        $em->persist($source);
                        $em->flush();
                    }
                }

                return $this->redirectToRoute("user_sources");
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash(
                    "error",
                    "Ce flux est déjà présent dans votre liste."
                );
            }
        }

        return $this->render("source/add.html.twig", [
            "form" => $form->createView(),
        ]);
    }

    // List all flows
    #[Route("/flows", name: "user_sources")]
    public function manage(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $userSources = $em->getRepository(UserSource::class)->findBy([
            "user" => $user,
        ]);

        return $this->render("source/list.html.twig", [
            "userSources" => $userSources,
        ]);
    }

    // Edit a flow
    #[Route("/flow/edit/{sourceId}", name: "edit_user_source")]
    public function editUserSource(
        int $sourceId,
        UserSourceRepository $userSourceRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        $userSource = $userSourceRepository->findOneBy([
            "user" => $user,
            "source" => $sourceId,
        ]);

        if (!$userSource) {
            throw $this->createNotFoundException(
                'Ce flux n\'existe pas ou ne vous appartient pas.'
            );
        }

        $form = $this->createForm(EditUserSourceType::class, $userSource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userSource->setCustomName($form->get("customName")->getData());
            $entityManager->flush();

            return $this->redirectToRoute("user_sources");
        }

        return $this->render("source/edit.html.twig", [
            "form" => $form->createView(),
            "userSource" => $userSource,
        ]);
    }

    // Delete a flow
    #[
        Route(
            "/flow/delete/{sourceId}",
            name: "delete_user_source",
            methods: ["POST"]
        )
    ]
    #[
        IsCsrfTokenValid(
            new Expression('"delete_user_source_" ~ args["sourceId"]'),
            tokenKey: "_token"
        )
    ]
    public function deleteUserSource(
        int $sourceId,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException(
                "Vous devez être connecté."
            );
        }

        $userSource = $em->getRepository(UserSource::class)->findOneBy([
            "user" => $user,
            "source" => $sourceId,
        ]);

        if (!$userSource) {
            $this->addFlash("error", "Ce flux n’existe pas dans votre liste.");
            return $this->redirectToRoute("user_sources");
        }

        $em->remove($userSource);
        $em->flush();
        $this->addFlash("success", "Flux supprimé.");

        return $this->redirectToRoute("user_sources");
    }
}
