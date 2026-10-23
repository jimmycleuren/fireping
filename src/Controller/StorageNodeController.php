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
    private readonly string $storagePath;

    public function __construct(string $storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/');
    }

    /**
     * @Route("/storagenode", name="storagenode")
     */
    public function index(StorageNodeRepository $storageNodeRepository, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $nodes = [];

        foreach ($storageNodeRepository->findAll() as $node) {
            $nodes[] = [
                'node' => $node,
                'rrdcached' => $this->checkRrdCached($node),
                'ssh' => $this->checkSsh($node),
                'permissions' => $this->checkPermissions($node),
            ];
        }

        return $this->render('storagenode/index.html.twig', [
            'nodes' => $nodes,
        ]);
    }

    private function checkRrdCached(StorageNode $node)
    {
        $errno = '';
        $errstr = '';
        $socket = @fsockopen($node->getIp(), 42217, $errno, $errstr, 1);

        return null != $socket;
    }

    private function checkSsh(StorageNode $node)
    {
        $process = new Process(['ssh', '-oBatchMode=yes', $node->getIp(), 'hostname']);
        $process->run();

        $output = $process->getOutput();
        $error = $process->getErrorOutput();

        if ($output) {
            return trim($output);
        }

        $this->logger->warning("SSH connection to $node failed: ".trim($error));

        return false;
    }

    private function checkPermissions(StorageNode $node)
    {
        $testFile = $this->storagePath.'/test.txt';

        $process = new Process(['ssh', '-oBatchMode=yes', $node->getIp(), 'touch', $testFile]);
        $process->run();

        $error = !$process->isSuccessful();
        if ($error) {
            $this->logger->warning("Error creating test file on $node: ".trim($process->getErrorOutput()));

            return false;
        }

        $process = new Process(['ssh', '-oBatchMode=yes', $node->getIp(), 'rm', $testFile]);
        $process->run();

        $error = !$process->isSuccessful();
        if ($error) {
            $this->logger->warning("Error deleting test file on $node: ".trim($process->getErrorOutput()));

            return false;
        }

        return true;
    }
}
