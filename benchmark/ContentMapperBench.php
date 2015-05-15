<?php

namespace Sulu\Benchmark;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use PhpBench\Benchmark;
use AppKernel;
use PhpBench\Benchmark\Iteration;

/**
 * Benchmarking class for the content mapper
 * Reuses the sulu test case class
 */
class ContentMapperBench extends SuluTestCase implements Benchmark
{
    private $contentMapper;
    private $session;

    public static function createKernel(array $options = array())
    {
        return new AppKernel('prod', true);
    }

    public function init()
    {
        $this->initPhpcr();
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->session = $this->getContainer()->get('doctrine_phpcr.default_session');
    }

    public function loadData(Iteration $iteration)
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $structure = $this->contentMapper->save($data, 'overview', 'sulu_io', 'de', 1);
        $iteration->setParameter('uuid', $structure->getUuid());
        $this->session->refresh(false);
    }

    /**
     * @description Save content
     * @beforeMethod init
     * @iterations 10
     */
    public function benchSave()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->contentMapper->save($data, 'overview', 'sulu_io', 'de', 1);
    }

    /**
     * @description Load a single record
     * @beforeMethod init
     * @beforeMethod loadData
     * @iterations 10
     */
    public function benchLoad(Iteration $iteration)
    {
        $this->contentMapper->load(
            $iteration->getParameter('uuid'),
            'sulu_io', 'de'
        );
    }

    /**
     * @description Load tree by path
     * @beforeMethod init
     * @beforeMethod loadData
     * @iterations 10
     */
    public function benchLoadTreeByPath()
    {
        $this->contentMapper->loadTreeByPath('/', 'de', 'sulu_io');
    }

    /**
     * @description Load by SQL2
     * @beforeMethod init
     * @beforeMethod loadData
     */
    public function benchLoadBySql2()
    {
        $sql2 = 'SELECT * FROM [nt:unstructured] WHERE [jcr:mixinTypes] = "sulu:page"';
        $this->contentMapper->loadBySql2($sql2, 'de', 'sulu_io');
    }
}
