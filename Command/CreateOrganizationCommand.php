<?php

namespace UnitedCMS\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use UnitedCMS\CoreBundle\Entity\Organization;

class CreateOrganizationCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('united:organization:create')
            ->setDescription('Creates a new organization and saves it to the database.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        $helper = $this->getHelper('question');
        $question = new Question('<info>Please enter the title of the organization:</info> ');
        $title = $helper->ask($input, $output, $question);

        $name = $this->titleToMachineName($title);

        $question = new Question(
            '<info>Please enter the identifier of the organization</info> [<comment>'.$name.'</comment>]: ', $name
        );
        $question->setAutocompleterValues([$name]);
        $identifier = $helper->ask($input, $output, $question);

        $organization = new Organization();
        $organization->setTitle($title)->setIdentifier($identifier);

        $question = new ConfirmationQuestion(
            '<info>Should the organization with title: "'.$organization->getTitle(
            ).'" and identifier: "'.$identifier.'" be created</info>? [<comment>Y/n</comment>] ',
            true,
            '/^(y|j)/i'
        );

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $errors = $this->getContainer()->get('validator')->validate($organization);
        if(count($errors) > 0) {
            $output->writeln("<error>\n\nThere was an error while creating the organization\n \n$errors\n</error>");
        } else {
            $em->persist($organization);
            $em->flush();
            $output->writeln('<info>Organization was created successfully!</info>');
        }
    }

    /**
     * @see: https://github.com/SymfonyContrib/MachineNameFieldBundle/blob/master/Transformer/LabelToMachineNameTransformer.php
     * @param $title
     * @return string
     */
    private function titleToMachineName($title): string
    {
        // Lowercase.
        $name = strtolower($title);
        // Replace spaces, underscores, and dashes with underscores.
        $name = preg_replace('/(\s|_+|-+)+/', '_', $name);
        // Trim underscores from the ends.
        $name = trim($name, '_');
        // Remove all except alpha-numeric and underscore characters.
        $name = preg_replace('/[^a-z0-9_]+/', '', $name);

        return $name;
    }
}
