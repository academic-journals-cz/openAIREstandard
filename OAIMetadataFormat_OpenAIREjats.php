<?php

/**
 * @defgroup oai_format_jats
 */

/**
 * @file OAIMetadataFormat_OpenAIREjats.php
 *
 * Copyright (c) 2013-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_OpenAIREjats
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- OpenAIRE JATS
 */

namespace APP\plugins\generic\openAIREstandard;

use APP\core\Application;
use APP\facades\Repo;
use PKP\core\PKPString;
use PKP\db\DAORegistry;
use PKP\i18n\LocaleConversion;
use PKP\oai\OAIMetadataFormat;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use PKP\submission\GenreDAO;

class OAIMetadataFormat_OpenAIREjats extends OAIMetadataFormat {

	/**
	 * @see OAIMetadataFormat::toXml()
	 */
	function toXml($record, $format = null): string
	{
		$request = Application::get()->getRequest();
		$article = $record->getData('article');
		$journal = $record->getData('journal');
		$section = $record->getData('section');
		$issue = $record->getData('issue');
		$articleId = $article->getId();
		$publication = $article->getCurrentPublication();
		$publicationLocale = $publication->getData('locale');
		$printIssn = $journal->getData('printIssn');
		$onlineIssn = $journal->getData('onlineIssn');
		$publisherInstitution = $journal->getData('publisherInstitution');
		$sectionTitle = $section->getTitle($journal->getPrimaryLocale());
		$datePublished = $publication->getData('datePublished');
		$publicationDoi = $publication->getDoi();
		/** @var OpenAIREstandardPlugin $parentPlugin */
		$parentPlugin = PluginRegistry::getPlugin('generic', 'openairestandardplugin');
		$accessRights = $parentPlugin->getAccessRights($journal, $issue, $publication);
		$resourceType = ($section->getData('resourceType') ? $section->getData('resourceType') : 'http://purl.org/coar/resource_type/c_6501'); # COAR resource type URI, defaults to "journal article"
		if (!$datePublished) $datePublished = $issue->getData('datePublished');
		if ($datePublished) $datePublished = strtotime($datePublished);

		$response = "
		<article
			dtd-version=\"1.1\"
			xmlns:xlink=\"http://www.w3.org/1999/xlink\"
			xmlns:mml=\"http://www.w3.org/1998/Math/MathML\"
			xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
			xmlns:ali=\"http://www.niso.org/schemas/ali/1.0\"
			xmlns=\"https://jats.nlm.nih.gov/publishing/1.1/\"
			article-type=\"" . htmlspecialchars($this->mapCoarResourceTypeToJatsArticleType($resourceType)) . "\"
			xml:lang=\"" . LocaleConversion::toBcp47($publicationLocale) . "\">
		<front>
		<journal-meta>
			<journal-id journal-id-type=\"ojs\">" . htmlspecialchars($journal->getPath()) . "</journal-id>
			<journal-title-group>
			<journal-title xml:lang=\"" . LocaleConversion::toBcp47($journal->getPrimaryLocale()) . "\">" . htmlspecialchars($journal->getName($journal->getPrimaryLocale())) . "</journal-title>\n";
		// Translated journal titles
		foreach ($journal->getName(null) as $locale => $title) {
			if ($locale == $journal->getPrimaryLocale()) continue;
			$response .= "\t\t\t<trans-title-group xml:lang=\"" . LocaleConversion::toBcp47($locale) . "\"><trans-title>" . htmlspecialchars($title) . "</trans-title></trans-title-group>\n";
		}
		$response .= ($journal->getAcronym($journal->getPrimaryLocale())?"\t\t\t<abbrev-journal-title xml:lang=\"" . LocaleConversion::toBcp47($journal->getPrimaryLocale()) . "\">" . htmlspecialchars($journal->getAcronym($journal->getPrimaryLocale())) . "</abbrev-journal-title>":'');
		$response .= "\n\t\t\t</journal-title-group>\n";

		$response .=
			(!empty($onlineIssn)?"\t\t\t<issn pub-type=\"epub\">" . htmlspecialchars($onlineIssn) . "</issn>\n":'') .
			(!empty($printIssn)?"\t\t\t<issn pub-type=\"ppub\">" . htmlspecialchars($printIssn) . "</issn>\n":'') .
			($publisherInstitution != ''?"\t\t\t<publisher><publisher-name>" . htmlspecialchars($publisherInstitution) . "</publisher-name></publisher>\n":'') .
			"\t\t</journal-meta>\n" .
			"\t\t<article-meta>\n" .
			"\t\t\t<article-id pub-id-type=\"publisher-id\">" . $article->getId() . "</article-id>\n" .
			(!empty($publicationDoi)?"\t\t\t<article-id pub-id-type=\"doi\">" . htmlspecialchars($publicationDoi) . "</article-id>\n":'') .
			(!empty($sectionTitle)?"\t\t\t<article-categories><subj-group xml:lang=\"" . LocaleConversion::toBcp47($journal->getPrimaryLocale()) . "\" subj-group-type=\"heading\"><subject>" . htmlspecialchars($sectionTitle) . "</subject></subj-group></article-categories>\n":'') .
			"\t\t\t<title-group>\n" .
			"\t\t\t\t<article-title xml:lang=\"" . LocaleConversion::toBcp47($publicationLocale) . "\">" . htmlspecialchars(strip_tags($publication->getData('title', $publicationLocale))) . "</article-title>\n";
		if (!empty($subtitle = $publication->getData('subtitle', $publicationLocale))) $response .= "\t\t\t\t<subtitle xml:lang=\"" . LocaleConversion::toBcp47($publicationLocale) . "\">" . htmlspecialchars($subtitle) . "</subtitle>\n";

		// Translated article titles
		foreach ((array) $publication->getData('title') as $locale => $title) {
			if ($locale == $publicationLocale) continue;
			if ($title){
				$response .= "\t\t\t\t<trans-title-group xml:lang=\"" . LocaleConversion::toBcp47($locale) . "\">\n";
				$response .= "\t\t\t\t\t<trans-title>" . htmlspecialchars(strip_tags($title)) . "</trans-title>\n";
				if (!empty($subtitle = $publication->getData('subtitle', $locale))) $response .= "\t\t\t\t\t<trans-subtitle>" . htmlspecialchars($subtitle) . "</trans-subtitle>\n";
				$response .= "\t\t\t\t\t</trans-title-group>\n";
			}
		}
		$response .=
			"\t\t\t</title-group>\n" .
			"\t\t\t<contrib-group content-type=\"author\">\n";

		// Authors
		$affiliations = array();
		foreach ($publication->getData('authors') as $author) {
			$affiliation = $author->getLocalizedAffiliationNamesAsString($publicationLocale);
			$affiliationToken = $affiliation ? array_search($affiliation, $affiliations) : false;
			if ($affiliation && $affiliationToken === false) {
				$affiliationToken = 'aff-' . (count($affiliations)+1);
				$affiliations[$affiliationToken] = $affiliation;
			}
			$response .=
				"\t\t\t\t<contrib " . ($author->getPrimaryContact()?'corresp="yes" ':'') . ">\n" .
				"\t\t\t\t\t<name name-style=\"western\">\n" .
				"\t\t\t\t\t\t<surname>" . htmlspecialchars($author->getFamilyName($publicationLocale)) . "</surname>\n" .
				"\t\t\t\t\t\t<given-names>" . htmlspecialchars($author->getGivenName($publicationLocale)) . "</given-names>\n" .
				"\t\t\t\t\t</name>\n" .
				($affiliationToken?"\t\t\t\t\t<xref ref-type=\"aff\" rid=\"$affiliationToken\" />\n":'') .
				(($author->getOrcid() && $author->hasVerifiedOrcid())?"\t\t\t\t\t<contrib-id contrib-id-type=\"orcid\" authenticated=\"true\">" . htmlspecialchars($author->getOrcid()) . "</contrib-id>\n":'') .
				"\t\t\t\t</contrib>\n";
		}
		$response .= "\t\t\t</contrib-group>\n";
		foreach ($affiliations as $affiliationToken => $affiliation) {
			$response .= "\t\t\t<aff id=\"$affiliationToken\"><institution content-type=\"orgname\">" . htmlspecialchars($affiliation) . "</institution></aff>\n";
		}

		// Publication date
		if ($datePublished) $response .=
			"\t\t\t<pub-date date-type=\"pub\" publication-format=\"epub\">\n" .
			"\t\t\t\t<day>" . date('d', $datePublished) . "</day>\n" .
			"\t\t\t\t<month>" . date('m', $datePublished) . "</month>\n" .
			"\t\t\t\t<year>" . date('Y', $datePublished) . "</year>\n" .
			"\t\t\t</pub-date>\n";

		// Issue details
		if ($issue->getVolume() && $issue->getShowVolume())
			$response .= "\t\t\t<volume>" . htmlspecialchars($issue->getVolume()) . "</volume>\n";
		if ($issue->getNumber() && $issue->getShowNumber())
			$response .= "\t\t\t<issue>" . htmlspecialchars($issue->getNumber()) . "</issue>\n";

		// Page info, if available.
		$pageInfo = $parentPlugin->getPageInfo($publication);
		if ($pageInfo){
			$response .=
				"\t\t\t\t<fpage>" . $pageInfo['fpage'] . "</fpage>\n" .
				"\t\t\t\t<lpage>" . $pageInfo['lpage'] . "</lpage>\n";
		}

		// Fetch funding data from other plugins if available
		$fundingReferences = null;
		Hook::call('OAIMetadataFormat_OpenAIRE::findFunders', [&$articleId, &$fundingReferences]);
		if ($fundingReferences){
			$response .= $fundingReferences;
		}

		// Copyright, license and other permissions
		$copyrightYear = $publication->getData('copyrightYear');
		$copyrightHolder = $publication->getLocalizedData('copyrightHolder', $publicationLocale);
		$licenseUrl = $publication->getData('licenseUrl');
		$ccBadge = Application::get()->getCCLicenseBadge($licenseUrl, $publicationLocale);
		$openAccessDate = null;
		if ($accessRights == 'embargoedAccess') {
			$openAccessDate = date('Y-m-d', strtotime($issue->getOpenAccessDate()));
		}
		if ($copyrightYear || $copyrightHolder || $licenseUrl || $ccBadge || $openAccessDate || $accessRights == "openAccess" ) $response .=
			"\t\t\t<permissions>\n" .
			(($copyrightYear||$copyrightHolder)?"\t\t\t\t<copyright-statement>" . htmlspecialchars(__('submission.copyrightStatement', array('copyrightYear' => $copyrightYear, 'copyrightHolder' => $copyrightHolder))) . "</copyright-statement>\n":'') .
			($copyrightYear?"\t\t\t\t<copyright-year>" . htmlspecialchars($copyrightYear) . "</copyright-year>\n":'') .
			($copyrightHolder?"\t\t\t\t<copyright-holder>" . htmlspecialchars($copyrightHolder) . "</copyright-holder>\n":'') .
			($licenseUrl?"\t\t\t\t<license xlink:href=\"" . htmlspecialchars($licenseUrl) . "\">\n" .
				($ccBadge?"\t\t\t\t\t<license-p>" . strip_tags($ccBadge) . "</license-p>\n":'') .
			"\t\t\t\t</license>\n":'') .
			($openAccessDate?"\t\t\t\t<ali:free_to_read xmlns:ali=\"http://www.niso.org/schemas/ali/1.0\" start_date=\"" . htmlspecialchars($openAccessDate) . "\" />\n":'') .
			($accessRights == "openAccess"?"\t\t\t\t<ali:free_to_read xmlns:ali=\"http://www.niso.org/schemas/ali/1.0\" />\n":'') .
			"\t\t\t</permissions>\n";

		// landing page link
		$response .= "\t\t\t<self-uri xlink:href=\"" . htmlspecialchars($request->getDispatcher()->url(
			$request, Application::ROUTE_PAGE, $journal->getPath(), 'article', 'view', [$article->getBestId()], null, null, true, ''
		)) . "\" />\n";

		// full text links
		$galleys = $publication->getData('galleys');
		if ($galleys) {
			/** @var GenreDAO $genreDao */
			$genreDao = DAORegistry::getDAO('GenreDAO');
			$primaryGenres = $genreDao->getPrimaryByContextId($journal->getId())->toArray();
			$primaryGenreIds = array_map(function($genre) {
				return $genre->getId();
			}, $primaryGenres);
			foreach ($galleys as $galley) {
				$isRemote = (bool) $galley->getData('urlRemote');
				$submissionFile = null;
				if (!$isRemote && $submissionFileId = $galley->getData('submissionFileId')) {
					$submissionFile = Repo::submissionFile()->get($submissionFileId);
				}
				if (!$isRemote && !$submissionFile) {
					continue;
				}
				if ($isRemote || in_array($submissionFile->getData('genreId'), $primaryGenreIds)) {
					$galleyUrl = $request->getDispatcher()->url(
						$request, Application::ROUTE_PAGE, $journal->getPath(), 'article', 'download',
						[$article->getBestId(), $galley->getBestGalleyId()], null, null, true, ''
					);
					$fileType = $isRemote ? null : $submissionFile->getData('mimetype');
					$response .= "\t\t\t<self-uri" . ($fileType ? " content-type=\"" . htmlspecialchars($fileType) . "\"" : '') . " xlink:href=\"" . htmlspecialchars($galleyUrl) . "\" />\n";
				}
			}
		}

		// Subjects
		if ($allSubjects = $publication->getData('subjects')) {
			foreach ($allSubjects as $locale => $subjects) {
				if (empty($subjects)) continue;
				$response .= "\t\t\t<kwd-group xml:lang=\"" . LocaleConversion::toBcp47($locale) . "\">\n";
				foreach ($subjects as $subject) $response .= "\t\t\t\t<kwd>" . htmlspecialchars($subject['name']) . "</kwd>\n";
				$response .= "\t\t\t</kwd-group>\n";
			}
		}

		// Keywords
		if ($allKeywords = $publication->getData('keywords')) {
			foreach ($allKeywords as $locale => $keywords) {
				if (empty($keywords)) continue;
				$response .= "\t\t\t<kwd-group xml:lang=\"" . LocaleConversion::toBcp47($locale) . "\">\n";
				foreach ($keywords as $keyword) $response .= "\t\t\t\t<kwd>" . htmlspecialchars($keyword['name']) . "</kwd>\n";
				$response .= "\t\t\t</kwd-group>\n";
			}
		}

		// abstract
		if ($publication->getData('abstract', $publicationLocale)) {
			$abstract = PKPString::html2text($publication->getData('abstract', $publicationLocale));
			$response .= "\t\t\t<abstract xml:lang=\"" . LocaleConversion::toBcp47($publicationLocale) . "\"><p>" . htmlspecialchars($abstract) . "</p></abstract>\n";
		}
		// Include translated abstracts
		foreach ((array) $publication->getData('abstract') as $locale => $abstract) {
			if ($locale == $publicationLocale) continue;
			if ($abstract){
				$abstract = PKPString::html2text($abstract);
				$response .= "\t\t\t<trans-abstract xml:lang=\"" . LocaleConversion::toBcp47($locale) . "\"><p>" . htmlspecialchars($abstract) . "</p></trans-abstract>\n";
			}
		}

		// Page count
		$response .=
			($pageInfo?"\t\t\t<counts><page-count count=\"" . (int) $pageInfo['pagecount'] . "\" /></counts>\n":'');

		// OpenAIRE COAR Access Rights and OpenAIRE COAR Resource Type
		$coarAccessRights = OpenAIREstandardPlugin::COAR_ACCESS_RIGHTS;
		$coarResourceLabel = $parentPlugin->getCoarResourceType($resourceType);

		if ($accessRights || $coarResourceLabel){
			$response .= "\t\t\t<custom-meta-group>\n";
			if ($accessRights) $response .=
				"\t\t\t\t<custom-meta specific-use=\"access-right\">\n" .
				"\t\t\t\t\t<meta-name>" . $coarAccessRights[$accessRights]['label'] . "</meta-name>\n" .
				"\t\t\t\t\t<meta-value>" . $coarAccessRights[$accessRights]['url'] . "</meta-value>\n" .
				"\t\t\t\t</custom-meta>\n";
			if ($coarResourceLabel) $response .=
				"\t\t\t\t<custom-meta specific-use=\"resource-type\">\n" .
				"\t\t\t\t\t<meta-name>" . $coarResourceLabel . "</meta-name>\n" .
				"\t\t\t\t\t<meta-value>" . $resourceType . "</meta-value>\n" .
				"\t\t\t\t</custom-meta>\n";
			$response .=  "\t\t\t</custom-meta-group>\n";
		}

		$response .=
			"\t\t</article-meta>\n" .
			"\t</front>\n" .
			"</article>";

		return $response;
	}

