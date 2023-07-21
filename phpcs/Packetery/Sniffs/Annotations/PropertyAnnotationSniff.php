<?php

namespace Packetery\Sniffs\Annotations;

use \PHP_CodeSniffer\Files\File;
use \PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\PropertyHelper;

class PropertyAnnotationSniff implements Sniff {
    /**
     * @return array
     */
    public function register() {
        return [T_VARIABLE];
    }

    /**
     * @param File $phpcsFile
     * @param integer $stackPtr
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();
        if (PropertyHelper::isProperty($phpcsFile, $stackPtr)) {
            $annotations = AnnotationHelper::getAnnotations($phpcsFile, $stackPtr);
            $presentAnnotationNames = [];
            foreach ($annotations as $annotation) {
                $presentAnnotationNames[] = $annotation->getName();
            }

            if (!in_array('@var', $presentAnnotationNames, true)) {
                $propertyName = $tokens[$stackPtr]['content'];
                $phpcsFile->addError(
                    sprintf('Property %s is missing @var annotation', $propertyName),
                    $stackPtr,
                    'MissingAnnotation'
                );
            }
        }
    }
}
