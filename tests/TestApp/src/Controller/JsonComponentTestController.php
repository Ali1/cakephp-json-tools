<?php

declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;

/**
 * @property \JsonTools\Controller\Component\JsonComponent $Json
 */
class JsonComponentTestController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('JsonTools.Json');
    }

    public function forceJson(): void
    {
        $this->Json->forceJson();
        $this->Json->set('answer', 42);
    }

    public function setArray(): void
    {
        $this->Json->forceJson();
        $this->Json->set([
            'answer' => 42,
            'name' => 'Ali',
        ]);
    }

    public function jsonSubmit(): void
    {
        $isJsonSubmit = $this->Json->isJsonSubmit();
        $this->viewBuilder()->setClassName('Json');
        $this->Json->set('is_json_submit', $isJsonSubmit);
    }

    public function defaultError(): void
    {
        $this->Json->forceJson();
        $this->Json->setError('Something went wrong');
    }

    public function httpError(): void
    {
        $this->Json->forceJson();
        $this->Json->setError('Something went wrong', true);
    }

    public function errorMessageInErrorKey(): void
    {
        $this->Json->forceJson();
        $this->Json->setErrorMessageInErrorKey(true);
        $this->Json->setError('Something went wrong');
    }
}
