// server.js
const express = require('express');
const fetch = require('node-fetch');
const dotenv = require('dotenv');
const path = require('path');

dotenv.config();
const app = express();

// Serve static files (HTML, JS)
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

app.post('/api/chat', async (req, res) => {
  const userMessage = req.body.message;
  const apiKey = process.env.GEMINI_API_KEY;

  try {
    const geminiResponse = await fetch(
      'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-goog-api-key': apiKey
        },
        body: JSON.stringify({
          contents: [
            {
              parts: [
                {
                  text: userMessage
                }
              ]
            }
          ]
        })
      }
    );

    const data = await geminiResponse.json();

    const reply = data?.candidates?.[0]?.content?.parts?.[0]?.text || "No response from Gemini.";
    res.json({ reply });

  } catch (err) {
    console.error('Gemini API error:', err);
    res.status(500).json({ reply: 'Internal server error' });
  }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`✅ Server is running at http://localhost:${PORT}`));
