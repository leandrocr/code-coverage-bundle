<?php
/**
 * Code Coverage Request Listener
 *
 * @copyright 2013 Anthon Pang
 * @license BSD-2-Clause
 */

namespace VIPSoft\CodeCoverageBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use VIPSoft\CodeCoverageBundle\Service\CodeCoverageRepository;
use PHP_CodeCoverage_Driver as DriverInterface;

/**
 * Code coverage request listener
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */
class RequestListener
{
    /**
     * @var \VIPSoft\CodeCoverageBundle\Service\CodeCoverageRepository
     */
    private $repository;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * Constructor
     *
     * @param \VIPSoft\CodeCoverageBundle\Service\CodeCoverageRepository $repository
     * @param DriverInterface                                            $driver
     */
    public function __construct(CodeCoverageRepository $repository, DriverInterface $driver = null)
    {
        $this->repository = $repository;
        $this->driver     = $driver;
    }

    /**
     * Start collecting at the beginning of a request
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()
            || ! $this->driver
            || ! $this->repository->isEnabled()
        ) {
            return;
        }

        #var_export($this->driver);
        #die;

        $this->driver->start();
    }

    /**
     * Stop collecting at the end of a request
     *
     * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {

        #var_export($event);
        #var_export($this->driver);

        if ( ! $this->driver
            || ! $this->repository->isEnabled()
        ) {
            return;
        }

        $coverage = $this->driver->stop();

        $this->repository->addCoverage($coverage);
    }
}
