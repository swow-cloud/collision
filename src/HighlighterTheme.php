<?php
/**
 * This file is part of SwowCloud
 * @license  https://github.com/swow-cloud/music-server/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Collision;

use SwowCloud\Highlighter\Theme\DefaultThemes\Minimalistic;

class HighlighterTheme extends Minimalistic
{
    public function getLineHighlightBgColor(): string
    {
        return 'none';
    }

    public function getLineNumberHighlightedBgColor(): string
    {
        return 'bg_red';
    }
}
