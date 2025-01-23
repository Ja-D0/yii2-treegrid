<?php

namespace Ja_D0\treegrid;

use Ja_D0\treegrid\columns\TreeColumn;
use libphonenumber\ValidationResult;
use Yii;
use Closure;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use yii\grid\DataColumn;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\i18n\Formatter;

/**
 * TreeGrid renders a jQuery TreeGrid component.
 * The code was based in: https://github.com/yiisoft/yii2/blob/master/framework/grid/GridView.php
 *
 * @see https://github.com/maxazan/jquery-treegrid
 * @author Leandro Gehlen <leandrogehlen@gmail.com>
 */
class TreeGrid extends Widget
{
    /**
     * Определяет положение поля поиска справа в строке свободного пространства
     */
    const SEARCH_POSITION_RIGHT = 'right';

    /**
     * Определяет положение поля поиска справа в строке свободного пространства
     */
    const SEARCH_POSITION_LEFT = 'left';

    /**
     * @var \yii\data\DataProviderInterface|\yii\data\BaseDataProvider the data provider for the view. This property is required.
     */
    public $dataProvider;

    /**
     * @var string the default data column class if the class name is not explicitly specified when configuring a data column.
     * Defaults to 'leandrogehlen\treegrid\TreeColumn'.
     */
    public $dataColumnClass;

    /**
     * @var array атрибуты HTML для тега виджета
     */
    public $options = ['class' => 'treegrid'];

    /**
     * @var string|Closure|boolean элементы свободного пространства
     */
    public $containerSpace = false;

    /**
     * @var array the HTML attributes for the table tag of the grid view.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $tableOptions = ['class' => 'table table-bordered col-sm-12'];

    /**
     * @var array аттрибуты HTML для тега контейнера таблицы дерева
     */
    public $tableContainerOptions = [];

    /**
     * @var array The plugin options
     */
    public $pluginOptions = [];

    /**
     * @var array аттрибуты HTML для тега контейнера свободного пространства
     */
    public $containerRowOptions = ["class" => "treegrid-container row"];

    /**
     * @var array аттрибуты HTML для тега контейнера поля поиска
     */
    public $searchContainerOptions = [];

    /**
     * @var array аттрибты HTML для поля поиска
     */
    public $searchInputOptions = ["placeholder" => "Введите текст для поиска"];

    /**
     * @var string положение поля поиска в строке свободного пространства
     */
    public $searchPosition = self::SEARCH_POSITION_RIGHT;

    /**
     * @var array the HTML attributes for the table header row.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $headerRowOptions = [];
    /**
     * @var array the HTML attributes for the table footer row.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $footerRowOptions = [];

    /**
     * @var string the HTML display when the content of a cell is empty
     */
    public $emptyCell = '&nbsp;';

    /**
     * @var string the HTML content to be displayed when [[dataProvider]] does not have any data.
     */
    public $emptyText;

    /**
     * @var array the HTML attributes for the emptyText of the list view.
     * The "tag" element specifies the tag name of the emptyText element and defaults to "div".
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $emptyTextOptions = ['class' => 'empty'];

    /**
     * @var bool отображать ли поле поиска
     */
    public $showSearch = true;

    /**
     * @var bool отображать ли кнопки управления деревом
     */
    public $showTreeManageButtons = true;

    /**
     * @var bool отображать ли контейнер с контентом над таблицей
     */
    public $showContainerContent = true;

    /**
     * @var bool whether to show the header section of the grid table.
     */
    public $showHeader = true;

    /**
     * @var bool whether to show the footer section of the grid table.
     */
    public $showFooter = false;

    /**
     * @var bool whether to show the grid view if [[dataProvider]] returns no data.
     */
    public $showOnEmpty = true;

    /**
     * @var array|Formatter the formatter used to format model attribute values into displayable texts.
     * This can be either an instance of [[Formatter]] or an configuration array for creating the [[Formatter]]
     * instance. If this property is not set, the "formatter" application component will be used.
     */
    public $formatter;

    /**
     * @var array|Closure the HTML attributes for the table body rows. This can be either an array
     * specifying the common HTML attributes for all body rows, or an anonymous function that
     * returns an array of the HTML attributes. The anonymous function will be called once for every
     * data model returned by [[dataProvider]]. It should have the following signature:
     *
     * ```php
     * function ($model, $key, $index, $grid)
     * ```
     *
     * - `$model`: the current data model being rendered
     * - `$key`: the key value associated with the current data model
     * - `$index`: the zero-based index of the data model in the model array returned by [[dataProvider]]
     * - `$grid`: the GridView object
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $tableRowOptions = [];

    /**
     * @var Closure an anonymous function that is called once BEFORE rendering each data model.
     * It should have the similar signature as [[rowOptions]]. The return result of the function
     * will be rendered directly.
     */
    public $beforeTableRow;

