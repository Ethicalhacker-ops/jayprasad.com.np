// server.js
require('dotenv').config();
const express = require('express');
const { OpenAI } = require('openai');

const app = express();
app.use(express.json());

const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY // Store key in environment variables
});

app.post('/api/ai-proxy', async (req, res) => {
  try {
    const completion = await openai.chat.completions.create({
      model: "gpt-3.5-turbo",
      messages: [{role: "user", content: req.body.message}]
    });
    
    res.json({
      answer: completion.choices[0].message.content,
      sources: ["OpenAI API"]
    });
  } catch (error) {
    console.error("OpenAI Error:", error);
    res.status(500).json({ error: "AI service unavailable" });
  }
});

app.listen(3000, () => console.log('Server running on port 3000'));
