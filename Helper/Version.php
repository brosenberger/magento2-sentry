<?php

namespace JustBetter\Sentry\Helper;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Psr\Log\LoggerInterface;

/**
 * Deployment version of static files.
 */
class Version extends AbstractHelper
{
    /**
     * @var string
     */
    private $cachedValue;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param \Magento\Framework\App\State                                    $appState
     * @param \Magento\Framework\App\View\Deployment\Version\StorageInterface $versionStorage
     * @param DeploymentConfig|null                                           $deploymentConfig
     */
    public function __construct(
        private \Magento\Framework\App\State $appState,
        private \Magento\Framework\App\View\Deployment\Version\StorageInterface $versionStorage,
        ?DeploymentConfig $deploymentConfig = null
    ) {
        $this->deploymentConfig = $deploymentConfig ?: ObjectManager::getInstance()->get(DeploymentConfig::class);
    }

    /**
     * Retrieve deployment version of static files.
     *
     * @return string
     */
    public function getValue()
    {
        if (!$this->cachedValue) {
            $this->cachedValue = $this->readValue($this->appState->getMode());
        }

        return $this->cachedValue;
    }

    /**
     * Load or generate deployment version of static files depending on the application mode.
     *
     * @param string $appMode
     *
     * @return string
     */
    protected function readValue($appMode)
    {
        $result = $this->versionStorage->load();
        if (!$result) {
            if ($appMode == \Magento\Framework\App\State::MODE_PRODUCTION
                && !$this->deploymentConfig->getConfigData(
                    ConfigOptionsListConstants::CONFIG_PATH_SCD_ON_DEMAND_IN_PRODUCTION
                )
            ) {
                $this->getLogger()->critical('Can not load static content version.');

                throw new \UnexpectedValueException(
                    'Unable to retrieve deployment version of static files from the file system.'
                );
            }
            $result = $this->generateVersion();
            $this->versionStorage->save((string) $result);
        }

        return $result;
    }

    /**
     * Generate version of static content.
     *
     * @return int
     */
    private function generateVersion()
    {
        return time();
    }

    /**
     * Get logger.
     *
     * @return LoggerInterface
     */
    private function getLogger()
    {
        if ($this->logger == null) {
            $this->logger = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(LoggerInterface::class);
        }

        return $this->logger;
    }
}
