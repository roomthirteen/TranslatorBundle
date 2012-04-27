<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\Bundle\TranslatorBundle\Translation;

use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Symfony\Component\Config\Resource\ResourceInterface;
use Knp\Bundle\TranslatorBundle\Dumper\DumperInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Knp\Bundle\TranslatorBundle\Exception\InvalidTranslationKeyException;

/**
 * Translator that adds write capabilites on translation files
 *
 * @author Florian Klein <florian.klein@free.fr>
 *
 */
class Translator extends BaseTranslator
{
    private $dumpers = array();
    private $locales = array();
    private $fallbackLocale;

    public function all()
    {
        $translations = array();
        foreach ($this->getLocales() as $locale) {
            $translations[$locale] = $this->getCatalog($locale)->all();
        }

        return $translations;
    }

    public function isTranslated($id, $domain, $locale)
    {
        return $id === $this->getCatalog($locale)->get((string) $id, $domain);
    }

    /**
     * Adds a dumper to the ones used to dump a resource
     */
    public function addDumper(DumperInterface $dumper)
    {
        $this->dumpers[] = $dumper;
    }

    public function addLocale($locale)
    {
        $this->locales[$locale] = $locale;
    }

    public function getLocales()
    {
        return $this->locales;
    }

    /**
     *
     * @return DumperInterface
     */
    private function getDumper(ResourceInterface $resource)
    {
        foreach ($this->dumpers as $dumper) {
            if ($dumper->supports($resource)) {
                return $dumper;
            }
        }

        return null;
    }

    /**
     *
     * Gets a catalog for a given locale
     *
     * @return MessageCatalogue
     */
    public function getCatalog($locale)
    {
        $this->loadCatalogue($locale);

        if (isset($this->catalogues[$locale])) {

            return $this->catalogues[$locale];
        }

        throw new \InvalidArgumentException(
            sprintf('The locale "%s" does not exist in Translations catalogues', $locale)
        );
    }

    /**
     * {@inheritdoc}
     *
     * Forced to override because of private visibility
     */
    public function setFallbackLocale($locale)
    {
        // needed as the fallback locale is used to fill-in non-yet translated messages
        $this->catalogues = array();

        $this->fallbackLocale = $locale;
    }

    public function getFallbackLocale()
    {
        return $this->fallbackLocale;
    }

    /**
     * Updates the value of a given trans id for a specified domain and locale
     *
     * @param string $id the trans id
     * @param string $value the translated value
     * @param string domain the domain name
     * @param string $locale
     *
     * @return boolean true if success
     */
    public function update($id, $value, $domain, $locale)
    {
        if (empty($id)) {
            throw new InvalidTranslationKeyException('Empty key not allowed');
        }

        $resources        = $this->getResources($locale, $domain);
        $alwaysPutDefault = $this->container->getParameter('knplabs.translator.always_put_to_default_resource');

        $success          = false;

        if(!$alwaysPutDefault)
        {
            // only update an existing file if the feature is enabled
            foreach ($resources as $resource) {
                if ($dumper = $this->getDumper($resource)) {
                    $success = $dumper->update($resource, $id, $value);
                }
            }
        }

        // only put to default if always default is enabled or updating an existing was unsuccessfull
        if($alwaysPutDefault || !$alwaysPutDefault && !$success)
        {
            // key has not been defined in any resource, so add it to the default resource
            $resource = $this->getDefaultResource($domain,$locale);
            $dumper   = $this->getDumper($resource);

            if($dumper)
            {
                var_dump($resource);
                $success = $dumper->update($resource, $id, $value);
            }
        }

        $this->loadCatalogue($locale);

        return $success;
    }


    private $defaultResourcePath = 'frontend/Resources/translations';

    /**
     * Gets the first resource that matches the default resource path
     * @param $domain
     * @param $locale
     * @return mixed
     */
    public function getDefaultResource($domain,$locale)
    {
        $catalog = $this->getCatalog($locale);
        $resources = $this->getMatchedResources($catalog, $domain, $locale);

        foreach($resources as $resource)
        {
            $path = pathinfo($resource->getResource());
            if(preg_match('%'.$this->defaultResourcePath.'$%',$path['dirname']))
            {
                return $resource;
            }
        }

        // default resource has not been found, so create it
        $base  = $this->container->get('kernel')->getRootDir().'/Resources/translations';
        $name  = $domain.'.'.$locale.'.'.$this->container->getParameter('knplabs.translator.default_translation_format');
        $file  = $base.'/'.$name;

        if(!is_dir($base))
        {
            mkdir($base,0665,true);
        }

        if(!file_exists($file))
        {
            // setup a dummy content so the dumper will accept this file
            file_put_contents($file,"dummy: ~\n");
        }

        return new \Symfony\Component\Config\Resource\FileResource($file);
    }

    /**
     * Gets the resources that matches a domain and a locale on a particular catalog
     *
     * @param MessageCatalogue $catalog the catalog
     * @param string $domain the domain name (default is 'messages')
     * @param string $locae the locale, to filter fallbackLocale
     * @return array of FileResource objects
     */
    private function getMatchedResources(MessageCatalogue $catalog, $domain, $locale)
    {
        $matched = array();
        foreach ($catalog->getResources() as $resource) {

            // @see Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
            // filename is domain.locale.format
            $basename = \basename($resource->getResource());
            list($resourceDomain, $resourceLocale, $format) = explode('.', $basename);

            if ($domain === $resourceDomain && $locale === $resourceLocale) {
                $matched[] = $resource;
            }
        }

        return $matched;
    }

    public function getResources($locale, $domain)
    {
        $catalog = $this->getCatalog($locale);
        $resources = $this->getMatchedResources($catalog, $domain, $locale);

        return $resources;
    }
}
