<?php

namespace Udigital\CustomModule\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RefreshCart implements ObserverInterface
{
    /**
     * Removes all items from cart if they aren't in same categories as the added item.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $quoteItem = $observer->getData('quote_item');
        $product = $observer->getData('product');

        $requiredCategoryIds = $product->getCategoryIds();

        $quote = $quoteItem->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $categoryIds = $item->getProduct()->getCategoryIds();
            if (!array_intersect($requiredCategoryIds, $categoryIds)) {
                $quote->removeItem($item->getId());
            }
        }
        $quote->save();
    }
}
