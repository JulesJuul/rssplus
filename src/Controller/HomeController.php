<?php

namespace App\Controller;

use App\Entity\UserSource;
use Doctrine\ORM\EntityManagerInterface;
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
        $dateParam = $request->query->get('date');

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

        if ($dateParam && $dateParam !== 'all') {
            $now = new \DateTime();
            $filteredArticles = [];

            foreach ($articles as $article) {
                $publishedAt = $article->getPubDate();

                if (!$publishedAt) {
                    continue;
                }

                switch ($dateParam) {
                    case 'Aujourd\'hui':
                        if ($publishedAt->format('Y-m-d') === $now->format('Y-m-d')) {
                            $filteredArticles[] = $article;
                        }
                        break;

                    case 'Semaine':
                        $startOfWeek = (clone $now)->modify('monday this week');
                        $endOfWeek = (clone $startOfWeek)->modify('+6 days');
                        if ($publishedAt >= $startOfWeek && $publishedAt <= $endOfWeek) {
                            $filteredArticles[] = $article;
                        }
                        break;

                    case 'Mois':
                        if ($publishedAt->format('Y-m') === $now->format('Y-m')) {
                            $filteredArticles[] = $article;
                        }
                        break;
                }
            }

            $articles = $filteredArticles;
        }

        $articlesArray = $articles instanceof \Doctrine\Common\Collections\Collection ? $articles->toArray() : $articles;
        usort($articlesArray, fn($a, $b) => $b->getPubDate() <=> $a->getPubDate());
        $articles = $articlesArray;

        return $this->render('home/index.html.twig', [
            'userSources' => $userSources,
            'articles' => $articles,
            'availableDates' => ['Aujourd\'hui', 'Semaine', 'Mois'],
        ]);
    }
}
