<?php

namespace Knp\Bundle\TranslatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder,
    Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for the bundle
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('knplabs_translator');

        $rootNode
            ->children()
                ->booleanNode('include_vendor_assets')->defaultTrue()->end()
                ->booleanNode('enabled')->defaultTrue()->end()
                ->scalarNode('default_resource')->end()
                ->booleanNode('always_put_to_default_resource')->defaultFalse()->end()
                ->scalarNode('default_translation_format')->defaultValue('yml')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

