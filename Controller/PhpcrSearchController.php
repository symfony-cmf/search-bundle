<?php

namespace Symfony\Cmf\Bundle\SearchBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Liip\SearchBundle\SearchInterface;
use Liip\SearchBundle\Helper\SearchParams;

use Doctrine\Common\Persistence\ManagerRegistry;

class PhpcrSearchController implements SearchInterface
{
    protected $manager;
    protected $managerName;
    protected $router;
    protected $templating;
    protected $perPage;
    protected $restrictByLanguage;
    protected $translationDomain;
    protected $pageParameterKey;
    protected $queryParameterKey;
    protected $searchRoute;
    // TODO make configurable
    protected $searchFields = array('title' => 'title', 'summary' => 'body');
    // TODO make configurable
    protected $searchPath = '/cms/content';

    /**
     * @param Doctrine\Common\Persistence\ManagerRegistry $manager
     * @param string $manager_name
     * @param Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $templating
     * @param integer $results_per_page
     * @param boolean $restrict_by_language
     * @param string $translation_domain
     * @param string $page_parameter_key parameter name used for page
     * @param string $query_parameter_key parameter name used for search term
     * @param string $search_route route used for submitting search query
     */
    public function __construct(ManagerRegistry $manager, $manager_name, RouterInterface $router, EngineInterface $templating, $results_per_page, $restrict_by_language,
        $translation_domain, $page_parameter_key, $query_parameter_key, $search_route)
    {
        $this->manager = $manager;
        $this->managerName = $manager_name;
        $this->router = $router;
        $this->templating = $templating;
        $this->perPage = $results_per_page;
        $this->restrictByLanguage = $restrict_by_language;
        $this->translationDomain = $translation_domain;
        $this->pageParameterKey = $page_parameter_key;
        $this->queryParameterKey = $query_parameter_key;
        $this->searchRoute = $search_route;
    }

    /**
     * Search method
     * @param mixed $query string current search query or null
     * @param mixed $page string current result page to show or null
     * @param mixed $lang string language to use for restricting search results, or null
     * @param array $options any options which should be passed along to underlying search engine
     * @param \Symfony\Component\HttpFoundation\Request current request object, will be automatically injected by symfony when called as an action
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchAction($query = null, $page = null, $lang = null, $options = array(), Request $request = null)
    {
        if (null === $page) {
            // If the page param is not given, it's value is read in the request
            $page = SearchParams::requestedPage($request, $this->pageParameterKey);
        }

        if (null === $query) {
            // If the query param is not given, it's value is read in the request
            $query = SearchParams::requestedQuery($request, $this->queryParameterKey);
        }

        $lang = $this->queryLanguage($lang, $request);

        if ('' !== $query) {
            $dm = $this->manager->getManager($this->managerName);
            $qb = $dm->createQueryBuilder();
            $this->buildQuery($qb, $query, $page, $lang);
            $searchResults = $this->buildSearchResults($dm->getPhpcrSession(), $qb->execute());
        } else {
            $searchResults = array();
        }

        $params = array(
            'searchTerm' => $query,
            'searchResults' => $searchResults,
            'estimated' => count($searchResults),
            'translationDomain' => $this->translationDomain,
            'showPaging' => false,
            'start' => $page,
            'perPage' => $this->perPage,
            'searchRoute' => $this->searchRoute,
        );

        return new Response($this->templating->render('LiipSearchBundle:Search:search.html.twig', $params));
    }

    private function buildQuery($qb, $query, $page, $lang)
    {
        $factory = $qb->getQOMFactory();

        $qb->select('jcr:uuid')
            ->addSelect('phpcr:class')
            ->from($factory->selector('nt:unstructured'))
            ->where($factory->descendantNode($this->searchPath))
            ->setFirstResult(($page - 1) * $this->perPage)
            ->setMaxResults($this->perPage);

        $constraint = null;
        foreach ($this->searchFields as $key => $field) {
            $qb->addSelect($field);
            $newConstraint = $factory->fullTextSearch($field, $query);
            if (empty($constraint)) {
                $constraint = $newConstraint;
            } else {
                $constraint = $factory->orConstraint($constraint, $newConstraint);
            }
        }
        $qb->andWhere($constraint);

        if (2 === strlen($lang)) {
            // TODO: check if we can/must validate lang to prevent evil hacking or accidental breakage
            // TODO: make this work depending on the translation strategy (currently only child strategy is supported)
            $qb->andWhere($factory->comparison($factory->nodeName('[nt:unstructured]'), '=', $factory->literal("phpcr_locale:".$lang)));
        }
    }

    private function buildSearchResults($session, $rows)
    {
        $searchResults = array();
        foreach ($rows as $row) {
            if (!$row->getValue('phpcr:class')) {
                $parent = $session->getNode(dirname($row->getPath()));
                $uuid = $parent->getIdentifier();
            } else {
                $uuid = $row->getValue('jcr:uuid');
            }

            $searchResults[$uuid] = array(
                'url' => $this->router->generate(null, array('content_id' => $uuid)),
                'title' => $row->getValue($this->searchFields['title']),
                'summary' => substr(strip_tags($row->getValue($this->searchFields['summary'])), 0, 100),
            );
        }

        return $searchResults;
    }

    /**
     * Determine language used to restrict search results, if one should be used at all.
     * If $this->restrictByLanguage is false, this will return false.
     * @return mixed string(=locale) or bool(=false)
     */
    public function queryLanguage($lang = null, Request $request)
    {
        if (!$this->restrictByLanguage) {
            return false;
        }

        if (null !== $lang) {
            return $lang;
        }

        return $request->getLocale();
    }
}
