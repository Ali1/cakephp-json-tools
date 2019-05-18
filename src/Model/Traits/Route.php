<?php
namespace JsonTools\Model\Entity\Traits;

use Cake\Routing\Router;
use Cake\Utility\Inflector;

/**
 * Trait Route
 *
 * @package App\Model\Entity\Traits
 *
 * @property array $route
 * @property array $classification
 * @property array $long_identifier
 * @property string $url
 */
trait Route
{
    /**
     * @return mixed|string
     */
    private static function className()
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
    private function _getClassification()
    {
        $classname = static::className();

        return Inflector::classify($classname);
    }

    /**
     * @return array
     */
    protected function _getRoute()
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
    private function routeActionAndId()
    {
        return [
            'action' => 'view',
            $this->id,
        ];
    }

    /**
     * @return string
     */
    protected function _getLongIdentifier()
    {
        // e.g. "Appointment 2917"
        return $this->_getClassification() . ' ' . $this->id;
    }

    /**
     * @return string
     */
    protected function _getUrl()
    {
        return Router::url($this->route, true);
    }
}
