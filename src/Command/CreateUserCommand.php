<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class CleanupCommand
 * @package App\Command
 */
class CreateUserCommand extends Command
{
    protected static $defaultName = 'fireping:create:user';
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * CleanupCommand constructor.
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Create a fireping user');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = new User();

        $helper = $this->getHelper('question');

        $questions['username'] = new Question('Please enter the username (defaults to admin): ', 'admin');
        $questions['password'] = new Question('Please enter the password: ');
        $questions['email'] = new Question('Please enter the email address: ');
        $questions['roles'] = new ChoiceQuestion(
            'Please choose roles for this user',
            ['ROLE_ADMIN', 'ROLE_API'],
            '1'
        );

        $questions['roles']->setMultiselect(true);
        $questions['password']->setHidden(true);
        $questions['password']->setHiddenFallback(false);

        foreach ($questions as $field => $question) {
            $answer = '';

            while (empty($answer)) {
                $answer = $helper->ask($input, $output, $question);
            }

            $user->setProperty($field, $answer);
        }

        $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));
        $user->setEnabled(1);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
