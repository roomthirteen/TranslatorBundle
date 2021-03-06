<?php

namespace Knp\Bundle\TranslatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class KnpTranslatorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        if (!$config['enabled']) {
            return;
        }

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        foreach (array('translation', 'controller') as $basename) {
            $loader->load(sprintf('%s.xml', $basename));
        }

        foreach (array('include_vendor_assets','default_resource','always_put_to_default_resource','default_translation_format') as $attribute) {
            if (isset($config[$attribute])) {
                $container->setParameter('knplabs.translator.'.$attribute, $config[$attribute]);
            }
        }

        if(isset($config['always_put_to_default_resource']) && $config['always_put_to_default_resource'] && !isset($config['default_resource']))
        {
            throw new \Exception("The 'always_put_to_default_resource' option has been activated but not 'default_resource' has been specified in configuration of knp_translator.");
        }

        // Use the "writer" translator instead of the default one
        $container->setAlias('translator', 'translator.writer');
        $container->setAlias('templating.helper.translator', 'templating.helper.translator.writer');
    }
}
