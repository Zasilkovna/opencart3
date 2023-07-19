<?php

namespace Packetery\Sniffs\Whitespace;

use \PHP_CodeSniffer\Files\File;
use \PHP_CodeSniffer\Sniffs\Sniff;

use SlevomatCodingStandard\Helpers\TokenHelper;

class BlankLineBeforeReturnSniff implements Sniff {

    /**
     * @return array
     */
    public function register() {
        return [T_RETURN];
    }

    /**
     * @param File $phpcsFile
     * @param integer $stackPtr
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();
        $returnLine = $tokens[$stackPtr]['line'];
        $previous = TokenHelper::findPreviousEffective($phpcsFile, $stackPtr-1);

        // Ignore return statements in closures.
        if ($tokens[$previous]['code'] === T_OPEN_CURLY_BRACKET) {
            return;
        }

        if ($tokens[$previous]['line'] === $returnLine - 1) {
            $fix = $phpcsFile->addFixableError(
                'Blank line before return statement is missing',
                $stackPtr,
                'MissingBlankLine'
            );
            if ($fix) {
                $phpcsFile->fixer->addNewline($previous);
            }
        }
    }
}
