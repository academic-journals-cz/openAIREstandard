<?php

/**
 * @defgroup oai_format_openaire
 */

/**
 * @file OAIMetadataFormat_OpenAIREstandard.inc.php
 *
 * Copyright (c) 2013-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_OpenAIREstandard
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- OpenAIREstandard
 */
class OAIMetadataFormat_OpenAIREstandard extends OAIMetadataFormat {

    /**
     * @see OAIMetadataFormat#toXml
     */
    function toXml($record, $format = null) {
        $request = Application::get()->getRequest();
        $article = $record->getData('article');
        $journal = $record->getData('journal');
        $section = $record->getData('section');
        $issue = $record->getData('issue');
        $galleys = $record->getData('galleys');
        $articleId = $article->getId();
        $publication = $article->getCurrentPublication();
        $abbreviation = $journal->getLocalizedSetting('abbreviation');
        $printIssn = $journal->getSetting('printIssn');
        $onlineIssn = $journal->getSetting('onlineIssn');
        $articleLocale = $article->getLocale();
        $publisherInstitution = $journal->getSetting('publisherInstitution');
        $datePublished = $article->getDatePublished();
        $articleDoi = $article->getStoredPubId('doi');
        $accessRights = $this->_getAccessRights($journal, $issue, $article);
        $resourceType = ($section->getData('resourceType') ? $section->getData('resourceType') : 'http://purl.org/coar/resource_type/c_6501'); # COAR resource type URI, defaults to "journal article"
        if (!$datePublished)
            $datePublished = $issue->getDatePublished();
        if ($datePublished)
            $datePublished = strtotime($datePublished);
        $parentPlugin = PluginRegistry::getPlugin('generic', 'openairestandardplugin');

        //resource - defining schemas and namespaces
        $response = "<resource xmlns=\"http://namespace.openaire.eu/schema/oaire/\" xmlns:rdf=\"http://www.w3.org/TR/rdf-concepts/\" xmlns:doc=\"http://www.lyncode.com/xoai\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:dcterms=\"http://purl.org/dc/terms/\" xmlns:oaire=\"http://namespace.openaire.eu/schema/oaire/\" xmlns:datacite=\"http://datacite.org/schema/kernel-4\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:vc=\"http://www.w3.org/2007/XMLSchema-versioning\"  xsi:schemaLocation=\"http://namespace.openaire.eu/schema/oaire/ https://www.openaire.eu/schema/repo-lit/4.0/openaire.xsd\"> \n";
                
        //1. Title (M) - Translated article titles
        $response .= "<datacite:titles>\n"
                . "<datacite:title xml:lang=\"" . substr($articleLocale, 0, 2) . "\">" . htmlspecialchars(strip_tags($article->getTitle($articleLocale))) . "</datacite:title>\n";

        if (!empty($subtitle = $article->getSubtitle($articleLocale))) {
            $response .= "<datacite:title xml:lang=\"" . substr($articleLocale, 0, 2) . "\" titleType=\"subtitle\">" . htmlspecialchars($subtitle) . "</datacite:title>\n";
        }
        foreach ($article->getFullTitle(null) as $locale => $title) {
            if ($locale == $articleLocale)
                continue;
            if ($title) {
                $response .= "<datacite:title xml:lang=\"" . substr($locale, 0, 2) . "\">" . htmlspecialchars(strip_tags($title)) . "</datacite:title>\n";
                if (!empty($subtitle = $article->getSubtitle($locale))) {
                    $response .= "<datacite:title xml:lang=\"" . substr($locale, 0, 2) . "\" titleType=\"Subtitle\">" . htmlspecialchars($subtitle) . "</datacite:title>\n";
                }
            }
        }
        $response .= "</datacite:titles>\n";
        
        
        //2. Creator (M) - Authors
        $response .= "<datacite:creators>\n";
        $affiliations = array();
        foreach ($article->getAuthors() as $author) {
            $affiliation = $author->getLocalizedAffiliation();
            $response .= "<datacite:creator>\n" .
                    "<datacite:creatorName nameType=\"Personal\">" . htmlspecialchars((method_exists($author, 'getLastName') ? $author->getLastName() : $author->getLocalizedFamilyName()) . ", " . (method_exists($author, 'getFirstName') ? $author->getFirstName() : $author->getLocalizedGivenName()) . (((method_exists($author, 'getMiddleName') && $s = $author->getMiddleName()) != '') ? " $s" : '')) . "</datacite:creatorName>\n" .
                    "<datacite:givenName>" . htmlspecialchars(method_exists($author, 'getFirstName') ? $author->getFirstName() : $author->getLocalizedGivenName()) . (((method_exists($author, 'getMiddleName') && $s = $author->getMiddleName()) != '') ? " $s" : '') . "</datacite:givenName>\n" .
                    "<datacite:familyName>" . htmlspecialchars(method_exists($author, 'getLastName') ? $author->getLastName() : $author->getLocalizedFamilyName()) . "</datacite:familyName>\n" .
                    ($author->getOrcid() ? "<datacite:nameIdentifier nameIdentifierScheme=\"ORCID\" schemeURI=\"http://orcid.org\">" . htmlspecialchars($author->getOrcid()) . "</datacite:nameIdentifier>\n" : '') .
                    ($affiliation ? "<datacite:affiliation>" . htmlspecialchars($affiliation) . "</datacite:affiliation>\n" : '') .
                    "</datacite:creator>\n";
        }
        $response .= "</datacite:creators>\n";

        //4. Funding Reference (MA) - Fetch funding data from other plugins if available - TODO
        $fundingReferences = null;
        HookRegistry::call('OAIMetadataFormat_OpenAIRE::findFunders', array(&$articleId, &$fundingReferences));
        if ($fundingReferences) {
            $response .= $fundingReferences;
        }

        //5. Alternate Identifier (R)
        If (!empty($articleDoi)) {
            $response .= "<datacite:alternateIdentifiers>\n"
                    . "<datacite:alternateIdentifier alternateIdentifierType=\"DOI\">" . htmlspecialchars($articleDoi) . "</datacite:alternateIdentifier>\n"
                    . "</datacite:alternateIdentifiers>\n";
        }
              

        //8. Languages (MA) - taken from galley locales
        $galleyLocales = Array();
        $galleyTypes = Array();
        $galleyUrls = Array();
        foreach ($galleys as $galley) {
            $galleyLocale = $galley->getLocale();
            if (!in_array($galleyLocale, $galleyLocales)) {
                $response .= "<dc:language>" . substr($galleyLocale, 0, 2) . "</dc:language>\n";
                $galleyLocales[] = $galleyLocale;
            }
        }

        //ISSN + eISSN
        if($printIssn) {
            $response .= "<dc:source>ISSN: " . $printIssn . "</dc:source>";
        }
        if ($onlineIssn){
            $response .= "<dc:source>eISSN: " . $onlineIssn . "</dc:source>";
        }
        
        //9. Publisher (MA) 
        $response .= ($publisherInstitution != '' ? "<dc:publisher>" . htmlspecialchars($publisherInstitution) . "</dc:publisher>\n" : '');
        
         // 10. Publication date (M)
        if ($datePublished) {
            $response .= "<datacite:dates>\n" .
                    "<datacite:date dateType=\"Issued\">" . strftime('%Y-%m-%d', $datePublished) . "</datacite:date>\n" .
                    "</datacite:dates>\n";
        }

        //11. Resource Type (M)
        $coarResourceLabel = $parentPlugin->_getCoarResourceType($resourceType);
        if ($coarResourceLabel) {
            $response .= "<oaire:resourceType uri=\"" . $resourceType . "\" resourceTypeGeneral=\"literature\">" . $coarResourceLabel . "</oaire:resourceType>\n";
        }       
        
        //12. description - abstract + translated abstracts
        if ($article->getAbstract($articleLocale)) {
            $abstract = PKPString::html2text($article->getAbstract($articleLocale));
            $response .= "<dc:description xml:lang=\"" . substr($articleLocale, 0, 2) . "\">" . htmlspecialchars($abstract) . "</dc:description>\n";
        }
        foreach ($article->getAbstract(null) as $locale => $abstract) {
            if ($locale == $articleLocale)
                continue;
            if ($abstract) {
                $abstract = PKPString::html2text($abstract);
                $response .= "<dc:description xml:lang=\"" . substr($locale, 0, 2) . "\">" . htmlspecialchars($abstract) . "</dc:description>\n";
            }
        }


        //13. Format (R)
        foreach ($galleys as $galley) {
            $response .= "<dc:format>" . $galley->getFileType() . "</dc:format>\n";
        }

         //14. Resource Identifier (M) - landing page link                 
        $response .= "<datacite:identifier identifierType=\"URL\">" . htmlspecialchars($request->url($journal->getPath(), 'article', 'view', $article->getBestArticleId())) . "</datacite:identifier>\n";
                
        //15. Access Rights (M) - OpenAIRE COAR Access Rights 
        $coarAccessRights = $this->_getCoarAccessRights();

        if ($accessRights) {
            $response .= "<datacite:rights rightsURI=\"" . $coarAccessRights[$accessRights]['url'] . "\">" . $coarAccessRights[$accessRights]['label'] . "</datacite:rights>\n";
        }

        //17. Subject (MA) - subjects + keywords                
        $subjects = array();
        if (is_array($article->getSubject(null))) {
            foreach ($article->getSubject(null) as $locale => $subject) {
                $s = array_map("trim", $subject);
                if (!empty($s))
                    $subjects[$locale] = $s;
            }
        }
        $subjectsOutput = "";
        if (!empty($subjects)) {
            foreach ($subjects as $locale => $s) {
                foreach ($s as $subject) {
                    $subjectsOutput .= "<datacite:subject xml:lang=\"" . substr($locale, 0, 2) . "\">" . htmlspecialchars($subject) . "</datacite:subject>\n";
                }
            }
        }

        $keywordsOutput = "";
        $submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
        foreach ($submissionKeywordDao->getKeywords($publication->getId(), $journal->getSupportedLocales()) as $locale => $keywords) {
            if (empty($keywords))
                continue;
            // Load the article.subject locale key in possible other languages
            AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, $locale);
            foreach ($keywords as $keyword) {
                $keywordsOutput .= "<datacite:subject xml:lang=\"" . substr($locale, 0, 2) . "\">" . htmlspecialchars($keyword) . "</datacite:subject>\n";
            }
        }

