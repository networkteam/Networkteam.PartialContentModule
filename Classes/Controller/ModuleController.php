<?php

declare(strict_types=1);

namespace Networkteam\PartialContentModule\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Repository\SiteRepository;

#[Flow\Scope('singleton')]
class ModuleController extends AbstractModuleController
{
    /**
     * @var FusionView
     */
    protected $view;

    /**
     * @var string
     */
    protected $defaultViewObjectName = FusionView::class;

    /**
     * @var array
     */
    protected $supportedMediaTypes = ['text/html'];

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'html' => FusionView::class,
    ];

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected siteRepository $siteRepository;

    /**
     * Shows the module
     */
    public function indexAction(): void
    {
        $csrfToken = $this->securityContext->getCsrfProtectionToken();
        $this->view->assignMultiple([
            'csrfToken' => $csrfToken,
            'sites' => $this->siteRepository->findAll(),
            'flashMessages' => []
        ]);
    }

}
