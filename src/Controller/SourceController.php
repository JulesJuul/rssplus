<?php

namespace App\Controller;

use App\Entity\Source;
use App\Entity\UserSource;
use App\Form\AddSourceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;


class SourceController extends AbstractController
{
#[Route('/flux/ajouter', name: 'add_source')]
public function add(Request $request, EntityManagerInterface $em): Response
{
    if (!$this->getUser()) {
        return $this->redirectToRoute('app_login');
    }

    $source = new Source();
    $form = $this->createForm(AddSourceType::class, $source);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $url = $source->getUrl();

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->addFlash('error', 'L\'URL fournie n\'est pas valide.');
            return $this->redirectToRoute('add_source');
        }

        $headers = get_headers($url, 1);

        $contentType = null;

        if ($headers === false || $headers === null) {
            $this->addFlash('error', 'Impossible de récupérer les en-têtes de l\'URL fournie.');
            return $this->redirectToRoute('add_source');
        }
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'content-type') {
                $contentType = is_array($value) ? $value[0] : $value;
                break;
            }
        }

        if (!$contentType || !str_contains(strtolower($contentType), 'xml')) {
            $this->addFlash('error', 'L\'URL fournie ne semble pas être un flux RSS/XML valide.');
            return $this->redirectToRoute('add_source');
        }


        $existingSource = $em->getRepository(Source::class)->findOneBy(['url' => $url]);

        if ($existingSource) {
            $source = $existingSource;
        } else {
            $source->setCreatedAt(new \DateTimeImmutable());
            $source->setUpdatedAt(new \DateTimeImmutable());
            $source->setUrl($url);
            $source->setStatus('0');
            $em->persist($source);
            $em->flush();
        }
        try {
            $userSource = new UserSource();
            $userSource->setUser($this->getUser());
            $userSource->setSource($source);
            $userSource->setCustomName($form->get('customName')->getData());
            $userSource->setCreatedAt(new \DateTimeImmutable());
            $em->persist($userSource);
            $em->flush();

            return $this->redirectToRoute('user_sources');

        } 
        catch (UniqueConstraintViolationException $e) {
            $this->addFlash('error', 'Ce flux est déjà présent dans votre liste.');
        }
    }

    return $this->render('source/add.html.twig', [
        'form' => $form->createView(),
    ]);
}

#[Route('/flux', name: 'user_sources')]
public function manage(EntityManagerInterface $em): Response
{
    $user = $this->getUser();

    $userSources = $em->getRepository(UserSource::class)->findBy([
        'user' => $user,
    ]);

    return $this->render('source/list.html.twig', [
        'userSources' => $userSources,
    ]);
}

}