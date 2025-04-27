<?php

namespace App\Controller;

use App\Entity\Dons;
use App\Form\DonsType;
use App\Repository\DonsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\WhatsAppService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/crud/dons')]
class DonsController extends AbstractController
{
    #[Route('/list', name: 'app_crud_dons', methods: ['GET'])]
public function list(Request $request, DonsRepository $repository): Response
{
    // Champ de recherche (optionnel si tu veux l'ajouter plus tard dans un formulaire)
    $query = $request->query->get('query');

    // Champs de tri
    $sortField = $request->query->get('sort', 'montant'); // champ par défaut
    $sortOrder = $request->query->get('order', 'asc'); // ordre par défaut

    // Requête : recherche avec tri ou simple tri
    if ($query) {
        $dons = $repository->searchDons($query, $sortField, $sortOrder);
    } else {
        $dons = $repository->findBy([], [$sortField => $sortOrder]);
    }

    return $this->render('dons/list.html.twig', [
        'dons' => $dons,
        'sortField' => $sortField,
        'sortOrder' => $sortOrder,
        'query' => $query,
    ]);
}

    #[Route('/add', name: 'app_crud_dons_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $don = new Dons();
    $form = $this->createForm(DonsType::class, $don);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($don);
        $entityManager->flush();
        return $this->redirectToRoute('app_dons_checkout', ['id' => $don->getIdDon()]);

        // Stripe
        Stripe::setApiKey('sk_test_51QvxwLFgrJdVJiBv771GwUXqxgqPKFYlMy6ndnE1pZRq5vQ2N667GLGRny5DPpqf1bNe4YlcM778arXjrw7SzwGP00Ii0nVFyY'); // Ta clé secrète

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $don->getMontant() * 100, // en centimes
                    'product_data' => [
                        'name' => 'Don à OrphanCare',
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_stripe_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_stripe_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url, 303);
    }

    return $this->render('dons/add.html.twig', [
        'form' => $form->createView(),
    ]);
    }

    #[Route('/checkout/{id}', name: 'app_dons_checkout', methods: ['GET'])]
public function createCheckoutSession(Dons $don): Response
{
    Stripe::setApiKey($this->getParameter('stripe_secret_key'));

    $session = Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Don pour OrphanCare',
                ],
                'unit_amount' => $don->getMontant() * 100, // en centimes
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $this->generateUrl('don_merci', [], UrlGeneratorInterface::ABSOLUTE_URL),
        'cancel_url' => $this->generateUrl('app_crud_dons_add', [], UrlGeneratorInterface::ABSOLUTE_URL),
    ]);

    return $this->redirect($session->url);
}

    #[Route('/edit/{id}', name: 'app_crud_dons_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, DonsRepository $donsRepository, int $id): Response
    {
        $don = $donsRepository->find($id);
        if (!$don) {
            throw $this->createNotFoundException("Don non trouvé !");
        }
        
        $form = $this->createForm(DonsType::class, $don);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_crud_dons');
        }

        return $this->render('dons/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_crud_dons_delete')]
    public function delete(EntityManagerInterface $entityManager, DonsRepository $donsRepository, int $id): Response
    {
        $don = $donsRepository->find($id);
        if (!$don) {
            throw $this->createNotFoundException("Don non trouvé !");
        }

        $entityManager->remove($don);
        $entityManager->flush();

        return $this->redirectToRoute('app_crud_dons');
    }

    #[Route('/search', name: 'app_crud_dons_search', methods: ['GET'])]
    public function search(Request $request, DonsRepository $repository): JsonResponse
    {
        $query = $request->query->get('query', '');
        $sortField = $request->query->get('sort', 'montant');
        $sortOrder = $request->query->get('order', 'asc');
    
        $dons = $repository->searchDons($query, $sortField, $sortOrder);
    
        $results = [];
        foreach ($dons as $don) {
            $results[] = [
                'id' => $don->getIdDon(),
                'montant' => $don->getMontant(),
                'typeDon' => $don->getTypeDon(),
                'dateDon' => $don->getDateDon()->format('d/m/Y'),
                'evenement' => $don->getIdEvent() ? $don->getIdEvent()->getNom() : 'Aucun événement',
                'description' => (strlen($don->getDescription()) > 30) ? 
                    substr($don->getDescription(), 0, 30) . '...' : $don->getDescription(),
                'editUrl' => $this->generateUrl('app_crud_dons_edit', ['id' => $don->getIdDon()]),
                'deleteUrl' => $this->generateUrl('app_crud_dons_delete', ['id' => $don->getIdDon()])
            ];
        }
    
        return new JsonResponse($results);
    }

    #[Route('/merci', name: 'don_merci')]
    public function merci(WhatsAppService $whatsAppService): Response
    {
        $message = "✅ Merci ! Votre don a bien été reçu. 🙏 - OrphanCare";
        $whatsAppService->sendConfirmationMessage($message);
        return $this->render('dons/merci.html.twig');
    }


   

    


}
   

