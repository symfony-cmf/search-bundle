<?php

namespace Symfony\Cmf\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SymfonyCmfSearchExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter($this->getAlias().'.document_manager_name', $config['document_manager_name']);
        $searchPath = $config['search_path'];
        if (null === $searchPath) {
            if ($container->hasParameter('symfony_cmf_core.content_basepath')) {
                $searchPath = $container->getParameter('symfony_cmf_core.content_basepath');
            } else {
                $searchPath = '/cms/content';
            }
        }
        $container->setParameter($this->getAlias() . '.search_path', $searchPath);
        $container->setParameter($this->getAlias().'.search_fields', $config['search_fields']);
        $container->setParameter($this->getAlias().'.translation_strategy', $config['translation_strategy']);
        $container->setParameter($this->getAlias() . '.show_paging', $config['show_paging']);

        $loader->load('services.xml');
    }
}
