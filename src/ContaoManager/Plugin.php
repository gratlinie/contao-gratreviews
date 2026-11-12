<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Gratlinie\GratReviewsBundle\GratlinieGratReviewsBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(GratlinieGratReviewsBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class, 'Gratlinie\GratFrameBundle\GratlinieGratFrameBundle']),
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): RouteCollection|null
    {
        $path = __DIR__.'/../Controller/Backend';

        return $resolver->resolve($path, 'attribute')?->load($path, 'attribute');
    }
}
