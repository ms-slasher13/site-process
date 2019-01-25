<?php

namespace Consolidation\SiteProcess\Transport;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Process\Process;
use Consolidation\SiteProcess\Util\RealtimeOutputHandler;
use Consolidation\SiteProcess\Util\Escape;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Consolidation\SiteAlias\AliasRecord;
use Consolidation\SiteProcess\Util\Shell;
use Consolidation\Config\ConfigInterface;

/**
 * DockerComposeTransport knows how to wrap a command such that it executes
 * on a Docker Compose service.
 */
class DockerComposeTransport implements TransportInterface
{
    protected $tty;
    protected $siteAlias;
    protected $cd;
    protected $config;

    public function __construct(AliasRecord $siteAlias, ConfigInterface $config)
    {
        $this->siteAlias = $siteAlias;
        $this->config = $config;
    }

    /**
     * inheritdoc
     */
    public function configure(Process $process)
    {
        $this->tty = $process->isTty();
    }

    /**
     * inheritdoc
     */
    public function wrap($args)
    {
        $transport = ['docker-compose', 'exec'];
        $transportOptions = $this->getTransportOptions();
        $commandToExecute = $this->getCommandToExecute($args);

        return array_merge(
            $transport,
            $transportOptions,
            $commandToExecute
        );
    }

    /**
     * @inheritdoc
     */
    public function addChdir($cd, $args)
    {
        $this->cd = $cd;
        return $args;
    }

    /**
     * getTransportOptions returns the transport options for the tranport
     * mechanism itself
     */
    protected function getTransportOptions()
    {
        $transportOptions[] = $this->siteAlias->getConfig($this->config, 'docker.service', '');
        if ($options = $this->siteAlias->getConfig($this->config, 'docker.exec.options', '')) {
            array_unshift($transportOptions, Shell::preEscaped($options));
        }
        if (!$this->tty) {
            array_unshift($transportOptions, '-T');
        }
        if ($this->cd) {
            $transportOptions = array_merge(['--workdir', $this->cd], $transportOptions);
        }
        return array_filter($transportOptions);
    }

    /**
     * getCommandToExecute processes the arguments for the command to
     * be executed such that they are appropriate for the transport mechanism.
     *
     * Nothing to do for this transport.
     */
    protected function getCommandToExecute($args)
    {
        return $args;
    }
}
