<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Require login
require_login();

$page_title = "AI Assistant";

// Get user ID
$user_id = $_SESSION['user_id'];

// Generate a session ID if not exists
if (!isset($_SESSION['ai_chat_session_id'])) {
    $_SESSION['ai_chat_session_id'] = bin2hex(random_bytes(16));
}
$session_id = $_SESSION['ai_chat_session_id'];

// Get chat history
$chat_history = [];
$stmt = $conn->prepare("CALL GetAIChatConversation(?, ?, 50)");
$stmt->bind_param("isi", $user_id, $session_id, 50);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $chat_history[] = $row;
}
$stmt->close();

// Sort messages by created_at in ascending order
usort($chat_history, function($a, $b) {
    return strtotime($a['created_at']) - strtotime($b['created_at']);
});

// Process chat message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = sanitize_input($_POST['message']);
    
    if (!empty($message)) {
        // Save user message
        $stmt = $conn->prepare("CALL AddAIChatMessage(?, ?, ?, TRUE)");
        $stmt->bind_param("iss", $user_id, $session_id, $message);
        $stmt->execute();
        $stmt->close();
        
        // Generate AI response (simplified for demo)
        $ai_response = generateAIResponse($message, $chat_history);
        
        // Save AI response
        $stmt = $conn->prepare("CALL AddAIChatMessage(?, ?, ?, FALSE)");
        $stmt->bind_param("iss", $user_id, $session_id, $ai_response);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to refresh the page
        header("Location: ai_chat.php");
        exit;
    }
}

// Function to generate AI response (simplified for demo)
function generateAIResponse($message, $chat_history) {
    // In a real application, this would call an AI service like OpenAI API
    // For demo purposes, we'll use some simple pattern matching
    
    $message = strtolower($message);
    
    if (strpos($message, 'hello') !== false || strpos($message, 'hi') !== false) {
        return "Hello! I'm your SafetyNet AI assistant. How can I help you today?";
    }
    
    if (strpos($message, 'help') !== false) {
        return "I can help you with various tasks like:\n- Reporting a crime\n- Finding nearby police stations\n- Understanding crime statistics\n- Safety tips\n- Emergency procedures\n\nWhat would you like to know more about?";
    }
    
    if (strpos($message, 'report') !== false && strpos($message, 'crime') !== false) {
        return "To report a crime, go to the 'Report Crime' section from the main menu. You'll need to provide details about the incident, location, and any evidence you might have. Would you like me to guide you through the process?";
    }
    
    if (strpos($message, 'police station') !== false) {
        return "You can find nearby police stations in the 'Emergency' section. The system will use your current location to show you the nearest stations. Would you like to know more about contacting the police?";
    }
    
    if (strpos($message, 'emergency') !== false || strpos($message, 'sos') !== false) {
        return "In case of an emergency, you can use the SOS button in the 'Emergency' section. This will alert the nearest police station with your location. For immediate life-threatening situations, use the Emergency SOS button which will also activate your camera and microphone for live assistance.";
    }
    
    if (strpos($message, 'safe') !== false || strpos($message, 'safety') !== false) {
        return "Here are some safety tips:\n1. Stay aware of your surroundings\n2. Keep emergency contacts easily accessible\n3. Share your location with trusted contacts when traveling\n4. Stay in well-lit areas at night\n5. Report suspicious activities promptly\n\nWould you like more specific safety advice?";
    }
    
    if (strpos($message, 'thank') !== false) {
        return "You're welcome! If you have any more questions, feel free to ask. Stay safe!";
    }
    
    // Default response
    return "I'm not sure I understand. Could you please rephrase your question? I can help with reporting crimes, finding police stations, safety tips, and emergency procedures.";
}

include_once 'includes/header.php';
?>

<div class="ai-chat-container">
    <div class="container">
        <h1 class="page-title">AI Assistant</h1>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-robot"></i> SafetyNet AI</h2>
                <p>Ask questions about safety, reporting crimes, or emergency procedures</p>
            </div>
            
            <div class="card-body">
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($chat_history)): ?>
                        <!-- Welcome message -->
                        <div class="chat-message ai-message">
                            <div class="message-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="message-content">
                                <p>Hello! I'm your SafetyNet AI assistant. How can I help you today?</p>
                                <span class="message-time"><?php echo date('g:i A'); ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($chat_history as $message): ?>
                            <div class="chat-message <?php echo $message['is_user'] ? 'user-message' : 'ai-message'; ?>">
                                <div class="message-avatar">
                                    <?php if ($message['is_user']): ?>
                                        <i class="fas fa-user"></i>
                                    <?php else: ?>
                                        <i class="fas fa-robot"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="message-content">
                                    <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                    <span class="message-time">
                                        <?php echo date('g:i A', strtotime($message['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input">
                    <form method="POST" action="ai_chat.php" id="chatForm">
                        <div class="input-group">
                            <input type="text" name="message" id="messageInput" placeholder="Type your message..." required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="ai-suggestions">
            <h3>Suggested Questions</h3>
            <div class="suggestion-buttons">
                <button class="suggestion-btn" data-message="How do I report a crime?">How do I report a crime?</button>
                <button class="suggestion-btn" data-message="Find nearby police stations">Find nearby police stations</button>
                <button class="suggestion-btn" data-message="What should I do in an emergency?">What should I do in an emergency?</button>
                <button class="suggestion-btn" data-message="Give me safety tips">Give me safety tips</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to bottom of chat
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Suggestion buttons
    const suggestionButtons = document.querySelectorAll('.suggestion-btn');
    const messageInput = document.getElementById('messageInput');
    const chatForm = document.getElementById('chatForm');
    
    suggestionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const message = this.getAttribute('data-message');
            messageInput.value = message;
            chatForm.submit();
        });
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>

