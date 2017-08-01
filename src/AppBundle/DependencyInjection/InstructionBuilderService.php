<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 19/06/2017
 * Time: 12:38
 */

namespace AppBundle\DependencyInjection;


use AppBundle\Instruction\Instruction;
use AppBundle\Instruction\InstructionBuilder;
use AppBundle\Probe\ProbeDefinition;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstructionBuilderService
{
    private $container;
    private $DEFAULT_TARGET_SIZE = 50;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(ProbeDefinition $probe) : Instruction
    {
        $size = $this->DEFAULT_TARGET_SIZE;

        if ($this->container->hasParameter('slave.instructions')) {
            $instructionConfig = $this->container->getParameter('slave.instructions');
            if (isset($instructionConfig[$probe->getType()])) {
                $size = $instructionConfig[$probe->getType()];
            } elseif (isset($instructionConfig['default'])) {
                $size = $instructionConfig['default'];
            }
        }

        return InstructionBuilder::create($probe, $size);
    }
}