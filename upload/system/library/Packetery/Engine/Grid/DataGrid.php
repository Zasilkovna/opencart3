<?php

namespace Packetery\Engine\Grid;

use Packetery\Engine\Grid\Exception\DataGridException;
use Packetery\Engine\Template;

class DataGrid {

    /** @var array */
    private $data = [];

    /** @var array */
    private $columns = [];

    /** @var array */
    private $columnCallbacks = [];

    /** @var Template */
    protected $template;

    /** @var string|null */
    protected $templateFile;

    /**
     * @param Template $template
     */
    public function __construct(Template $template) {
        $this->template = $template;
    }

    /**
     * @param array $data
     * @return void
     */
    public function setData(array $data) {
        $this->data = $data;
    }

    /**
     * @param string $key
     * @param string $name
     * @return void
     * @throws DataGridException
     */
    public function addColumnText($key, $name) {
        if (isset($this->columns[$key])) {
            throw new DataGridException(
                sprintf('There is already column at key [%s] defined.', $key)
            );
        }
        $this->columns[$key] = $name;
    }

    /**
     * @param string $key
     * @param callable $callback
     * @return void
     */
    public function addColumnCallback($key, callable $callback) {
        $this->columnCallbacks[$key] = $callback;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getColumnCallback($key) {
        return isset($this->columnCallbacks[$key]) ? $this->columnCallbacks[$key] : null;
    }

    /**
     * @param string $key
     * @param string $column
     * @return mixed
     */
    public function applyColumnCallback($key, $column) {
        $callback = $this->getColumnCallback($key);

        if ($callback !== null) {
            call_user_func($callback, $column);
        }

        return $column;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function render() {
        if ($this->columns === []) {
            throw new DataGridException('You have to add at least one column.');
        }

        $this->template->addParameter('columns', $this->columns);
        $this->template->addParameter('data', $this->data);

        return $this->template->render($this->getTemplateFile());
    }

    /**
     * @return string
     */
    public function getOriginalTemplateFile() {
        return __DIR__ . '/templates/grid.twig';
    }

    /**
     * @return string
     */
    public function getTemplateFile() {
        return $this->templateFile ?: $this->getOriginalTemplateFile();
    }

    /**
     * @param string $templateFile
     * @return void
     */
    public function setTemplateFile($templateFile) {
        $this->templateFile = $templateFile;
    }
}
