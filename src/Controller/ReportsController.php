<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use MongoDB;
use Symfony\Component\HttpFoundation\Request;

class ReportsController extends AbstractController
{
    // ...
    /**
     * @Route("/demo_wsm", name="demo_wsm")
     */
    public function demo_wsm(Request $request)
    {           
        $dbs = "demo-db";
        $collection = (new MongoDB\Client)->$dbs->accounts;

        $aggregateArray = [
            ['$match' => ['status' => 'ACTIVE']]
        ];

        if(!is_null($request->query->get('input')) && $request->query->get('input') != "")
        {
            array_push($aggregateArray,['$match' => ['accountId' => $request->query->get('input')]]);
        }
        
        array_push($aggregateArray,['$lookup' => ['from' => 'metrics', 'localField' => 'accountId','foreignField' => 'accountId','as' => 'metrics']]);
        array_push($aggregateArray,['$unwind' => ['path' => '$metrics','preserveNullAndEmptyArrays' => true]]);
        array_push($aggregateArray,['$group' => ['_id' => ['accountId' => '$accountId', 'accountName' => '$accountName'],'spend' => ['$sum' => '$metrics.spend'], 'impressions' => ['$sum' => '$metrics.impressions'], 'clicks' => ['$sum' => '$metrics.clicks']]]);
        array_push($aggregateArray,['$addFields' => ['costPerClick' => ['$cond' => [['$eq' => ['$clicks',0]],0,['$divide' => ['$spend', '$clicks']]]]]]);

        $accounts = $collection->aggregate(
                $aggregateArray
            );            

         // the template path is the relative file path from `templates/`
         return $this->render('reports/view.html.twig', [
             'accounts' => $accounts,
             'input' => $request->query->get('input')
         ]);
    }
}
