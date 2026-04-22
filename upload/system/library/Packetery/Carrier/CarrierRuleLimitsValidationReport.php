<?php

declare(strict_types=1);

namespace Packetery\Carrier;

class CarrierRuleLimitsValidationReport
{
	private bool $isValid;

	private ?string $errorTranslationKey;

	private function __construct(bool $isValid, ?string $errorTranslationKey)
	{
		$this->isValid = $isValid;
		$this->errorTranslationKey = $errorTranslationKey;
	}

	public static function valid(): self
	{
		return new self(true, null);
	}

	public static function invalid(string $errorTranslationKey): self
	{
		return new self(false, $errorTranslationKey);
	}

	public function isValid(): bool
	{
		return $this->isValid;
	}

	public function getErrorTranslationKey(): ?string
	{
		return $this->errorTranslationKey;
	}
}
