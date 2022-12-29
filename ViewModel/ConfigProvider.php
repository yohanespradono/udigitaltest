<?php

namespace Udigital\CustomModule\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Udigital\CustomModule\Helper\Data as SearchHelper;

class ConfigProvider implements ArgumentInterface
{

    /**
     * @var SearchHelper
     */
    private $searchHelper;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param SearchHelper $searchHelper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SearchHelper         $searchHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->searchHelper = $searchHelper;
    }

    /**
     * Retrieve search helper instance for template view
     *
     * @return SearchHelper
     */
    public function getSearchHelperData(): SearchHelper
    {
        return $this->searchHelper;
    }
}
