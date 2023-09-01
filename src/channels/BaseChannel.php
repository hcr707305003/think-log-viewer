<?php
/**
 * User: Shiroi
 * EMail: 707305003@qq.com
 */

namespace Shiroi\ThinkLogViewer\channels;

class BaseChannel
{
    protected int $limit = 10;

    protected int $page = 1;

    protected array $order = [];

    protected array $field = [];

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return BaseChannel
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return BaseChannel
     */
    public function setPage(int $page): self
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return array
     */
    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * @param array $order
     * @return BaseChannel
     */
    public function setOrder(array $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return array
     */
    public function getField(): array
    {
        return $this->field;
    }

    /**
     * @param array $field
     * @return BaseChannel
     */
    public function setField(array $field): self
    {
        $this->field = $field;
        return $this;
    }
}