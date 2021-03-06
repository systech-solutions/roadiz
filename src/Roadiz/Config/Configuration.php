<?php
/**
 * Copyright (c) 2016.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file Configuration.php
 * @author ambroisemaupate
 *
 */
namespace RZ\Roadiz\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('roadiz');

        $root->children()
            ->scalarNode('appNamespace')
                ->defaultValue('chooseAnUniqueNameForYourApp')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('timezone')
                ->defaultValue('Europe/Paris')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->arrayNode('doctrine')
                ->children()
                    ->enumNode('driver')
                        ->isRequired()
                        ->defaultValue('pdo_mysql')
                        ->values(['pdo_mysql', 'pdo_sqlite', 'pdo_pgsql'])
                    ->end()
                    ->scalarNode('host')->defaultValue('localhost')->end()
                    ->scalarNode('user')->end()
                    ->scalarNode('password')->end()
                    ->scalarNode('application_name')->end()
                    ->scalarNode('dbname')->end()
                    ->scalarNode('unix_socket')->end()
                    ->scalarNode('port')->end()
                    ->scalarNode('path')->end()
                    ->booleanNode('logging')->defaultValue(false)->end()
                    ->booleanNode('profiling')->defaultValue(false)->end()
                    ->scalarNode('charset')->end()
                    ->arrayNode('options')
                        ->useAttributeAsKey('key')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('mapping_types')
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('default_table_options')
                        ->info("This option is used by the schema-tool and affects generated SQL. Possible keys include 'charset','collate', and 'engine'.")
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('cacheDriver')
                ->addDefaultsIfNotSet()
                ->children()
                    ->enumNode('type')
                        ->values([null, 'array', 'apcu', 'apc', 'xcache', 'memcache', 'memcached', 'redis'])
                        ->defaultNull()
                        ->info('If null or empty, Roadiz will try to detect best cache driver available')
                    ->end()
                    ->scalarNode('host')->defaultNull()->end()
                    ->scalarNode('port')->defaultNull()->end()
                ->end()
            ->end()
            ->arrayNode('security')
                ->children()
                    ->scalarNode('secret')
                    ->defaultValue('change#this#secret#very#important')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('entities')
                ->requiresAtLeastOneElement()
                ->prototype('scalar')
                    ->cannotBeEmpty()
                ->end()
                ->info('Doctrine entities search paths. Append yours here if you want to create custom entities in your theme.')
            ->end()
            ->scalarNode('rememberMeLifetime')
                ->defaultValue(2592000)
                ->isRequired()
                ->cannotBeEmpty()
                ->info('Lifetime of remember-me cookie in seconds (default 30 days)')
            ->end()
            ->arrayNode('additionalServiceProviders')
                ->prototype('scalar')
                    ->cannotBeEmpty()
                ->end()
            ->end()
            ->arrayNode('additionalCommands')
                ->prototype('scalar')
                    ->cannotBeEmpty()
                ->end()
            ->end()
            ->append($this->addMonologNode())
            ->append($this->addMailerNode())
            ->append($this->addAssetsNode())
            ->append($this->addSolrNode())
        ;
        return $builder;
    }

    /**
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    protected function addMailerNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('mailer');
        $node->addDefaultsIfNotSet()
            ->children()
                ->enumNode('type')
                    ->values([null, 'smtp'])
                    ->defaultNull()
                ->end()
                ->scalarNode('host')->defaultValue('localhost')->end()
                ->integerNode('port')->treatNullLike(25)->defaultValue(25)->end()
                ->booleanNode('encryption')->defaultFalse()->end()
                ->scalarNode('username')->end()
                ->scalarNode('password')->end()
            ->end();

        return $node;
    }

    /**
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    protected function addAssetsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('assetsProcessing');
        $node->addDefaultsIfNotSet()
            ->children()
                ->enumNode('driver')
                    ->values(['gd', 'imagick'])
                    ->defaultValue('gd')
                    ->isRequired()
                    ->info('GD does not support TIFF and PSD formats, but iMagick must be installed')
                ->end()
                ->integerNode('defaultQuality')
                    ->min(10)->max(100)
                    ->defaultValue(90)
                ->end()
                ->integerNode('maxPixelSize')
                    ->min(600)
                    ->defaultValue(1920)
                    ->info('Pixel width limit after Roadiz should create a smaller copy')
                ->end()
                ->scalarNode('jpegoptimPath')->end()
                ->scalarNode('pngquantPath')->end()
            ->arrayNode('subscribers')
                ->prototype('array')
                    ->children()
                        ->scalarNode('class')->isRequired()->cannotBeEmpty()->end()
                        ->arrayNode('args')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    protected function addSolrNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('solr');

        $node->children()
                ->arrayNode('endpoint')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('host')
                                ->isRequired()
                                ->defaultValue('localhost')
                            ->end()
                            ->scalarNode('username')->end()
                            ->scalarNode('password')->end()
                            ->scalarNode('core')->isRequired()->end()
                            ->scalarNode('timeout')->isRequired()->defaultValue(3)->end()
                            ->scalarNode('port')->isRequired()->defaultValue(8983)->end()
                            ->scalarNode('path')->isRequired()->defaultValue('/solr')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    protected function addMonologNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('monolog');

        $node->children()
                ->arrayNode('handlers')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->enumNode('type')
                                ->values([
                                    'default',
                                    'stream',
                                    'syslog',
                                    'gelf',
                                ])
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->defaultValue('default')
                            ->end()
                            ->enumNode('level')
                                ->values([
                                    'DEBUG',
                                    'INFO',
                                    'NOTICE',
                                    'WARNING',
                                    'ERROR',
                                    'CRITICAL',
                                    'ALERT',
                                    'EMERGENCY',
                                ])
                                ->isRequired()
                                ->defaultValue('INFO')
                            ->end()
                            ->scalarNode('url')->end()
                            ->scalarNode('ident')->end()
                            ->scalarNode('path')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
