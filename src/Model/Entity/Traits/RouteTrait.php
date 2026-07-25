<?php
declare(strict_types=1);

namespace JsonTools\Model\Entity\Traits;

use Cake\Routing\Router;
use Cake\Utility\Inflector;

/**
 * The RouteTrait trait give entities shortcuts notably $entity->route for use with Url::build($entity->route)
 * The route action can be overwritten by defining function routeActionAndId in the Entity class
 *
 * @package App\Model\Entity\Traits
 * @property array $route
 * @property array $classification
 * @property array $long_identifier
 * @property string $url
 */
trait RouteTrait
{
    /**
     * @return string
     */
    private static function className(): string
    {
        $classname = static::class;
        if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
            $classname = $matches[1];
        }

        return $classname;
    }

    /**
     * @return string
     */
    private function _getClassification(): string
    {
        $classname = static::className();

        return Inflector::classify($classname);
    }

    /**
     * @return array
     */
    protected function _getRoute(): array
    {
        $classname = static::className();
        $baseRoute = [
            'controller' => Inflector::pluralize(Inflector::classify($classname)),
            '_method' => 'GET',
        ];
        $specificRoute = $this->routeActionAndId();

        return array_merge($baseRoute, $specificRoute);
    }

    /**
     * @return array
     */
    private function routeActionAndId(): array
    {
        return [
            'action' => 'view',
            $this->id,
        ];
    }

    /**
     * @return string
     */
    protected function _getLongIdentifier(): string
    {
        // e.g. "Appointment 2917"
        return $this->_getClassification() . ' ' . $this->id;
    }

    /**
     * @return string
     */
    protected function _getUrl(): string
    {
        return Router::url($this->route, true);
    }
}
