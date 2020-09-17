<?php

namespace Toyokumo\SessionBundle;

use Exception;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Proxy\SessionHandlerProxy;

/**
 * Class DualSessionHandlerProxy
 * Extension of SessionHandlerProxy to switch handlers on connection failure
 * (https://symfony.com/doc/current/session/proxy_examples.html)
 *
 * @package SessionBundle
 */
class DualSessionHandlerProxy extends SessionHandlerProxy
{
    protected SessionHandlerInterface $subHandler;

    /**
     * SessionHandler constructor.
     * @param SessionHandlerInterface $mainHandler
     * @param SessionHandlerInterface $subHandler
     */
    public function __construct(SessionHandlerInterface $mainHandler, SessionHandlerInterface $subHandler)
    {
        $this->subHandler = $subHandler;
        parent::__construct($mainHandler);
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        return parent::close() && (bool) $this->subHandler->close();
    }

    /**
     * @param $sessionId
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        try {
            return parent::destroy($sessionId);
        } catch (Exception $e) {
            return (bool) $this->subHandler->destroy($sessionId);
        }
    }

    /**
     * @param int $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime): bool
    {
        $succeed = true;

        try {
            $succeed &= parent::gc($maxlifetime);
        } catch (Exception $e) {
            $succeed = false;
        }

        try {
            $succeed &= (bool) $this->subHandler->gc($maxlifetime);
        } catch (Exception $e) {
            $succeed = false;
        }

        return $succeed;
    }

    /**
     * @param string $savePath
     * @param string $name
     * @return bool
     */
    public function open($savePath, $name): bool
    {
        return parent::open($savePath, $name) && (bool) $this->subHandler->open($savePath, $name);
    }

    /**
     * @param string $sessionId
     * @return string
     */
    public function read($sessionId): string
    {
        try {
            return parent::read($sessionId);
        } catch (Exception $e) {
            return (string) $this->subHandler->read($sessionId);
        }
    }

    /**
     * @param string $sessionId
     * @param string $sessionData
     * @return bool
     */
    public function write($sessionId, $sessionData): bool
    {
        try {
            return parent::write($sessionId, $sessionData);
        } catch (Exception $e) {
            return (bool) $this->subHandler->write($sessionId, $sessionData);
        }
    }

    /**
     * @param string $sessionId
     * @return bool
     */
    public function validateId($sessionId): bool
    {
        try {
            return parent::validateId($sessionId);
        } catch (Exception $e) {
            return !$this->subHandler instanceof SessionUpdateTimestampHandlerInterface ||
                $this->subHandler->validateId($sessionId);
        }
    }

    /**
     * @param string $sessionId
     * @param string $sessionData
     * @return bool
     */
    public function updateTimestamp($sessionId, $sessionData): bool
    {
        try {
            return parent::updateTimestamp($sessionId, $sessionData);
        } catch (Exception $e) {
            return $this->subHandler instanceof SessionUpdateTimestampHandlerInterface
                ? $this->subHandler->updateTimestamp($sessionId, $sessionData)
                : $this->subHandler->write($sessionId, $sessionData);
        }
    }
}
