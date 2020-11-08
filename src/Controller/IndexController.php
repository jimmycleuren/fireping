<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\Domain;
use App\Entity\Probe;
use App\Entity\ProbeArchive;
use App\Entity\Slave;
use App\Entity\SlaveGroup;
use App\Entity\User;
use App\Repository\DeviceRepository;
use App\Repository\SlaveRepository;
use App\Repository\StorageNodeRepository;
use App\Repository\UserRepository;
use App\Version\Version;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function indexAction(StorageNodeRepository $storageNodeRepository, SlaveRepository $slaveRepository, DeviceRepository $deviceRepository, UserRepository $userRepository)
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
            'firstLaunch' => $slaveRepository->count([]) == 0 && $deviceRepository->count([]) == 0 && $userRepository->count([]) == 0,
        ]);
    }

    /**
     * @Route("/database-init", name="database-init")
     */
    public function initDatabaseAction(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder,
        SlaveRepository $slaveRepository,
        DeviceRepository $deviceRepository,
        UserRepository $userRepository
    ) {
        if ($slaveRepository->count([]) != 0 || $deviceRepository->count([]) != 0 || $userRepository->count([]) != 0) {
            return $this->redirectToRoute("home");
        }

        $admin = new User();
        $admin->setUsername('admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setEmail("admin@fireping.com");
        $admin->setEnabled(true);
        $admin->setPassword($passwordEncoder->encodePassword($admin, "admin"));
        $entityManager->persist($admin);

        $slave = new User();
        $slave->setUsername('slave');
        $slave->setRoles(['ROLE_API']);
        $slave->setEmail("slave@fireping.com");
        $slave->setEnabled(true);
        $slave->setPassword($passwordEncoder->encodePassword($slave, "password"));
        $entityManager->persist($slave);

        $ping = new Probe();
        $ping->setName('ping');
        $ping->setType('ping');
        $ping->setStep(60);
        $ping->setSamples(15);
        $entityManager->persist($ping);

        $archive = new ProbeArchive();
        $archive->setProbe($ping);
        $archive->setFunction('AVERAGE');
        $archive->setSteps(1);
        $archive->setRows(1440); //24 hours
        $entityManager->persist($archive);

        $traceroute = new Probe();
        $traceroute->setName('traceroute');
        $traceroute->setType('traceroute');
        $traceroute->setStep(60);
        $traceroute->setSamples(15);
        $entityManager->persist($traceroute);

        $archive = new ProbeArchive();
        $archive->setProbe($traceroute);
        $archive->setFunction('AVERAGE');
        $archive->setSteps(1);
        $archive->setRows(1440); //24 hours
        $entityManager->persist($archive);

        $http = new Probe();
        $http->setName('http');
        $http->setType('http');
        $http->setStep(60);
        $http->setSamples(10);
        $entityManager->persist($http);

        $archive = new ProbeArchive();
        $archive->setProbe($http);
        $archive->setFunction('AVERAGE');
        $archive->setSteps(1);
        $archive->setRows(1440); //24 hours
        $entityManager->persist($archive);

        $slavegroup = new SlaveGroup();
        $slavegroup->setName("slavegroup");
        $entityManager->persist($slavegroup);

        $slave = new Slave();
        $slave->setId('slave');
        $slave->setVersion(new Version("0.1"));
        $slave->setSlaveGroup($slavegroup);
        $slave->setLastContact(new \DateTime());
        $entityManager->persist($slave);

        $domain = new Domain();
        $domain->setName("Google");
        $domain->addProbe($ping);
        $domain->addProbe($traceroute);
        $domain->addProbe($http);
        $domain->addSlaveGroup($slavegroup);
        $entityManager->persist($domain);

        $device = new Device();
        $device->setName("www.google.com");
        $device->setIp("www.google.com");
        $device->setDomain($domain);
        $entityManager->persist($device);

        $entityManager->flush();

        return $this->redirectToRoute("home");
    }
}
