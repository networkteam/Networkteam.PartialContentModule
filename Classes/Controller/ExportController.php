<?php

declare(strict_types=1);

namespace Networkteam\PartialContentModule\Controller;

use Exception;
use Muensmedia\PartialContentExport\Service\NodePathNormalizerService;
use Muensmedia\PartialContentExport\Service\PartialContentExportService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Utility\Environment;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Exception as NeosException;
use Neos\Utility\Files;
use Throwable;
use ZipArchive;

#[Flow\Scope('singleton')]
class ExportController extends AbstractModuleController
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
    protected $supportedMediaTypes = ['application/json', 'text/html'];

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'html' => FusionView::class,
        'json' => JsonView::class,
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
     * @var Environment
     */
    protected $environment;

    /**
     * @Flow\Inject
     * @var PartialContentExportService
     */
    protected $partialContentExportService;

    /**
     * Shows the export interface with its options and actions
     */
    public function exportAction(string $siteName, string $source, ?string $title = null): void
    {
        $node = $this->nodePathNormalizerService->getNodeFromPathOrIdentifier( $siteName, $source );
        if (!$node)
            throw new NeosException('Error: The given source node could not be found.', 1749801801);

        if (!empty($title)) {
            $dirName = $this->sanitizeTitle($title);
        } else {
            $dirName = 'export-' . ($node->getProperty('uriPathSegment') ?? $node->getIdentifier());
        }
        $filePath = rtrim($this->environment->getPathToTemporaryDirectory(), '/') . '/Networkteam.PartialContentModule/'. $dirName .'/export.xml';

        try {
            $this->partialContentExportService->exportToFile(
                source: (string)$node->findNodePath(),
                pathAndFilename: $filePath
            );
            $archivePath = $this->createArchive(dirname($filePath));
            $this->downloadZipFile($archivePath);
        } catch (Throwable $throwable) {
            // @todo
            throw $throwable;
        }
    }

    protected function createArchive(string $dirName): string
    {
        $zipFilePath = $dirName . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception("Cannot open $zipFilePath", 1749801802);
        }

        foreach(Files::getRecursiveDirectoryGenerator($dirName) as $file) {
            $zip->addFile($file, str_replace(dirname($dirName) . '/', '', $file));
        }

        $zip->close();
        return $zipFilePath;
    }

    protected function downloadZipFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        header('Pragma: no-cache');
        header('Content-type: application/zip');
        header('Content-Length: ' . strlen($content));
        header('Content-Disposition: attachment; filename=' . basename($filePath));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        echo $content;
        unlink($filePath);
        Files::removeDirectoryRecursively(dirname($filePath));
        exit;
    }

    protected function sanitizeTitle(string $fileName): string
    {
        // Replace any character that is not A-Z, a-z, 0-9, dot, dash or underscore with underscore
        $fileName = preg_replace('/[^A-Za-z0-9\.\-_]/', '-', $fileName);
        // Prevent multiple dots at start (hidden files or traversal)
        $fileName = ltrim($fileName, '.');
        return $fileName;
    }
}
