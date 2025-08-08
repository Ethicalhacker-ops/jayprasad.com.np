// Live Chat Functionality
const chatToggleBtn = document.getElementById('chatToggleBtn');
const chatCloseBtn = document.getElementById('chatCloseBtn');
const liveChatWidget = document.getElementById('liveChatWidget');
const chatMessages = document.getElementById('chatMessages');
const chatMessageInput = document.getElementById('chatMessageInput');
const chatSendBtn = document.getElementById('chatSendBtn');

// Toggle chat visibility
chatToggleBtn.addEventListener('click', () => {
    liveChatWidget.classList.toggle('active');
});

chatCloseBtn.addEventListener('click', () => {
    liveChatWidget.classList.remove('active');
});

// Send message function
async function sendMessage() {
    const message = chatMessageInput.value.trim();
    if (message) {
        addMessage(message, 'user');
        chatMessageInput.value = '';
        
        try {
            const response = await fetch('/api/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message })
            });

            const data = await response.json();
            addMessage(data.reply, 'bot');
        } catch (error) {
            console.error('Error sending message:', error);
            addMessage('Sorry, something went wrong. Please try again later.', 'bot');
        }
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
chatSendBtn.addEventListener('click', sendMessage);
chatMessageInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

// Initial bot greeting
setTimeout(() => {
    addMessage("Hello! I'm Jay's virtual assistant. How can I help you today?", 'bot');
}, 1500);

