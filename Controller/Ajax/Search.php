<?php

namespace Udigital\CustomModule\Controller\Ajax;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Review\Model\AppendSummaryDataFactory;
use Magento\Review\Model\Review;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Search extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var QueryFactory
     */
    private $_queryFactory;

    /**
     * Catalog Layer Resolver
     *
     * @var Resolver
     */
    private $layerResolver;

    /**
     * @var Image
     */
    private $imageHelper;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var AppendSummaryDataFactory
     */
    private $appendSummaryDataFactory;


    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    const RESULT_MAX_SIE = 5;
    /**
     * @var Cart
     */
    private $cartHelper;

    /**
     * @param Context $context
     * @param QueryFactory $queryFactory
     * @param Resolver $layerResolver
     * @param Image $imageHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param ItemFactory $itemFactory
     * @param StoreManagerInterface $_storeManager
     * @param AppendSummaryDataFactory $appendSummaryDataFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Cart $cartHelper
     */
    public function __construct(
        Context $context,
        QueryFactory $queryFactory,
        Resolver $layerResolver,
        Image $imageHelper,
        PriceCurrencyInterface $priceCurrency,
        ItemFactory $itemFactory,
        StoreManagerInterface $_storeManager,
        AppendSummaryDataFactory $appendSummaryDataFactory,
        ProductRepositoryInterface $productRepository,
        Cart $cartHelper
    ) {
        parent::__construct($context);
        $this->_queryFactory = $queryFactory;
        $this->layerResolver = $layerResolver;
        $this->imageHelper = $imageHelper;
        $this->priceCurrency = $priceCurrency;
        $this->itemFactory = $itemFactory;
        $this->_storeManager = $_storeManager;
        $this->appendSummaryDataFactory = $appendSummaryDataFactory;
        $this->productRepository = $productRepository;
        $this->cartHelper = $cartHelper;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->layerResolver->create(Resolver::CATALOG_LAYER_SEARCH);
        $query      = $this->_queryFactory->get()->getQueryText();
        $productCollection = $this->layerResolver->get()
            ->getProductCollection()
            ->addAttributeToSelect('description')
            ->setPageSize(10)
            ->addSearchFilter($query);
        $responseData = [];

        foreach ($productCollection as $product) {
            if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $children = $product->getTypeInstance()->getUsedProducts($product);
                foreach ($children as $childrenProduct) {
                    $responseData[] = $this->createResponseItem($childrenProduct);
                    if (count($responseData) >= self::RESULT_MAX_SIE) {
                        break 1;
                    }
                }
            } else {
                $responseData[] = $this->createResponseItem($product);
            }
            if (count($responseData) >= self::RESULT_MAX_SIE) {
                break;
            }
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseData);
        return $resultJson;
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Catalog\Api\Data\ProductInterface $product
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createResponseItem($product)
    {
        $image = $this->imageHelper->init($product, 'product_page_image_small')->getUrl();

        $this->appendSummaryDataFactory->create()
            ->execute(
                $product,
                $this->_storeManager->getStore()->getId(),
                Review::ENTITY_PRODUCT_CODE
            );

        return $this->itemFactory->create([
            'title'             => $product->getName(),
            'price'             => $this->priceCurrency->format($product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue(), false),
            'special_price'     => $this->priceCurrency->format($product->getPriceInfo()->getPrice('special_price')->getAmount()->getValue(), false),
            'has_special_price' => $product->getSpecialPrice() > 0 ? true : false,
            'image'             => $image,
            'url'               => $product->getProductUrl(),
            'rating'            => $product->getRatingSummary(),
            'description'       => substr(strip_tags($product->getDescription()), 0, 200),
            'sku'               => $product->getSku(),
            'entity_id'         => $product->getId(),
            'add_url'           => $this->getAddToCartUrl($product)
        ])->toArray();
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Catalog\Api\Data\ProductInterface $product
     * @return string
     */
    private function getAddToCartUrl($product)
    {
        return $this->cartHelper->getAddUrl($product);
    }
}
