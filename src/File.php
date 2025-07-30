<?php

declare(strict_types=1);

namespace Crell\HttpTools;

/**
 * Flags a parameter as wanting a PSR-7 uploaded file passed to it.
 *
 * This attribute may only be used on a parameter typed to UploadedFileInterface.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class File extends ActionParameter
{
    /**
     * @var string[]
     */
    protected(set) array $treePath = [];

    /**
     * @param string|array<string> $treePath
     *   The path to the file in the uploaded files tree.
     *   In most cases, this will be just a single string
     *   for the uploaded file field name. However,
     *   PHP supports nested names for form fields, which
     *   can lead to a deeper $_FILES tree.  This path
     *   assumes the structure defined by PSR-7. Omitting this value
     *   defaults to the variable name.
     *
     * @see https://www.php-fig.org/psr/psr-7/#16-uploaded-files
     *
     * To use the examples from PSR-7, given this HTML:
     *
     * ```html
     * <input type="file" name="avatar" />
     * ```
     *
     * you would have a parameter:
     *
     * ```php
     * function foo(#[File('avatar')] UploadedFileInterface $foo) {}
     * // or
     * function foo(#[File] UploadedFileInterface $avatar) {}
     * ```
     *
     * Whereas for this HTML:
     *
     * ```html
     * <input type="file" name="my-form[details][avatar]" />
     * ```
     *
     * you would instead write:
     *
     * ```php
     * * function foo(#[File(['my-form',  'details', 'avatar'])] UploadedFileInterface $foo) {}
     * * ```
     *
     * @todo Multiple uploaded files with the same name are not yet supported.
     *
     */
    public function __construct(
        string|array $treePath = [],
    ) {
        $this->treePath = is_array($treePath) ? $treePath : [$treePath];
    }
}
