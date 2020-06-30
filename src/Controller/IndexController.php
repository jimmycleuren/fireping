<?php

namespace App\Controller;

use App\Repository\DeviceRepository;
use App\Repository\SlaveRepository;
use App\Repository\StorageNodeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function indexAction(StorageNodeRepository $storageNodeRepository, SlaveRepository $slaveRepository, DeviceRepository $deviceRepository)
    {
        $onlineSlaves = 0;
        $offlineSlaves = 0;
        foreach ($slaveRepository->findAll() as $slave) {
            if ($slave->isOnline()) {
                ++$onlineSlaves;
            } else {
                ++$offlineSlaves;
            }
        }

        return $this->render('default/index.html.twig', [
            'storageNodes' => $storageNodeRepository->findAll(),
            'onlineSlaves' => $onlineSlaves,
            'offlineSlaves' => $offlineSlaves,
        ]);
    }
}
