<?php
/**
 * Hreflang Block
 *
 * Generates hreflang meta tags for CMS pages based on store views.
 *
 * @category  Roni
 * @package   Roni_CmsHreflang
 * @author    Roni Clei <roneclay@gmail.com>
 * @copyright Copyright (c) 2025 Roni Clei
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/roniclei/magento2-cms-hreflang
 * @version   1.0.0
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Roni_CmsHreflang',
    __DIR__
);
