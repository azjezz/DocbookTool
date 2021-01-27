<?php

declare(strict_types=1);

namespace Roave\DocbookTool\Writer;

use Psr\Log\LoggerInterface;
use Roave\DocbookTool\DocbookPage;
use RuntimeException;
use Twig\Environment;
use function Safe\file_put_contents;
use function Safe\sprintf;
use function Safe\unlink;

class MultiplePdfFilesWriter
{
    public function __construct(
        private Environment $twig,
        private string $twigTemplate,
        private string $locationOfWkhtmltopdf,
        private string $pdfOutputPath,
        private LoggerInterface $logger
    ) {
    }

    /** @param DocbookPage[] $docbookPages */
    public function __invoke(array $docbookPages) : void
    {
        foreach ($docbookPages as $page) {
            if (! $page->shouldGeneratePdf()) {
                continue;
            }

            $this->logger->info(sprintf("Rendering %s.pdf ...\n", $page->slug()));

            $tmpHtmlFile = sys_get_temp_dir() . '/' . $page->slug() . '.html';
            $pdfFile     = $this->pdfOutputPath . '/' . $page->slug() . '.pdf';
            file_put_contents($tmpHtmlFile, $this->twig->render($this->twigTemplate, ['page' => $page]));

            exec(
                escapeshellcmd(implode(
                    ' ',
                    [
                        $this->locationOfWkhtmltopdf,
                        $tmpHtmlFile,
                        $pdfFile,
                    ]
                )),
                $output,
                $exitCode
            );

            unlink($tmpHtmlFile);

            if ($exitCode !== 0) {
                throw new RuntimeException('Failed to generate PDF. Output was: ' . implode("\n", $output));
            }
        }
    }
}