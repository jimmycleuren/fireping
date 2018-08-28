<?php

namespace App\Controller;

use App\Entity\StorageNode;
use App\Repository\StorageNodeRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

class StorageNodeController extends AbstractController
{
    private $logger = null;

    /**
     * @Route("/storagenode", name="storagenode")
     */
    public function index(StorageNodeRepository $storageNodeRepository, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $nodes = array();

        foreach($storageNodeRepository->findAll() as $node) {
            $nodes[] = [
                'node' => $node,
                'rrdcached' => $this->checkRrdCached($node),
                'ssh' => $this->checkSsh($node)
            ];
        }

        return $this->render('storagenode/index.html.twig', [
            'nodes' => $nodes
        ]);
    }

    private function checkRrdCached(StorageNode $node)
    {
        $errno = "";
        $errstr = "";
        $socket = @fsockopen($node->getIp(), 42217, $errno, $errstr, 1);

        return $socket != null;
    }

    private function checkSsh(StorageNode $node)
    {
        $process = new Process('ssh '.$node->getIp().' hostname');
        $process->run();

        $output = $process->getOutput();
        $error = $process->getErrorOutput();

        if ($output) {
            return trim($output);
        }

        $this->logger->warning("SSH connection to $node failed: ".trim($error));

        return false;
    }
}
