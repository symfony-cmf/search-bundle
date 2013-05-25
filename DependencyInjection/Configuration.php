<?php

namespace Symfony\Cmf\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
* This class contains the configuration information for the bundle
*
* This information is solely responsible for how the different configuration
* sections are normalized, and merged.
*/
class Configuration implements ConfigurationInterface
{
    /**
     * Returns the config tree builder.
     *
     * @return \Symfony\Component\DependencyInjection\Configuration\NodeInterface
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('cmf_search')
            ->children()
                ->scalarNode('document_manager_name')->defaultValue('default')->end()
                ->scalarNode('translation_strategy')->defaultNull()->end()
                ->scalarNode('search_path')->defaultNull()->end()
                ->booleanNode('show_paging')->defaultFalse()->end()
                ->arrayNode('search_fields')
                    ->prototype('scalar')
                ->end()
        ->end();

        return $treeBuilder;
    }
}
