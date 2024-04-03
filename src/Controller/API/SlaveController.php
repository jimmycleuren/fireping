<?php
declare(strict_types=1);

namespace App\Controller\API;

use App\Common\Version\Version;
use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\Slave;
use App\Exception\DirtyInputException;
use App\Exception\WrongTimestampRrdException;
use App\Processor\ProcessorFactory;
use App\Storage\SlaveStatsRrdStorage;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SlaveController extends AbstractController
{
    public function __construct(private readonly LoggerInterface $logger, private readonly EntityManagerInterface $em, private readonly ProcessorFactory $processorFactory, private readonly SlaveStatsRrdStorage $slaveStatsRrdStorage)
    {
    }

    /**
     * @return JsonResponse
     *
     * @Route("/api/slaves/{id}/result", methods={"POST"})
     * @ParamConverter("slave", class="App:Slave")
     *
     * Process new results from a slave
     */
    public function resultAction(Slave $slave, Request $request)
    {
        $slave->setLastContact(new DateTime());
        $this->em->persist($slave);
        $this->em->flush();

        $probeRepository = $this->em->getRepository(Probe::class);
        $deviceRepository = $this->em->getRepository(Device::class);

        $probes = json_decode($request->getContent());

        if (null === $probes || (is_array($probes) && 0 == count($probes))) {
            return new JsonResponse(['code' => 400, 'message' => 'Invalid json input'], 400);
        }

        try {
            foreach ($probes as $probeId => $probeData) {
                if (!isset($probeData->timestamp)) {
                    $this->logger->warning('Incorrect data received from slave');

                    return new JsonResponse(['code' => 400, 'message' => 'No timestamp found in probe data'], 400);
                }
                if (!isset($probeData->targets)) {
                    $this->logger->warning('Incorrect data received from slave');

                    return new JsonResponse(['code' => 400, 'message' => 'No targets found in probe data'], 400);
                }
                $probe = $probeRepository->findOneBy(['id' => $probeId]);
                $timestamp = $probeData->timestamp;
                $targets = $probeData->targets;

                if (!$probe) {
                    continue;
                }

                foreach ($targets as $targetId => $targetData) {
                    $device = $deviceRepository->findOneBy(['id' => $targetId]);
                    if (!$device) {
                        $this->logger->info("Slave sends data for device '$targetId' but it does not exist");
                        continue;
                    }
                    $this->logger->debug('Updating data for probe ' . $probe->getType() . ' on ' . $device->getName());
                    $processor = $this->processorFactory->create($probe->getType());
                    $processor->storeResult($device, $probe, $slave->getSlaveGroup(), $timestamp, $targetData);
                }
            }

            //execute 1 flush at the end, not for every datapoint
            $this->em->flush();
        } catch (WrongTimestampRrdException | DirtyInputException $e) {
            $this->logger->warning($e->getMessage());

            return new JsonResponse(['code' => 409, 'message' => $e->getMessage()], 409);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());

            return new JsonResponse(['code' => 500, 'message' => $e->getMessage()], 500);
        }

        return new JsonResponse(['code' => 200, 'message' => 'Results saved']);
    }

    /**
     * @return JsonResponse
     * @Route("/api/slaves/{id}/stats", methods={"POST"})
     */
    public function statsAction(Slave $slave, Request $request)
    {
        $data = json_decode($request->getContent());

        $slave->setLastContact(new DateTime());
        $slave->setVersion(new Version((string)($data->version ?? '')));
        $this->em->flush();

        if (isset($data->workers)) {
            foreach ($data->workers as $timestamp => $workerData) {
                $this->slaveStatsRrdStorage->store($slave, "workers", $timestamp, $workerData);
            }
        }

        if (isset($data->queues)) {
            foreach ($data->queues as $timestamp => $queues) {
                $result = [];
                foreach ($queues as $id => $items) {
                    $result['queue' . $id] = $items;
                }
                $this->slaveStatsRrdStorage->store($slave, "queues", $timestamp, $result);
            }
        }

        $this->slaveStatsRrdStorage->store($slave, 'posts', date('U'), [
            'successful' => $data->posts->success,
            'failed' => $data->posts->failed,
            'discarded' => $data->posts->discarded,
        ]);

        $this->slaveStatsRrdStorage->store($slave, 'load', date('U'), [
            'load1' => $data->load[0],
            'load5' => $data->load[1],
            'load15' => $data->load[2],
        ]);

        $this->slaveStatsRrdStorage->store($slave, 'memory', date('U'), [
            'total' => $data->memory[0],
            'used' => $data->memory[1],
            'free' => $data->memory[2],
            'shared' => $data->memory[3],
            'buffer' => $data->memory[4],
            'available' => $data->memory[5],
        ]);

        return new JsonResponse(['code' => 200]);
    }

    /**
     * @return JsonResponse
     *
     * @Route("/api/slaves/{id}/error", methods={"POST"})
     * @ParamConverter("slave", class="App:Slave")
     *
     * Process errors from a slave
     */
    public function errorAction(Slave $slave)
    {
        //TODO: implement slave error handling
        $this->logger->info("Error received from $slave");

        return new JsonResponse(['code' => 200]);
    }

    /**
     * @Route("/api/slaves/health", name="slave_test")
     * @IsGranted("ROLE_API")
     */
    public function testAction(): JsonResponse
    {
        return new JsonResponse();
    }

}
