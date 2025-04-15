<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security as SymfonySecurity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\UserSource;

class HomeController extends AbstractController
{
    #[Route("/", name: "app_home")]
    public function home(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $userSources = [];

        if ($user) {
            $userSources = $em->getRepository(UserSource::class)->findBy([
                'user' => $user,
            ]);
        }

        return $this->render('home/index.html.twig', [
            'userSources' => $userSources,
        ]);
    }
}
