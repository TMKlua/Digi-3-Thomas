<?php

namespace App\Controller;

use App\Entity\Parameter;
use App\Form\AppFormParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ParameterController extends AbstractController
{
    
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



   #[Route('/parameter/app_configuration', name: 'app_parameter_app_configuration', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $parameters = $entityManager->getRepository(Parameter::class)->findAll();

        // Traiter les formulaires d'édition et de suppression
        foreach ($parameters as $existingParameter) {
            // Formulaire pour la modification
            $editForm = $this->createForm(AppFormParameterType::class, $existingParameter);
            
            // Formulaire pour la suppression
            $deleteForm = $this->createFormBuilder()
                ->setAction($this->generateUrl('app_parameter_app_configuration', ['id' => $existingParameter->getId()]))
                ->setMethod('POST')
                ->getForm();

            $existingParameter->editForm = $editForm->createView();
            $existingParameter->deleteForm = $deleteForm->createView();
        }

        return $this->render('parameter/config.html.twig', [
            'parameters' => $parameters,  // Transmets les paramètres ici
        ]);
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
    $searchTerm = $request->request->get('search_term');
    // Rechercher les paramètres correspondant à la recherche
    $parameters = $entityManager
        ->getRepository(Parameter::class)
        ->createQueryBuilder('p')
        // ->where('p.paramKey LIKE :searchTerm') // Ajuste le champ selon ta structure
        // ->setParameter('searchTerm', '%' . $searchTerm . '%')
        ->where('p.paramKey = :searchTerm') // Remplacez LIKE par =
        ->setParameter('searchTerm', $searchTerm) // Utilisez simplement searchTerm sans '%' pour l'égalité
        ->getQuery()
        ->getResult();
        dump($parameters);
      
    // Renvoyer les résultats en JSON
    return $this->json([
        'parameters' => array_map(function ($parameter) {
            return [
          'id' => $parameter->getId(),
            'paramKey' => $parameter->getParamKey(),  // Assurez-vous que cela correspond bien à votre méthode
            'paramValue' => $parameter->getParamValue(),
            'paramDateFrom' => $parameter->getParamDateFrom() ? $parameter->getParamDateFrom()->format('Y-m-d') : null, // Formatage de la date
            'paramDateTo' => $parameter->getParamDateTo() ? $parameter->getParamDateTo()->format('Y-m-d') : null, // Formatage de la date
            'paramUser' => $parameter->getParamUser(),
            ];
        }, $parameters),
    ]);
}
}   


