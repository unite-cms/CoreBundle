<?php

namespace UnitedCMS\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use UnitedCMS\CoreBundle\Entity\User;

class CreatePlatformAdminCommand extends ContainerAwareCommand
{
    private $hidePasswordInput = true;

    /**
     * This function can be called to disable hiding of the password input. This can be useful if this feature is not
     * supported (for example for phpunit tests this can be the case).
     */
    public function disableHidePasswordInput() {
       $this->hidePasswordInput = false;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('united:user:create')
            ->setDescription('Creates a new Platform admin for this installation.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        $helper = $this->getHelper('question');

        $question = new Question('<info>Please enter the firstname of the new user:</info> ');
        $firstname = $helper->ask($input, $output, $question);

        $question = new Question('<info>And the lastname:</info> ');
        $lastname = $helper->ask($input, $output, $question);

        $question = new Question('<info>And the email:</info> ');
        $email = $helper->ask($input, $output, $question);

        $question = new Question('<info>Please set a password:</info> ');
        $question->setHidden($this->hidePasswordInput);
        $question->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $question);

        $user = new User();
        $user
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setRoles([User::ROLE_PLATFORM_ADMIN]);

        $user->setPassword($this->getContainer()->get('security.password_encoder')->encodePassword(
            $user,
            $password
        ));

        $password = NULL;

        $question = new ConfirmationQuestion(
            '<info>Should the user "' . $user->getFirstname() . ' ' . $user->getFirstname() . '" with email "' . $user->getEmail() . '" be created</info>? [<comment>Y/n</comment>] ',
            true,
            '/^(y|j)/i'
        );

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $errors = $this->getContainer()->get('validator')->validate($user);
        if(count($errors) == 0) {
            $em->persist($user);
            $em->flush();
            $output->writeln('<info>Platform Admin was created!</info>');
        } else {
            $output->writeln("<error>\n\nThere was an error while creating the user\n \n$errors\n</error>");
        }
    }
}
