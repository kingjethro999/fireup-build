<?php

namespace FireUp\PhpBuild\Services;

use Exception;

class ChatService
{
    private CodeGenerator $codeGenerator;
    private FileManager $fileManager;
    private DebugService $debugService;
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->codeGenerator = new CodeGenerator();
        $this->fileManager = new FileManager();
        $this->debugService = new DebugService();
        $this->apiUrl = 'https://fireup-php-build.onrender.com/api/chat';
        $this->apiKey = 'fireup/php-build';
    }

    public function processMessage(string $message): array
    {
        $message = trim($message);
        
        if (empty($message)) {
            return [
                'type' => 'error',
                'content' => 'Please provide a message to process.'
            ];
        }

        // Get project analysis for context
        $projectAnalysis = $this->codeGenerator->analyzeAndSuggest($message);
        
        // Send to AI API for intelligent processing
        $aiResponse = $this->sendToClaudeAPI($message, $projectAnalysis);
        
        if ($aiResponse === null) {
            // Fallback to basic processing if API fails
            return $this->fallbackProcessing($message, $projectAnalysis);
        }
        
        // Parse AI response and execute actions
        return $this->executeAIInstructions($aiResponse, $message);
    }

    private function sendToClaudeAPI(string $message, array $projectAnalysis): ?array
    {
        try {
            $config = [
                "name" => "AI Code Logic",
                "languages" => ["php", "javascript", "tailwind", "css"],
                "api_info" => [
                    "key" => "fireup/php-build",
                    "url" => "https://fireup-php-build.onrender.com"
                ]
            ];

            // Create context-aware system prompt
            $systemPrompt = $this->buildSystemPrompt($projectAnalysis);
            
            $payload = [
                "config_file" => json_encode($config),
                "messages" => [
                    [
                        "role" => "system",
                        "content" => $systemPrompt
                    ],
                    [
                        "role" => "user", 
                        "content" => $message
                    ]
                ],
                "stream" => false
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$response) {
                return null;
            }

            $decoded = json_decode($response, true);
            return $decoded;

        } catch (Exception $e) {
            error_log("Claude API Error: " . $e->getMessage());
            return null;
        }
    }

    private function buildSystemPrompt(array $projectAnalysis): string
    {
        $currentDir = getcwd();
        $projectName = basename($currentDir);
        $structure = $projectAnalysis['structure'] ?? [];
        $fileContents = $projectAnalysis['fileContents'] ?? [];
        $suggestions = $projectAnalysis['suggestions'] ?? [];
        
        $prompt = "You are an intelligent PHP development assistant integrated into a local development environment.

CURRENT PROJECT CONTEXT:
- Project: {$projectName}
- Directory: {$currentDir}
- Total Files: {$structure['totalFiles']}
- Has Composer: " . ($structure['hasComposer'] ? 'Yes' : 'No') . "
- Has Public folder: " . ($structure['hasPublic'] ? 'Yes' : 'No') . "
- Has Controllers: " . ($structure['hasControllers'] ? 'Yes' : 'No') . "
- Has Models: " . ($structure['hasModels'] ? 'Yes' : 'No') . "
- PHP Files: " . count($structure['phpFiles'] ?? []) . "
- HTML Files: " . count($structure['htmlFiles'] ?? []) . "
- CSS Files: " . count($structure['cssFiles'] ?? []) . "
- JS Files: " . count($structure['jsFiles'] ?? []) . "

EXISTING FILES:";

        // Add relevant file contents for context
        foreach ($fileContents as $path => $content) {
            $prompt .= "\n\nFile: {$path}\n```\n{$content}\n```";
        }

        $prompt .= "\n\nSUGGESTIONS: " . implode(", ", $suggestions);

        $prompt .= "\n\nINSTRUCTIONS:
When a user asks you to create, build, or develop something, you should:

1. ANALYZE the request and determine what files and structure are needed
2. RESPOND with a JSON structure containing the action to take and specific instructions

Response format should be JSON:
{
  \"action\": \"create_project|edit_file|debug|generate_code\",
  \"type\": \"blog|website|api|ecommerce|landing_page|php_app\",
  \"files\": [
    {\"path\": \"file/path.php\", \"content\": \"file content here\", \"description\": \"what this file does\"}
  ],
  \"explanation\": \"Brief explanation of what you're creating\"
}

EXAMPLES:
- \"create a blog\" → Generate blog project structure with posts, admin, etc.
- \"make a shopping website\" → Generate ecommerce structure with products, cart, etc.
- \"build an API\" → Generate API structure with routes, controllers, models
- \"edit index.php to add...\" → Modify existing file
- \"debug this error...\" → Analyze and fix code issues

Be practical and create working, functional code. Don't ask for clarification - just build what makes sense based on the request and existing project structure.";

        return $prompt;
    }

    private function executeAIInstructions(array $aiResponse, string $originalMessage): array
    {
        try {
            // Extract content from AI response
            $content = '';
            if (isset($aiResponse['choices'][0]['message']['content'])) {
                $content = $aiResponse['choices'][0]['message']['content'];
            } elseif (isset($aiResponse['content'])) {
                $content = $aiResponse['content'];
            }

            // Try to parse JSON from the AI response
            $instructions = $this->extractJSONFromResponse($content);
            
            if ($instructions && isset($instructions['action'])) {
                return $this->executeAction($instructions);
            }

            // If no JSON found, treat as natural language response
            return $this->handleNaturalLanguageResponse($content, $originalMessage);

        } catch (Exception $e) {
            error_log("AI Instruction Execution Error: " . $e->getMessage());
            return [
                'type' => 'error',
                'content' => 'Failed to process AI response: ' . $e->getMessage()
            ];
        }
    }

    private function extractJSONFromResponse(string $content): ?array
    {
        // Look for JSON in the response
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }
        return null;
    }

    private function executeAction(array $instructions): array
    {
        $action = $instructions['action'];
        $files = $instructions['files'] ?? [];
        $explanation = $instructions['explanation'] ?? 'Processing your request...';

        switch ($action) {
            case 'create_project':
                return $this->createProjectFromInstructions($instructions);
                
            case 'edit_file':
                return $this->editFileFromInstructions($instructions);
                
            case 'debug':
                return $this->debugFromInstructions($instructions);
                
            case 'generate_code':
                return $this->generateCodeFromInstructions($instructions);
                
            default:
                return [
                    'type' => 'ai_response',
                    'content' => $explanation,
                    'files' => $files
                ];
        }
    }

    private function createProjectFromInstructions(array $instructions): array
    {
        $files = $instructions['files'] ?? [];
        $type = $instructions['type'] ?? 'php application';
        $explanation = $instructions['explanation'] ?? 'Creating project structure...';

        // Use CodeGenerator to execute file operations
        $result = $this->codeGenerator->executeFileOperations($files);
        
        $response = "✅ {$explanation}\n\n";
        
        if (!empty($result['created'])) {
            $response .= "📁 Files created:\n" . implode("\n", array_map(fn($file) => "• {$file}", $result['created'])) . "\n\n";
        }
        
        if (!empty($result['updated'])) {
            $response .= "📝 Files updated:\n" . implode("\n", array_map(fn($file) => "• {$file}", $result['updated'])) . "\n\n";
        }
        
        if (!empty($result['errors'])) {
            $response .= "❌ Errors:\n" . implode("\n", array_map(fn($error) => "• {$error}", $result['errors'])) . "\n\n";
        }

        return [
            'type' => 'project_creation',
            'content' => $response,
            'files' => array_merge($result['created'], $result['updated'])
        ];
    }

    private function editFileFromInstructions(array $instructions): array
    {
        $files = $instructions['files'] ?? [];
        $explanation = $instructions['explanation'] ?? 'Editing files...';

        $result = $this->codeGenerator->executeFileOperations($files);
        
        $response = "✅ {$explanation}\n\n";
        
        if (!empty($result['updated'])) {
            $response .= "📝 Files updated:\n" . implode("\n", array_map(fn($file) => "• {$file}", $result['updated'])) . "\n\n";
        }
        
        if (!empty($result['errors'])) {
            $response .= "❌ Errors:\n" . implode("\n", array_map(fn($error) => "• {$error}", $result['errors'])) . "\n\n";
        }

        return [
            'type' => 'file_operation',
            'content' => $response,
            'files' => $result['updated']
        ];
    }

    private function debugFromInstructions(array $instructions): array
    {
        $explanation = $instructions['explanation'] ?? 'Debugging...';
        
        return [
            'type' => 'debug',
            'content' => "🔧 {$explanation}\n\n" . $this->debugService->handleDebugRequest($instructions)
        ];
    }

    private function generateCodeFromInstructions(array $instructions): array
    {
        $files = $instructions['files'] ?? [];
        $explanation = $instructions['explanation'] ?? 'Generating code...';

        $result = $this->codeGenerator->executeFileOperations($files);
        
        $response = "✅ {$explanation}\n\n";
        
        if (!empty($result['created'])) {
            $response .= "📁 Files created:\n" . implode("\n", array_map(fn($file) => "• {$file}", $result['created'])) . "\n\n";
        }
        
        if (!empty($result['updated'])) {
            $response .= "📝 Files updated:\n" . implode("\n", array_map(fn($file) => "• {$file}", $result['updated'])) . "\n\n";
        }

        return [
            'type' => 'code_generation',
            'content' => $response,
            'files' => array_merge($result['created'], $result['updated'])
        ];
    }

    private function handleNaturalLanguageResponse(string $content, string $originalMessage): array
    {
        // If AI gave a natural language response, try to infer what to do
        $lowerContent = strtolower($content);
        $lowerMessage = strtolower($originalMessage);

        // Check if the AI is asking for clarification or giving instructions
        if (strpos($lowerContent, 'create') !== false || strpos($lowerContent, 'generate') !== false) {
            // Try to extract what should be created from the AI response
            if (strpos($lowerMessage, 'blog') !== false) {
                return $this->handleProjectCreation($originalMessage);
            }
            if (strpos($lowerMessage, 'shop') !== false || strpos($lowerMessage, 'ecommerce') !== false) {
                return $this->handleProjectCreation($originalMessage);
            }
            if (strpos($lowerMessage, 'website') !== false || strpos($lowerMessage, 'site') !== false) {
                return $this->handleProjectCreation($originalMessage);
            }
        }

        return [
            'type' => 'ai_response',
            'content' => $content
        ];
    }

    private function handleProjectCreation(string $message): array
    {
        // Fallback to original project creation logic
        return [
            'type' => 'project_creation',
            'content' => $this->codeGenerator->createProjectStructure($message)['content']
        ];
    }

    private function fallbackProcessing(string $message, array $projectAnalysis): array
    {
        // Original keyword-based processing as fallback
        if ($this->isProjectCreationRequest($message)) {
            return $this->handleProjectCreation($message);
        }
        
        if ($this->isFileOperationRequest($message)) {
            return $this->handleFileOperation($message);
        }
        
        if ($this->isDebugRequest($message)) {
            return $this->handleDebugRequest($message);
        }
        
        if ($this->isCodeGenerationRequest($message)) {
            return $this->handleCodeGeneration($message);
        }
        
        return $this->generateIntelligentResponse($message, $projectAnalysis);
    }

    // Keep all your existing helper methods for fallback processing
    private function isFileOperationRequest(string $message): bool
    {
        $fileOperationKeywords = [
            'edit', 'modify', 'update', 'change', 'file', 'create file', 
            'add method', 'add property', 'add section', 'add function',
            'delete', 'remove', 'delete file', 'remove file',
            'append', 'insert', 'replace', 'modify'
        ];
        
        return $this->containsKeywords($message, $fileOperationKeywords);
    }

    private function isProjectCreationRequest(string $message): bool
    {
        $createKeywords = [
            'create', 'make', 'build', 'start', 'new', 'generate',
            'set up', 'initialize', 'begin', 'start project'
        ];
        
        $projectKeywords = [
            'project', 'app', 'application', 'website', 'blog', 'api', 
            'landing page', 'site', 'system', 'platform'
        ];
        
        $substantialKeywords = [
            'blog', 'website', 'api', 'landing page', 'ecommerce', 'portfolio',
            'dashboard', 'cms', 'forum', 'social', 'news', 'shop', 'store'
        ];
        
        $conversationalKeywords = [
            'can we create', 'can we make', 'i want to create', 'i want to make',
            'help me create', 'help me build', 'let\'s create', 'let\'s build'
        ];
        
        foreach ($conversationalKeywords as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }
        
        $hasCreate = $this->containsKeywords($message, $createKeywords);
        $hasProject = $this->containsKeywords($message, $projectKeywords);
        $hasSubstantial = $this->containsKeywords($message, $substantialKeywords);
        
        return ($hasCreate && $hasProject) || ($hasCreate && $hasSubstantial);
    }

    private function isDebugRequest(string $message): bool
    {
        $debugKeywords = [
            'debug', 'error', 'fix', 'problem', 'issue', 'bug', 'broken',
            'not working', 'syntax error', 'parse error', 'fatal error'
        ];
        
        return $this->containsKeywords($message, $debugKeywords);
    }

    private function isCodeGenerationRequest(string $message): bool
    {
        $codeKeywords = [
            'class', 'function', 'method', 'controller', 'model', 'view',
            'component', 'service', 'helper', 'utility', 'library'
        ];
        
        return $this->containsKeywords($message, $codeKeywords);
    }

    private function containsKeywords(string $message, array $keywords): bool
    {
        $message = strtolower($message);
        foreach ($keywords as $keyword) {
            if (str_contains($message, strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }

    private function handleFileOperation(string $message): array
    {
        return ['type' => 'file_operation', 'content' => 'File operation handled'];
    }

    private function handleDebugRequest(string $message): array
    {
        return ['type' => 'debug', 'content' => 'Debug request handled'];
    }

    private function handleCodeGeneration(string $message): array
    {
        return ['type' => 'code_generation', 'content' => 'Code generation handled'];
    }

    private function generateIntelligentResponse(string $message, array $projectAnalysis): array
    {
        return ['type' => 'intelligent_response', 'content' => 'Intelligent response generated'];
    }
}