<?php

namespace FireUp\PhpBuild\Web;

use FireUp\PhpBuild\Services\ChatService;

class Server
{
    private int $port;
    private ChatService $chatService;

    public function __construct(int $port = 8000)
    {
        $this->port = $port;
        $this->chatService = new ChatService();
    }

    public function start(): void
    {
        $server = "http://localhost:{$this->port}";
        
        echo "🚀 Starting PHP Build Web Interface...\n";
        echo "📍 Server running at: {$server}\n";
        echo "🛑 Press Ctrl+C to stop\n\n";
        
        // Start the built-in PHP server from project root to access .chat folder
        $command = "php -S localhost:{$this->port} -t " . __DIR__ . "/../../";
        passthru($command);
    }

    public function handleRequest(string $method, string $path, array $headers, string $body): string
    {
        // Handle static files
        if ($method === 'GET' && $path === '/.chat/interface.html') {
            return $this->serveChatInterface();
        }
        
        if ($method === 'GET' && $path === '/') {
            return $this->serveInterface();
        }
        
        if ($method === 'POST' && $path === '/api/chat') {
            return $this->handleChatRequest($body);
        }
        
        if ($method === 'GET' && $path === '/api/health') {
            return json_encode(['status' => 'ok', 'message' => 'PHP Build Server is running']);
        }
        
        http_response_code(404);
        return json_encode(['error' => 'Not found']);
    }

    private function serveInterface(): string
    {
        return $this->getInterfaceHtml();
    }

    private function serveChatInterface(): string
    {
        $chatInterfacePath = __DIR__ . '/../../.chat/interface.html';
        if (file_exists($chatInterfacePath)) {
            return file_get_contents($chatInterfacePath);
        }
        
        http_response_code(404);
        return 'Chat interface not found';
    }

