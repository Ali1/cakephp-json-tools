<?php

declare(strict_types=1);

namespace JsonTools\Test\TestCase\Controller\Component;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TestApp\Controller\JsonComponentTestController;

class JsonComponentTest extends TestCase
{
    private JsonComponentTestController $Controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Controller = $this->newController();
    }

    protected function tearDown(): void
    {
        unset($this->Controller);

        parent::tearDown();
    }

    public function testForceJsonPreparesDefaultsAndSerializesExtraKeys(): void
    {
        $this->invokeAction('forceJson');

        $this->assertSame('Json', $this->Controller->viewBuilder()->getClassName());
        $this->assertSame(
            ['answer', 'error', 'field_errors', 'message', '_redirect', 'content'],
            $this->Controller->viewBuilder()->getOption('serialize')
        );

        $this->assertEquals([
            'error' => false,
            'field_errors' => [],
            'message' => '',
            '_redirect' => false,
            'content' => null,
            'answer' => 42,
        ], $this->renderJson());
    }

    public function testSetWithArraySerializesArrayKeys(): void
    {
        $this->invokeAction('setArray');

        $this->assertEquals([
            'error' => false,
            'field_errors' => [],
            'message' => '',
            '_redirect' => false,
            'content' => null,
            'answer' => 42,
            'name' => 'Ali',
        ], $this->renderJson());
    }

    public function testIsJsonSubmitPreparesVarsForAjaxJsonPost(): void
    {
        $this->Controller = $this->newController([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'CONTENT_TYPE' => 'application/json; charset=UTF-8',
                'HTTP_ACCEPT' => 'application/json',
            ],
        ]);

        $this->invokeAction('jsonSubmit');

        $this->assertEquals([
            'error' => false,
            'field_errors' => [],
            'message' => '',
            '_redirect' => false,
            'content' => null,
            'is_json_submit' => true,
        ], $this->renderJson());
    }

    public function testSetErrorUsesMessageAndBooleanErrorByDefault(): void
    {
        $this->invokeAction('defaultError');

        $this->assertSame(200, $this->Controller->getResponse()->getStatusCode());
        $json = $this->renderJson();

        $this->assertTrue($json['error']);
        $this->assertSame('Something went wrong', $json['message']);
    }

    public function testSetErrorCanSetHttpBadRequestStatus(): void
    {
        $this->invokeAction('httpError');

        $this->assertSame(400, $this->Controller->getResponse()->getStatusCode());
        $json = $this->renderJson();

        $this->assertTrue($json['error']);
        $this->assertSame('Something went wrong', $json['message']);
    }

    public function testSetErrorCanMirrorMessageIntoErrorKey(): void
    {
        $this->invokeAction('errorMessageInErrorKey');

        $json = $this->renderJson();

        $this->assertSame('Something went wrong', $json['error']);
        $this->assertSame('Something went wrong', $json['message']);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function newController(array $config = []): JsonComponentTestController
    {
        $request = new ServerRequest($config);
        $controller = new JsonComponentTestController($request);
        $controller->startupProcess();

        return $controller;
    }

    private function invokeAction(string $action): void
    {
        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', $action));
        $this->Controller->invokeAction($this->Controller->getAction(), []);
    }

    /**
     * @return array<string, mixed>
     */
    private function renderJson(): array
    {
        $json = json_decode($this->Controller->createView()->render(), true);

        $this->assertIsArray($json);

        return $json;
    }
}
