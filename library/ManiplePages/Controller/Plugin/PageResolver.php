<?php

/**
 * Controller plugin which intercepts failed routing attempts and 404 dispatch errors
 * and attempts redirect to page view if requestUri matches any existing slug.
 */
class ManiplePages_Controller_Plugin_PageResolver extends Zend_Controller_Plugin_Abstract
{
    const className = __CLASS__;

    /**
     * @var bool
     */
    protected $_throwExceptions;

    /**
     * @var int
     */
    protected $_exceptionCount;

    /**
     * @var ManiplePages_Model_Page|null
     */
    protected $_resolvedPage;

    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        $this->_captureExceptions();
    }

    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $exception = $this->_restoreExceptions();

        /** @var Zend_Controller_Router_Rewrite $router */
        $router = Zend_Controller_Front::getInstance()->getRouter();

        if (
            ($exception || $router->getCurrentRouteName() === 'default')
            && $this->_resolvePage($request)
        ) {
            $this->_clearExceptions();
            return;
        }

        if ($exception && Zend_Controller_Front::getInstance()->throwExceptions()) {
            throw $exception;
        }
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        if ($this->_resolvedPage) {
            return;
        }

        $this->_captureExceptions();
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        if ($this->_resolvedPage) {
            return;
        }

        $exception = $this->_restoreExceptions();

        if ($exception) {
            if ($exception->getCode() === 404 && $this->_resolvePage($request)) {
                $this->_clearExceptions();
                return;
            }
            if (Zend_Controller_Front::getInstance()->throwExceptions()) {
                throw $exception;
            }
        }
    }

    protected function _captureExceptions()
    {
        $frontController = Zend_Controller_Front::getInstance();
        $this->_throwExceptions = $frontController->throwExceptions();

        // suppress throwing routing or dispatch exceptions, remember current number
        // of exceptions already thrown, it will be used for determining if this plugin
        // should throw last exception during routeShutdown or postDispatch phase
        $frontController->throwExceptions(false);

        $this->_exceptionCount = count($this->_response->getException());
    }

    /**
     * @return Exception|null
     */
    protected function _restoreExceptions()
    {
        $frontController = Zend_Controller_Front::getInstance();
        $frontController->throwExceptions($this->_throwExceptions);

        $exceptions = $this->_response->getException();
        $exceptionThrown = count($exceptions) - $this->_exceptionCount > 0;

        return $exceptionThrown ? end($exceptions) : null;
    }

    protected function _clearExceptions()
    {
        // A little hacky, but there is no other way to clear exceptions from the response
        $responseRef = new ReflectionObject($this->_response);
        $responseRef->getProperty('_exceptions')->setValue($this->_response, []);
    }

    protected function _resolvePage(Zend_Controller_Request_Abstract $request)
    {
        if (!$request instanceof Zend_Controller_Request_Http) {
            return null;
        }

        // route was not matched - try to check if any page slug matches
        /** @var ManiplePages_Repository_PageRepository $pageRepository */
        $pageRepository = Zend_Controller_Front::getInstance()
            ->getParam('bootstrap')
            ->getResource(ManiplePages_Repository_PageRepository::className);

        $requestUri = $request->getRequestUri();
        $baseUrl = $request->getBaseUrl();

        // remove baseUrl from requestUri
        if (substr($requestUri, 0, strlen($baseUrl)) === $baseUrl) {
            $requestUri = substr($requestUri, strlen($baseUrl));
        }

        $slug = trim(strtok($requestUri, '?'), '/');
        $page = $pageRepository->getPageBySlug($slug);
        $this->_resolvedPage = $page;

        if ($page) {
            $request->setModuleName('maniple-pages');
            $request->setControllerName('pages');
            $request->setActionName('view');
            $request->setParam('page', $page);
            $request->setDispatched(false);
            return true;
        }

        return false;
    }

    /**
     * @return ManiplePages_Model_Page|null
     */
    public function getResolvedPage()
    {
        return $this->_resolvedPage;
    }
}
