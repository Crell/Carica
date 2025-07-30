<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

use Crell\Carica\ActionMetadata;
use Crell\Carica\ExplicitActionMetadata;
use Crell\Carica\File;
use Crell\Carica\Point;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

class ActionDispatcherTest extends TestCase
{
    public static function actionDispatcherExamples(): \Generator
    {
        yield 'basic' => [
            'url' => '/foo',
            'action' => fn() => new Response(200, body: 'success'),
            'arguments' => [],
            'meta' => new ExplicitActionMetadata([], null, null),
            'expectedResponseBody' => 'success',
            'expectedStatus' => 200,
        ];

        // Nyholm/UploadFile requires a real file.
        $dummyFileName = tempnam(directory: sys_get_temp_dir(), prefix: 'fake');
        file_put_contents($dummyFileName, 'success file');

        yield 'with one file' => [
            'url' => '/foo',
            'action' => fn(#[File] UploadedFileInterface $file) => new Response(200, body: $file->getStream()->getContents()),
            'arguments' => [],
            'meta' => new ExplicitActionMetadata(['file' => UploadedFileInterface::class], uploadedFileParameters: ['file' => ['file']]),
            'expectedResponseBody' => 'success file',
            'expectedStatus' => 200,
            'files' => [
                'file' => new UploadedFile($dummyFileName, 12, UPLOAD_ERR_OK),
            ],
        ];

        yield 'with one nested file' => [
            'url' => '/foo',
            'action' => fn(#[File(['files', 'bar', 'baz'])] UploadedFileInterface $file) => new Response(200, body: $file->getStream()->getContents()),
            'arguments' => [],
            'meta' => new ExplicitActionMetadata(['file' => UploadedFileInterface::class], uploadedFileParameters: ['file' => ['files', 'bar', 'baz']]),
            'expectedResponseBody' => 'success file',
            'expectedStatus' => 200,
            'files' => [
                'files' => ['bar' => ['baz' => new UploadedFile($dummyFileName, 12, UPLOAD_ERR_OK)]],
            ],
        ];

        yield 'no renderer' => [
            'url' => '/foo',
            'action' => fn() => new Point(3, 5),
            'arguments' => [],
            'meta' => new ExplicitActionMetadata([], null, null),
            'expectedException' => ActionResultNotRendered::class,
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @phpstan-param class-string<\Throwable> $expectedException
     * @param array<string, mixed> $files
     */
    #[Test, DataProvider('actionDispatcherExamples')]
    public function actionDispatcher(
        string $url,
        \Closure $action,
        array $arguments = [],
        ?ActionMetadata $meta = null,
        string $expectedResponseBody = '',
        int $expectedStatus = 200,
        ?string $expectedException = null,
        ?array $files = null,
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $dispatcher = new ActionDispatcher();

        $routeResult = new RouteSuccess($action, $arguments, $meta);

        $request = new ServerRequest('GET', $url)
            ->withAttribute(RouteResult::class, $routeResult);

        if ($files) {
            $request = $request->withUploadedFiles($files);
        }

        $response = $dispatcher->handle($request);

        self::assertEquals($expectedResponseBody, $response->getBody()->getContents());
        self::assertEquals($expectedStatus, $response->getStatusCode());
    }
}
