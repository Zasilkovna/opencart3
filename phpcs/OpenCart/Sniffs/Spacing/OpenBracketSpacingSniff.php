<?php
/**
 * Checks that there is no white space after an opening bracket, for "(" and "{".
 * Square Brackets are handled by Squiz_Sniffs_Arrays_ArrayBracketSpacingSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 * @Licence   http://www.gnu.org/licenses/gpl-2.0.html
 * source: https://github.com/opencart/opencart/blob/3.0.x.x_Maintenance/tests/phpcs/OpenCart/Sniffs/Spacing/OpenBracketSpacingSniff.php
 */

use \PHP_CodeSniffer\Files\File;
use \PHP_CodeSniffer\Sniffs\Sniff;

class OpenCart_Sniffs_Spacing_OpenBracketSpacingSniff implements Sniff {
	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = [
		'PHP',
		'JS',
	];


	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [
			T_OPEN_CURLY_BRACKET,
			T_OPEN_PARENTHESIS,
		];

	}//end register()

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param integer $stackPtr  The position of the current token
	 *                           in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		// Ignore curly brackets in javascript files.
		if ($tokens[$stackPtr]['code'] === T_OPEN_CURLY_BRACKET
			&& $phpcsFile->tokenizerType === 'JS'
		) {
			return;
		}

		if (isset($tokens[($stackPtr + 1)]) === true
			&& $tokens[($stackPtr + 1)]['code'] === T_WHITESPACE
			&& strpos($tokens[($stackPtr + 1)]['content'], $phpcsFile->eolChar) === false
		) {
			$error = 'There should be no white space after an opening "%s"';
			$phpcsFile->addError(
				$error,
				($stackPtr + 1),
				'OpeningWhitespace',
				[$tokens[$stackPtr]['content']]
			);
		}

	}//end process()
}//end class
