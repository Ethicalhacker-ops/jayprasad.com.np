// Live Chat Functionality
const chatToggleBtn = document.getElementById('chatToggleBtn');
const chatCloseBtn = document.getElementById('chatCloseBtn');
const liveChatWidget = document.getElementById('liveChatWidget');
const chatMessages = document.getElementById('chatMessages');
const chatMessageInput = document.getElementById('chatMessageInput');
const chatSendBtn = document.getElementById('chatSendBtn');

const qa_pairs = {
    "hello": "Hello! How can I help you today?",
    "hi": "Hi there! What can I do for you?",
    "what is your name": "I am a humble chatbot assistant.",
    "what can you do": "I can answer some basic questions. Try asking me something!",
    "what are your services": "Jay offers Web Development, IT Courses, IELTS Preparation, Cloud Architecture, SEE Results, Cybersecurity, Task Management, AI Solutions, and DevOps. Which one are you interested in?",
    "tell me about web development": "Jay builds custom web applications with modern frameworks like React, Angular, and Vue.js, focusing on responsive design and performance.",
    "tell me about it courses": "Jay offers comprehensive IT training programs covering programming, cybersecurity, cloud computing, and more.",
    "tell me about ielts preparation": "Jay provides professional IELTS coaching with proven strategies for all four skills to help you achieve your target band score.",
    "bye": "Goodbye! Have a great day!",
    "default": "I am not sure how to answer that. Please try asking another question."
};

// Toggle chat visibility
if (chatToggleBtn) {
    chatToggleBtn.addEventListener('click', () => {
        liveChatWidget.classList.toggle('active');
    });
}

if (chatCloseBtn) {
    chatCloseBtn.addEventListener('click', () => {
        liveChatWidget.classList.remove('active');
    });
}

// Send message function
function sendMessage() {
    const message = chatMessageInput.value.trim().toLowerCase();
    if (message) {
        addMessage(message, 'user');
        chatMessageInput.value = '';
        
        setTimeout(() => {
            const response = qa_pairs[message] || qa_pairs["default"];
            addMessage(response, 'bot');
        }, 1000);
    }
}

// Add message to chat
function addMessage(text, sender) {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('chat-message', `${sender}-message`);
    messageDiv.textContent = text;
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Event listeners
if (chatSendBtn) {
    chatSendBtn.addEventListener('click', sendMessage);
}
if (chatMessageInput) {
    chatMessageinput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
}

// Initial bot greeting
setTimeout(() => {
    addMessage("Hello! I'm Jay's virtual assistant. How can I help you today?", 'bot');
}, 1500);
