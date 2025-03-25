<?php

namespace Tests\Controllers;

use App\Controllers\CustomerDashboardController;
use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Models\UserAddress;
use PHPUnit\Framework\TestCase;

class CustomerDashboardControllerTest extends TestCase
{
    private CustomerDashboardController $controller;
    private Request $request;
    private Response $response;
    private Session $session;
    private User $user;
    
    protected function setUp(): void
    {
        // Создаем моки для зависимостей
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->session = $this->createMock(Session::class);
        $this->user = $this->createMock(User::class);
        
        // Настраиваем Application
        $app = $this->createMock(Application::class);
        $app->user = $this->user;
        $app->request = $this->request;
        $app->response = $this->response;
        $app->session = $this->session;
        $app->logger = $this->createMock(\App\Core\Logger::class);
        
        // Устанавливаем статическое свойство Application::$app
        $reflection = new \ReflectionClass(Application::class);
        $property = $reflection->getProperty('app');
        $property->setAccessible(true);
        $property->setValue(null, $app);
        
        // Создаем контроллер
        $this->controller = new CustomerDashboardController();
    }
    
    protected function tearDown(): void
    {
        // Сбрасываем статическое свойство Application::$app
        $reflection = new \ReflectionClass(Application::class);
        $property = $reflection->getProperty('app');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
    
    public function testProfileMethodRetrievesUserAddresses(): void
    {
        // Устанавливаем ID пользователя
        $this->user->id = 1;
        
        // Создаем мок для UserAddress::findAll
        $addressesMock = [
            $this->createMock(UserAddress::class),
            $this->createMock(UserAddress::class)
        ];
        
        // Используем PHP-мок для статического метода
        $userAddressMock = $this->getMockBuilder(UserAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Настраиваем ожидания для метода render
        $this->response->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('customer/dashboard/profile'),
                $this->callback(function($params) use ($addressesMock) {
                    return isset($params['addresses']) && $params['addresses'] === $addressesMock;
                })
            );
        
        // Выполняем метод
        $this->controller->profile();
    }
    
    public function testAddAddressMethod(): void
    {
        // Устанавливаем ID пользователя
        $this->user->id = 1;
        
        // Настраиваем Request
        $this->request->expects($this->once())
            ->method('isPost')
            ->willReturn(true);
        
        $addressData = [
            'title' => 'Дом',
            'recipient_name' => 'Иван Иванов',
            'phone' => '+7 (999) 123-45-67',
            'country' => 'Россия',
            'city' => 'Москва',
            'street' => 'ул. Ленина',
            'house' => '10',
            'apartment' => '101',
            'postal_code' => '123456',
            'is_default' => '1'
        ];
        
        $this->request->expects($this->once())
            ->method('getBody')
            ->willReturn($addressData);
        
        $this->request->expects($this->once())
            ->method('isAjax')
            ->willReturn(true);
        
        // Настраиваем ожидания для сессии
        $this->session->expects($this->never())
            ->method('setFlash');
        
        // Настраиваем ожидания для редиректа
        $this->response->expects($this->never())
            ->method('redirect');
        
        // Выполняем метод
        $result = $this->controller->addAddress($this->request);
        
        // Проверяем результат
        $this->assertIsString($result);
        $resultData = json_decode($result, true);
        $this->assertIsArray($resultData);
        $this->assertArrayHasKey('success', $resultData);
    }
    
    public function testUpdateAddressMethod(): void
    {
        // Устанавливаем ID пользователя
        $this->user->id = 1;
        
        // Настраиваем Request
        $this->request->expects($this->once())
            ->method('isPost')
            ->willReturn(true);
        
        $addressData = [
            'address_id' => '1',
            'title' => 'Дом',
            'recipient_name' => 'Иван Иванов',
            'phone' => '+7 (999) 123-45-67',
            'country' => 'Россия',
            'city' => 'Москва',
            'street' => 'ул. Ленина',
            'house' => '10',
            'apartment' => '101',
            'postal_code' => '123456',
            'is_default' => '1'
        ];
        
        $this->request->expects($this->once())
            ->method('getBody')
            ->willReturn($addressData);
        
        $this->request->expects($this->once())
            ->method('isAjax')
            ->willReturn(true);
        
        // Настраиваем ожидания для сессии
        $this->session->expects($this->never())
            ->method('setFlash');
        
        // Настраиваем ожидания для редиректа
        $this->response->expects($this->never())
            ->method('redirect');
        
        // Выполняем метод
        $result = $this->controller->updateAddress($this->request);
        
        // Проверяем результат
        $this->assertIsString($result);
        $resultData = json_decode($result, true);
        $this->assertIsArray($resultData);
        $this->assertArrayHasKey('success', $resultData);
    }
    
    public function testDeleteAddressMethod(): void
    {
        // Устанавливаем ID пользователя
        $this->user->id = 1;
        
        // Настраиваем Request
        $this->request->expects($this->once())
            ->method('isPost')
            ->willReturn(true);
        
        $addressData = [
            'address_id' => '1'
        ];
        
        $this->request->expects($this->once())
            ->method('getBody')
            ->willReturn($addressData);
        
        $this->request->expects($this->once())
            ->method('isAjax')
            ->willReturn(true);
        
        // Настраиваем ожидания для сессии
        $this->session->expects($this->never())
            ->method('setFlash');
        
        // Настраиваем ожидания для редиректа
        $this->response->expects($this->never())
            ->method('redirect');
        
        // Выполняем метод
        $result = $this->controller->deleteAddress($this->request);
        
        // Проверяем результат
        $this->assertIsString($result);
        $resultData = json_decode($result, true);
        $this->assertIsArray($resultData);
        $this->assertArrayHasKey('success', $resultData);
    }
}
