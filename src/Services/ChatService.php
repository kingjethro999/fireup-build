<?php

namespace FireUp\PhpBuild\Services;

use FireUp\PhpBuild\Services\CodeGenerator;
use FireUp\PhpBuild\Services\FileManager;
use FireUp\PhpBuild\Services\DebugService;

class ChatService
{
    private CodeGenerator $codeGenerator;
    private FileManager $fileManager;
    private DebugService $debugService;

    public function __construct()
    {
        $this->codeGenerator = new CodeGenerator();
        $this->fileManager = new FileManager();
        $this->debugService = new DebugService();
    }

    public function processMessage(string $message): array
    {
        $message = strtolower(trim($message));
        
        // Analyze message intent - check project creation first
        if ($this->isProjectCreationRequest($message)) {
            return $this->handleProjectCreation($message);
        }
        
        if ($this->isCodeGenerationRequest($message)) {
            return $this->handleCodeGeneration($message);
        }
        
        if ($this->isFileOperationRequest($message)) {
            return $this->handleFileOperation($message);
        }
        
        if ($this->isDebugRequest($message)) {
            return $this->handleDebug($message);
        }
        
        // Default response
        return [
            'type' => 'general',
            'content' => $this->generateGeneralResponse($message)
        ];
    }

    private function isCodeGenerationRequest(string $message): bool
    {
        $codeKeywords = ['create class', 'generate class', 'make class', 'create function', 'generate function', 'make function', 'create method', 'generate method', 'make method', 'create controller', 'generate controller', 'make controller', 'create model', 'generate model', 'make model'];
        
        // Check for specific code generation patterns
        foreach ($codeKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        
        // Check for individual code elements
        $individualKeywords = ['class', 'function', 'method', 'controller', 'model'];
        foreach ($individualKeywords as $keyword) {
            if (str_contains($message, $keyword) && !str_contains($message, 'project')) {
                return true;
            }
        }
        
        return false;
    }

    private function isFileOperationRequest(string $message): bool
    {
        $keywords = ['edit', 'modify', 'update', 'change', 'file', 'create file'];
        return $this->containsKeywords($message, $keywords);
    }

    private function isDebugRequest(string $message): bool
    {
        $keywords = ['debug', 'fix', 'error', 'bug', 'problem', 'issue'];
        return $this->containsKeywords($message, $keywords);
    }

    private function isProjectCreationRequest(string $message): bool
    {
        $projectKeywords = ['new project', 'create project', 'start project', 'initialize project', 'create a project', 'make a project'];
        $phpKeywords = ['php project', 'php app', 'php application'];
        $blogKeywords = ['blog', 'website', 'site', 'app', 'application'];
        
        // Check for project-specific keywords
        foreach ($projectKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        
        // Check for PHP project keywords
        foreach ($phpKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        
        // Check for "create" + "blog/website/app" pattern (more natural)
        if (str_contains($message, 'create') && (
            str_contains($message, 'blog') || 
            str_contains($message, 'website') || 
            str_contains($message, 'site') || 
            str_contains($message, 'app') || 
            str_contains($message, 'application')
        )) {
            return true;
        }
        
        // Check for "create a new" + "project" pattern
        if (str_contains($message, 'create') && str_contains($message, 'new') && str_contains($message, 'project')) {
            return true;
        }
        
        // Check for "working" + "blog/app" pattern
        if (str_contains($message, 'working') && (
            str_contains($message, 'blog') || 
            str_contains($message, 'app') || 
            str_contains($message, 'website')
        )) {
            return true;
        }
        
        return false;
    }

    private function containsKeywords(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function handleCodeGeneration(string $message): array
    {
        $generatedCode = $this->codeGenerator->generateFromRequest($message);
        
        return [
            'type' => 'code_generation',
            'content' => $generatedCode['content'],
            'files' => $generatedCode['files'] ?? []
        ];
    }

    private function handleFileOperation(string $message): array
    {
        $result = $this->fileManager->processFileOperation($message);
        
        return [
            'type' => 'file_operation',
            'message' => $result['message'],
            'changes' => $result['changes'] ?? []
        ];
    }

    private function handleDebug(string $message): array
    {
        $debugResult = $this->debugService->analyzeAndFix($message);
        
        return [
            'type' => 'debug',
            'content' => $debugResult['analysis'],
            'suggestions' => $debugResult['suggestions'] ?? []
        ];
    }

    private function handleProjectCreation(string $message): array
    {
        $projectStructure = $this->codeGenerator->createProjectStructure($message);
        
        return [
            'type' => 'project_creation',
            'content' => $projectStructure['content'],
            'files' => $projectStructure['files'] ?? []
        ];
    }

    private function generateGeneralResponse(string $message): string
    {
        // Try to understand what the user wants
        if (str_contains($message, 'blog') || str_contains($message, 'website')) {
            return "I can help you create a blog or website! Try saying:\n" .
                   "• 'Create a blog project'\n" .
                   "• 'Create a working blog'\n" .
                   "• 'Create a new website'\n" .
                   "• 'Create a PHP blog application'";
        }
        
        if (str_contains($message, 'create') || str_contains($message, 'make')) {
            return "I can help you create things! Try saying:\n" .
                   "• 'Create a new PHP project'\n" .
                   "• 'Create a blog'\n" .
                   "• 'Create a User class'\n" .
                   "• 'Create a UserController'\n" .
                   "• 'Create a database model'";
        }
        
        return "I can help you with PHP development! Try asking me to:\n" .
               "• Create a new PHP project or blog\n" .
               "• Generate classes, functions, or methods\n" .
               "• Debug and fix code issues\n" .
               "• Edit or create files\n" .
               "• Build complete applications\n\n" .
               "Just tell me what you want to build!";
    }
} 