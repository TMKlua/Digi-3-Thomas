<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ManagementProjectController extends AbstractController
{

    #[Route('/management-project', name: 'app_management_project')]
    public function managementProject(): Response
    {
        // Sample project data (this could be fetched from a database)
        $projects = [
            ['id' => 1, 'name' => 'Projet ZE-623', 'description' => 'Description du projet actuel ...', 'start_planned' => '01/08', 'start_actual' => '15/09', 'end_planned' => '15/09', 'end_actual' => '29/09'],
            // Add more projects as needed
        ];
        return $this->render('project/management_project.html.twig', [
            'controller_name' => 'ProjectController',
            'projects' => $projects,
            'current_project' => $projects[0], // assuming the first project is selected by default
        ]);
    }
}
