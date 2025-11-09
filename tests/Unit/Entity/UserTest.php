<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCanBeCreated(): void
    {
        $user = new User();
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertNull($user->getId());
    }

    public function testUserEmailCanBeSet(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('test@example.com', $user->getUserIdentifier());
    }

    public function testUserRolesCanBeSet(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        // ROLE_USER est toujours ajouté par défaut
        $this->assertCount(1, array_unique($roles));
    }

    public function testUserPasswordCanBeSet(): void
    {
        $user = new User();
        $user->setPassword('hashed_password');
        
        $this->assertEquals('hashed_password', $user->getPassword());
    }

    public function testUserCreatedAtIsSet(): void
    {
        $user = new User();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
    }
}

