<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2013 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Symfony\Cmf\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

class CmfSearchExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter($this->getAlias() . '.show_paging', $config['show_paging']);

        if ($config['persistence']['phpcr']) {
            $loader->load('services.xml');

            $searchController = $container->getDefinition($this->getAlias().'.phpcr_controller');
            $searchController->replaceArgument(0, new Reference($config['persistence']['phpcr']['manager_registry']));

            $container->setParameter($this->getAlias() . '.persistence.phpcr.manager_name', $config['persistence']['phpcr']['manager_name']);
            $container->setParameter($this->getAlias() . '.persistence.phpcr.search_basepath', $config['persistence']['phpcr']['search_basepath']);
            $container->setParameter($this->getAlias() . '.persistence.phpcr.search_fields', $config['persistence']['phpcr']['search_fields']);
            $container->setParameter($this->getAlias() . '.persistence.phpcr.translation_strategy', $config['persistence']['phpcr']['translation_strategy']);
        }
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__ . '/../Resources/config/schema';
    }

    public function getNamespace()
    {
        return 'http://cmf.symfony.com/schema/dic/search';
    }
}
