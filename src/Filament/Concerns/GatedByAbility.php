<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Concerns;

use Filagora\Core\Filament\Concerns\GatedByAbility as CoreGatedByAbility;

/**
 * @deprecated Use Filagora\Core\Filament\Concerns\GatedByAbility. Kept so code that imported it from
 *             here keeps working.
 */
trait GatedByAbility
{
    use CoreGatedByAbility;
}