	/**
	 * Get a JATS article-type string based on COAR Resource Type URI.
	 * https://jats.nlm.nih.gov/archiving/tag-library/1.1/attribute/article-type.html
	 */
	public function mapCoarResourceTypeToJatsArticleType(string $uri): ?string
	{
		$resourceTypes = [
			'http://purl.org/coar/resource_type/c_6501' => 'research-article',
			'http://purl.org/coar/resource_type/c_2df8fbb1' => 'research-article',
			'http://purl.org/coar/resource_type/c_dcae04bc' => 'review-article',
			'http://purl.org/coar/resource_type/c_beb9' => 'research-article',
			'http://purl.org/coar/resource_type/c_7bab' => 'research-article',
			'http://purl.org/coar/resource_type/c_b239' => 'editorial',
			'http://purl.org/coar/resource_type/c_545b' => 'letter',
			'http://purl.org/coar/resource_type/c_93fc' => 'case-report',
			'http://purl.org/coar/resource_type/c_efa0' => 'product-review',
			'http://purl.org/coar/resource_type/c_ba08' => 'book-review',
			'http://purl.org/coar/resource_type/c_26e4' => 'other', // interview -- no exact JATS article-type equivalent
			'http://purl.org/coar/resource_type/c_8544' => 'other', // lecture -- no exact JATS article-type equivalent
			'http://purl.org/coar/resource_type/c_5794' => 'meeting-report',
			'http://purl.org/coar/resource_type/c_46ec' => 'dissertation',
			'http://purl.org/coar/resource_type/c_8042' => 'research-article',
			'http://purl.org/coar/resource_type/c_816b' => 'research-article',
			'http://purl.org/coar/resource_type/c_1843' => 'other',
		];
		return $resourceTypes[$uri] ?? null;
	}

}
