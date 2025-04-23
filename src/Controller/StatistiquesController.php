<?php

namespace App\Controller;

use App\Repository\DonsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatistiquesController extends AbstractController
{
    #[Route('/statistiques', name: 'app_statistiques')]
    public function index(DonsRepository $donsRepository): Response
    {
        // 1. Récupérer les données pour le graphique par type de don
        $donsByType = $donsRepository->countDonsByType();
        $pieChartData = [];
        foreach ($donsByType as $type) {
            $pieChartData[] = [
                'type' => $type['type_don'],
                'count' => (int)$type['count']
            ];
        }
        
        // 2. Récupérer les données pour le graphique par mois
        $donsByMonth = $donsRepository->sumDonsByMonth();
        $columnChartData = [];
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        foreach ($donsByMonth as $month) {
            $monthNumber = (int)$month['month'];
            $monthName = $monthNames[$monthNumber] ?? 'Mois ' . $monthNumber;
            $columnChartData[] = [
                'month' => $monthName,
                'total' => (float)$month['total']
            ];
        }
        
        // 3. Récupérer les données pour le graphique d'évolution
        $donsEvolution = $donsRepository->getDonsEvolution();
        $lineChartData = [];
        foreach ($donsEvolution as $point) {
            $date = $point['date'] instanceof \DateTime 
                ? $point['date']->format('Y-m-d') 
                : (new \DateTime($point['date']))->format('Y-m-d');
            
            $lineChartData[] = [
                'date' => $date,
                'total' => (float)$point['cumulative_amount']
            ];
        }
        
        return $this->render('dons/Stats.html.twig', [
            'pieChartData' => json_encode($pieChartData),
            'columnChartData' => json_encode($columnChartData),
            'lineChartData' => json_encode($lineChartData),
        ]);
    }
}