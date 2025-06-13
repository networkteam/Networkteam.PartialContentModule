<?php

declare(strict_types=1);

namespace Networkteam\PartialContentModule\Controller;

use Exception;
use Muensmedia\PartialContentExport\Service\NodePathNormalizerService;
use Muensmedia\PartialContentExport\Service\PartialContentExportService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Exception as NeosException;
use Neos\Utility\Files;
use Throwable;
use ZipArchive;

#[Flow\Scope('singleton')]
class ImportController extends AbstractModuleController
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
     * @var NodePathNormalizerService
     */
    protected $nodePathNormalizerService;

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var \Muensmedia\PartialContentExport\Service\PartialContentImportService
     */
    protected $partialContentImportService;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;




    public function uploadAction(PersistentResource $zipFile, string $targetPath): void
    {
        $localCopyPath = $zipFile->createTemporaryLocalCopy();
        $extractedPath = $localCopyPath . '_extracted';

        //TODO: unzip resorce
        $zip = new ZipArchive();
        $zip->open($localCopyPath);
        $zip->extractTo($extractedPath);
        $zip->close();

        //TODO: find path to xml file
        $xmlPath = sprintf(
            '%s/%s/export.xml',
            $extractedPath,
            pathinfo($zipFile->getFileName(), PATHINFO_FILENAME),
        );

        $xmlReader = new \XMLReader();
        if ($xmlReader->open($xmlPath, null, LIBXML_PARSEHUGE) === false) {
            throw new NeosException(sprintf('Error: XMLReader could not open "%s".', $xmlPath), 1749826123);
        }

        $this->partialContentImportService->findPartialImportRoot( $xmlReader );

        //TODO: validate targetPath
        $this->partialContentImportService->importFromXML($xmlReader, dirname($xmlPath), $targetPath, []);

        //TODO: cleanup - remove zipFile and extracted content
        Files::removeDirectoryRecursively($extractedPath);



        $this->addFlashMessage('Import successful');
        $this->redirect('index', 'Module');
    }
}