    private function handleChatRequest(string $body): string
    {
        // Set headers for streaming response
        header('Content-Type: text/plain');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-api-key');
        
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['messages'])) {
            http_response_code(400);
            return json_encode(['error' => 'Invalid request']);
        }
        
        // Get the last user message
        $lastMessage = end($data['messages']);
        $userMessage = $lastMessage['content'] ?? '';
        
        if (empty($userMessage)) {
            http_response_code(400);
            return json_encode(['error' => 'No message content']);
        }
        
        try {
            $response = $this->chatService->processMessage($userMessage);
            
            // Format response for streaming
            $streamResponse = [
                'choices' => [
                    [
                        'delta' => [
                            'content' => $response['content']
                        ]
                    ]
                ]
            ];
            
            return "data: " . json_encode($streamResponse) . "\n\ndata: [DONE]\n\n";
        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    private function getInterfaceHtml(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FireUp PHP Build - Interactive Development</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .chat-message { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .typing-indicator { animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">🚀 FireUp PHP Build</h1>
            <p class="text-gray-600 text-lg">Interactive PHP Development Environment</p>
        </div>

        <!-- Main Interface -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Chat Interface -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-semibold text-gray-800">💬 Development Chat</h2>
                        <div class="flex space-x-2">
                            <button onclick="clearChat()" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                                Clear
                            </button>
                            <button onclick="exportChat()" class="px-3 py-1 text-sm bg-blue-100 hover:bg-blue-200 rounded-md transition-colors">
                                Export
                            </button>
                        </div>
                    </div>

                    <!-- Chat Messages -->
                    <div id="chatMessages" class="space-y-4 mb-6 max-h-96 overflow-y-auto">
                        <div class="chat-message bg-blue-50 p-4 rounded-lg">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-bold">AI</div>
                                <div class="flex-1">
                                    <p class="text-gray-800">Hello! I\'m your PHP development assistant. I can help you:</p>
                                    <ul class="mt-2 text-sm text-gray-600 space-y-1">
                                        <li>• Create new PHP projects and components</li>
                                        <li>• Generate classes, controllers, and models</li>
                                        <li>• Debug and fix code issues</li>
                                        <li>• Edit and manage files</li>
                                        <li>• Build complete applications</li>
                                    </ul>
                                    <p class="mt-2 text-sm text-gray-600">Just tell me what you want to build!</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div class="flex space-x-3">
                        <input type="text" id="messageInput" placeholder="Tell me what you want to build..." 
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               onkeypress="handleKeyPress(event)">
                        <button onclick="sendMessage()" id="sendButton" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Send
                        </button>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button onclick="quickAction(\'Create a new PHP project\')" 
                                class="px-3 py-1 text-sm bg-green-100 hover:bg-green-200 rounded-md transition-colors">
                            🆕 New Project
                        </button>
                        <button onclick="quickAction(\'Create a User model with properties\')" 
                                class="px-3 py-1 text-sm bg-purple-100 hover:bg-purple-200 rounded-md transition-colors">
                            📝 Create Model
                        </button>
                        <button onclick="quickAction(\'Create a UserController\')" 
                                class="px-3 py-1 text-sm bg-orange-100 hover:bg-orange-200 rounded-md transition-colors">
                            🎮 Create Controller
                        </button>
                        <button onclick="quickAction(\'Debug my PHP code\')" 
                                class="px-3 py-1 text-sm bg-red-100 hover:bg-red-200 rounded-md transition-colors">
                            🐛 Debug Code
                        </button>
                    </div>
                </div>
            </div>

            <!-- Project Info & Actions -->
            <div class="space-y-6">
                <!-- Project Status -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Project Status</h3>
                    <div id="projectStatus" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">PHP Files:</span>
                            <span id="phpFileCount" class="font-semibold">-</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Syntax Status:</span>
                            <span id="syntaxStatus" class="font-semibold text-yellow-500">Checking...</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Last Build:</span>
                            <span id="lastBuild" class="font-semibold">-</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Commands -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">⚡ Quick Commands</h3>
                    <div class="space-y-3">
                        <button onclick="runCommand(\'build\')" 
                                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            🏗️ Build Project
                        </button>
                        <button onclick="runCommand(\'serve\')" 
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            🚀 Start Server
                        </button>
                        <button onclick="runCommand(\'debug\')" 
                                class="w-full px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                            🐛 Debug Project
                        </button>
                    </div>
                </div>

                <!-- File Explorer -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">📁 Project Files</h3>
                    <div id="fileExplorer" class="space-y-2 text-sm">
                        <div class="text-gray-500">Loading files...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let chatHistory = [];
        let isProcessing = false;

        // Initialize
        document.addEventListener(\'DOMContentLoaded\', function() {
            updateProjectStatus();
            loadFileExplorer();
        });

        function handleKeyPress(event) {
            if (event.key === \'Enter\' && !isProcessing) {
                sendMessage();
            }
        }

        function sendMessage() {
            const input = document.getElementById(\'messageInput\');
            const message = input.value.trim();
            
            if (!message || isProcessing) return;
            
            addMessage(\'user\', message);
            input.value = \'\';
            isProcessing = true;
            
            // Show typing indicator
            addTypingIndicator();
            
            // Send to API
            fetch(\'/api/chat\', {
                method: \'POST\',
                headers: { \'Content-Type\': \'application/json\' },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                removeTypingIndicator();
                handleResponse(data);
                isProcessing = false;
            })
            .catch(error => {
                removeTypingIndicator();
                addMessage(\'ai\', \'Sorry, I encountered an error. Please try again.\');
                isProcessing = false;
            });
        }

        function addMessage(sender, content) {
            const messagesDiv = document.getElementById(\'chatMessages\');
            const messageDiv = document.createElement(\'div\');
            messageDiv.className = \'chat-message \' + (sender === \'user\' ? \'bg-gray-50\' : \'bg-blue-50\') + \' p-4 rounded-lg\';
            
            const icon = sender === \'user\' ? \'👤\' : \'🤖\';
            const name = sender === \'user\' ? \'You\' : \'AI\';
            
            messageDiv.innerHTML = `
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-${sender === \'user\' ? \'gray\' : \'blue\'}-500 rounded-full flex items-center justify-center text-white text-sm font-bold">${icon}</div>
                    <div class="flex-1">
                        <p class="text-gray-800">${content}</p>
                    </div>
                </div>
            `;
            
            messagesDiv.appendChild(messageDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            
            // Store in history
            chatHistory.push({ sender, content, timestamp: new Date() });
        }

        function addTypingIndicator() {
            const messagesDiv = document.getElementById(\'chatMessages\');
            const indicator = document.createElement(\'div\');
            indicator.id = \'typingIndicator\';
            indicator.className = \'chat-message bg-blue-50 p-4 rounded-lg\';
            indicator.innerHTML = `
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-bold">🤖</div>
                    <div class="flex-1">
                        <div class="typing-indicator text-gray-600">AI is thinking...</div>
                    </div>
                </div>
            `;
            messagesDiv.appendChild(indicator);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function removeTypingIndicator() {
            const indicator = document.getElementById(\'typingIndicator\');
            if (indicator) {
                indicator.remove();
            }
        }

        function handleResponse(data) {
            if (data.error) {
                addMessage(\'ai\', \'Error: \' + data.error);
                return;
            }
            
            let content = data.content || \'No response received\';
            
            if (data.type === \'code_generation\') {
                content += \'\\n\\n✅ Code generated successfully!\';
                if (data.files) {
                    content += \'\\n\\n📁 Created files:\\n\' + data.files.map(f => \'• \' + f.path).join(\'\\n\');
                }
            } else if (data.type === \'file_operation\') {
                content = \'✅ \' + (data.message || content);
                if (data.changes) {
                    content += \'\\n\\n📝 Changes:\\n\' + data.changes.map(c => \'• \' + c).join(\'\\n\');
                }
            } else if (data.type === \'debug\') {
                content = \'🐛 \' + content;
                if (data.suggestions) {
                    content += \'\\n\\n💡 Suggestions:\\n\' + data.suggestions.map(s => \'• \' + s).join(\'\\n\');
                }
            }
            
            addMessage(\'ai\', content);
            
            // Update project status
            updateProjectStatus();
            loadFileExplorer();
        }

        function quickAction(action) {
            document.getElementById(\'messageInput\').value = action;
            sendMessage();
        }

        function runCommand(command) {
            addMessage(\'user\', \'Running: \' + command);
            // In a real implementation, this would call the CLI commands
            addMessage(\'ai\', \'Command executed: \' + command + \'\\n\\nThis feature would integrate with the CLI commands in a full implementation.\');
        }

        function clearChat() {
            document.getElementById(\'chatMessages\').innerHTML = \'\';
            chatHistory = [];
            addMessage(\'ai\', \'Chat cleared. How can I help you today?\');
        }

        function exportChat() {
            const dataStr = JSON.stringify(chatHistory, null, 2);
            const dataBlob = new Blob([dataStr], {type: \'application/json\'});
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement(\'a\');
            link.href = url;
            link.download = \'php-build-chat-\' + new Date().toISOString().split(\'T\')[0] + \'.json\';
            link.click();
        }

        function updateProjectStatus() {
            // Simulate project status update
            document.getElementById(\'phpFileCount\').textContent = \'12\';
            document.getElementById(\'syntaxStatus\').textContent = \'✅ Valid\';
            document.getElementById(\'syntaxStatus\').className = \'font-semibold text-green-500\';
            document.getElementById(\'lastBuild\').textContent = new Date().toLocaleTimeString();
        }

        function loadFileExplorer() {
            // Simulate file explorer
            const explorer = document.getElementById(\'fileExplorer\');
            explorer.innerHTML = `
                <div class="text-gray-600">📁 src/</div>
                <div class="ml-4 text-gray-600">📄 App.php</div>
                <div class="ml-4 text-gray-600">📁 Controllers/</div>
                <div class="ml-8 text-gray-600">📄 HomeController.php</div>
                <div class="ml-4 text-gray-600">📁 Models/</div>
                <div class="ml-8 text-gray-600">📄 User.php</div>
                <div class="text-gray-600">📁 public/</div>
                <div class="ml-4 text-gray-600">📄 index.php</div>
                <div class="text-gray-600">📄 composer.json</div>
            `;
        }
    </script>
</body>
</html>';
    }
} 