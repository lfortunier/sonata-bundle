<?php

namespace Smart\SonataBundle\Admin\Extension;

use Sonata\AdminBundle\Admin\AbstractAdminExtension;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Route\RouteCollection;

class ImportExtension extends AbstractAdminExtension
{
    public function configureRoutes(AdminInterface $admin, RouteCollection $collection)
    {
        $collection->add('import', 'import');
    }

    public function configureActionButtons(AdminInterface $admin, $list, $action, $object)
    {
        $list = parent::configureActionButtons($admin, $list, $action, $object);

        if (!isset($list['import'])) {
            $list['import']['template'] = '@SmartSonata/action/import.html.twig';
        }

        return $list;
    }
}
