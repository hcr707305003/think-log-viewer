<?php
namespace test;

use PHPUnit\Framework\TestCase;
use Shiroi\ThinkLogViewer\ViewPager;

class ViewPagerTest extends TestCase
{
    /**
     * 调试
     * @doesNotPerformAssertions
     */
    public function testViewPager() {
        $pager = new ViewPager();
        $pager->index();
        echo 111;

    }
}