    /**
     * @var Closure an anonymous function that is called once AFTER rendering each data model.
     * It should have the similar signature as [[rowOptions]]. The return result of the function
     * will be rendered directly.
     */
    public $afterTableRow;

    /**
     * @var string name of key column used to build tree
     */
    public $keyColumnName;

    /**
     * @var string name of parent column used to build tree
     */
    public $parentColumnName;

    /**
     * @var mixed parent column value of root elements from data
     */
    public $parentRootValue = null;

    /**
     * @var array grid column configuration. Each array element represents the configuration
     * for one particular grid column.
     * @see \yii\grid::$columns for details.
     */
    public $columns = [];
    /**
     * Initializes the grid view.
     * This method will initialize required property values and instantiate [[columns]] objects.
     */
    public function init()
    {
        if ($this->dataProvider === null) {
            throw new InvalidConfigException('The "dataProvider" property must be set.');
        }
        if ($this->emptyText === null) {
            $this->emptyText = Yii::t('yii', 'No results found.');
        }

        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }

        if ($this->formatter == null) {
            $this->formatter = Yii::$app->getFormatter();
        } elseif (is_array($this->formatter)) {
            $this->formatter = Yii::createObject($this->formatter);
        }
        if (!$this->formatter instanceof Formatter) {
            throw new InvalidConfigException('The "formatter" property must be either a Format object or a configuration array.');
        }
        if (!$this->keyColumnName) {
            throw new InvalidConfigException('The "keyColumnName" property must be specified"');
        }
        if (!$this->parentColumnName) {
            throw new InvalidConfigException('The "parentColumnName" property must be specified"');
        }

