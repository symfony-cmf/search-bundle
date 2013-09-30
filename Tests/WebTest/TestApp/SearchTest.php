<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2013 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Symfony\Cmf\Bundle\SearchBundle\Tests\WebTest\TestApp;

use Symfony\Cmf\Component\Testing\Functional\BaseTestCase;

class SearchTest extends BaseTestCase
{
    public function testPage()
    {
        $client = $this->createClient();
        $crawler = $client->request('get', $this->getContainer()->get('router')->generate('liip_search'));
        $resp = $client->getResponse();

        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertGreaterThanOrEqual(1, $crawler->filter('.quick_search')->count());
    }
}
