<?php

namespace Sonata\EasyExtendsBundle\Command;

use Sonata\EasyExtendsBundle\Bundle\BundleMetadata;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends ContainerAwareCommand
{
    const ENTITY_DIRECTORY = 'Entity';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sonata:easy-extends:generate')
            ->setHelp(<<<EOT
The <info>easy-extends:generate:entities</info> command generating a valid bundle structure from a Vendor Bundle.

  <info>ie: ./app/console sonata:easy-extends:generate SonataUserBundle</info>
EOT
            );

        $this->setDescription('Create entities used by Sonata\'s bundles');

        $this->addArgument('bundle', InputArgument::IS_ARRAY, 'The bundle name to "easy-extends"');
        $this->addOption('dest', 'd', InputOption::VALUE_OPTIONAL, 'The base folder where the Application will be created', false);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $destOption = $input->getOption('dest');
        if ($destOption) {
            $dest = realpath($destOption);
            if (false === $dest) {
                $output->writeln('');
                $output->writeln(sprintf('<error>The provided destination folder \'%s\' does not exist!</error>', $destOption));

                return 0;
            }
        } else {
            $dest = $this->getContainer()->get('kernel')->getRootDir();
        }

        $configuration = array(
            'application_dir' => sprintf('%s/Application', $dest),
        );

        $bundleNames = $input->getArgument('bundle');

        if (empty($bundleNames)) {
            $output->writeln('');
            $output->writeln('<error>You must provide a bundle name!</error>');
            $output->writeln('');
            $output->writeln('  Bundles availables :');
            foreach ($this->getContainer()->get('kernel')->getBundles() as $bundle) {
                $bundleMetadata = new BundleMetadata($bundle, $configuration);

                if (!$bundleMetadata->isExtendable()) {
                    continue;
                }

                $output->writeln(sprintf('     - %s', $bundle->getName()));
            }

            $output->writeln('');

            return 0;
        }

        foreach ($bundleNames as $bundleName) {
            $processed = $this->generate($bundleName, $configuration, $output);

            if (!$processed) {
                $output->writeln(sprintf('<error>The bundle \'%s\' does not exist or not defined in the kernel file!</error>', $bundleName));

                return -1;
            }
        }

        $output->writeln('done!');

        return 0;
    }

    /**
     * Generates a bundle entities from a bundle name.
     *
     * @param string          $bundleName
     * @param array           $configuration
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function generate($bundleName, array $configuration, $output)
    {
        $processed = false;

        foreach ($this->getContainer()->get('kernel')->getBundles() as $bundle) {
            if ($bundle->getName() != $bundleName) {
                continue;
            }

            $processed = true;

            $configuration['entity_directory'] = $this->getEntityDirectory();
            $configuration['repository_directory'] = $this->getRepositoryDirectory();

            $configuration['extended_entity_directory'] = $this->getExtendedEntityDirectory();
            $configuration['extended_repository_directory'] = $this->getExtendedRepositoryDirectory();

            $bundleMetadata = new BundleMetadata($bundle, $configuration);

            // generate the bundle file
            if (!$bundleMetadata->isExtendable()) {
                $output->writeln(sprintf('Ignoring bundle : "<comment>%s</comment>"', $bundleMetadata->getClass()));
                continue;
            }

            // generate the bundle file
            if (!$bundleMetadata->isValid()) {
                $output->writeln(sprintf('%s : <comment>wrong folder structure</comment>', $bundleMetadata->getClass()));
                continue;
            }

            $output->writeln(sprintf('Processing bundle : "<info>%s</info>"', $bundleMetadata->getName()));

            $this->getContainer()->get('sonata.easy_extends.generator.bundle')
                ->generate($output, $bundleMetadata);

            $output->writeln(sprintf('Processing Doctrine ORM : "<info>%s</info>"', $bundleMetadata->getName()));
            $this->getContainer()->get('sonata.easy_extends.generator.orm')
                ->generate($output, $bundleMetadata);

            $output->writeln(sprintf('Processing Doctrine ODM : "<info>%s</info>"', $bundleMetadata->getName()));
            $this->getContainer()->get('sonata.easy_extends.generator.odm')
                ->generate($output, $bundleMetadata);

            $output->writeln(sprintf('Processing Doctrine PHPCR : "<info>%s</info>"', $bundleMetadata->getName()));
            $this->getContainer()->get('sonata.easy_extends.generator.phpcr')
                ->generate($output, $bundleMetadata);

            $output->writeln(sprintf('Processing Serializer config : "<info>%s</info>"', $bundleMetadata->getName()));
            $this->getContainer()->get('sonata.easy_extends.generator.serializer')
                ->generate($output, $bundleMetadata);

            $output->writeln('');
        }

        return $processed;
    }

    /**
     * @return mixed|string
     */
    protected function getEntityDirectory()
    {
        if ($this->getContainer()->hasParameter('sonata.easyExtend.entity.directory')) {
            return $this->getContainer()->getParameter('sonata.easyExtend.entity.directory');
        }
        return static::ENTITY_DIRECTORY;
    }

    /**
     * @return mixed|string
     */
    protected function getRepositoryDirectory()
    {
        if ($this->getContainer()->hasParameter('sonata.easyExtend.repository.directory')) {
            return $this->getContainer()->getParameter('sonata.easyExtend.repository.directory');
        }

        return static::ENTITY_DIRECTORY;
    }

    /**
     * @return mixed|string
     */
    protected function getExtendedEntityDirectory()
    {
        if ($this->getContainer()->hasParameter('sonata.easyExtend.extended_entity.directory')) {
            return $this->getContainer()->getParameter('sonata.easyExtend.extended_entity.directory');
        }

        return $this->getEntityDirectory();
    }

    /**
     * @return mixed|string
     */
    protected function getExtendedRepositoryDirectory()
    {
        if ($this->getContainer()->hasParameter('sonata.easyExtend.extended_repository.directory')) {
            return $this->getContainer()->getParameter('sonata.easyExtend.extended_repository.directory');
        }

        return $this->getRepositoryDirectory();
    }
}
