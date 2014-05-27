<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\ConfigurableProduct\Test\Block\Adminhtml\Product;

use Mtf\Block\Mapper;
use Mtf\Client\Element;
use Mtf\Client\Browser;
use Mtf\Factory\Factory;
use Mtf\Util\XmlConverter;
use Mtf\Block\BlockFactory;
use Mtf\Client\Element\Locator;
use Mtf\Fixture\FixtureInterface;
use Magento\Catalog\Test\Fixture\Product;
use Magento\Catalog\Test\Fixture\Category;
use Magento\Backend\Test\Block\Widget\Tab;
use Magento\Backend\Test\Block\Widget\FormTabs;

/**
 * Class ProductForm
 * Product creation form
 */
class ProductForm extends FormTabs
{
    /**
     * 'Save' split button
     *
     * @var string
     */
    protected $saveButton = '#save-split-button-button';

    /**
     * New attribute selector
     *
     * @var string
     */
    protected $newAttribute = 'body';

    /**
     * New attribute frame selector
     *
     * @var string
     */
    protected $newAttributeFrame = '#create_new_attribute_container';

    /**
     * Variations tab selector
     *
     * @var string
     */
    protected $variationsTab = '[data-ui-id="product-tabs-tab-content-super-config"] .title';

    /**
     * Variations tab selector
     *
     * @var string
     */
    protected $productDetailsTab = '#product_info_tabs_product-details';

    /**
     * Variations wrapper selector
     *
     * @var string
     */
    protected $variationsWrapper = '[data-ui-id="product-tabs-tab-content-super-config"]';

    /**
     * New variation set button selector
     *
     * @var string
     */
    protected $newVariationSet = '[data-ui-id="admin-product-edit-tab-super-config-grid-container-add-attribute"]';

    /**
     * Choose affected attribute set dialog popup window
     *
     * @var string
     */
    protected $affectedAttributeSet = "//div[div/@data-id='affected-attribute-set-selector']";

    /**
     * Category name selector
     *
     * @var string
     */
    protected $categoryName = '//*[contains(@class, "mage-suggest-choice")]/*[text()="%categoryName%"]';

    /**
     * 'Advanced Settings' tab
     *
     * @var string
     */
    protected $advancedSettings = '#ui-accordion-product_info_tabs-advanced-header-0[aria-selected="false"]';

    /**
     * Advanced tab list
     *
     * @var string
     */
    protected $advancedTabList = '#product_info_tabs-advanced[role="tablist"]';

    /**
     * Advanced tab panel
     *
     * @var string
     */
    protected $advancedTabPanel = '[role="tablist"] [role="tabpanel"][aria-expanded="true"]:not("overflow")';

    /**
     * Category fixture
     *
     * @var Category
     */
    protected $category;

    /**
     * Client Browser
     *
     * @var Browser
     */
    protected $browser;

    /**
     * @param Element $element
     * @param Mapper $mapper
     * @param XmlConverter $xmlConverter
     * @param BlockFactory $blockFactory
     * @param Browser $browser
     */
    public function __construct(
        Element $element,
        Mapper $mapper,
        XmlConverter $xmlConverter,
        BlockFactory $blockFactory,
        Browser $browser
    ) {
        $this->browser = $browser;
        parent::__construct($element, $mapper, $blockFactory, $xmlConverter);
    }

    /**
     * Get choose affected attribute set dialog popup window
     *
     * @return \Magento\ConfigurableProduct\Test\Block\Backend\Product\AffectedAttributeSet
     */
    protected function getAffectedAttributeSetBlock()
    {
        return Factory::getBlockFactory()->getMagentoConfigurableProductBackendProductAffectedAttributeSet(
            $this->_rootElement->find($this->affectedAttributeSet, Locator::SELECTOR_XPATH)
        );
    }

    /**
     * Get attribute edit block
     *
     * @return \Magento\ConfigurableProduct\Test\Block\Backend\Product\Attribute\Edit
     */
    public function getConfigurableAttributeEditBlock()
    {
        $this->browser->switchToFrame(new Locator($this->newAttributeFrame));
        return Factory::getBlockFactory()->getMagentoConfigurableProductBackendProductAttributeEdit(
            $this->browser->find($this->newAttribute, Locator::SELECTOR_TAG_NAME)
        );
    }

    /**
     * Initialization categories before use in the form of
     *
     * @param Category $category
     * @return void
     */
    public function setCategory(Category $category)
    {
        $this->category = $category;
    }

    /**
     * Fill the product form
     *
     * @param FixtureInterface $fixture
     * @param Element|null $element
     * @return $this
     */
    public function fill(FixtureInterface $fixture, Element $element = null)
    {
        $this->fillCategory($fixture);
        parent::fill($fixture);
        if ($fixture->getAttributeOptions()) {
            $this->_rootElement->find($this->productDetailsTab)->click();
            $this->clickCreateNewVariationSet();
            $attributeBlockForm = $this->getConfigurableAttributeEditBlock();
            $attributeBlockForm->fillAttributeOption($fixture->getAttributeOptions());
        }
        if ($fixture->getConfigurableOptions()) {
            $this->browser->switchToFrame();
            $this->fillVariations($fixture->getConfigurableOptions());
        }

    }

