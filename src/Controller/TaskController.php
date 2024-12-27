<?php

namespace App\Controller;

use App\Entity\Tasks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends AbstractController
{
    private $entityManager;

    // Injection de la dépendance Doctrine via le constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/task/{id}', name: 'app_details_tasks')]
    public function details(int $id): Response
    {
        // Utilisation de l'EntityManager pour récupérer la tâche
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);

        if (!$task) {
            throw $this->createNotFoundException('La tâche n\'existe pas.');
        }

        return $this->render('project/details_task.html.twig', [
            'task' => $task,
        ]);
    }
}