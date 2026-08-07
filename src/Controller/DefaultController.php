<?php

namespace App\Controller;

use App\Repository\AccountRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(AccountRepository $repository): Response
    {
        return $this->render('default/index.html.twig', [
            'accounts' => $repository->findAll(),
        ]);
    }
}
