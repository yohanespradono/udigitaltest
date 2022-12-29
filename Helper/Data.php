<?php

namespace Udigital\CustomModule\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Search\Model\QueryFactory;

class Data extends AbstractHelper
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    private $formKey;

    public function __construct(Context $context, \Magento\Framework\Data\Form\FormKey $formKey)
    {
        parent::__construct($context);
        $this->formKey = $formKey;
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Retrieve result page url and set "secure" param to avoid confirm
     * message when we submit form from secure page to unsecure
     *
     * @param   string $query
     * @return  string
     */
    public function getAjaxSearchUrl($query = null)
    {
        return $this->_getUrl(
            'udigital/ajax/search',
            ['_query' => [QueryFactory::QUERY_VAR_NAME => $query], '_secure' => $this->_request->isSecure()]
        );
    }
}
