<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\JobInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\SnapshotInterface;
use Badoo\LiveProfilerUI\Interfaces\StorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class LiveProfilerUI
{
    /** @var Container|null */
    protected $Container;

    /**
     * @return Container
     * @throws \Exception
     */
    public function getContainer() : Container
    {
        if (null === $this->Container) {
            $this->Container = new ContainerBuilder();
            $env_config_path = getenv('AGGREGATOR_CONFIG_PATH');
            $config_path = !empty($env_config_path) && file_exists($env_config_path)
                ? $env_config_path
                : __DIR__ . '/../../config/services.yaml';
            $DILoader = new YamlFileLoader($this->Container, new FileLocator(\dirname($config_path)));
            $DILoader->load(basename($config_path));
        }

        return $this->Container;
    }

    /**
     * @param string $page_name
     * @return object
     * @throws \Exception
     */
    public function getPage(string $page_name)
    {
        try {
            if (!$this->getContainer()->has($page_name)) {
                throw new \InvalidArgumentException('Page not found');
            }

            return $this->getContainer()->get($page_name);
        } catch (\Throwable $Ex) {
            echo $this->getContainer()
                ->get('view')
                ->fetchFile('error', ['error' => $Ex->getMessage()]);
            exit;
        }
    }

    /**
     * @return Aggregator
     * @throws \Exception
     */
    public function getAggregator() : Aggregator
    {
        /** @var Aggregator $Aggregator */
        $Aggregator = $this->getContainer()->get('aggregator');

        return $Aggregator;
    }

    /**
     * @return StorageInterface
     * @throws \Exception
     */
    public function getSourceStorage() : StorageInterface
    {
        /** @var StorageInterface $Storage */
        $Storage = $this->getContainer()->get('source_storage');

        return $Storage;
    }

    /**
     * @return StorageInterface
     * @throws \Exception
     */
    public function getAggregatorStorage() : StorageInterface
    {
        /** @var StorageInterface $Storage */
        $Storage = $this->getContainer()->get('aggregator_storage');

        return $Storage;
    }

    /**
     * @return SnapshotInterface
     * @throws \Exception
     */
    public function getSnapshotDataProvider() : SnapshotInterface
    {
        /** @var SnapshotInterface $SnapshotDataProvider */
        $SnapshotDataProvider = $this->getContainer()->get('snapshot');

        return $SnapshotDataProvider;
    }

    /**
     * @return JobInterface
     * @throws \Exception
     */
    public function getJobDataProvider() : JobInterface
    {
        /** @var JobInterface $JobDataProvider */
        $JobDataProvider = $this->getContainer()->get('job');

        return $JobDataProvider;
    }

    /**
     * @return LoggerInterface
     * @throws \Exception
     */
    public function getLogger() : LoggerInterface
    {
        /** @var LoggerInterface $Logger */
        $Logger = $this->getContainer()->get('logger');

        return $Logger;
    }

    /**
     * @return FieldList
     * @throws \Exception
     */
    public function getFieldService() : FieldList
    {
        /** @var FieldList $FieldList */
        $FieldList = $this->getContainer()->get('fields');

        return$FieldList;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getCallsCountField() : string
    {
        return $this->getContainer()->getParameter('aggregator.calls_count_field');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getDefaultApp() : string
    {
        return $this->getContainer()->getParameter('aggregator.default_app');
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isUseJobsInAggregation() : bool
    {
        return (bool)$this->getContainer()->getParameter('aggregator.use_jobs_in_aggregation');
    }
}
