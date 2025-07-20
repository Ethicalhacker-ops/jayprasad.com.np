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
    generateUniverseBackground();
});

chatCloseBtn.addEventListener('click', () => {
    liveChatWidget.classList.remove('active');
});

// Send message function
function sendMessage() {
    const message = chatMessageInput.value.trim();
    if (message) {
        addMessage(message, 'user');
        chatMessageInput.value = '';
        
        // Simulate bot response (in real implementation, this would call your API)
        setTimeout(() => {
            const responses = [
                "Thanks for your message! How can I help you today?",
                "I'm currently offline, but I'll get back to you soon.",
                "That's an interesting question. Let me check...",
                "Hello! I'm Jay's virtual assistant. What can I do for you?"
            ];
            const randomResponse = responses[Math.floor(Math.random() * responses.length)];
            addMessage(randomResponse, 'bot');
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

// Universe Background Generator
function generateUniverseBackground() {
    const universe = document.createElement('div');
    universe.id = 'universe-bg';
    document.body.appendChild(universe);
    
    const starCount = 100;
    for (let i = 0; i < starCount; i++) {
        const star = document.createElement('div');
        star.className = 'star';
        star.style.width = `${Math.random() * 3}px`;
        star.style.height = star.style.width;
        star.style.top = `${Math.random() * 100}%`;
        star.style.left = `${Math.random() * 100}%`;
        star.style.animationDelay = `${Math.random() * 5}s`;
        universe.appendChild(star);
    }

    const shootingStar = document.createElement('div');
    shootingStar.className = 'shooting-star';
    universe.appendChild(shootingStar);
    
    // Remove universe when chat is closed
    liveChatWidget.addEventListener('transitionend', function handler() {
        if (!liveChatWidget.classList.contains('active')) {
            universe.remove();
            liveChatWidget.removeEventListener('transitionend', handler);
        }
    });
}