        $this->initColumns();
    }

    /**
     * Runs the widget.
     */
    public function run()
    {
        $id = $this->options['id'];
        $options = Json::htmlEncode($this->pluginOptions);

        $view = $this->getView();
        TreeGridAsset::register($view);

        $view->registerJs("jQuery('#$id').treegrid($options);");

        if ($this->showSearch) {
            $view->registerJs("jQuery('#$id').treegrid('initSearch');");
        }
        if ($this->showTreeManageButtons) {
            $view->registerJs("jQuery('#$id').treegrid('initManageButtons');");
        }

        if ($this->showOnEmpty || $this->dataProvider->getCount() > 0) {
            $containerContent = $this->showContainerContent ? $this->renderContainerContent() : false;
            $header = $this->showHeader ? $this->renderTableHeader() : false;
            $body = $this->renderItems();
            $footer = $this->showFooter ? $this->renderTableFooter() : false;

            $tableContent = array_filter([
                $header,
                $body,
                $footer
            ]);

            $table = Html::tag('table', implode("\n", $tableContent), $this->tableOptions);
            $tableRow = Html::tag('div', $table, $this->tableContainerOptions);

            $content =  array_filter([
                $containerContent,
                $tableRow,
            ]);

            return Html::tag('div', implode("\n", $content), $this->options);
        } else {
            return $this->renderEmpty();
        }
    }

    /**
     * Рендерит контейнер с контентом над таблицей
     * @return string результат рендера
     */
    public function renderContainerContent()
    {
        $search = $this->showSearch ? $this->renderSearch() : false;
        $expandAll = $this->showTreeManageButtons ? $this->renderButtonExpandAll() : false;
        $collapseAll = $this->showTreeManageButtons ? $this->renderButtonCollapseAll() : false;
        $containerSpaceContent = $this->renderContainerSpace();

        if (!$this->showSearch) {
            if ($this->showTreeManageButtons && $this->searchPosition === self::SEARCH_POSITION_RIGHT) {
                $buttonGroup = Html::tag("div", $expandAll . $collapseAll, ["class" => "treegrid-manage-button-group-right"]);
            } else if ($this->searchPosition === self::SEARCH_POSITION_LEFT) {
                $buttonGroup = Html::tag("div", $expandAll. $collapseAll, ["class" => "treegrid-manage-button-group-left"]);
            } else {
                $buttonGroup = false;
            }
        } else {
            $buttonGroup = $expandAll . $collapseAll;
        }

        $manageContent = array_filter([
            $buttonGroup,
            $search
        ]);

        if ($this->searchPosition === self::SEARCH_POSITION_LEFT) {
            $manageContent = array_reverse($manageContent);
        }

        $manageContentOptions = [];

        if ($this->showSearch || $this->showTreeManageButtons) {
            if ($search) {
                Html::addCssClass($manageContentOptions, "col-sm-6");
                Html::addCssClass($manageContentOptions, "treegrid-flex-container");
            } else if ($this->showTreeManageButtons) {
                Html::addCssClass($manageContentOptions, "col-sm-2");
            }

            $manageContent = Html::tag("div" , implode("\n", $manageContent), $manageContentOptions);
        } else {
            $manageContent = false;
        }

        $content = array_filter([
            $containerSpaceContent,
            $manageContent
        ]);

        if ($this->searchPosition === self::SEARCH_POSITION_LEFT) {
            $content = array_reverse($content);
        }

        return Html::tag("div", implode("\n", $content), $this->containerRowOptions);

    }

    /**
     * Рендерит контейнер с полем поиска
     * @return string результат рендера
     */
    public function renderSearch(): string
    {
        Html::addCssClass($this->searchInputOptions, "treegrid-search form-control");
        Html::addCssClass($this->searchContainerOptions, "treegrid-search-container");

        $searchLogo = Html::tag("span", options: ["class" => "treegrid-search-icon search-logo"]);
        $searchInput = Html::input("text", options: $this->searchInputOptions);

        return Html::tag("div", $searchLogo . $searchInput, $this->searchContainerOptions);
    }

    /**
     * Рендерит кнопку разворачивания всех узлов
     * @return string
     */
    public function renderButtonExpandAll(): string
    {
        $icon = Html::tag("span", options: ["id" => "treegrid-expand-all-icon"]);
        return Html::a($icon, options: ["id" => "treegrid-expand-all", "class" => "btn btn-primary", "style" => "padding: 0; height: 34px; width: 34px;"]);
    }

    /**
     * Рендерит кнопку сворачивания всех узлов
     * @return string
     */
    public function renderButtonCollapseAll(): string
    {
        $icon =  $icon = Html::tag("span", options: ["id" => "treegrid-collapse-all-icon"]);
        return Html::button($icon, ["id" => "treegrid-collapse-all", "class" => "btn btn-danger", "style" => "padding: 0; height: 34px; width: 34px;"]);
    }

    /**
     * Рендерит пространство слева в контейнере над таблицей
     * @return string результат рендера
     */
    public function renderContainerSpace(): string
    {
        $containerOptions = [];
        if ($this->showTreeManageButtons && !$this->showSearch) {
            Html::addCssClass($containerOptions, "col-sm-10");
        } else if ($this->showSearch) {
            Html::addCssClass($containerOptions, "col-sm-6");
        } else {
            Html::addCssClass($containerOptions, "col-sm-12");
        }

        if ($this->containerSpace instanceof Closure) {
            $containerSpaceContent = call_user_func($this->containerSpace);
        } else {
            $containerSpaceContent = $this->containerSpace;
        }

        if ($containerSpaceContent === false) {
            return Html::tag("div", null, $containerOptions);
        }

        return Html::tag("div", $containerSpaceContent, $containerOptions);
    }

    /**
     * Renders the HTML content indicating that the list view has no data.
     * @return string the rendering result
     * @see emptyText
     */
    public function renderEmpty()
    {
        $options = $this->emptyTextOptions;
        $tag = ArrayHelper::remove($options, 'tag', 'div');
        return Html::tag($tag, ($this->emptyText === null ? Yii::t('yii', 'No results found.') : $this->emptyText), $options);
    }

    /**
     * Renders a table row with the given data model and key.
     * @param mixed $model the data model to be rendered
     * @param mixed $key the key associated with the data model
     * @param integer $index the zero-based index of the data model among the model array returned by [[dataProvider]].
     * @return string the rendering result
     */
    public function renderTableRow($model, $key, $index)
    {
        $cells = [];
        /* @var $column TreeColumn */
        foreach ($this->columns as $column) {
            $cells[] = $column->renderDataCell($model, $key, $index);
        }
        if ($this->tableRowOptions instanceof Closure) {
            $options = call_user_func($this->tableRowOptions, $model, $key, $index, $this);
        } else {
            $options = $this->tableRowOptions;
        }
        $options['data-id'] = is_array($key) ? json_encode($key) : (string) $key;

        $id = ArrayHelper::getValue($model, $this->keyColumnName);
        Html::addCssClass($options, "treegrid-$id");

        $parentId = ArrayHelper::getValue($model, $this->parentColumnName);
        if ($parentId) {
            if(ArrayHelper::getValue($this->pluginOptions, 'initialState') == 'collapsed'){
                Html::addCssStyle($options, 'display: none;');
            }
            Html::addCssClass($options, "treegrid-parent-$parentId");
        }

        return Html::tag('tr', implode('', $cells), $options);
    }

    /**
     * Renders the table header.
     * @return string the rendering result.
     */
    public function renderTableHeader()
    {
        $cells = [];
        foreach ($this->columns as $column) {
            /* @var $column TreeColumn */
            $cells[] = $column->renderHeaderCell();
        }
        $content = Html::tag('tr', implode('', $cells), $this->headerRowOptions);
        return "<thead>\n" . $content . "\n</thead>";
    }

    /**
     * Renders the table footer.
     * @return string the rendering result.
     */
    public function renderTableFooter()
    {
        $cells = [];
        foreach ($this->columns as $column) {
            /* @var $column TreeColumn */
            $cells[] = $column->renderFooterCell();
        }
        $content = Html::tag('tr', implode('', $cells), $this->footerRowOptions);
        return "<tfoot>\n" . $content . "\n</tfoot>";
    }

    /**
     * Renders the data models for the grid view.
     */
    public function renderItems()
    {
        $rows = [];
        $this->dataProvider->setKeys([]);
        $models = array_values($this->dataProvider->getModels());
        $models = $this->normalizeData($models, $this->parentRootValue);
        $this->dataProvider->setModels($models);
        $this->dataProvider->setKeys(null);
        $this->dataProvider->prepare();
        $keys = $this->dataProvider->getKeys();
        foreach ($models as $index => $model) {
            $key = $keys[$index];
            if ($this->beforeTableRow !== null) {
                $row = call_user_func($this->beforeTableRow, $model, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }

            $rows[] = $this->renderTableRow($model, $key, $index);

            if ($this->afterTableRow !== null) {
                $row = call_user_func($this->afterTableRow, $model, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
        }

        if (empty($rows)) {
            $colspan = count($this->columns);
            return "<tr><td colspan=\"$colspan\">" . $this->renderEmpty() . "</td></tr>";
        } else {
            return implode("\n", $rows);
        }
    }

    /**
     * Creates column objects and initializes them.
     */
    protected function initColumns()
    {
        if (empty($this->columns)) {
            $this->guessColumns();
        }
        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
                $column = $this->createDataColumn($column);
            } else {
                $column = Yii::createObject(array_merge([
                    'class' => $this->dataColumnClass ? : TreeColumn::className(),
                    'grid' => $this,
                ], $column));
            }
            if (!$column->visible) {
                unset($this->columns[$i]);
                continue;
            }
            $this->columns[$i] = $column;
        }
    }

    /**
     * Creates a [[DataColumn]] object based on a string in the format of "attribute:format:label".
     * @param string $text the column specification string
     * @return DataColumn the column instance
     * @throws InvalidConfigException if the column specification is invalid
     */
    protected function createDataColumn($text)
    {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
        }

        return Yii::createObject([
            'class' => $this->dataColumnClass ? : TreeColumn::className(),
            'grid' => $this,
            'attribute' => $matches[1],
            'format' => isset($matches[3]) ? $matches[3] : 'text',
            'label' => isset($matches[5]) ? $matches[5] : null,
        ]);
    }

    /**
     * This function tries to guess the columns to show from the given data
     * if [[columns]] are not explicitly specified.
     */
    protected function guessColumns()
    {
        $models = $this->dataProvider->getModels();
        $model = reset($models);
        if (is_array($model) || is_object($model)) {
            foreach ($model as $name => $value) {
                $this->columns[] = $name;
            }
        }
    }

    /**
     * Normalize tree data
     * @param array $data
     * @param string $parentId
     * @return array
     */
    protected function normalizeData(array $data, $parentId = null) {
        $result = [];
        foreach ($data as $element) {
            if (ArrayHelper::getValue($element, $this->parentColumnName) == $parentId) {
                $result[] = $element;
                $children = $this->normalizeData($data, ArrayHelper::getValue($element, $this->keyColumnName));
                if ($children) {
                    $result = array_merge($result, $children);
                }
            }
        }
        return $result;
    }
}
