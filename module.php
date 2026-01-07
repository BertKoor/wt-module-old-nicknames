<?php

/**
 * Copyright (C) 2025 BertKoor.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details:
 * <https://www.gnu.org/licenses/>
 */

declare(strict_types=1);

namespace BertKoor\WtModule\OldNicknames;

use Composer\Autoload\ClassLoader;
use Fisharebest\Webtrees\Services\DataFixService;

$loader = new ClassLoader();
$loader->addPsr4('BertKoor\\WtModule\\OldNicknames\\', __DIR__);
$loader->register();

return new OldNicknames(new DataFixService());