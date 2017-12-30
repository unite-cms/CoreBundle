<?php

namespace UnitedCMS\CoreBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;

class CreateDomainCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('united:domain:create')
            ->setDescription('Creates a new domain for an organization and saves it to the database.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $organizations = $em->getRepository('UnitedCMSCoreBundle:Organization')->findAll();

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            '<info>Please select the organization to create the domain for:</info> ',
            $organizations
        );
        $organization_title = $helper->ask($input, $output, $question);
        /**
         * @var Organization $organization
         */
        $organization = null;
        foreach ($organizations as $org) {
            if ($organization_title === $org->getTitle()) {
                $organization = $org;
            }
        }

        $helper = $this->getHelper('question');
        $question = new Question('<info>Please insert the domain definition JSON string:</info> ');
        $definition = $helper->ask($input, $output, $question);
        $domain = $this->getContainer()->get('united.cms.domain_definition_parser')->parse($definition);
        $domain->setOrganization($organization);

        $output->writeln(['', '', '<info>*****Domain definition*****</info>', '']);
        $output->writeln('Title</>: <comment>'.$domain->getTitle().'</comment>');
        $output->writeln('Identifier: <comment>'.$domain->getIdentifier().'</comment>');
        $output->writeln('Roles: [<comment>'.join(', ', $domain->getRoles()).'</comment>]');
        $output->writeln('ContentTypes: [');

        foreach ($domain->getContentTypes() as $contentType) {

            $fields = [];
            $collections = [];

            foreach ($contentType->getFields() as $field) {
                $fields[] = $field->getTitle();
            }

            foreach ($contentType->getCollections() as $collection) {
                $collections[] = $collection->getTitle();
            }

            $output->writeln('    {');
            $output->writeln('      Title: <comment>'.$contentType->getTitle().'</comment>');
            $output->writeln('      Identifier: <comment>'.$contentType->getIdentifier().'</comment>');
            $output->writeln('      Icon: <comment>'.$contentType->getIcon().'</comment>');
            $output->writeln('      Description: <comment>'.$contentType->getDescription().'</comment>');
            $output->writeln('      Fields: [<comment>'.join(', ', $fields).'</comment>]');
            $output->writeln('      Collections: [<comment>'.join(', ', $collections).'</comment>]');
            $output->writeln('    }');
        }

        $output->writeln(['', '']);

        $question = new ConfirmationQuestion(
            '<info>Should the domain for the organization: "'.$organization.'" be created</info>? [<comment>Y/n</comment>] ',
            true,
            '/^(y|j)/i'
        );

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $errors = $this->getContainer()->get('validator')->validate($domain);
        if(count($errors) > 0) {
            $output->writeln("<error>\n\nThere was an error while creating the domain\n \n$errors\n</error>");
        } else {
            $em->persist($domain);
            $em->flush();
            $output->writeln('<info>Domain was created successfully!</info>');
        }

        $output->writeln('<info>Domain was created successfully!</info>');
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
