<?php

declare(strict_types=1);

namespace Packetery\Carrier;

/**
 * Vendor group identifiers attached to a carrier.
 *
 * These values are the carrier domain marker for which Packeta vendor types
 * a carrier supports. {@see ZBOX} is also the literal string sent to the
 * widget API as the `group` parameter for Z-Box vendors. {@see ZPOINT} is
 * a PHP-internal marker only — Z-Point is the widget API's implicit default,
 * so the `'zpoint'` value is never sent to the widget.
 */
class VendorGroup
{
	public const ZBOX = 'zbox';
	public const ZPOINT = 'zpoint';
}
