<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Functional\Application\ContentWorkflow;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Exception\NoRequestWorkflowException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Infrastructure\Sulu\Admin\ContentAdmin;
use Sulu\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactoryInterface;
use Sulu\Content\Tests\Application\DefaultRequestWorkflowKernel;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;

/**
 * The `default` workflow is the only one a template cannot select by tag: it applies to every
 * untagged template whose resource key is in its `resources` list, and it is what the create form
 * has to go by, because content that does not exist yet has no template decision behind it.
 */
#[CoversNothing]
class DefaultRequestWorkflowTest extends SuluTestCase
{
    use WorkflowTransitionRequestTrait;

    private ContentManagerInterface $contentManager;

    private WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository;

    protected static function getKernelClass(): string
    {
        return DefaultRequestWorkflowKernel::class;
    }

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->contentManager = static::getContainer()->get(ContentManagerInterface::class);
        $this->workflowTransitionRequestRepository = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class);
        $this->authenticateAsRequestCreator();
    }

    public function testUntaggedTemplateFallsBackToTheDefaultWorkflow(): void
    {
        $example = $this->createExampleAtDraft('example-2');

        $this->contentManager->applyTransition(
            $example,
            ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'],
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $request = $this->workflowTransitionRequestRepository->getOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]);

        $this->assertSame('default', $request->getWorkflowName());
    }

    public function testTemplateOptOutBeatsTheApplyingDefaultWorkflow(): void
    {
        $example = $this->createExampleAtDraft('example-no-workflow');

        $this->expectException(NoRequestWorkflowException::class);
        $this->contentManager->applyTransition(
            $example,
            ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'],
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
    }

    public function testAdminConfigListsTheTemplatesTheCreateFormMayOfferTheReviewPathFor(): void
    {
        /** @var ContentAdmin $contentAdmin */
        $contentAdmin = static::getContainer()->get('sulu_content.admin');

        $templates = $contentAdmin->getConfig()['requestWorkflowTemplates'][Example::RESOURCE_KEY] ?? null;
        $this->assertIsArray($templates);

        $this->assertContains(
            'example-2',
            $templates,
            'An untagged template is covered by the default workflow, which lists this resource.',
        );
        $this->assertNotContains(
            'example-no-workflow',
            $templates,
            'A template tagged `none` opts out, on the create form too.',
        );
    }

    public function testToolbarConditionsSeparateTheReviewPathFromDirectPublishing(): void
    {
        /** @var ContentViewBuilderFactoryInterface $contentViewBuilderFactory */
        $contentViewBuilderFactory = static::getContainer()->get('sulu_content.content_view_builder_factory');

        $saveActions = $contentViewBuilderFactory
            ->getWorkflowTransitionRequestToolbarActions(Example::RESOURCE_KEY)['save']
            ->getOptions()['toolbarActions'];
        $this->assertIsArray($saveActions);

        $conditions = [];
        foreach ($saveActions as $action) {
            $this->assertInstanceOf(ToolbarAction::class, $action);

            $options = $action->getOptions();
            $label = $options['label'] ?? $action->getType();
            $this->assertIsString($label);
            $this->assertIsString($options['visible_condition']);

            $conditions[$label] = $options['visible_condition'];
        }

        $this->assertStringNotContainsString(
            'workflowTransitionRequestEnabled',
            $conditions['sulu_content.request_for_publish'],
            'Whether a workflow covers the content is the toolbar action\'s own answer, not a condition.',
        );
        $this->assertStringNotContainsString(
            'workflowTransitionRequestEnabled',
            $conditions['sulu_admin.publish'],
            'Publishing directly is one route now: with no open request the guard has nothing to hold.',
        );
        $this->assertStringContainsString(
            '!activeWorkflowTransitionRequest',
            $conditions['sulu_admin.publish'],
            'It gives way to the review button once a request is open.',
        );
    }
}