        if (!empty($subjectsOutput) OR!empty($keywordsOutput)) {
            $response .= "<datacite:subjects>\n";
            $response .= $subjectsOutput;
            $response .= $keywordsOutput;
            $response .= "</datacite:subjects>\n";
        }
        
         //18. Licence Condition (R) 
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);
        $licenseUrl = $article->getLicenseURL();
        $ccLabel = $this->_getCCLicenseLabel($licenseUrl);
        $openAccessDate = null;
        if ($accessRights == 'embargoedAccess') {
            $openAccessDate = date('Y-m-d', strtotime($issue->getOpenAccessDate()));
        } else {
            $openAccessDate = $datePublished;
        }
        if($licenseUrl){
            $response .= "<oaire:licenseCondition startDate=\"" . strftime('%Y-%m-%d', $openAccessDate) . "\" uri=\"" . htmlspecialchars($licenseUrl) . "\">" . strip_tags($ccLabel) . "</oaire:licenseCondition>\n";
        }

        //20. Size (O) - gallyes file sizes + page count		
        $pageInfo = $this->_getPageInfo($article);
        if ($galleys || $pageInfo) {
            $response .= "<datacite.sizes>\n";
            $response .= ($pageInfo ? "<datacite:size>" . (int) $pageInfo['pagecount'] . " Pages</datacite:size>\n" : '');
            foreach ($galleys as $galley) {
                if($galley->getFile()){
                        $submissionFile = Services::get('submissionFile')->get($galley->getData('submissionFileId'));
                        $response .= "<datacite:size>" . round(Services::get('file')->fs->getSize($submissionFile->getData('path')) / 1024 / 1024, 2) . " MB</datacite:size>\n";
                }
            }
            $response .= "</datacite.sizes>\n";
        }
        
        //23. File Location (MA) - full text links
        $galleys = $article->getGalleys();
        $primaryGalleys = array();
        if ($galleys) {
            $genreDao = DAORegistry::getDAO('GenreDAO');
            $primaryGenres = $genreDao->getPrimaryByContextId($journal->getId())->toArray();
            $primaryGenreIds = array_map(function($genre) {
                return $genre->getId();
            }, $primaryGenres);
            foreach ($galleys as $galley) {
                $remoteUrl = $galley->getRemoteURL();
                $file = $galley->getFile();
                if (!$remoteUrl && !$file) {
                    continue;
                }
                if ($remoteUrl || in_array($file->getGenreId(), $primaryGenreIds)) {
                    $response .= "<oaire:file accessRightsURI=\"" . $coarAccessRights[$accessRights]['url'] . "\" mimeType=\"" . $galley->getFileType() . "\" objectType=\"fulltext\">" . htmlspecialchars($request->url($journal->getPath(), 'article', 'download', array($article->getBestArticleId(), $galley->getBestGalleyId()), null, null, true)) . "</oaire:file>\n";
                }
            }
        }

        //24. Citation Title (R)
        $response .= "<oaire:citationTitle>" . htmlspecialchars($journal->getName($journal->getPrimaryLocale())) . "</oaire:citationTitle>\n";

        //25. Citation Volume (R)
        if ($issue->getVolume() && $issue->getShowVolume()) {
            $response .= "<oaire:citationVolume>" . htmlspecialchars($issue->getVolume()) . "</oaire:citationVolume>\n";
        }

        //26. Citation Issue (R)
        if ($issue->getNumber() && $issue->getShowNumber()) {
            $response .= "<oaire:citationIssue>" . htmlspecialchars($issue->getNumber()) . "</oaire:citationIssue>\n";
        }

        //27. Citation Start Page (R) + 28. Citation End Page (R) - Page info, if available and parseable.
        if ($pageInfo) {
            $response .= "<oaire:citationStartPag>" . $pageInfo['fpage'] . "</oaire:citationStartPag>\n"
                    . "<oaire:citationEndPage>" . $pageInfo['lpage'] . "</oaire:citationEndPage>\n";
        }
        $response .= "</resource>\n";
        
        return $response;
    }

    /**
     * Get an associative array containing COAR Access Rights.
     * @return array
     */
    function _getCoarAccessRights() {
        static $coarAccessRights = array(
            'openAccess' => array('label' => 'open access', 'url' => 'http://purl.org/coar/access_right/c_abf2'),
            'embargoedAccess' => array('label' => 'embargoed access', 'url' => 'http://purl.org/coar/access_right/c_abf2'),
            'restrictedAccess' => array('label' => 'restricted access', 'url' => 'http://purl.org/coar/access_right/c_abf2'),
            'metadataOnlyAccess' => array('label' => 'metadata only access', 'url' => 'http://purl.org/coar/access_right/c_abf2')
        );
        return $coarAccessRights;
    }

    /**
     * Get a JATS article-type string based on COAR Resource Type URI.
     * https://jats.nlm.nih.gov/archiving/tag-library/1.1/attribute/article-type.html
     * @param $uri string
     * @return string
     */
    function _mapCoarResourceTypeToJatsArticleType($uri) {
        $resourceTypes = array(
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
            'http://purl.org/coar/resource_type/c_5794' => 'meeting-report',
            'http://purl.org/coar/resource_type/c_46ec' => 'dissertation',
            'http://purl.org/coar/resource_type/c_8042' => 'research-article',
            'http://purl.org/coar/resource_type/c_816b' => 'research-article'
        );
        return $resourceTypes[$uri];
    }

    /**
     * Get an associative array containing page info
     * @return array
     */
    function _getPageInfo($article) {
        $matches = $pageCount = null;
        if (PKPString::regexp_match_get('/^(\d+)$/', $article->getPages(), $matches)) {
            $matchedPage = htmlspecialchars($matches[1]);
            return array('fpage' => $matchedPage, 'lpage' => $matchedPage, 'pagecount' => '1');
        } elseif (PKPString::regexp_match_get('/^[Pp][Pp]?[.]?[ ]?(\d+)$/', $article->getPages(), $matches)) {
            $matchedPage = htmlspecialchars($matches[1]);
            return array('fpage' => $matchedPage, 'lpage' => $matchedPage, 'pagecount' => '1');
        } elseif (PKPString::regexp_match_get('/^[Pp][Pp]?[.]?[ ]?(\d+)[ ]?-[ ]?([Pp][Pp]?[.]?[ ]?)?(\d+)$/', $article->getPages(), $matches)) {
            $matchedPageFrom = htmlspecialchars($matches[1]);
            $matchedPageTo = htmlspecialchars($matches[3]);
            $pageCount = $matchedPageTo - $matchedPageFrom + 1;
            return array('fpage' => $matchedPageFrom, 'lpage' => $matchedPageTo, 'pagecount' => $pageCount);
        } elseif (PKPString::regexp_match_get('/^(\d+)[ ]?-[ ]?(\d+)$/', $article->getPages(), $matches)) {
            $matchedPageFrom = htmlspecialchars($matches[1]);
            $matchedPageTo = htmlspecialchars($matches[2]);
            $pageCount = $matchedPageTo - $matchedPageFrom + 1;
            return array('fpage' => $matchedPageFrom, 'lpage' => $matchedPageTo, 'pagecount' => $pageCount);
        } else {
            return null;
        }
    }

    /**
     * Get article access rights
     * @param $journal
     * @param $issue
     * @param $article
     * @return string
     */
    function _getAccessRights($journal, $issue, $article) {
        $accessRights = null;
        if ($journal->getData('publishingMode') == PUBLISHING_MODE_OPEN) {
            $accessRights = 'openAccess';
        } else if ($journal->getData('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION) {
            if ($issue->getAccessStatus() == 0 || $issue->getAccessStatus() == ISSUE_ACCESS_OPEN) {
                $accessRights = 'openAccess';
            } else if ($issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION) {
                if (is_a($article, 'PublishedArticle') && $article->getAccessStatus() == ARTICLE_ACCESS_OPEN) {
                    $accessRights = 'openAccess';
                } else if ($issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION && $issue->getOpenAccessDate() != NULL) {
                    $accessRights = 'embargoedAccess';
                } else if ($issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION && $issue->getOpenAccessDate() == NULL) {
                    $accessRights = 'metadataOnlyAccess';
                }
            }
        }
        if ($journal->getData('restrictSiteAccess') == 1 || $journal->getData('restrictArticleAccess') == 1) {
            $accessRights = 'restrictedAccess';
        }
        return $accessRights;
    }
    
    /**
	 * Get the Creative Commons license labels associated with a given
	 * license URL.
	 * @param $ccLicenseURL URL to creative commons license
	 * @param $locale string Optional locale to return badge in
	 * @return string HTML code for CC license
	 */
	public function _getCCLicenseLabel($ccLicenseURL, $locale = null) {
		$licenseKeyMap = array(
			'|http[s]?://(www\.)?creativecommons.org/licenses/by-nc-nd/4.0[/]?|' => 'submission.license.cc.by-nc-nd4',
			'|http[s]?://(www\.)?creativecommons.org/licenses/by-nc/4.0[/]?|' => 'submission.license.cc.by-nc4',
			'|http[s]?://(www\.)?creativecommons.org/licenses/by-nc-sa/4.0[/]?|' => 'submission.license.cc.by-nc-sa4',
			'|http[s]?://(www\.)?creativecommons.org/licenses/by-nd/4.0[/]?|' => 'submission.license.cc.by-nd4',
			'|http[s]?://(www\.)?creativecommons.org/licenses/by/4.0[/]?|' => 'submission.license.cc.by4',
			'|http[s]?://(www\.)?creativecommons.org/licenses/by-sa/4.0[/]?|' => 'submission.license.cc.by-sa4',
			'|http[s]?://(www\.)?creativecommons.org/licenses/by-nc-nd/3.0[/]?|' => 'submission.license.cc.by-nc-nd3',
			'|http[s]?://(www\.)?creativecommons.org/licenses/by-nc/3.0[/]?|' => 'submission.license.cc.by-nc3',
			'|http[s]?://(www\.)?creativecommons.org/licenses/by-nc-sa/3.0[/]?|' => 'submission.license.cc.by-nc-sa3',
			'|http[s]?://(www\.)?creativecommons.org/licenses/by-nd/3.0[/]?|' => 'submission.license.cc.by-nd3',
			'|http[s]?://(www\.)?creativecommons.org/licenses/by/3.0[/]?|' => 'submission.license.cc.by3',
			'|http[s]?://(www\.)?creativecommons.org/licenses/by-sa/3.0[/]?|' => 'submission.license.cc.by-sa3'
		);
		if ($locale === null) $locale = AppLocale::getLocale();

		foreach($licenseKeyMap as $pattern => $key) {
			if (preg_match($pattern, $ccLicenseURL)) {
				PKPLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, $locale);
				return __($key, array(), $locale);
			}
		}
		return null;
	}

}
