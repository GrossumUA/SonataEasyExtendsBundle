<?php

namespace Sonata\EasyExtendsBundle\Generator;

use Sonata\EasyExtendsBundle\Bundle\BundleMetadata;
use Symfony\Component\Console\Output\OutputInterface;

class OrmGenerator implements GeneratorInterface
{
    protected $entityTemplate;
    protected $entityRepositoryTemplate;

    public function __construct()
    {
        $this->entityTemplate = file_get_contents(__DIR__.'/../Resources/skeleton/orm/entity.mustache');
        $this->entityRepositoryTemplate = file_get_contents(__DIR__.'/../Resources/skeleton/orm/repository.mustache');
    }

    /**
     * @param OutputInterface $output
     * @param BundleMetadata  $bundleMetadata
     */
    public function generate(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $this->generateMappingEntityFiles($output, $bundleMetadata);
        $this->generateEntityFiles($output, $bundleMetadata);
        $this->generateEntityRepositoryFiles($output, $bundleMetadata);
    }

    /**
     * @param OutputInterface $output
     * @param BundleMetadata  $bundleMetadata
     */
    public function generateMappingEntityFiles(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $output->writeln(' - Copy entity files');

        $files = $bundleMetadata->getOrmMetadata()->getEntityMappingFiles();
        foreach ($files as $file) {
            // copy mapping definition
            $fileName = substr($file->getFileName(), 0, strrpos($file->getFileName(), '.'));

            $destinationFile = sprintf(
                '%s/%s',
                $bundleMetadata->getOrmMetadata()->getExtendedMappingEntityDirectory(),
                $fileName
            );

            $srcFile = sprintf(
                '%s/%s',
                $bundleMetadata->getOrmMetadata()->getMappingEntityDirectory(),
                $file->getFileName()
            );

            if (is_file($destinationFile)) {
                $output->writeln(sprintf('   ~ <info>%s</info>', $fileName));
            } else {
                $output->writeln(sprintf('   + <info>%s</info>', $fileName));
                copy($srcFile, $destinationFile);
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param BundleMetadata  $bundleMetadata
     */
    public function generateEntityFiles(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $output->writeln(' - Generating entity files');

        $names = $bundleMetadata->getOrmMetadata()->getEntityNames();

        foreach ($names as $name) {
            $extendedName = $name;

            $destinationFile = sprintf(
                '%s/%s.php',
                $bundleMetadata->getOrmMetadata()->getExtendedEntityDirectory(),
                $name
            );

            $srcFile = sprintf('%s/%s.php', $bundleMetadata->getOrmMetadata()->getEntityDirectory(), $extendedName);

            if (!is_file($srcFile)) {
                $extendedName = 'Base'.$name;
                $srcFile = sprintf('%s/%s.php', $bundleMetadata->getOrmMetadata()->getEntityDirectory(), $extendedName);

                if (!is_file($srcFile)) {
                    $output->writeln(sprintf('   ! <info>%s</info>', $extendedName));

                    continue;
                }
            }

            if (is_file($destinationFile)) {
                $output->writeln(sprintf('   ~ <info>%s</info>', $name));
            } else {
                $output->writeln(sprintf('   + <info>%s</info>', $name));

                $string = Mustache::replace($this->getEntityTemplate(), array(
                    'extended_namespace'    => $bundleMetadata->getExtendedNamespace(),
                    'name'                  => $name != $extendedName ? $extendedName : $name,
                    'class'                 => $name,
                    'extended_name'         => $name == $extendedName ? 'Base'.$name : $extendedName,
                    'namespace'             => $bundleMetadata->getNamespace(),
                ));

                file_put_contents($destinationFile, $string);
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param BundleMetadata  $bundleMetadata
     */
    public function generateEntityRepositoryFiles(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $output->writeln(' - Generating entity repository files');

        $names = $bundleMetadata->getOrmMetadata()->getEntityNames();

        foreach ($names as $name) {
            $destinationDir = $bundleMetadata->getOrmMetadata()->getExtendedRepositoryDirectory();
            $destinationFile = sprintf('%s/%sRepository.php', $destinationDir, $name);
            $srcFile = sprintf(
                '%s/Base%sRepository.php',
                $bundleMetadata->getOrmMetadata()->getRepositoryDirectory(),
                $name
            );

            if (!is_file($srcFile)) {
                $output->writeln(sprintf('   ! <info>%sRepository</info>', $name));
                continue;
            }

            if (is_file($destinationFile)) {
                $output->writeln(sprintf('   ~ <info>%sRepository</info>', $name));
            } else {
                $output->writeln(sprintf('   + <info>%sRepository</info>', $name));

                $extendedNamespace = $bundleMetadata->getExtendedNamespace() . '\\' .
                    $bundleMetadata->getExtendedRepositoryNamespace();

                $namespace = $bundleMetadata->getNamespace() . '\\' . $bundleMetadata->getRepositoryNamespace();

                $string = Mustache::replace($this->getEntityRepositoryTemplate(), array(
                    'extended_namespace'    => $extendedNamespace,
                    'name'                  => $name,
                    'namespace'             => $namespace,
                ));

                if (!is_dir($destinationDir)) {
                    mkdir($destinationDir, 0775, true);
                }

                file_put_contents($destinationFile, $string);
            }
        }
    }

    /**
     * @return string
     */
    public function getEntityTemplate()
    {
        return $this->entityTemplate;
    }

    /**
     * @return string
     */
    public function getEntityRepositoryTemplate()
    {
        return $this->entityRepositoryTemplate;
    }
}
