<?php

namespace FireUp\PhpBuild\Web;

use FireUp\PhpBuild\Services\ChatService;

class Server
{
    private ChatService $chatService;
    private int $port;

    public function __construct()
    {
        $this->chatService = new ChatService();
        $this->port = 8000;
    }

    public function start(int $port = 8000): void
    {
        $this->port = $port;
        
        echo "🚀 Starting FireUp PHP Build Web Server...\n";
        echo "📍 Server running at: http://localhost:{$port}\n";
        echo "💬 Chat interface: http://localhost:{$port}/.chat/interface.html\n";
        echo "🛑 Press Ctrl+C to stop the server\n\n";

        // Set document root to project root to access vendor directory
        $documentRoot = dirname(dirname(dirname(__DIR__)));
        
        $command = "php -S localhost:{$port} -t " . escapeshellarg($documentRoot) . " " . escapeshellarg(__DIR__ . '/router.php');
        
        passthru($command);
    }

    public function serveChatInterface(): string
    {
        // Look for interface.html in vendor directory first, then local .chat
        $vendorPath = dirname(dirname(dirname(__DIR__))) . '/vendor/fireup/php-build/.chat/interface.html';
        $localPath = dirname(dirname(dirname(__DIR__))) . '/.chat/interface.html';
        
        if (file_exists($vendorPath)) {
            return file_get_contents($vendorPath);
        } elseif (file_exists($localPath)) {
            return file_get_contents($localPath);
        }
        
        return $this->generateFallbackInterface();
    }

    public function handleChatRequest(string $requestBody): string
    {
        try {
            $data = json_decode($requestBody, true);
            
            if (!$data || !isset($data['messages']) || !is_array($data['messages'])) {
                return json_encode([
                    'error' => 'Invalid request format'
                ]);
            }

            // Extract the last user message
            $lastMessage = '';
            foreach (array_reverse($data['messages']) as $message) {
                if (isset($message['role']) && $message['role'] === 'user') {
                    $lastMessage = $message['content'] ?? '';
                    break;
                }
            }

            if (empty($lastMessage)) {
                return json_encode([
                    'error' => 'No user message found'
                ]);
            }

            // Process message through ChatService
            $response = $this->chatService->processMessage($lastMessage);

            // Format response for streaming (mimicking OpenAI API format)
            $formattedResponse = [
                'id' => 'chatcmpl-' . uniqid(),
                'object' => 'chat.completion',
                'created' => time(),
                'model' => 'fireup-php-build',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => $response['content'] ?? 'No response generated'
                        ],
                        'finish_reason' => 'stop'
                    ]
                ],
                'usage' => [
                    'prompt_tokens' => strlen($lastMessage),
                    'completion_tokens' => strlen($response['content'] ?? ''),
                    'total_tokens' => strlen($lastMessage) + strlen($response['content'] ?? '')
                ]
            ];

            return json_encode($formattedResponse);

        } catch (\Exception $e) {
            error_log("Chat request error: " . $e->getMessage());
            return json_encode([
                'error' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }

    private function generateFallbackInterface(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <title>FireUp PHP Build - Chat Interface</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .chat-area { height: 400px; border: 1px solid #ddd; border-radius: 5px; padding: 15px; overflow-y: auto; margin-bottom: 20px; background: #fafafa; }
        .input-area { display: flex; gap: 10px; }
        .input-area input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .input-area button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .input-area button:hover { background: #0056b3; }
        .message { margin-bottom: 10px; padding: 10px; border-radius: 5px; }
        .user-message { background: #e3f2fd; margin-left: 20px; }
        .assistant-message { background: #f1f8e9; margin-right: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔥 FireUp PHP Build</h1>
            <p>AI-Powered PHP Development Assistant</p>
        </div>
        
        <div class="chat-area" id="chatArea">
            <div class="message assistant-message">
                <strong>AI Assistant:</strong> Hello! I\'m here to help you with PHP development. What would you like to create or work on today?
            </div>
        </div>
        
        <div class="input-area">
            <input type="text" id="messageInput" placeholder="Type your message here..." onkeypress="if(event.key==\'Enter\') sendMessage()">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>

    <script>
        function sendMessage() {
            const input = document.getElementById("messageInput");
            const message = input.value.trim();
            
            if (!message) return;
            
            // Add user message
            addMessage("You", message, "user-message");
            input.value = "";
            
            // Send to API
            fetch("/.chat/api", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    messages: [{ role: "user", content: message }]
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    addMessage("AI Assistant", "Error: " + data.error, "assistant-message");
                } else if (data.choices && data.choices[0]) {
                    addMessage("AI Assistant", data.choices[0].message.content, "assistant-message");
                } else {
                    addMessage("AI Assistant", "No response received", "assistant-message");
                }
            })
            .catch(error => {
                addMessage("AI Assistant", "Error: " + error.message, "assistant-message");
            });
        }
        
        function addMessage(sender, content, className) {
            const chatArea = document.getElementById("chatArea");
            const messageDiv = document.createElement("div");
            messageDiv.className = "message " + className;
            messageDiv.innerHTML = "<strong>" + sender + ":</strong> " + content;
            chatArea.appendChild(messageDiv);
            chatArea.scrollTop = chatArea.scrollHeight;
        }
    </script>
</body>
</html>';
    }
} 