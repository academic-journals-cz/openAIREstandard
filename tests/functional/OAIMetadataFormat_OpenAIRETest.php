<?php

/**
 * @file OAIMetadataFormat_OpenAIRETest.php
 *
 * Copyright (c) 2013-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Unit tests for the OpenAIRE COAR/DataCite format's version-relation
 *  (datacite:relatedIdentifiers) generation.
 */

namespace APP\plugins\generic\openAIRE\tests\functional;

use APP\core\Request;
use APP\journal\Journal;
use APP\plugins\generic\openAIRE\OAIMetadataFormat_OpenAIRE;
use APP\publication\Publication;
use APP\publication\Repository;
use APP\submission\Submission;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PKP\core\Dispatcher;
use PKP\publication\enums\VersionRelationType;
use PKP\tests\PKPTestCase;
use ReflectionMethod;

#[CoversClass(OAIMetadataFormat_OpenAIRE::class)]
class OAIMetadataFormat_OpenAIRETest extends PKPTestCase
{
	protected function getMockedContainerKeys(): array
	{
		return [...parent::getMockedContainerKeys(), Repository::class];
	}

	/**
	 * Invoke the protected getRelatedIdentifiersXml() method under test.
	 */
	private function invoke(Publication $publication, Submission $article, Journal $journal, Request $request): ?string
	{
		$format = new OAIMetadataFormat_OpenAIRE('oai_openaire', '', '');
		$method = new ReflectionMethod($format, 'getRelatedIdentifiersXml');
		return $method->invoke($format, $publication, $article, $journal, $request);
	}

	private function stubVersionRelation(?object $versionRelation): void
	{
		$publicationRepoMock = Mockery::mock(Repository::class);
		$publicationRepoMock->shouldReceive('getVersionRelation')->andReturn($versionRelation);
		app()->instance(Repository::class, $publicationRepoMock);
	}

	/**
	 * Build a Request mock whose getDispatcher()->url() returns a predictable,
	 * inspectable string instead of hitting real routing.
	 */
	private function createRequestMockInstance(): Request
	{
		/** @var Dispatcher|MockObject $dispatcher */
		$dispatcher = $this->getMockBuilder(Dispatcher::class)
			->onlyMethods(['url'])
			->getMock();
		$dispatcher->method('url')->willReturnCallback(
			fn ($request, $newContext, $journalPath, $handler, $op, $path) =>
				"https://example.org/{$journalPath}/{$handler}/{$op}/" . implode('/', $path)
		);

		/** @var Request|MockObject $request */
		$request = $this->getMockBuilder(Request::class)
			->onlyMethods(['getDispatcher'])
			->getMock();
		$request->method('getDispatcher')->willReturn($dispatcher);
		return $request;
	}

	private function createPublication(array $data = []): Publication
	{
		/** @var Publication $publication */
		$publication = $this->getMockBuilder(Publication::class)->onlyMethods([])->getMock();
		foreach ($data as $key => $value) {
			$publication->setData($key, $value);
		}
		return $publication;
	}

	private function createJournal(array $data = []): Journal
	{
		/** @var Journal $journal */
		$journal = $this->getMockBuilder(Journal::class)->onlyMethods([])->getMock();
		$journal->setPath('test-journal');
		foreach ($data as $key => $value) {
			$journal->setData($key, $value);
		}
		return $journal;
	}

	private function createArticle(): Submission
	{
		/** @var Submission|MockObject $article */
		$article = $this->getMockBuilder(Submission::class)
			->onlyMethods(['getBestId'])
			->getMock();
		$article->method('getBestId')->willReturn(5);
		return $article;
	}

	public function testNoVersionRelationMeansNoRelatedIdentifiers(): void
	{
		$this->stubVersionRelation(null);

		$result = $this->invoke(
			$this->createPublication(),
			$this->createArticle(),
			$this->createJournal(),
			$this->createRequestMockInstance()
		);

		$this->assertNull($result);
	}

	public function testRelationTypeIsMappedToRealDataCitePascalCase(): void
	{
		// VersionRelationType's own ->value is lowercase-initial ("isNewVersionOf"),
		// which does NOT match the real DataCite/OpenAIRE controlled vocabulary
		// ("IsNewVersionOf") - this must be mapped explicitly, not passed through.
		$this->stubVersionRelation((object) [
			'publicationId' => 2,
			'doi' => null,
			'relationType' => VersionRelationType::IS_NEW_VERSION_OF,
		]);

		$result = $this->invoke(
			$this->createPublication(),
			$this->createArticle(),
			$this->createJournal(),
			$this->createRequestMockInstance()
		);

		$this->assertStringContainsString('relationType="IsNewVersionOf"', $result);
		$this->assertStringNotContainsString('isNewVersionOf', $result);
	}

	public function testUsesDoiAsRelatedIdentifierWhenAvailable(): void
	{
		$this->stubVersionRelation((object) [
			'publicationId' => 2,
			'doi' => '10.1234/previous',
			'relationType' => VersionRelationType::IS_NEW_VERSION_OF,
		]);

		$result = $this->invoke(
			$this->createPublication(),
			$this->createArticle(),
			$this->createJournal(),
			$this->createRequestMockInstance()
		);

		$this->assertStringContainsString('relatedIdentifierType="DOI"', $result);
		$this->assertStringContainsString('>10.1234/previous<', $result);
	}

	public function testFallsBackToArticleViewUrlWhenNoDoi(): void
	{
		$this->stubVersionRelation((object) [
			'publicationId' => 2,
			'doi' => null,
			'relationType' => VersionRelationType::IS_NEW_VERSION_OF,
		]);

		$result = $this->invoke(
			$this->createPublication(),
			$this->createArticle(),
			$this->createJournal(),
			$this->createRequestMockInstance()
		);

		$this->assertStringContainsString('relatedIdentifierType="URL"', $result);
		// Journal path must be passed explicitly (not null), otherwise this breaks
		// at the site level - see the pkp-lib#12950 review findings for the same
		// bug in Dc11SchemaArticleAdapter.php.
		$this->assertStringContainsString('https://example.org/test-journal/article/view/5/version/2', $result);
	}
}
