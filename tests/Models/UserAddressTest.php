<?php

namespace Tests\Models;

use App\Models\UserAddress;
use PHPUnit\Framework\TestCase;

class UserAddressTest extends TestCase
{
    private UserAddress $address;
    
    protected function setUp(): void
    {
        $this->address = new UserAddress();
        $this->address->user_id = 1;
        $this->address->title = 'Дом';
        $this->address->recipient_name = 'Иван Иванов';
        $this->address->phone = '+7 (999) 123-45-67';
        $this->address->country = 'Россия';
        $this->address->city = 'Москва';
        $this->address->street = 'ул. Ленина';
        $this->address->house = '10';
        $this->address->apartment = '101';
        $this->address->postal_code = '123456';
        $this->address->is_default = 1;
    }
    
    public function testTableName(): void
    {
        $this->assertEquals('user_addresses', UserAddress::tableName());
    }
    
    public function testRules(): void
    {
        $rules = $this->address->rules();
        
        $this->assertArrayHasKey('user_id', $rules);
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('recipient_name', $rules);
        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('country', $rules);
        $this->assertArrayHasKey('city', $rules);
        $this->assertArrayHasKey('street', $rules);
        $this->assertArrayHasKey('house', $rules);
        $this->assertArrayHasKey('apartment', $rules);
        $this->assertArrayHasKey('postal_code', $rules);
        $this->assertArrayHasKey('is_default', $rules);
    }
    
    public function testAttributes(): void
    {
        $attributes = $this->address->attributes();
        
        $this->assertContains('user_id', $attributes);
        $this->assertContains('title', $attributes);
        $this->assertContains('recipient_name', $attributes);
        $this->assertContains('phone', $attributes);
        $this->assertContains('country', $attributes);
        $this->assertContains('city', $attributes);
        $this->assertContains('street', $attributes);
        $this->assertContains('house', $attributes);
        $this->assertContains('apartment', $attributes);
        $this->assertContains('postal_code', $attributes);
        $this->assertContains('is_default', $attributes);
    }
    
    public function testGetFullAddress(): void
    {
        $fullAddress = $this->address->getFullAddress();
        
        $this->assertStringContainsString('123456', $fullAddress);
        $this->assertStringContainsString('Россия', $fullAddress);
        $this->assertStringContainsString('Москва', $fullAddress);
        $this->assertStringContainsString('ул. Ленина', $fullAddress);
        $this->assertStringContainsString('д. 10', $fullAddress);
        $this->assertStringContainsString('кв./офис 101', $fullAddress);
    }
    
    public function testGetShortAddress(): void
    {
        $shortAddress = $this->address->getShortAddress();
        
        $this->assertEquals('Москва, ул. Ленина, д. 10', $shortAddress);
    }
    
    public function testGetRecipientInfo(): void
    {
        $recipientInfo = $this->address->getRecipientInfo();
        
        $this->assertEquals('Иван Иванов, +7 (999) 123-45-67', $recipientInfo);
    }
    
    public function testBeforeSave(): void
    {
        // Тест для нового адреса
        $result = $this->address->beforeSave(true);
        
        $this->assertTrue($result);
        $this->assertNotEmpty($this->address->created_at);
        $this->assertNotEmpty($this->address->updated_at);
        
        // Тест для существующего адреса
        $previousUpdatedAt = $this->address->updated_at;
        sleep(1); // Ждем 1 секунду, чтобы убедиться, что updated_at изменится
        
        $result = $this->address->beforeSave(false);
        
        $this->assertTrue($result);
        $this->assertNotEquals($previousUpdatedAt, $this->address->updated_at);
    }
}
