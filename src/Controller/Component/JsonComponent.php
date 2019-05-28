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
     * @var bool whether setError should also set the HTTP status to 400 Bad Request by default
     */
    private $httpErrorStatusOnError = false;

    /**
     * @var bool true: setError would result in error=true, and message=msg. false: setError would result in error=message and message=message
     */
    private $errorMessageInErrorKey = false;

    /**
     * @inheritDoc
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);
        $this->Controller = $this->_registry->getController();
    }

    /**
     * Whether $this->Json->setError('message') should also set the HTTP status to 400 Bad Request
     * By default, this is false and a HTTP Status Code of 200 (OK) is emitted
     *
     * @param bool $option
     */
    public function setHttpErrorStatusOnError(bool $option): void
    {
        $this->httpErrorStatusOnError = $option;
    }
    /**
     * By default, the json output contains a boolean 'error' key and the 'message' key is intended to contain the error message.
     * Setting this to true, will also set the 'error' key to a string message as defined in $this->Json->setError('message').
     *
     * @param bool $option
     */

    public function setErrorMessageInErrorKey(bool $option): void
    {
        $this->errorMessageInErrorKey = $option;
    }

    /**
     * Sets the boilerplate Json view vars. With no further action, will send a message of OK.
     * The view variables set are consistent with the json RequestHandler i.e. the _serialize viewVar is set.
     * Will not replace already viewVars that have been already set.
     * This should usually be executed in all your json actions, but many of the other component methods will execute it for you e.g. $this->Json->requireJsonSubmit
     *
     * @return void
     */
    public function prepareVars(): void
    {
        $defaults = [
            'error' => false,
            'field_errors' => [],
            'message' => 'OK',
            '_redirect' => false,
            'content' => null,
        ];
        foreach ($defaults as $key => $value) {
            if (!isset($this->viewVars[$key])) {
                $this->Controller->set($key, $value);
            }
        }
        $serialize = array_keys($defaults);
        // accessing viewVars in this way is depreciated and methods to access them have not been developed yet.
        /** @noinspection PhpDeprecationInspection */
        if (!empty($this->Controller->viewVars['_serialize']) && is_array($this->Controller->viewVars['_serialize'])) {
            /** @noinspection PhpDeprecationInspection */
            $serialize = array_merge($serialize, $this->Controller->viewVars['_serialize']);
        }
        $this->Controller->set('_serialize', $serialize);
    }

    /**
     * Checks whether the request is ajax_submit with json option
     * Will also execute $this->Json->prepareVars too by default
     *
     * @param bool $autoPrepare (optional), true by default, set to false to prevent running Json->prepareVars
     *
     * @return bool
     */
    public function isJsonSubmit(bool $autoPrepare = true): bool
    {
        if ($this->Controller->request->is(['post', 'put'])
            && $this->Controller->request->is('ajax')
            && ($this->Controller->request->is('json')
                || $this->Controller->viewBuilder()->getClassName() === 'Cake\View\JsonView' // if $this->Json->forceJson has been used
            )
        ) {
            if ($autoPrepare) {
                $this->prepareVars();
            }

            return true;
        }

        return false;
    }

    /**
     * Always render as json (even if request isn't) and will automatically set variables (will execute $this->Json->prepareVars)
     *
     * @return void
     */
    public function forceJson(): void
    {
        $this->Controller->RequestHandler->renderAs($this->Controller, 'json');
        $this->prepareVars();
    }

    /**
     * Like $this->Json->isJsonSubmit but will throw exception if request is not a json submit
     * Will also run Json->prepareVars()
     *
     * @throws \Cake\Http\Exception\BadRequestException
     *
     * @return void
     */
    public function requireJsonSubmit(): void
    {
        if (!$this->isJsonSubmit(true)) {
            throw new BadRequestException();
        }
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
    public function redirect($url): void
    {
        $this->Controller->set('_redirect', Router::url($url));
    }

    /**
     * Used to send template content in json under the content key
     *
     * @todo auto-detect template file if null
     *
     * @param string|null $template
     *
     * @return void
     */
    public function sendContent($template = null): void
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
     * When dealing with json requests, use $this->Json->set rather than $this->set in your controller as the former
     *   method will also ensure the _serialize view var contains the key, hence ensure that the json response contains the
     *   variable you are setting.
     *
     * @param string|array $name A string or an array of data.
     * @param mixed $value Value in case $name is a string (which then works as the key).
     *   Unused if $name is an associative array, otherwise serves as the values to $name's keys.
     *
     * @return void
     */
    public function set($name, $value = null): void
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
     * Used like so:
     * if (!$this->Articles->save($articles) {
     *      $this->Json->entityErrorVars($entity);
     * }
     * This the above will:
     *      Produce a string with human readable list of validation errors as the json output 'message' key
     *      Give field_errors in the json with an array of validation errors
     *
     * @param \Cake\ORM\Entity|\Cake\Form\Form $entity
     *
     * @return void
     */
    public function entityErrorVars($entity): void
    {
        $this->set('field_errors', $entity->getErrors());
        $this->setError($this->generateErrorMessage($entity));
    }

    /**
     * Returns a string message from a CakePHP array of field errors i.e. $entity->getErrors()
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
     * Sets an error message in the json output ('error' = true, 'message' = $message).
     * Can be customised to also produce HTTP status error (see setHttpErrorStatusOnError) and the error key can also contain the
     *  error message but using setErrorMessageInErrorKey
     *
     * @param string $message
     * @param bool|null $httpError true to also return a HTTP 400 Bad Request
     *
     * @return void
     */
    public function setError(string $message, ?bool $httpError = null): void
    {
        if ($httpError === null) {
            $httpError = $this->httpErrorStatusOnError;
        }
        if ($httpError) {
            $this->getController()->setResponse($this->getController()->getResponse()->withStatus(400, 'Bad request'));
        }
        if ($this->errorMessageInErrorKey) {
            $this->set('error', $message);
        } else {
            $this->set('error', true);
        }
        $this->set('message', $message);
    }

    /**
     * $this->Json->set shortcut to set the message key
     *
     * @param string $message
     *
     * @return void
     */
    public function setMessage(string $message): void
    {
        $this->set('message', $message);
    }
}
