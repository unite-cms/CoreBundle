<?php

namespace UniteCMS\CoreBundle\Domain;

use LogicException;

class DomainManager
{

    /**
     * @var string $schemaConfigDir
     */
    protected $schemaConfigDir;

    /**
     * @var array
     */
    protected $domainConfig = [];

    /**
     * @var Domain
     */
    protected $domain = null;

    public function __construct(string $schemaConfigDir, array $domainConfig = [])
    {
        $this->schemaConfigDir = $schemaConfigDir;
        $this->domainConfig = $domainConfig;
    }

    /**
     * @param Domain|null $domain
     * @return $this
     */
    public function setCurrentDomain(?Domain $domain) : self {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @param string $schemaFile
     * @return null|string
     */
    public function getSchemaFromFile(string $schemaFile) : ?string {

        if(!file_exists($schemaFile)) {
            return null;
        }

        $pathInfo = pathinfo($schemaFile);

        if(empty($pathInfo['extension']) || $pathInfo['extension'] != 'graphql') {
            return null;
        }

        return file_get_contents($schemaFile);
    }

    /**
     * @param string $dir
     * @return array
     */
    public function findSchemaFilesInDir(string $dir) : array {
        $schemaFiles = [];

        if(is_dir($dir)) {

            if(substr($dir, -1, 1) !== '/') {
                $dir .= '/';
            }

            foreach (scandir($dir) as $file) {
                $filePath = $dir . $file;

                if($content = $this->getSchemaFromFile($filePath)) {
                    $schemaFiles[$filePath] = $content;
                }
            }
        }

        return $schemaFiles;
    }

    /**
     * @param array $schemaConfig
     * @return array
     */
    protected function normalizeSchemaConfig(array $schemaConfig) : array {

        $normalizedConfig = [];

        foreach($schemaConfig as $config) {

            // If this is a direct schema string
            if(!file_exists($config)) {
                $normalizedConfig[] = $config;
                continue;
            }

            // If this is a schema file
            if($content = $this->getSchemaFromFile($config)) {
                $normalizedConfig[$config] = $content;
                continue;
            }

            // If this is a folder, add all .graphql schema files.
            $normalizedConfig = array_merge($normalizedConfig, $this->findSchemaFilesInDir($config));
        }

        return $normalizedConfig;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setCurrentDomainFromConfigId(string $id) : self {

        if(!empty($this->domain)) {
            return $this;
        }

        if(!isset($this->domainConfig[$id])) {
            throw new LogicException(sprintf('No domain with id "%s" found in domain configuration.', $id));
        }

        $config = $this->domainConfig[$id];

        $this->setCurrentDomain(new Domain(
            $id,
            $config['content_manager'],
            $config['user_manager'],
            $config['logger'],
            $this->normalizeSchemaConfig($config['schema']),
            $config['jwt_ttl_short_living'],
            $config['jwt_ttl_long_living']
        ));

        return $this;
    }

    /**
     * @return Domain|null
     */
    public function current() : Domain {

        if(empty($this->domain)) {

            // If only one domain is configured, automatically use it.
            if(count($this->domainConfig) === 1) {
                $this->setCurrentDomainFromConfigId(array_key_first($this->domainConfig));
                return $this->domain;
            }

            throw new LogicException('You tried to access the current domain before it was set.');
        }

        return $this->domain;
    }

    /**
     * @return string
     */
    public function getSchemaConfigDir() : string {
        return $this->schemaConfigDir;
    }
}
