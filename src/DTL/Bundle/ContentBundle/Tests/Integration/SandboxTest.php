<?php

namespace DTL\Bundle\ContentBundle\Tests\Integration;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use DTL\Bundle\ContentBundle\Document\Page;
use DTL\Bundle\ContentBundle\Tests\Resources\Types\OverviewType;
use PHPCR\Util\NodeHelper;
use DTL\Bundle\ContentBundle\Document\PageDocument;

class SandboxTest extends SuluTestCase
{
    public function setUp()
    {
        $this->dm = $this->getContainer()->get('doctrine_phpcr.odm.document_manager');
        $parentPath = '/cmf/sulu_io/contents';
        $node = NodeHelper::createPath($this->dm->getPhpcrSession(), $parentPath);

        foreach ($node->getNodes() as $node) {
            $node->remove();
        }
        $this->dm->getPhpcrSession()->save();

        $parent = $this->dm->find(null, $parentPath);
        $this->createDocuments($parent);
    }

    private function createDocuments($parent)
    {
        $page1 = new PageDocument();
        $page1->setName('page');
        $page1->setParent($parent);
        $page1->setTitle('Gastronomy');
        $page1->setFormType('overview');
        $page1->setContentData(array(
            'title' => 'bar',
        ));

        $this->dm->persist($page1);
        $this->dm->flush();
        $this->page1 = $page1;
    }

    public function testPersist()
    {
        $page = $this->dm->find(null, '/cmf/sulu_io/contents/page');

        $resolver = $this->getContainer()->get('dtl_content.form.content_view_resolver');
        $contentView = $resolver->resolve($page);
        var_dump($contentView);die();;
    }
}
