<?php

namespace Tests\App\Command;

use App\Command\CleanupCommand;
use App\Services\CleanupService;
use App\Storage\RrdStorage;
use App\Storage\StorageFactory;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class CleanupCommandTest extends KernelTestCase
{

    private $logger;
    private $em;
    private $fileSystem;
    private $dirPath;
    private $application;
    private $cleanupService;
    private $storageFactory;


    public function __construct()
    {
        parent::__construct();
    }

    public function setUp()
    {
        $kernel = self::bootKernel();
        $this->dirPath = $kernel->getContainer()->getParameter('kernel.cache_dir'). '/funny';
        $this->application = new Application($kernel);
        $this->logger = $this->prophesize("Psr\Log\LoggerInterface");
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();

        $this->cleanupService = $kernel->getContainer()->get('App\Services\CleanupService');

        $this->storageFactory = $kernel->getContainer()->get('App\Storage\StorageFactory');

        $this->fileSystem = new Filesystem();
        $this->setupDirectory();
    }

    public function testExecute()
    {
        $cleanUpCommand = new CleanupCommand($this->logger->reveal(), $this->storageFactory, $this->cleanupService);
        $this->application->add($cleanUpCommand);

        $this->assertTrue($this->fileSystem->exists($this->dirPath .'/VeryFunnyFolder'));

        $command = $this->application->find('app:cleanup');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
        ));

        //Check whether the irrelevant "device" is deleted
        $this->assertFalse($this->fileSystem->exists($this->dirPath .'/VeryFunnyFolder'));

        //Check if probe 1 and 3 exists, and 2 is removed for Device 1
        $this->assertTrue($this->fileSystem->exists($this->dirPath .'/1/1'));
        $this->assertFalse($this->fileSystem->exists($this->dirPath .'/1/2'));
        $this->assertTrue($this->fileSystem->exists($this->dirPath .'/1/3'));

        //Check if slavegroups 2 and 3 are removed, and 1 kept for Device 1
        $this->assertTrue($this->fileSystem->exists($this->dirPath .'/1/3/1.rrd'));
        $this->assertFalse($this->fileSystem->exists($this->dirPath .'/1/3/2.rrd'));
        $this->assertFalse($this->fileSystem->exists($this->dirPath .'/1/3/3.rrd'));

        //Random checks on Device 2
        $this->assertTrue($this->fileSystem->exists($this->dirPath .'/2'));
        $this->assertTrue($this->fileSystem->exists($this->dirPath .'/2/1/1.rrd'));
        $this->assertFalse($this->fileSystem->exists($this->dirPath .'/2/2/2.rrd'));
        $this->assertFalse($this->fileSystem->exists($this->dirPath .'/2/3'));


        //There are only 3 device fixtures 1, 2, 3 (mocked 20)
        $this->assertEquals($this->cleanupService->getActiveDeviceCount(), $this->cleanupService->getStoredDeviceCount());

        //There should no longer be any inactive devices
        $this->assertEquals(0, $this->cleanupService->getInactiveDeviceCount());


    }
    
    public function setupDirectory(){
        $this->fileSystem->mkdir($this->dirPath);

        //create 20 random devices
        for($i=0; $i<20; $i++){
            $this->fileSystem->mkdir($this->dirPath .'/'.$i);
        }

        //generate 3 probe folders and rrd files for the first 3 devices
        //because only 3 device fixtures exist, rest gets removed anyway
        for($i=1; $i<=3; $i++){
            //create 3 probe folders
            for($j=1; $j<=3; $j++){
                $this->fileSystem->mkdir($this->dirPath .'/'.$i.'/'.$j);
                //create 3 rrd folders
                for($k=1; $k<=3; $k++){
                    $this->fileSystem->touch($this->dirPath . '/' . $i . '/' . $j . '/' .$k.'.rrd', '');
                }
            }
        }

        //create an irrelevant folder
        $this->fileSystem->mkdir($this->dirPath.'/VeryFunnyFolder');

    }

    public function tearDown()
    {
        //$this->fileSystem->remove($this->dirPath);
        parent::tearDown();
    }
}
