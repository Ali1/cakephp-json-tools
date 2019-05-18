<?php
namespace JsonTools\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Exception\BadRequestException;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Class JsonComponent
 *
 * @package App\Controller\Component
 */
class JsonComponent extends Component
{
    /**
     * @var \Cake\Controller\Controller
     */
    private $Controller;

    /**
     * @inheritDoc
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);
        $this->Controller = $this->_registry->getController();
    }

    /**
     * Checks whether the request is ajax_submit with json option
     *
     * @param bool $autoPrepare - run Json->prepareVars too?
     *
     * @return bool
     */
    public function isJsonSubmit(bool $autoPrepare = true)
    {
        if ($this->Controller->request->is(['post', 'put'])
            && $this->Controller->request->is('ajax')
            && $this->Controller->request->is('json')
        ) {
            if ($autoPrepare) {
                $this->prepareVars();
            }

            return true;
        }

        return false;
    }

    /**
     * Render as json and set variables
     *
     * @return void
     */
    public function forceJson()
    {
        $this->Controller->RequestHandler->renderAs($this->Controller, 'json');
        $this->Controller->RequestHandler->respondAs('json');
        $this->prepareVars();
    }

    /**
     * Ensure request is json and post/put, otherwise throw error
     * Will also run Json->prepareVars()
     *
     * @throws \Cake\Http\Exception\BadRequestException
     *
     * @return void
     */
    public function requireJsonSubmit()
    {
        if (!$this->isJsonSubmit(true)) {
            throw new BadRequestException();
        }
    }

    /**
     * Sets the boilerplate Json view vars to output a generic OK messages
     *
     * @return array _serialize to allow easier additions to the Json output
     */
    public function prepareVars()
    {
        $defaults = [
            'error' => false,
            'field_errors' => [],
            'message' => 'OK',
            '_redirect' => false,
            'content' => null,
        ];
        foreach (array_merge($defaults, ['']) as $key => $value) {
            if (!isset($this->viewVars[$key])) {
                $this->Controller->set($key, $value);
            }
        }
        $serialize = array_keys($defaults);
        /** @noinspection PhpDeprecationInspection */
        if (!empty($this->Controller->viewVars['_serialize']) && is_array($this->Controller->viewVars['_serialize'])) {
            /** @noinspection PhpDeprecationInspection */
            $serialize = array_merge($serialize, $this->Controller->viewVars['_serialize']);
        }
        $this->Controller->set('_serialize', $serialize);

        return $serialize;
    }

    /**
     * Sets the _redirect key in the Json output. The client must be configured to handle this.
     *
     * @param string|array|null $url An array specifying any of the following:
     *   'controller', 'action', 'plugin' additionally, you can provide routed
     *   elements or query string parameters. If string it can be name any valid url
     *   string.
     *
     * @return void
     */
    public function redirect($url)
    {
        $this->Controller->set('_redirect', Router::url($url));
    }

    /**
     * @todo auto-detect template file if null
     *
     * @param string|null $template
     *
     * @return void
     */
    public function sendContent($template = null)
    {
        $builder = $this->Controller->viewBuilder();
        $builder->getTemplatePath();
        if ($template) {
            $builder->setTemplate($template);
        }
        $builder->setLayout(false);
        /** @noinspection PhpDeprecationInspection */
        $this->Controller->set('content', $builder->build($this->Controller->viewVars)->render());
    }

    /**
     * @param string|array $name A string or an array of data.
     * @param mixed $value Value in case $name is a string (which then works as the key).
     *   Unused if $name is an associative array, otherwise serves as the values to $name's keys.
     *
     * @return void
     */
    public function set($name, $value = null)
    {
        $this->Controller->set($name, $value);
        if (is_array($name)) {
            $keys = $name;
        } else {
            $keys = [$name];
        }
        /** @noinspection PhpDeprecationInspection */
        $serialize = array_merge($this->Controller->viewVars['_serialize'], $keys);
        $this->Controller->set('_serialize', $serialize);
    }

    /**
     * Set necessary view vars to respond with a json error and relevant message
     *
     * @param \Cake\ORM\Entity|\Cake\Form\Form $entity
     *
     * @return void
     */
    public function entityErrorVars($entity)
    {
        $this->Controller->set('error', true);
        $this->Controller->set('field_errors', $entity->getErrors());
        $this->Controller->set('message', $this->generateErrorMessage($entity));
    }

    /**
     * Generate single string message from a CakePHP array of field errors i.e. $entity->getErrors()
     *
     * @param \Cake\Datasource\EntityInterface|\Cake\Form\Form|array $entity
     *
     * @return string A message detailing all the field errors, or an empty string if no errors
     */
    public function generateErrorMessage($entity): string
    {
        if (is_array($entity)) {
            $entityErrors = $entity; // send an array of errors e.g. in manual controller validation
        } elseif (!$entity->getErrors()) {
            return false;
        } else {
            $entityErrors = $entity->getErrors();
        }
        $error_msg = '';

        $errorMessage = function ($key, $errors) {
            $error_msg = Inflector::humanize($key) . ':';
            foreach ($errors as $error) {
                $error_msg .= ' ' . $error . ',';
            }
            $error_msg = substr($error_msg, 0, -1);
            $error_msg .= '. ';
            return $error_msg;
        };

        foreach ($entityErrors as $key => $errors) {
            if (Hash::maxDimensions($errors) > 2) {
                // error in a hasMany associated
                foreach ($errors as $hasManyNum => $intermediate) {
                    foreach ($intermediate as $subkey => $suberrors) {
                        $error_msg .= $errorMessage("$key.$hasManyNum.$subkey", $suberrors);
                    }
                }
            } elseif (Hash::maxDimensions($errors) > 1) {
                foreach ($errors as $subkey => $suberrors) {
                    $error_msg .= $errorMessage("$key.$subkey", $suberrors);
                }
            } else {
                $error_msg .= $errorMessage($key, $errors);
            }
        }

        return $error_msg;
    }

    /**
     * $this->Json->set shortcut
     *
     * @param string $message
     *
     * @return void
     */
    public function setError(string $message)
    {
        $this->set('error', true);
        $this->set('message', $message);
    }

    /**
     * $this->Json->set shortcut
     *
     * @param string $message
     *
     * @return void
     */
    public function setMessage(string $message)
    {
        $this->set('message', $message);
    }
}
