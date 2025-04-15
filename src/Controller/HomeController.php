<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security as SymfonySecurity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class HomeController extends AbstractController
{
    #[Route("/", name: "app_home")]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render("home/index.html.twig", [
            "user" => $user,
        ]);
    }
}
