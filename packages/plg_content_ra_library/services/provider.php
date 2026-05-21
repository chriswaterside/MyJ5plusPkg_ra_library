<?php
//             Part of the RA_library package
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\CMS\Extension\PluginInterface;
use Ramblers\Plugin\Content\Ra_library\Extension\Ra_library;

return new class implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $config = (array) PluginHelper::getPlugin('content', 'ra_library');
                $subject = $container->get(DispatcherInterface::class);
                $app = Factory::getApplication();

                $plugin = new Ra_library($subject, $config);
                $plugin->setApplication($app);

                return $plugin;
            }
        );
    }
};