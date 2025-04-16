<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\UserSource;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Laminas\Validator\NotEmpty;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route("/", name: "app_home")]
    public function home(EntityManagerInterface $em, RequestStack $requestStack): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $userSources = $em->getRepository(UserSource::class)->findBy([
            'user' => $user,
        ]);

        $request = $requestStack->getCurrentRequest();
        $sourceParam = $request->query->get('source');

        $articles = [];

        if ($sourceParam && $sourceParam != '') {
            $selectedUserSource = array_filter($userSources, function ($us) use ($sourceParam) {
                return $us->getSource()->getId() == $sourceParam;
            });
            $selectedUserSource = reset($selectedUserSource);

            if ($selectedUserSource) {
                $articles = $selectedUserSource->getSource()->getArticles();
            }
        } else {
            foreach ($userSources as $us) {
                foreach ($us->getSource()->getArticles() as $article) {
                    $articles[] = $article;
                }
            }
        }

        return $this->render('home/index.html.twig', [
            'userSources' => $userSources,
            'articles' => $articles,
        ]);
    }
}
