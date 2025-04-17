<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\UserArticle;
use App\Repository\UserArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

class UserArticleController extends AbstractController
{
    #[
        Route(
            "/article/read/{articleId}",
            name: "read_article",
            methods: ["POST"]
        )
    ]
    #[
        IsCsrfTokenValid(
            new Expression('"read_article_" ~ args["articleId"]'),
            tokenKey: "_token"
        )
    ]
    public function readArticle(
        int $articleId,
        UserArticleRepository $userArticleRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException(
                "Vous devez être connecté."
            );
        }

        $existingUserArticle = $userArticleRepository->findOneBy([
            "user" => $user,
            "article" => $articleId,
        ]);
        $article = $em->getRepository(Article::class)->find($articleId);

        if (!$existingUserArticle) {
            $existingUserArticle = new UserArticle();
            $existingUserArticle->setUser($user);
            $existingUserArticle->setArticle($article);
            $existingUserArticle->setRead(false);
            $existingUserArticle->setLiked(false);
        }

        if ($existingUserArticle->isRead()) {
            return new Response(
                json_encode(["success" => true]),
                Response::HTTP_OK,
                ["Content-Type" => "application/json"]
            );
        }

        $existingUserArticle->setRead(true);
        $em->persist($existingUserArticle);
        $em->flush();

        return new Response(
            json_encode(["success" => true]),
            Response::HTTP_OK,
            ["Content-Type" => "application/json"]
        );
    }

    #[
        Route(
            "/article/like/{articleId}",
            name: "like_article",
            methods: ["POST"]
        )
    ]
    #[
        IsCsrfTokenValid(
            new Expression('"like_article_" ~ args["articleId"]'),
            tokenKey: "_token"
        )
    ]
    public function likeArticle(
        int $articleId,
        UserArticleRepository $userArticleRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException(
                "Vous devez être connecté."
            );
        }

        $existingUserArticle = $userArticleRepository->findOneBy([
            "user" => $user,
            "article" => $articleId,
        ]);
        $article = $em->getRepository(Article::class)->find($articleId);

        if (!$existingUserArticle) {
            $existingUserArticle = new UserArticle();
            $existingUserArticle->setUser($user);
            $existingUserArticle->setArticle($article);
            $existingUserArticle->setRead(true);
            $existingUserArticle->setLiked(false);
        }

        $liked = !$existingUserArticle->isLiked();
        $existingUserArticle->setLiked($liked);
        $em->persist($existingUserArticle);
        $em->flush();

        $allUserArticles = $userArticleRepository->findBy([
            "article" => $articleId,
        ]);
        $likes = 0;
        foreach ($allUserArticles as $userArticle) {
            if ($userArticle->isLiked()) {
                $likes++;
            }
        }

        return new Response(
            json_encode([
                "success" => true,
                "liked" => $liked,
                "likes" => $likes,
                "userHasLiked" =>
                    $existingUserArticle && $existingUserArticle->isLiked(),
            ]),
            Response::HTTP_OK,
            ["Content-Type" => "application/json"]
        );
    }
}
