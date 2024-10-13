<?php

namespace App\Controller;

use App\Form\SearchFormType;
use App\Entity\Parameter;
use App\Form\AppFormParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ParameterController extends AbstractController
{
   #[Route('/parameter/app_configuration', name: 'app_parameter_app_configuration', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
         // Créez le formulaire de recherche ici
    $searchForm = $this->createForm(SearchFormType::class);

    // Autres logiques de traitement (ex: affichage des paramètres par défaut)
    $parameters = $entityManager->getRepository(Parameter::class)->findAll();

    return $this->render('parameter/config.html.twig', [
        'searchForm' => $searchForm->createView(), // Passer le formulaire à la vue
        'parameters' => $parameters
    ]);
        // $parameters = $entityManager->getRepository(Parameter::class)->findAll();

        // // Traiter les formulaires d'édition et de suppression
        // foreach ($parameters as $existingParameter) {
        //     // Formulaire pour la modification
        //     $editForm = $this->createForm(AppFormParameterType::class, $existingParameter);
            
        //     // Formulaire pour la suppression
        //     $deleteForm = $this->createFormBuilder()
        //         ->setAction($this->generateUrl('app_parameter_app_configuration', ['id' => $existingParameter->getId()]))
        //         ->setMethod('POST')
        //         ->getForm();

        //     $existingParameter->editForm = $editForm->createView();
        //     $existingParameter->deleteForm = $deleteForm->createView();
        // }

        // return $this->render('parameter/config.html.twig', [
        //     'parameters' => $parameters,  // Transmets les paramètres ici
        // ]); 
    }

    

    #[Route('/parameter/create', name: 'app_parameter_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parameter = new Parameter();
        $form = $this->createForm(AppFormParameterType::class, $parameter);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($parameter);
            $entityManager->flush();
    
            // Renvoie une réponse JSON pour confirmer la création
            return $this->json(['status' => 'success', 'parameter' => [
                'paramKey' => $parameter->getParamKey(),
                'paramValue' => $parameter->getParamValue(),
                // Ajoute d'autres propriétés si nécessaire
            ]]);
        }
    
        return $this->json(['status' => 'error', 'errors' => (string) $form->getErrors(true, false)]);
    }
    

    #[Route('/parameter/search', name: 'app_ajax_search', methods: ['POST'])]
    public function search(Request $request, EntityManagerInterface $entityManager): Response
    {
           // Créer le formulaire
           $form = $this->createForm(SearchFormType::class);
           $form->handleRequest($request); // Gère la requête
   
           $parameters = [];
   
           if ($form->isSubmitted() && $form->isValid()) {
               // Récupère les données du formulaire
               $searchTerm = $form->get('searchTerm')->getData();
   
               // Rechercher les paramètres correspondant à la recherche
               $parameters = $entityManager
                   ->getRepository(Parameter::class)
                   ->createQueryBuilder('p')
                   ->where('p.paramKey LIKE :searchTerm')
                   ->orWhere('p.paramValue LIKE :searchTerm')
                   ->orWhere('p.paramDateFrom LIKE :searchTerm')
                   ->orWhere('p.paramDateTo LIKE :searchTerm')
                   ->orWhere('p.paramUser LIKE :searchTerm')
                   ->setParameter('searchTerm', '%' . $searchTerm . '%')
                   ->getQuery()
                   ->getResult();
           }
        // Générer le HTML avec le fichier Twig
        $html = $this->renderView('parameter/tableau_parameter.html.twig', [
            'parameters' => $parameters
        ]);
    
        // Renvoyer le HTML sous forme de réponse JSON
        return $this->json([
            'html' => $html
        ]);
    }
        
    #[Route('/parameter/generaux', name: 'app_parameter_generaux')]
    public function generaux(): Response
    {
        return $this->render('parameter/index.html.twig'); // Assurez-vous de créer ce fichier Twig
    }

    


    #[Route('/parameter/about', name: 'app_parameter_about')]
    public function about(): Response
    {
        return $this->render('parameter/about.html.twig'); // Assurez-vous de créer ce fichier Twig
    }



}   


