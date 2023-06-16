<?php
/**
 * Warns about TODO comments.
 */

namespace Packetery\Sniffs\Generic\Commenting;

use \PHP_CodeSniffer\Files\File;
use \PHP_CodeSniffer\Sniffs\Sniff;
use \PHP_CodeSniffer\Util\Tokens;

class EmptyTodoSniff implements Sniff {

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
		return array_diff(Tokens::$commentTokens, Tokens::$phpcsCommentTokens);
	}

	/**
	 * Processes this sniff, when one of its tokens is encountered.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param integer  $stackPtr  The position of the current token
	 *                            in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$content = $tokens[$stackPtr]['content'];
		$matches = [];
		preg_match('/(?:\A|[^\p{L}]+)todo(?::\s*$|\Z)/ui', $content, $matches);
		if (empty($matches) === false) {
			// Clear whitespace and some common characters not required at
			// the end of a to-do message to make the warning more informative.
			$type = 'EmptyTodoFound';
			$todoMessage = trim($matches[1]);
			$todoMessage = trim($todoMessage, '-:[](). ');
			$error = 'Comment contains empty TODO ';
			$data = [$todoMessage];
			if ($todoMessage !== '') {
				$error .= ' "%s"';
			}

			$phpcsFile->addError($error, $stackPtr, $type, $data);
		}
	}
}
