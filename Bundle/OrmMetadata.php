<?php

namespace Sonata\EasyExtendsBundle\Bundle;

use Symfony\Component\Finder\Finder;

class OrmMetadata
{
    /**
     * @var string
     */
    protected $mappingEntityDirectory;

    /**
     * @var string
     */
    protected $extendedMappingEntityDirectory;

    /**
     * @var string
     */
    protected $entityDirectory;

    /**
     * @var string
     */
    protected $extendedEntityDirectory;

    /**
     * @var string
     */
    protected $extendedSerializerDirectory;

    /**
     * @var string
     */
    protected $repositoryDirectory;

    /**
     * @var string
     */
    protected $extendedRepositoryDirectory;

    /**
     * @param BundleMetadata $bundleMetadata
     */
    public function __construct(BundleMetadata $bundleMetadata)
    {
        $this->mappingEntityDirectory = sprintf(
            '%s/Resources/config/doctrine/',
            $bundleMetadata->getBundle()->getPath()
        );

        $this->extendedMappingEntityDirectory = sprintf(
            '%s/Resources/config/doctrine/',
            $bundleMetadata->getExtendedDirectory()
        );

        $this->extendedSerializerDirectory = sprintf(
            '%s/Resources/config/serializer',
            $bundleMetadata->getExtendedDirectory());

        $this->entityDirectory = sprintf(
            '%s/%s',
            $bundleMetadata->getBundle()->getPath(),
            $bundleMetadata->getEntityDirectory()
        );

        $this->repositoryDirectory = sprintf(
            '%s/%s',
            $bundleMetadata->getBundle()->getPath(),
            $bundleMetadata->getRepositoryDirectory()
        );

        $this->extendedEntityDirectory = sprintf(
            '%s/%s',
            $bundleMetadata->getExtendedDirectory(),
            $bundleMetadata->getExtendedEntityDirectory()
        );

        $this->extendedRepositoryDirectory = sprintf(
            '%s/%s',
            $bundleMetadata->getExtendedDirectory(),
            $bundleMetadata->getExtendedRepositoryDirectory()
        );
    }

    /**
     * @return string
     */
    public function getMappingEntityDirectory()
    {
        return $this->mappingEntityDirectory;
    }

    /**
     * @return string
     */
    public function getExtendedMappingEntityDirectory()
    {
        return $this->extendedMappingEntityDirectory;
    }

    /**
     * @return string
     */
    public function getEntityDirectory()
    {
        return $this->entityDirectory;
    }

    /**
     * @return string
     */
    public function getExtendedEntityDirectory()
    {
        return $this->extendedEntityDirectory;
    }

    /**
     * @return string
     */
    public function getExtendedSerializerDirectory()
    {
        return $this->extendedSerializerDirectory;
    }

    /**
     * @return array|\Iterator
     */
    public function getEntityMappingFiles()
    {
        try {
            $f = new Finder();
            $f->name('*.orm.xml.skeleton');
            $f->name('*.orm.yml.skeleton');
            $f->in($this->getMappingEntityDirectory());

            return $f->getIterator();
        } catch (\Exception $e) {
            return array();
        }
    }

    /**
     * @return array
     */
    public function getEntityNames()
    {
        $names = array();

        try {
            $f = new Finder();
            $f->name('*.orm.xml.skeleton');
            $f->name('*.orm.yml.skeleton');
            $f->in($this->getMappingEntityDirectory());

            foreach ($f->getIterator() as $file) {
                $name = explode('.', basename($file));
                $names[] = $name[0];
            }
        } catch (\Exception $e) {
        }

        return $names;
    }

    /**
     * @return array|\Iterator
     */
    public function getRepositoryFiles()
    {
        try {
            $f = new Finder();
            $f->name('*Repository.php');
            $f->in($this->getRepositoryDirectory());

            return $f->getIterator();
        } catch (\Exception $e) {
            return array();
        }
    }

    /**
     * @return string
     */
    public function getRepositoryDirectory()
    {
        return $this->repositoryDirectory;
    }

    /**
     * @return string
     */
    public function getExtendedRepositoryDirectory()
    {
        return $this->extendedRepositoryDirectory;
    }

}
