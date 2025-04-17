<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\UserArticle;
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

        $content = preg_replace(
            "/padding-top:\s*[\d.]+%?;?/i",
            "",
            $article->getContent()
        );
        $article->setContent($content);

        $request = $requestStack->getCurrentRequest();
        $sourceId = $request->query->get("source");
        $date = $request->query->get("date");

        $allUserArticles = $em
            ->getRepository(UserArticle::class)
            ->findBy(["article" => $id]);
        $likes = 0;
        foreach ($allUserArticles as $userArticle) {
            if ($userArticle->isLiked()) {
                $likes++;
            }
        }

        $userHasLiked = false;
        if ($this->getUser()) {
            $userArticle = $em->getRepository(UserArticle::class)->findOneBy([
                "user" => $this->getUser(),
                "article" => $id,
            ]);
            if ($userArticle !== null) {
                $userHasLiked = $userArticle->isLiked();
            }
        }

        return $this->render("article/index.html.twig", [
            "article" => $article,
            "sourceId" => $sourceId,
            "likes" => $likes,
            "date" => $date,
            "userHasLiked" => $userHasLiked,
        ]);
    }
}
