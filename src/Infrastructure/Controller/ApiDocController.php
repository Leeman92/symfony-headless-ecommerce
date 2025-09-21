<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/doc', name: 'api_doc_')]
final class ApiDocController extends AbstractController
{
    #[Route('/', name: 'ui', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('api_doc/swagger.html.twig');
    }
}