    /**
     * Select category
     *
     * @param FixtureInterface $fixture
     * @return void
     */
    protected function fillCategory(FixtureInterface $fixture)
    {
        // TODO should be removed after suggest widget implementation as typified element
        $categoryName = $this->category
            ? $this->category->getCategoryName()
            : ($fixture->getCategoryName() ? $fixture->getCategoryName() : '');

        if (!$categoryName) {
            return;
        }
        $category = $this->_rootElement->find(
            str_replace('%categoryName%', $categoryName, $this->categoryName),
            Locator::SELECTOR_XPATH
        );
        if (!$category->isVisible()) {
            $this->fillCategoryField(
                $categoryName,
                'category_ids-suggest',
                '//*[@id="attribute-category_ids-container"]'
            );
        }
    }

    /**
     * Save product
     *
     * @param FixtureInterface $fixture
     * @return \Magento\Backend\Test\Block\Widget\Form|void
     */
    public function save(FixtureInterface $fixture = null)
    {
        parent::save($fixture);
        if ($this->getAffectedAttributeSetBlock()->isVisible()) {
            $this->getAffectedAttributeSetBlock()->chooseAttributeSet($fixture);
        }
    }

    /**
     * Save new category
     *
     * @param Product $fixture
     */
    public function addNewCategory(Product $fixture)
    {
        $this->openNewCategoryDialog();
        $this->_rootElement->find('input#new_category_name', Locator::SELECTOR_CSS)
            ->setValue($fixture->getNewCategoryName());

        $this->clearCategorySelect();
        $this->selectParentCategory();

        $this->_rootElement->find('div.ui-dialog-buttonset button.action-create')->click();
        $this->waitForElementNotVisible('div.ui-dialog-buttonset button.action-create');
    }

    /**
     * Get variations block
     *
     * @return \Magento\ConfigurableProduct\Test\Block\Adminhtml\Product\Edit\Tab\Super\Config
     */
    protected function getVariationsBlock()
    {
        return Factory::getBlockFactory()->getMagentoConfigurableProductAdminhtmlProductEditTabSuperConfig(
            $this->browser->find($this->variationsWrapper)
        );
    }

    /**
     * Fill product variations
     *
     * @param array $variations
     */
    public function fillVariations($variations)
    {
        $variationsBlock = $this->getVariationsBlock();
        $variationsBlock->fillAttributeOptions($variations);
        $variationsBlock->generateVariations();
    }

    /**
     * Open variations tab
     */
    public function openVariationsTab()
    {
        $this->_rootElement->find($this->variationsTab)->click();
    }

    /**
     * Click on 'Create New Variation Set' button
     */
    public function clickCreateNewVariationSet()
    {
        $this->_rootElement->find($this->newVariationSet)->click();
    }

    /**
     * Clear category field
     */
    public function clearCategorySelect()
    {
        $selectedCategory = 'li.mage-suggest-choice span.mage-suggest-choice-close';
        if ($this->_rootElement->find($selectedCategory)->isVisible()) {
            $this->_rootElement->find($selectedCategory)->click();
        }
    }

    /**
     * Select parent category for new one
     */
    protected function selectParentCategory()
    {
        // TODO should be removed after suggest widget implementation as typified element
        $this->fillCategoryField(
            'Default Category',
            'new_category_parent-suggest',
            '//*[@id="new_category_form_fieldset"]'
        );
    }

    /**
     * Fills select category field
     *
     * @param string $name
     * @param string $elementId
     * @param string $parentLocation
     */
    protected function fillCategoryField($name, $elementId, $parentLocation)
    {
        // TODO should be removed after suggest widget implementation as typified element
        $this->_rootElement->find($elementId, Locator::SELECTOR_ID)->setValue($name);
        //*[@id="attribute-category_ids-container"]  //*[@id="new_category_form_fieldset"]
        $categoryListLocation = $parentLocation . '//div[@class="mage-suggest-dropdown"]'; //
        $this->waitForElementVisible($categoryListLocation, Locator::SELECTOR_XPATH);
        $categoryLocation = $parentLocation . '//li[contains(@data-suggest-option, \'"label":"' . $name . '",\')]//a';
        $this->_rootElement->find($categoryLocation, Locator::SELECTOR_XPATH)->click();
    }

    /**
     * Open new category dialog
     */
    protected function openNewCategoryDialog()
    {
        $this->_rootElement->find('#add_category_button', Locator::SELECTOR_CSS)->click();
        $this->waitForElementVisible('input#new_category_name');
    }

    /**
     * Open tab
     *
     * @param string $tabName
     * @return Tab|bool
     */
    public function openTab($tabName)
    {
        $rootElement = $this->_rootElement;
        $selector = $this->tabs[$tabName]['selector'];
        $strategy = isset($this->tabs[$tabName]['strategy'])
            ? $this->tabs[$tabName]['strategy']
            : Locator::SELECTOR_CSS;
        $advancedTabList = $this->advancedTabList;
        $tab = $this->_rootElement->find($selector, $strategy);
        $advancedSettings = $this->_rootElement->find($this->advancedSettings);

        // Wait until all tabs will load
        $this->_rootElement->waitUntil(
            function () use ($rootElement, $advancedTabList) {
                return $rootElement->find($advancedTabList)->isVisible();
            }
        );

        if ($tab->isVisible()) {
            $tab->click();
        } elseif ($advancedSettings->isVisible()) {
            $advancedSettings->click();
            // Wait for open tab animation
            $tabPanel = $this->advancedTabPanel;
            $this->_rootElement->waitUntil(
                function () use ($rootElement, $tabPanel) {
                    return $rootElement->find($tabPanel)->isVisible();
                }
            );
            // Wait until needed tab will appear
            $this->_rootElement->waitUntil(
                function () use ($rootElement, $selector, $strategy) {
                    return $rootElement->find($selector, $strategy)->isVisible();
                }
            );
            $tab->click();
        } else {
            return false;
        }

        return $this;
    }
}
