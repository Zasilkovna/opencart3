<?php

namespace Packetery\Sniffs\Annotations;

use \PHP_CodeSniffer\Files\File;
use \PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;

class MethodAnnotationSniff implements Sniff {
    const REQUIRED_ANNOTATIONS = ['@return'];

    /**
     * @return array
     */
    public function register() {
        return [T_FUNCTION];
    }

    /**
     * @param File $phpcsFile
     * @param integer $stackPtr
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr) {
        if (!FunctionHelper::isMethod($phpcsFile, $stackPtr)) {
            return;
        }
        $methodName = FunctionHelper::getName($phpcsFile, $stackPtr);
        $annotations = AnnotationHelper::getAnnotations($phpcsFile, $stackPtr);
        $paramNames = FunctionHelper::getParametersNames($phpcsFile, $stackPtr);
        $paramAnnotations = FunctionHelper::getParametersAnnotations($phpcsFile, $stackPtr);

        foreach ($paramNames as $paramName) {
            $annotationFound = false;
            foreach ($paramAnnotations as $paramAnnotation) {
                if ($paramAnnotation->getName() === '@param'
                    && $paramAnnotation->getValue()->parameterName === $paramName) {
                    $annotationFound = true;
                    break;
                }
            }
            if (!$annotationFound) {
                $phpcsFile->addError(
                    sprintf(
                        'Method %s is missing annotation for parameter %s',
                        $methodName,
                        $paramName
                    ),
                    $stackPtr,
                    'MissingParamAnnotation'
                );
            }
        }

        $missingAnnotations = [];
        $presentAnnotationNames = [];
        foreach ($annotations as $annotation) {
            $presentAnnotationNames[] = $annotation->getName();
        }

        foreach (self::REQUIRED_ANNOTATIONS as $requiredAnnotation) {
            if ($requiredAnnotation === '@return' && $methodName === '__construct') {
                continue;
            }
            if (!in_array($requiredAnnotation, $presentAnnotationNames, true)) {
                $missingAnnotations[] = $requiredAnnotation;
            }
        }

        if (!empty($missingAnnotations)) {
            $phpcsFile->addError(
                sprintf(
                    'Method %s is missing %s annotation%s',
                    $methodName,
                    implode(', ', $missingAnnotations),
                    (count($missingAnnotations) > 1) ? 's' : ''
                ),
                $stackPtr,
                'MissingRequiredAnnotation'
            );
        }
    }
}
