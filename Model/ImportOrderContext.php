<?php

namespace Shopthru\Connector\Model;

class ImportOrderContext
{
    /**
     * @var bool
     */
    private bool $isShopthruImport = false;

    /**
     * @param bool $flag
     * @return $this
     */
    public function setIsShopthruImport($flag = true): self
    {
        $this->isShopthruImport = $flag;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsShopthruImport(): bool
    {
        return $this->isShopthruImport;
    }
}
