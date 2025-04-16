<?php

namespace App\Controller;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    #[Route("/article/{id}", name: "get_article")]
    public function getArticle(
        int $id,
        ?int $sourceId,
        RequestStack $requestStack,
        EntityManagerInterface $em
    ): Response {
        $article = $em->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException("Article non trouvé.");
        }

        $request = $requestStack->getCurrentRequest();
        $sourceId = $request->query->get("source");
        $date = $request->query->get("date");

        return $this->render("article/index.html.twig", [
            "article" => $article,
            "sourceId" => $sourceId,
            "date" => $date,
        ]);
    }
}
