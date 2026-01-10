<?php

declare(strict_types=1);

namespace Revolution\Feedable\Core\Support;

/**
 * Atom manipulation helpers.
 */
class Atom extends RSS
{
    // RSSとはentryしか違いがないけど今後別の機能を追加した時用に別クラスにしておく
    protected const string ITEM_XPATH = '//entry';
}
