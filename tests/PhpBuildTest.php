<?php

namespace FireUp\PhpBuild\Tests;

use PHPUnit\Framework\TestCase;
use FireUp\PhpBuild\Services\ChatService;
use FireUp\PhpBuild\Services\CodeGenerator;
use FireUp\PhpBuild\Services\FileManager;

class PhpBuildTest extends TestCase
{
    public function testChatServiceCanProcessMessages()
    {
        $chatService = new ChatService();
        
        $response = $chatService->processMessage('create a new PHP project');
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('type', $response);
        $this->assertArrayHasKey('content', $response);
    }
    
    public function testCodeGeneratorCanGenerateClass()
    {
        $generator = new CodeGenerator();
        
        $result = $generator->generateFromRequest('create a User class with properties name, email');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('files', $result);
    }
    
    public function testFileManagerCanProcessOperations()
    {
        $fileManager = new FileManager();
        
        $result = $fileManager->processFileOperation('create file test.php');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('changes', $result);
    }
    
    public function testProjectStructureIsValid()
    {
        $this->assertFileExists('composer.json');
        $this->assertFileExists('bin/php-build');
        $this->assertFileExists('src/Console/Application.php');
        $this->assertFileExists('README.md');
    }
} 